<?php

require_once("modules/Contacts/ContactsHandler.php");
require_once("modules/com_vtiger_workflow/VTEntityCache.inc");
require_once("include/Webservices/Utils.php");
require_once("include/Webservices/Retrieve.php");

$func_reflection = new ReflectionFunction('Contacts_sendCustomerPortalLoginDetails');
$num_of_params = $func_reflection->getNumberOfParameters();
$crmid = intval($_REQUEST["crmid"]);

$id = vtws_getWebserviceEntityId("Contacts", $crmid);

$entity = new VTWorkflowEntity($current_user, $id);

// if the custom solution is used? RECOMMENDED, because there are security issues!
if($num_of_params == 2) {
    Contacts_sendCustomerPortalLoginDetails($entity, true);
} else {
    $sql = "UPDATE vtiger_portalinfo SET isactive = 0 WHERE crmid = ".$crmid;
    $adb->query($sql);

    Contacts_sendCustomerPortalLoginDetails($entity);
}