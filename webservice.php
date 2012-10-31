<?php
require_once(dirname(__FILE__)."/config.inc.php");

function get_currencies() {
    global $adb;

    $sql = "SELECT * FROM vtiger_currency_info ORDER BY id";
    $result = $adb->query($sql);

    $currencies = array();
    while($row = $adb->fetch_array($result)) {
        $currencies[] = $row;
    }

    return $currencies;
}
