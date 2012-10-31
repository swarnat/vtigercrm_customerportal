<?
class Customerportal2 {
    const VERSION = "1.3";

    const DEBUG = false;

	public function vtlib_handler($modulename, $event_type) {
		global $adb;
		if($event_type == 'module.postinstall') {
		
            $fieldid = $adb->getUniqueID('vtiger_settings_field');
            $blockid = getSettingsBlockId('LBL_OTHER_SETTINGS');
            $seq_res = $adb->pquery("SELECT max(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?", array($blockid));
            if ($adb->num_rows($seq_res) > 0) {
                $cur_seq = $adb->query_result($seq_res, 0, 'max_seq');
                if ($cur_seq != null)	$seq = $cur_seq + 1;
            }

            $adb->pquery('INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)
                VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'Customer Portal', 'modules/CustomerPortal2/icon.png', 'Configure Customerportal', 'index.php?module=Customerportal2&action=admin&parenttab=Settings', $seq));

			$adb->query('ALTER TABLE  `vtiger_portalinfo` CHANGE  `user_password`  `user_password` VARCHAR( 60 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
			
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
            switch(self::VERSION) {
                case "1.1":

                   break;
            }

		}
	}

    public static function updateCheck($lastCheckDate = false) {
        global $adb,$vtiger_current_version,$activateAutoUpdate;

        if($activateAutoUpdate == false) {
            return;
        }

        $moduleName = "Customerportal2";

        $doCheck = false;

        if(!file_exists(dirname(__FILE__)."/updatecheck.log")) {
            $doCheck = true;
        } else {
            $content = file(dirname(__FILE__)."/updatecheck.log");
            if($content[0] < time() - 86400 * 7) {
                $doCheck = true;
            }
        }

        if($doCheck == false)
            return;

        $lastCheckDate = $content[0];
        if(!empty($content[1])) {
            echo "<br /><div class='updateHint'>".sprintf(getTranslatedString("Version %s available. Please update!", "Customerportal2"), (float)$content[1])."</div>";
        }

        $lastCheckDate = $lastCheckDate;

        if($lastCheckDate < time() - (86400 * 7)) {
            $checkUrl = "http://vtiger.stefanwarnat.de/extensions/check_version.php?vtiger_version=".$vtiger_current_version."&extension=".$moduleName;
            $data = file_get_contents($checkUrl);

            $result = unserialize(base64_decode($data));

            if(!empty($result)) {

                if($result["result"] == "ok") {
                    $installed_version = $adb->query_result($adb->query("SELECT version FROM vtiger_tab WHERE name = '".$moduleName."'"), 0, "version");

                    if($installed_version < (float)$result["version"]) {
                        echo "<br /><div class='updateHint'>".sprintf(getTranslatedString("Version %s available. Please update!", "Customerportal2"), (float)$result["version"])."</div>";

                        file_put_contents(dirname(__FILE__)."/updatecheck.log", time()."\n".(float)$result["version"]);return;
                    }
                }
            }

            file_put_contents(dirname(__FILE__)."/updatecheck.log", time());
        }
    }
}