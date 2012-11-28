<?php
$fieldset = $_POST["title"];
$module = $_POST["moduleName"];

$sql = "SELECT COUNT(*) as num, MIN(sort) as min, MAX(sort) as max FROM vtiger_customerportal_columns WHERE fieldset = ? AND module = ?";
$result = $adb->pquery($sql, array($fieldset, $module));

$data = $adb->fetchByAssoc($result);

if($data["min"] > 1) {
    // Vorheriges Fieldset
    $sql = "SELECT fieldset FROM vtiger_customerportal_columns WHERE sort = ".($data["min"]-1)." AND module = ?";
    $result = $adb->pquery($sql, array($module));

    $prevFieldsetName = $adb->query_result($result, 0, "fieldset");

    $sql = "SELECT COUNT(*) as num, MIN(sort) as min, MAX(sort) as max FROM vtiger_customerportal_columns WHERE fieldset = ? AND module = ?";
    $result = $adb->pquery($sql, array($prevFieldsetName, $module));

    $prevFieldset = $adb->fetchByAssoc($result);

    $newMin = $prevFieldset["min"];
    $countPrev = $prevFieldset["num"];
} else {
    // Not available
    return;
}

$sql = "UPDATE vtiger_customerportal_columns SET `sort` = `sort` - ".$countPrev." WHERE `fieldset`  = ? AND module = ?";
echo $sql;
$adb->pquery($sql, array($fieldset, $module));

$sql = "UPDATE vtiger_customerportal_columns SET `sort` = `sort` + ".$data["num"]." WHERE `fieldset` = ? AND module = ?";
echo $sql;
$adb->pquery($sql, array($prevFieldsetName, $module));
