<?php
function getPicklistValuesForModule($module, $columns = array())
{
	global $adb;
	global $current_user;

	$picklists = array();

	$query = "SELECT fieldname,columnname,fieldid,fieldlabel,tabid,uitype FROM vtiger_field WHERE tabid = '". getTabid($module) ."' AND uitype IN (15,33,55)".(count($columns) > 0 ? "and columnname in ('".implode("','", $columns) ."')" : "");
	$result = $adb->query($query);//,$select_column));

	if($adb->num_rows($result) == 0) {
		return array();
	}

	$rolesID = $current_user->roleid;
	$subrole = getRoleSubordinates($rolesID);
	if(count($subrole)> 0) 	{
		$roleIds = $subrole;
		array_push($roleIds, $rolesID);
	} else {
		$roleIds = $rolesID;
	}


	$tmp = Array();
	$times = array();
	$counter = 0;
	while($row = $adb->fetch_array($result)) {

		$fieldname = $row["fieldname"];
        if($fieldname == "firstname") continue;

		$sql = "SELECT DISTINCT ".$fieldname." as value
				FROM vtiger_".$fieldname."
					INNER JOIN vtiger_role2picklist ON vtiger_role2picklist.picklistvalueid = vtiger_".$fieldname.".picklist_valueid
				WHERE ".(is_array($roleIds)?"roleid in ('". implode("','", $roleIds) ."')":"roleid = '".$rolesID."'")." and picklistid in (select picklistid from vtiger_".$fieldname.")
				ORDER BY sortid ASC";
		$resultFields = $adb->query($sql);

		$values = array();
		while($field = $adb->fetch_array($resultFields)) {
			$picklists[$fieldname][] = $field["value"];
		}
	}

	return $picklists;
}
function getFields($module, $cp_id, $create) {
	global $adb;
	$tabID = getTabId($module);

	if(empty($tabID))
		return array();

	$picklists = false;

	$sql = "SELECT vtiger_customerportal_columns.*, vtiger_field.fieldlabel, vtiger_field.fieldname, vtiger_field.uitype
				FROM
					vtiger_customerportal_columns
						LEFT JOIN vtiger_field ON(vtiger_field.tabid = ".$tabID." AND fieldname = vtiger_customerportal_columns.field)
				WHERE customerportal_id = '".$cp_id."' AND module = '".$module."' ".($create=="1"?"AND `create` = '1'":"")." ORDER BY sort";
	$result = $adb->query($sql);

	$return = array();
	while($row = $adb->fetch_array($result)) {
		$xTmp = array();

        // Salutation Picklist must insert with firstname
        if($row["fieldname"] == "salutation") continue;

#        echo $row["fieldname"].":".$row["uitype"]."\n";
		switch($row["uitype"]) {
			case "5":
				$type = "date";
			break;
			case "56":
				$type = "checkbox";
			break;
			case "15":
				if($picklists === false) {
					$picklists = getPicklistValuesForModule($module);
				}
				$type = "picklist";
				$xTmp = array(
					"options" => $picklists[$row["fieldname"]]
				);
			break;
            case "55":
                $type = "firstname";
                if($picklists === false) {
                    $picklists = getPicklistValuesForModule($module);
                }
                $xTmp = array(
                    "options" => $picklists["salutationtype"]
                );
                break;
            case "19":
                $type = "textarea";
                break;
			case "1":
			default:
				$type = "text";
			break;
		}

		$return[$row["fieldset"]][$row["fieldname"]] = array_merge(array(
			"label" => (empty($row["label"])?$row["fieldlabel"]:$row["label"]),
			"type" => $type,
			"readonly" => $row["readonly"] == "1" && empty($create),
            "default" => $row["default"],
            "show" => $row["show"] == "1"
		), $xTmp);
	}
;
	return json_encode($return);
}

