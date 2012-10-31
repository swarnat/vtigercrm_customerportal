<?php
require_once("Base.php");

class CP_Helpdesk extends CP_Base {
    protected $module_name = "HelpDesk";

    public function getRelated($crmID, $targetModule) {
        $return = parent::getRelated($crmID, $targetModule);

        if($return !== false) {
            return $return;
        }
    }

    public function get_comments($crmID) {
        global $adb;

        $sql = "select * from vtiger_ticketcomments where ticketid=? ORDER BY createdtime DESC";
        $result = $adb->pquery($sql, array($crmID));

        $tickets = array();
        while($row = $adb->fetch_array($result)) {
            if($row["ownertype"] == 'user')
                $row["author"] = getUserFullName($row['ownerid']);
            elseif($row["ownertype"] == 'customer') {
                $contactid = $row['ownerid'];
                $displayValueArray = getEntityName('Contacts', $contactid);
                if (!empty($displayValueArray)) {
                    foreach ($displayValueArray as $key => $field_value) {
                        $contact_name = $field_value;
                    }
                } else {
                    $contact_name='';
                }
                $row["author"] = $contact_name;
            }

            $tickets[] = $row;
        }

        return $tickets;
    }

    public function create_comment($crmid, $comment, $authorID) {
        global $adb;

        if(strpos($authorID, "x") !== false) {
            $authorID = explode("x", $authorID);
            $authorID = $authorID[1];
        }

        $sql = "INSERT INTO vtiger_ticketcomments SET ticketid = ?, ownerid = ?, ownertype = ?, comments = ?, createdtime = UTC_TIMESTAMP()";
        $adb->pquery($sql, array($crmid, $authorID, "customer", $comment));

    }

}