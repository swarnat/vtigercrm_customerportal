<?
class Customerportal2 {
    const VERSION = "1.1";

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
                VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'Customer Portal', 'modules/Customerportal2/icon.png', 'Configure Customerportal', 'index.php?module=Customerportal2&action=admin&parenttab=Settings', $seq));

			$adb->query('ALTER TABLE  `vtiger_portalinfo` CHANGE  `user_password`  `user_password` VARCHAR( 60 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');

            echo "Create table: vtiger_customerportal_columns<br>";
            $adb->query("CREATE TABLE IF NOT EXISTS `vtiger_customerportal_columns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customerportal_id` varchar(32) NOT NULL,
  `module` varchar(32) NOT NULL,
  `label` varchar(255) NOT NULL,
  `field` varchar(255) NOT NULL,
  `readonly` tinyint(1) NOT NULL,
  `create` tinyint(1) NOT NULL DEFAULT '1',
  `default` varchar(255) NOT NULL,
  `show` tinyint(1) NOT NULL DEFAULT '1',
  `fieldset` varchar(64) NOT NULL,
  `sort` smallint(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `customerportal_id` (`customerportal_id`,`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

            require_once('vtlib/Vtiger/Module.php');
            $module = Vtiger_Module::getInstance("Contacts");
            $module->addLink('HEADERSCRIPT','Customerportal2','modules/Customerportal2/js/Customerportal2.js');

            $module->addLink('DETAILVIEWWIDGET','Customerportal Management',"module=Customerportal2&action=Customerportal2Ajax&file=contactwidget&return_module=".'$MODULE$&record=$RECORD$');

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
                    $adb->query("CREATE TABLE `vtiger_customerportal_lostpasswords` (
                    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `login` VARCHAR( 128 ) NOT NULL ,
                    `key` VARCHAR( 32 ) NOT NULL ,
                    `timestamp` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    UNIQUE (
                    `key`
                    )
                    ) ENGINE = INNODB;");

                    require_once('vtlib/Vtiger/Module.php');
                    $module = Vtiger_Module::getInstance("Contacts");
                    $module->addLink('HEADERSCRIPT','Customerportal2','modules/Customerportal2/js/Customerportal2.js');

                    $module->addLink('DETAILVIEWWIDGET','Customerportal Management',"module=Customerportal2&action=Customerportal2Ajax&file=contactwidget&return_module=".'$MODULE$&record=$RECORD$');

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