$server->register('getFields',
			array('module' => 'xsd:string', 'cp_id' => 'xsd:string', 'create' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#get_fields');

function relatedDocuments($crmid) {
    global $adb;

    if(strpos($crmid, "x") !== false) {
        $crmid = explode("x", $crmid);
        $crmid = $crmid[1];
    }

    $sql = "SELECT folderid FROM vtiger_attachmentsfolder WHERE foldername = 'Public'";
    $result = $adb->query($sql);
    if($adb->num_rows($result) > 0) {
        $folderID = $adb->query_result($result, 0, "folderid");
    } else {
        $folderID = "1";
    }

    $sql = "SELECT
                vtiger_attachments.attachmentsid,
                vtiger_attachments.name,
                vtiger_attachments.type,
                vtiger_notes.title,
                vtiger_notes.filesize,
                vtiger_crmentity2.modifiedtime
        FROM vtiger_senotesrel
            INNER JOIN vtiger_seattachmentsrel ON(vtiger_seattachmentsrel.crmid = vtiger_senotesrel.notesid)
            INNER JOIN vtiger_attachments ON(vtiger_attachments.attachmentsid = vtiger_seattachmentsrel.attachmentsid)

            INNER JOIN vtiger_crmentity as vtiger_crmentity2 ON(vtiger_crmentity2.crmid = vtiger_senotesrel.notesid)
            INNER JOIN vtiger_notes ON(vtiger_notes.notesid = vtiger_senotesrel.notesid)
        WHERE vtiger_senotesrel.crmid = ".intval($crmid)." AND vtiger_notes.folderid = ".$folderID." AND filelocationtype = 'I' AND vtiger_crmentity2.deleted = 0";
# INNER JOIN vtiger_crmentity ON(vtiger_crmentity.crmid = vtiger_seattachmentsrel.attachmentsid)
    $result = $adb->query($sql, true);


    $files = array();
    while($row = $adb->fetch_array($result)) {
        $row["secure-hash"] = sha1($row["attachmentsid"]."#".intval($crmid)."#".date("Y-m-d-H")."#".SECURITY_SALT).";".intval($crmid);
        $files[] = $row;
    }

    return json_encode($files);
}
$server->register('relatedDocuments',
			array('crmid' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#relatedDocuments');

function getRelated($module, $crmid, $target_module) {
    global $adb;

    if($target_module != "ModComments") {
        $sql = "SELECT * FROM vtiger_relatedlists WHERE tabid = '".getTabid($module)."' AND related_tabid = '".getTabid($target_module)."'";
        $result = $adb->query($sql);

        if($adb->num_rows($result) == 0) {
            return array();
        }

        $relatedList = $adb->fetch_array($result);
        $functionName = $relatedList["name"];

    } else {
        $functionName = "get_comments";
    }

    require_once(dirname(__FILE__)."/../modules/".$module.".php");

    $parts = explode("x", $crmid);

    $className = "CP_".$module;
    $instance = new $className();

    if(!method_exists($instance, $functionName)) {
        return "NOT_IMPLEMENTED "."CP_".$module."::".$functionName;
    }

    $records = $instance->$functionName($parts[1]);

    return json_encode($records);
}
$server->register('getRelated',
			array('module' => 'xsd:string', 'crmid' => 'xsd:string', 'target_module' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#getRelated');


function createComment($crmid, $comment, $authorid) {
    $elements = vtws_getIdComponents($crmid);

    $module = getModuleName($elements[0]);

    require_once(dirname(__FILE__)."/../modules/".$module.".php");

    $parts = explode("x", $crmid);

    $className = "CP_".$module;
    $instance = new $className();
    $functionName = "create_comment";

    if(!method_exists($instance, $functionName)) {
        return false; #"NOT_IMPLEMENTED "."CP_".$module."::".$functionName;
    }

    $records = $instance->$functionName($elements[1], $comment, $authorid);

    return true;
}
$server->register('createComment',
			array('crmid' => 'xsd:string', 'comment' => 'xsd:string', 'authorid' => 'xsd:string'),
            array('return' => 'xsd:boolean'),
			'urn:server',
			'urn:server#createComment');

function changeLogin($crmid, $username, $password) {
    global $adb;

    if(strpos($crmid, "x") !== false) {
        $crmid = explode("x", $crmid);
        $crmid = $crmid[1];
    }

    $password = encrypt_passwort($password);
    require_once("PHPass.php");
    $phpass = new tx_t3secsaltedpw_phpass();

    $password = $phpass->getHashedPassword($password);

    $crmid = intval($crmid);

    $sql = "UPDATE vtiger_portalinfo SET user_name = ?, user_password = ? WHERE id = ?";
    $result = $adb->pquery($sql, array($username, $password, $crmid));

    return true;
}
$server->register('changeLogin',
			array('crmid' => 'xsd:string', 'username' => 'xsd:string', 'password' => 'xsd:string'),
            array('return' => 'xsd:boolean'),
			'urn:server',
			'urn:server#changeLogin');

function webformRelay($data) {
    global $adb;
    ob_start();
    $_REQUEST = unserialize($data);

    $result = $adb->pquery("SELECT * FROM vtiger_webforms WHERE publicid=? AND enabled=?", array($_REQUEST["publicid"], 1));

    if ($adb->num_rows($result)) {

        $webformsid = $adb->query_result($result, 0, "id");
        $returnurl = $adb->query_result($result, 0, "returnurl");

        $sql = "UPDATE vtiger_webforms SET returnurl = '' WHERE id = ".$webformsid;
        $adb->query($sql);

        require_once("modules/Webforms/capture.php");
        ob_end_clean();

        $sql = "UPDATE vtiger_webforms SET returnurl = '".$returnurl."' WHERE id = ".$webformsid;
        $adb->query($sql);

        return json_encode(array("returnurl" => $returnurl));

    }

    return true;
}
$server->register('webformRelay',
			array('data' => 'xsd:string'),
            array('return' => 'xsd:string'),
			'urn:server',
			'urn:server#webformRelay');