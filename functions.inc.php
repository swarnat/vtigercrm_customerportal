<?php
function encrypt_passwort($value) {
    $passwort = explode("#~#~#", $value);

    $iv = base64_decode($passwort[0]);
    $passwort = base64_decode($passwort[1]);

    $passworts = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, SECURITY_SALT, $passwort, MCRYPT_MODE_CBC, $iv);
    return trim($passworts);
}
function getModuleName($tabid) {
    global $adb;

    $sql = "SELECT name FROM vtiger_tab WHERE tabid = ".intval($tabid);
    $result = $adb->query($sql);

    return $adb->query_result($result, 0, "name");
}
