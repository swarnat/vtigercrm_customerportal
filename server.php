<?php
// vtiger Initialization
chdir(dirname(__FILE__)."/../../");
session_start();

/* If app_key is not set, pick the value from cron configuration */
if(empty($_REQUEST['app_key'])) $_REQUEST['app_key'] = $VTIGER_CRON_CONFIGURATION['app_key'];

/** All service invocation needs have valid app_key parameter sent */
require_once('config.inc.php');

set_include_path(dirname(__FILE__).PATH_SEPARATOR.get_include_path());

ini_set("display_errors", 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

#require_once dirname(__FILE__).'/config.inc.php';

require_once('include/logging.php');
require_once('include/database/PearDatabase.php');
require_once('modules/Documents/Documents.php');
require_once('modules/Users/Users.php');
require_once('include/utils/UserInfoUtil.php');
require_once 'modules/PickList/PickListUtils.php';
// vtiger initialization end

require_once(dirname(__FILE__).'/nusoap/nusoap.php');

$server = new nusoap_server;

$server->configureWSDL('server', 'urn:server', $site_URL."/modules/Customerportal2/server.php");

$server->wsdl->schemaTargetNamespace = 'urn:server';

if(!empty($_SESSION["authUserId"])) {
    $user = new Users();
    $current_user = $user->retrieveCurrentUserInfoFromFile($_SESSION["authUserId"]);
}
#error_log("SESSIONID:".session_id());
#error_log("UserID:".$_SESSION["authUserId"]);

require_once(dirname(__FILE__)."/functions.inc.php");

foreach (glob(dirname(__FILE__)."/functions/*.ws.php") as $filename)
{
    include $filename;
}

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
