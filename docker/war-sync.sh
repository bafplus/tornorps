#!/bin/bash
# TornOps scheduler - runs ALL jobs every minute (ignores schedule settings)
# This is a temporary solution until Laravel scheduler is properly configured

PHP="/usr/local/bin/php"
ARTISAN="/var/www/html/artisan"
LOG="/dev/null"

# Run all sync jobs every minute
$PHP $ARTISAN torn:sync-faction >> $LOG 2>&1
$PHP $ARTISAN torn:sync-ffstats >> $LOG 2>&1  
$PHP $ARTISAN torn:sync-wars >> $LOG 2>&1
$PHP $ARTISAN torn:sync-active >> $LOG 2>&1
$PHP $ARTISAN torn:sync-attacks >> $LOG 2>&1
$PHP $ARTISAN torn:sync-chains >> $LOG 2>&1
$PHP $ARTISAN torn:sync-ocs >> $LOG 2>&1
$PHP $ARTISAN torn:sync-stocks >> $LOG 2>&1
$PHP $ARTISAN torn:sync-items >> $LOG 2>&1
