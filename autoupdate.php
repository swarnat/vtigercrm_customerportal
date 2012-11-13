<?
    $moduleName = "Customerportal2";
    $backURL = "index.php?module=".$moduleName."&action=admin&parenttab=Settings";
?>
<br><br>
<table cellspacing="0" cellpadding="0" border="0" align="center" width="98%">
<tr>
       <td valign="top"><img src="themes/softed/images/showPanelTopLeft.gif"></td>
        <td width="100%" valign="top" style="padding: 10px;" class="showPanelBg">
            <br>
            <div class="settingsUI" style="width:95%;padding:10px;margin-left:10px;">

<?php
                function auto_update($module_name, $url) {
                    global $Vtiger_Utils_Log;
                    $filename = sys_get_temp_dir()."/autoupdater.zip";

                    $data = file_get_contents($url);

                    if($data == "false" || $data == "max_vtiger_version" || $data == "not_found") {
                        echo "Update failed ...<br>";
                        return;
                    }

                    file_put_contents($filename, $data);

                    $Vtiger_Utils_Log = true;

                    $package = new Vtiger_Package();
                    $package->update(Vtiger_Module::getInstance($module_name), $filename);

                    unlink($filename);

                }

$installed_version = $adb->query_result($adb->query("SELECT version FROM vtiger_tab WHERE name = '".$moduleName."'"), 0, "version");
echo "&nbsp;&nbsp;&nbsp;1. Get current installed Version of Module '<b>".$moduleName."</b>' ... ".$installed_version."<br>";

echo "&nbsp;&nbsp;&nbsp;2. Connection the Repository ... ";
$checkUrl = "http://vtiger.stefanwarnat.de/extensions/check_version.php?vtiger_version=".$vtiger_current_version."&extension=".$moduleName."&license=opensource";
$data = file_get_contents($checkUrl);

$result = unserialize(base64_decode($data));

if(empty($result)) {
    echo "<span style='color:red;font-weight:bold;'>ERROR</span><br>";
echo "</div></td></tr></table>";
    return;
}
if($result["result"] == "notfound") {
    echo "sucess!<br>";
    echo "<span style='color:red;'>&nbsp;&nbsp;&nbsp;3. Extension not found for this vtigerCRM Version!</span><br>";
    echo "<p style='text-align:center;font-weight:bold;'><a href='".$backURL."'>&laquo; Back to Administration</a></p>";
} elseif($result["result"] == "license_error") {
    echo "sucess!<br>";
    echo "<span style='color:red;'>&nbsp;&nbsp;&nbsp;3. You have to extend your license to receive upgrades for this vtigerCRM Version!</span><br>";
    echo "<p style='text-align:center;font-weight:bold;'><a href='".$backURL."'>&laquo; Back to Administration</a></p>";
} elseif(!empty($result)) {
    echo "sucess!<br>";
}
if($result["result"] == "ok") {
    if($installed_version < (float)$result["version"] || $_GET["force"] === "true") {
        echo "&nbsp;&nbsp;&nbsp;3. Start Upgrade from ".$installed_version." to ".(float)$result["version"]." ... ";
        echo "<div style='margin-left:30px;margin-top:10px;border:1px solid #ccc;background-color:#fff;padding:5px;'>";auto_update($moduleName, $result["url"]);echo "</div>";
        echo "<p style='text-align:center;font-weight:bold;'><a href='".$backURL."'>&laquo; Back to Administration</a></p>";
    } else {
        echo "<p style='text-align:center;font-weight:bold;'>You have already the newest Version ".(float)$result["version"]."!</p>";
        echo "<p style='text-align:center;font-weight:bold;'><a href='".$backURL."'>&laquo; Back to Administration</a><a style='margin-left:100px;' href='index.php?module=".$moduleName."&action=autoupdate&parenttab=Settings&force=true'>Force Reinstallation</a></p>";
    }
}

?>
                </div>
            </td>
    </tr>
    </table>

<?
