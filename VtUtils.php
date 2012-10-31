<?php
/**
 This File was developed by Stefan Warnat <vtiger@stefanwarnat.de>

 It belongs to the Workflow Designer and must not be distrubuted wihout complete extension
**/
class VtUtils
{
    public static function getFieldsForModule($module_name, $uitype = false) {
        global $current_language;

        if($uitype !== false && !is_array($uitype)) {
            $uitype = array($uitype);
        }

        // Fields in this module
        include_once("vtlib/Vtiger/Module.php");

       	$alle = glob(dirname(__FILE__).'/functions/*.inc.php');
       	foreach($alle as $datei) { include $datei;		 }

       	$module = $module_name;
       	$instance = Vtiger_Module::getInstance($module);
       	$blocks = Vtiger_Block::getAllForModule($instance);

       	$modLang = return_module_language($current_language, $module);
        $moduleFields = array();

    	foreach($blocks as $block) {
            $fields = Vtiger_Field::getAllForBlock($block, $instance);

            if(empty($fields)) {
                continue;
            }
            foreach($fields as $field) {
                $field->label = (isset($modLang[$field->label])?$modLang[$field->label]:$field->label);
                if($uitype !== false) {
                    if(in_array($field->uitype, $uitype)) {
                        $moduleFields[] = $field;
                    }
                } else {
                    $moduleFields[] = $field;
                }

            }
    	}

        return $moduleFields;
    }

    public static function getFieldsWithBlocksForModule($module_name, $references = false) {
        global $current_language, $adb, $app_strings;

        if($uitype !== false && !is_array($uitype)) {
            $uitype = array($uitype);
        }

        // Fields in this module
        include_once("vtlib/Vtiger/Module.php");

       	$alle = glob(dirname(__FILE__).'/functions/*.inc.php');
       	foreach($alle as $datei) { include $datei;		 }

       	$module = $module_name;
       	$instance = Vtiger_Module::getInstance($module);
       	$blocks = Vtiger_Block::getAllForModule($instance);

       	$modLang = return_module_language($current_language, $module);
        $moduleFields = array();

        $addReferences = array();

    	foreach($blocks as $block) {
            $fields = Vtiger_Field::getAllForBlock($block, $instance);

            if(empty($fields)) {
                continue;
            }

            foreach($fields as $field) {
                $field->label = (isset($modLang[$field->label])?$modLang[$field->label]:$field->label);

                if($references !== false) {
                    switch ($field->uitype) {
                        case "51":
                               $addReferences[] = array($field,"Accounts");
                        break;
                        case "57":
                               $addReferences[] = array($field,"Contacts");
                           break;
                        case "58":
                               $addReferences[] = array($field,"Campaigns");
                           break;
                        case "59":
                               $addReferences[] = array($field,"Products");
                           break;
                        case "73":
                               $addReferences[] = array($field,"Accounts");
                           break;
                        case "75":
                               $addReferences[] = array($field,"Vendors");
                           break;
                        case "81":
                               $addReferences[] = array($field,"Vendors");
                           break;
                        case "76":
                               $addReferences[] = array($field,"Potentials");
                           break;
                        case "78":
                               $addReferences[] = array($field,"Quotes");
                           break;
                        case "80":
                               $addReferences[] = array($field,"SalesOrder");
                           break;
                        case "68":
                               $addReferences[] = array($field,"Accounts");
                               $addReferences[] = array($field,"Contacts");
                               break;
                        case "10": # Possibly multiple relations
                                $result = $adb->pquery('SELECT relmodule FROM `vtiger_fieldmodulerel` WHERE fieldid = ?', array($field->id));
                                while ($data = $adb->fetch_array($result)) {
                                    $addReferences[] = array($field,$data["relmodule"]);
                                }
                            break;
                    }
                }

                $moduleFields[$block->label][] = $field;
            }
    	}

        foreach($addReferences as $refField) {
#            var_dump($refField);
            $fields = self::getFieldsForModule($refField[1]);

            foreach($fields as $field) {
                $field->label = "(".(isset($app_strings[$refField[1]])?$app_strings[$refField[1]]:$refField[1]).") ".$field->label;
                $field->name = "(".$refField[0]->name.": (".$refField[1].")) ".$field->name;

                $moduleFields["References (".$refField[0]->label.")"][] = $field;
            }
        }

        return $moduleFields;
    }

    public static function getAdminUser() {
        return Users::getActiveAdminUser();
    }

    public static function getRelatedModules($module_name) {
        global $adb, $current_user, $app_strings;

        require('user_privileges/user_privileges_' . $current_user->id . '.php');

        $sql = "SELECT vtiger_relatedlists.related_tabid,vtiger_relatedlists.label, vtiger_relatedlists.name, vtiger_tab.name as module_name FROM
                vtiger_relatedlists
                    INNER JOIN vtiger_tab ON(vtiger_tab.tabid = vtiger_relatedlists.related_tabid)
                WHERE vtiger_relatedlists.tabid = '".getTabId($module_name)."' AND related_tabid not in (SELECT tabid FROM vtiger_tab WHERE presence = 1) ORDER BY sequence, vtiger_relatedlists.relation_id";
        $result = $adb->query($sql);

        $relatedLists = array();
        while($row = $adb->fetch_array($result)) {

            // Nur wenn Zugriff erlaubt, dann zugreifen lassen
            if ($profileTabsPermission[$row["related_tabid"]] == 0) {
                if ($profileActionPermission[$row["related_tabid"]][3] == 0) {
                    $relatedLists[] = array(
                        "related_tabid" => $row["related_tabid"],
                        "module_name" => $row["module_name"],
                        "action" => $row["name"],
                        "label" => isset($app_strings[$row["label"]])?$app_strings[$row["label"]]:$row["label"],
                    );
                }
            }

        }

        return $relatedLists;
    }

    public static function getModuleName($tabid) {
        global $adb;

        $sql = "SELECT name FROM vtiger_tab WHERE tabid = ".intval($tabid);
        $result = $adb->query($sql);

        return $adb->query_result($result, 0, "name");
    }

}
