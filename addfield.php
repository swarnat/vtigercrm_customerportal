<?php
$field = $_POST["field"];
$module = $_POST["cp_module"];

$adb->pquery("INSERT INTO vtiger_customerportal_columns SET `field` = ?, module = ?, customerportal_id = 'CUSTOMERPORTAL_ID'", array($field, $module));