<?php
$order = $_POST["order"];

$fieldID = intval($_POST["fieldid"]);
$fieldSet = $_POST["fieldset"];
$nextFieldID = intval($_POST["nextField"]);
$module = $_POST["cp_module"];

$sql = "UPDATE vtiger_customerportal_columns SET fieldset = ? WHERE id = ".$fieldID;
$adb->pquery($sql, array($fieldSet));

$ids = array();

$sql = "SELECT sort FROM vtiger_customerportal_columns WHERE id = ".$fieldID;
$result = $adb->query($sql);
$index = $adb->query_result($result, 0, "sort");

if($nextFieldID != -1) {
    $sql = "SELECT sort FROM vtiger_customerportal_columns WHERE id = ".$nextFieldID;
    $result = $adb->query($sql);
    $nextIndex = $adb->query_result($result, 0, "sort");
} else {
    $sql = "SELECT MAX(sort) FROM vtiger_customerportal_columns WHERE module = ?";
    $result = $adb->pquery($sql, array($module));
    $nextIndex = $adb->query_result($result, 0, "sort") + 1;
}

var_dump($index);
var_dump($nextIndex);

if($index > $nextIndex) {
    $sql = "UPDATE vtiger_customerportal_columns SET sort = sort + 1 WHERE sort >= ".$nextIndex." AND sort < $index AND module = ?";
    $adb->pquery($sql, array($module));
    $newIndex = $nextIndex;
} else {
    $sql = "UPDATE vtiger_customerportal_columns SET sort = sort - 1 WHERE sort > ".$index." AND sort < $nextIndex AND module = ?";
    $adb->pquery($sql, array($module));
    $newIndex = $nextIndex - 1;
}

$sql = "UPDATE vtiger_customerportal_columns SET sort = ".($newIndex)." WHERE id = ".$fieldID;
$adb->query($sql);


$sql = "SELECT id FROM vtiger_customerportal_columns WHERE module = ? GROUP BY sort HAVING COUNT(*) > 1";
$result = $adb->pquery($sql, array($module));



// Es wurden mehr als ein Eintrag mit der gleichen Sort ID gefunden
if($adb->num_rows($result) > 0) {
    var_dump("REORDER9,");
    $sql = "SELECT id FROM vtiger_customerportal_columns WHERE module = ? ORDER BY sort";
    $result = $adb->pquery($sql, array($module));

    $counter = 1;
    while($row = $adb->fetchByAssoc($result)) {
        $sql = "UPDATE vtiger_customerportal_columns SET sort = $counter WHERE id = ".$row["id"];
        $adb->query($sql);

        $counter++;
    }
}