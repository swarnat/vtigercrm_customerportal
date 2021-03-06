<?php
function ws_login($username, $accesskey) {
    global $adb, $current_user;

    $user = new Users();
    $userId = $user->retrieve_user_id($username);

    $sql = "select * from vtiger_users where id=?";
    $result = $adb->pquery($sql, array($userId));

    if($adb->num_rows($result) > 0){
        $usersAccessKey = $adb->query_result($result, 0, "accesskey");
    } else {
        return json_encode(array("result" => false, "error" => "NotFound"));
    }

    if($usersAccessKey != $accesskey) {
        return json_encode(array("result" => false, "error" => "AccessKey"));
    }

    $user = $user->retrieveCurrentUserInfoFromFile($userId);

    session_start();

    $_SESSION["authUserId"] = $userId;

    $current_user = $user;

    if($user->status != 'Inactive'){
        return json_encode(array("result" => true, "WS_SESS_ID" => session_id()));
    }
}

$server->register('ws_login',
			array('username' => 'xsd:string','accesskey' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#ws_login');

function doQuery($query) {
    global $current_user;

    require_once("include/Webservices/Query.php");

    $query = trim($query);
    if(substr($query, -1) != ";") {
        $query .= ";";
    }
    return json_encode(vtws_query($query, $current_user));
}
function doUpdate($data) {
    global $current_user;

    $data = json_decode($data, true);

    require_once("include/Webservices/Update.php");

    try {
        $return = vtws_update($data, $current_user);
    } catch(Exception $exp) {
        return json_encode(array("result" => "error", "code" => $exp->getCode(), "message" => $exp->message));
    }

    return json_encode(array("result" => "ok", "return" => $return));
}
function doCreate($module, $data) {
    global $current_user, $adb;

    $data = json_decode($data, true);

    if(empty($data["assigned_user_id"])) {
        $sql = "SELECT id FROM vtiger_ws_entity WHERE name = 'Users'";
        $wsEntityId = $adb->query_result($adb->query($sql), 0, "id");

        $data["assigned_user_id"] = $wsEntityId."x".$current_user->id;
    }

    $fields = json_decode(getFields($module, "CUSTOMERPORTAL_ID", false), true);

    $newData = array();

    foreach($fields as $fieldSetValue) {
        foreach($fieldSetValue as $key => $value) {
            if(!empty($data[$key])) {
                $newData[$key] = $data[$key];
            } elseif(empty($data[$key]) && !empty($value["default"])) {
                $newData[$key] = $value["default"];
            }
        }
    }
    $newData["assigned_user_id"] = $data["assigned_user_id"];

    require_once("include/Webservices/Create.php");

    $return = vtws_create($module, $newData, $current_user);

    return json_encode($return);
}

$server->register('doQuery',
			array('query' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#doQuery');

$server->register('doCreate',
			array('module' => 'xsd:string', 'data' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#doCreate');

$server->register('doUpdate',
			array('data' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#doQuery');
