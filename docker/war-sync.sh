#!/bin/bash
# War-aware sync wrapper script

PHP="/usr/local/bin/php"
ARTISAN="/var/www/html/artisan"
LOG="/dev/null"

# Check for active war
WAR_ACTIVE=$($PHP $ARTISAN tinker --execute="echo App\Services\WarService::hasActiveWar() ? '1' : '0';" 2>/dev/null | tr -d '[:space:]')

if [ "$WAR_ACTIVE" = "1" ]; then
    # Active war - only run war-essential syncs every minute
    $PHP $ARTISAN torn:sync-active >> $LOG 2>&1
    $PHP $ARTISAN torn:sync-attacks --force >> $LOG 2>&1
    # Still sync stocks but warn (user can still view manually)
    $PHP $ARTISAN torn:sync-stocks --force >> $LOG 2>&1
    # Sync overdoses even during war
    $PHP $ARTISAN torn:sync-overdoses --force >> $LOG 2>&1
    # Sync OCs even during war
    $PHP $ARTISAN torn:sync-ocs --force >> $LOG 2>&1
else
    # Normal operation - full syncs
    $PHP $ARTISAN torn:sync-faction >> $LOG 2>&1
    $PHP $ARTISAN torn:sync-stocks >> $LOG 2>&1
    $PHP $ARTISAN torn:sync-overdoses >> $LOG 2>&1
    $PHP $ARTISAN torn:sync-ocs >> $LOG 2>&1
fi
