<?php
require_once("Base.php");

class CP_Contacts extends CP_Base {
    protected $module_name = "Contacts";

    public function getRelated($crmID, $targetModule) {
        $return = parent::getRelated($crmID, $targetModule);

        if($return !== false) {
            return $return;
        }
    }
	
	public function get_applicants($crmID) {
		global $adb;
		
		if(strpos($crmID, "x") !== false) {
			$crmID = explode("x", $crmID);
			$crmID = $crmID[1];
		}
        $query = 'SELECT vtiger_applicants.applicantsid
					FROM vtiger_applicants
      				INNER JOIN vtiger_crmentity
      					ON vtiger_crmentity.crmid = vtiger_applicants.applicantsid
      			   WHERE vtiger_applicants.contact_id = '.$crmID.' and vtiger_crmentity.deleted = 0';
        $result = $adb->query($query);	
		
		$result = $adb->fetch_array($result);
		
		return getTabId("Applicants")."x".$result["applicantsid"];
	}


}