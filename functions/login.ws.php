<?php

function login($username, $passwort) {
    global $adb;

    $passwort = encrypt_passwort($passwort);

    $current_date = date("Y-m-d");
   	$sql = "select id, user_name, user_password,last_login_time, support_start_date, support_end_date, vtiger_portalinfo.type
   				from vtiger_portalinfo
   					inner join vtiger_customerdetails on vtiger_portalinfo.id=vtiger_customerdetails.customerid
   					inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_portalinfo.id
   				where vtiger_crmentity.deleted=0 and user_name=?
   					and isactive=1 and vtiger_customerdetails.portal=1
   					and (vtiger_customerdetails.support_start_date IS NULL OR vtiger_customerdetails.support_start_date <= ?) and (vtiger_customerdetails.support_end_date IS NULL OR vtiger_customerdetails.support_end_date >= ?)";
   	$result = $adb->pquery($sql, array($username, $current_date, $current_date), true);

    if($adb->num_rows($result) == 0) {
        return array("result" => false);
    }

    $data = $adb->fetch_array($result);

    require_once("PHPass.php");
    $phpass = new tx_t3secsaltedpw_phpass();

    if($passwort != $data["user_password"] && !$phpass->checkPassword($passwort, $data["user_password"])) {
        return array("result" => false);
    }

    $RecordModule = $data["type"]=="C"?"Contacts":"";

    $sql = "SELECT tabid FROM vtiger_tab WHERE name = '".$RecordModule."'";
    $result = $adb->query($sql);
    $tabid = $adb->query_result($result, 0, "tabid");

    $sql = "SELECT id FROM vtiger_ws_entity WHERE name = '".$RecordModule."'";
    $result = $adb->query($sql);
    $wstabid = $adb->query_result($result, 0, "id");

    $sql = "SELECT firstname, lastname FROM vtiger_contactdetails WHERE contactid = ".$data["id"];
    $result = $adb->query($sql);
    $contactdata = $adb->fetch_array($result);

    return array("result" => true, "contact_id" => $tabid."x".$data["id"], "wscontact_id" => $wstabid."x".$data["id"], "module" => $RecordModule, "firstname" => $contactdata["firstname"], "lastname" => $contactdata["lastname"]);
}

$server->wsdl->addComplexType(
    'user',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result' => array('name' => 'result', 'type' => 'xsd:boolean'),
        'contact_id' => array('name' => 'contact_id', 'type' => 'xsd:string'),
        'wscontact_id' => array('name' => 'wscontact_id', 'type' => 'xsd:string'),
        'module' => array('name' => 'module', 'type' => 'xsd:string'),
        'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
        'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
    )
);

$server->register('login',
			array('username' => 'xsd:string', 'passwort' => 'xsd:string'),
            array('return' => 'tns:user'),
			'urn:server',
			'urn:server#login');