#!/bin/bash

#Use this script if you want logging to file
PHP=/usr/bin/php
ENTRY_UPDATE_SCRIPT=src/moodle_entry_update_kaf.php
ENTRY_UPDATE_LOG=moodle_entry_update_kaf.log
$PHP $ENTRY_UPDATE_SCRIPT | tee $ENTRY_UPDATE_LOG

