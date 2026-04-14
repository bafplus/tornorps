#!/bin/bash
# TornOps scheduler - reads schedule from database and respects intervals

PHP="/usr/local/bin/php"
ARTISAN="/var/www/html/artisan"
LOG="/dev/null"

# Check for active war or war mode enabled
WAR_ACTIVE=$($PHP $ARTISAN tinker --execute="echo App\Models\RankedWar::where('status', 'in progress')->exists() ? '1' : '0';" 2>/dev/null | tr -d '[:space:]')
WAR_MODE=$($PHP $ARTISAN tinker --execute="echo App\Models\FactionSettings::first()?->war_mode_enabled ? '1' : '0';" 2>/dev/null | tr -d '[:space:]')

IS_WAR_MODE="0"
if [ "$WAR_ACTIVE" = "1" ] || [ "$WAR_MODE" = "1" ]; then
    IS_WAR_MODE="1"
fi

# Get all enabled jobs from database
JOBS_DATA=$($PHP $ARTISAN tinker --execute="
\$jobs = App\Models\ScheduledJob::where('enabled', true)->get();
\$settings = App\Models\FactionSettings::first();
\$warModeEnabled = \$settings?->war_mode_enabled ?? false;
\$isWarActive = App\Models\RankedWar::where('status', 'in progress')->exists();
\$isWarMode = \$warModeEnabled || \$isWarActive;

foreach (\$jobs as \$j) {
    // Use war_cron if in war mode, otherwise use cron_expression
    \$cron = \$isWarMode && \$j->war_cron ? \$j->war_cron : \$j->cron_expression;
    
    // Parse interval from cron
    \$interval = 60; // default 1 minute
    if (preg_match('/^\*\/(\d+)\s+\*/', \$cron, \$m)) {
        \$interval = (int)\$m[1] * 60;
    }
    
    // Get last run time
    \$lastRun = \$j->last_run_at ? \$j->last_run_at->timestamp : 0;
    \$now = time();
    \$elapsed = \$now - \$lastRun;
    
    echo \$j->command . '|' . \$interval . '|' . \$lastRun . PHP_EOL;
}
" 2>/dev/null)

# Parse jobs and run if interval has passed
echo "$JOBS_DATA" | while IFS='|' read -r cmd interval lastRun; do
    [ -z "$cmd" ] && continue
    
    CURRENT_TIME=$(date +%s)
    ELAPSED=$((CURRENT_TIME - lastRun))
    
    if [ $ELAPSED -ge $interval ]; then
        # Run the command
        $PHP $ARTISAN $cmd >> $LOG 2>&1
        
        # Update last_run_at in database
        CMD_BASE=$(echo $cmd | sed 's/torn://')
        $PHP $ARTISAN tinker --execute="
\$j = App\Models\ScheduledJob::where('command', '$cmd')->first();
if (\$j) {
    \$j->last_run_at = now();
    \$j->save();
}
" >> $LOG 2>&1
    fi
done
