<?php
$availKeys = array("label", "readonly", "create", "default", "show", "fieldset");

$key = $_POST["key"];
$value = $_POST["value"];
$fieldID = intval($_POST["fieldid"]);

if(!in_array($key, $availKeys)) {
    die("Not allowed");
}

$adb->pquery("UPDATE vtiger_customerportal_columns SET `".$key."` = ? WHERE id = ".$fieldID, array($value));