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

    return json_encode(vtws_update($data, $current_user));
}

$server->register('doQuery',
			array('query' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#doQuery');

$server->register('doUpdate',
			array('data' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#doQuery');
