<?php
$excludeModules = array("Colorizer","CustomerPortal","Customerportal2");

    require_once("VtUtils.php");
    require_once("Customerportal2.php");
?>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.8.2.js"></script>
<script type="text/javascript">
    jQuery.noConflict();
</script>

<script type="text/javascript" src="http://code.jquery.com/ui/1.9.1/jquery-ui.js"></script>
<link rel="stylesheet" type="text/css" media="all" href="http://code.jquery.com/ui/1.9.1/themes/base/jquery-ui.css">
<link rel="stylesheet" type="text/css" media="all" href="modules/Customerportal2/adminStyle.css">
<style type="text/css">
    .fieldsetHead {
        font-weight:bold;
        padding-left:20px;
        background-color:#eee;
    }
    /*.sortableDiv:nth-child(even) {background: #f2f2f2}*/
    /*.sortableDiv:nth-child(odd) {background: #FFF}*/

    .sortable {
        list-style:none;
        background-color:#fff;
    }
    .sortableDiv {
        margin-left:20px;
        overflow:hidden;
        clear:both;

        border-top:1px solid #eee;
        border-bottom:1px solid #eee;

        background-color:#fff;
    }
    .row {
        float:left;
        height:28px;
        line-height:28px;
    }
    .row.Title {
        width:200px;
    }
    .row.Field {
        width:200px;
    }
    .row.Checkbox {
        width:80px;
    }
    .ui-state-highlight {
        margin-left:20px;
        height:28px;
    }
    .sortableHandle {
        float:left;
        margin:5px 10px 5px 0;
        cursor:move;
    }
</style>
<script type="text/javascript">
jQuery(function() {


    jQuery( ".sortable" ).sortable({
                placeholder: "ui-state-highlight",
                forcePlaceholderSize: true,
                distance: 15,
                handle: '.sortableHandle',
                cancel: ".notSortable",
                stop: function(event, ui) {
                    if(ui.item.nextAll(".sortableDiv").length > 0) {
                        nextField = jQuery(ui.item.nextAll(".sortableDiv")[0]).attr("id").substr(6);
                    } else {
                        nextField = -1;
                    }

                    var fieldSet = jQuery(ui.item.prevAll(".fieldsetHead")[0]).attr("title");
                    if(ui.position.top == ui.originalPosition.top)
                        return;
                    jQuery.post("index.php?module=Customerportal2&action=Customerportal2Ajax&file=sort", {
                        order:jQuery(this).sortable('toArray'),
                        fieldid:ui.item.attr("id").substr(6),
                        nextField:nextField,
                        fieldset: fieldSet,
                        cp_module: "<?php echo $_GET["cp_module"] ?>"
                    });
                }
            });
     jQuery( ".sortable .valueField" ).disableSelection();


});
function getAllBetween(firstEl,lastEl) {
    var firstElement = jQuery(firstEl); // First Element
    var lastElement = jQuery(lastEl); // Last Element
    var collection = new Array(); // Collection of Elements
    collection.push(firstElement); // Add First Element to Collection
    jQuery(firstEl).nextAll().each(function(){ // Traverse all siblings
    	var siblingID  = jQuery(this).attr("id"); // Get Sibling ID
    	if (siblingID != jQuery(lastElement).attr("id")) { // If Sib is not LastElement
    		collection.push(jQuery(this)); // Add Sibling to Collection
    	} else { // Else, if Sib is LastElement
    		return false; // Break Loop
    	}
    });
    return collection; // Return Collection
}
    function addField() {
        var fieldName = jQuery("#newField").val();

        jQuery.post("index.php?module=Customerportal2&action=Customerportal2Ajax&file=addfield", {
            field: fieldName,
            cp_module: "<?php echo $_GET["cp_module"] ?>"
        }, function() {
            window.location.reload();
        });
    }

    function saveValue(el, key, value) {
        var fieldid = jQuery(el).parents("li").attr("id").substr(6);

        jQuery.post("index.php?module=Customerportal2&action=Customerportal2Ajax&file=setvalue", {
            key: key,
            value: value,
            fieldid: fieldid
        }, function() {

        })
    }
    function createFieldset(el) {
        var fieldSet = prompt("Title of the new Fieldset");

        if(fieldSet == null)
            return;

        var fieldid = jQuery(el).parent().attr("id").substr(6);

        jQuery.post("index.php?module=Customerportal2&action=Customerportal2Ajax&file=setvalue", {
            key: "fieldset",
            value: fieldSet,
            fieldid: fieldid
        }, function() {

        })
    }

    function moveFieldset(direction, title, module, ele) {

        jQuery.post("index.php?module=Customerportal2&action=Customerportal2Ajax&file=movefieldset", {
            title: title,
            direction: direction,
            moduleName: module
        }, function() {
            var titleEle = jQuery(ele).closest("li");
            var containerElements;

            if(titleEle.nextAll(".fieldsetHead") != undefined) {
                containerElements = getAllBetween(titleEle.next(), titleEle.nextAll(".fieldsetHead"));
            } else {
                containerElements = titleEle.nextAll();
            }

            containerElements = jQuery(containerElements);

            if(titleEle.prevAll(".fieldsetHead") != undefined) {
                var prevTitle = titleEle.prevAll(".fieldsetHead");

                titleEle.remove();
                jQuery.each(containerElements, function(index, value) {
                    jQuery(value).remove();
                });

                titleEle.insertBefore(prevTitle);
                containerElements.insertBefore(prevTitle);
            }




//            var img = $('h3').prev(); //get the element before this one
//              $('h3').remove().insertBefore(img);
        })
    }
</script>
<?php
$sql = "SELECT * FROM vtiger_tab WHERE presence = 0";
$result = $adb->query($sql);

$module = array();
$existingFields = array();

while($row = $adb->fetch_array($result)) {
    if(in_array($row["name"], $excludeModules)) continue;

    $module[$row["name"]] = isset($app_strings[$row["tablabel"]])?$app_strings[$row["tablabel"]]:$row["tablabel"];
}
asort($module);
?>

<h2 style="margin-left:40px;">Customer Portal Settings</h2>
<table cellspacing="0" cellpadding="0" border="0" align="center" width="98%">
<tr>
       <td valign="top"><img src="themes/softed/images/showPanelTopLeft.gif"></td>
        <td width="100%" valign="top" style="padding: 10px;" class="showPanelBg">
            <br>
            <div class="settingsUI" style="width:95%;padding:10px;margin-left:10px;">

    <form method="GET" action="index.php" style="padding:10px;margin:10px 0 20px 0;border:1px solid #ccc;background-color:#fff;" >
        <input type="hidden" name="module" value="Customerportal2">
        <input type="hidden" name="action" value="admin">
        Show Module:
        <select name='cp_module' id='cp_module'>
            <? foreach($module as $key => $label) { ?>
                <option value="<?php echo $key ?>" <?php if($_GET["cp_module"]==$key) echo "selected='selected'"; ?>><?php echo $label ?></option>
            <? } ?>
        </select>
        <input type="submit" name="changeModule" value="change Module">
    </form>
<?php
if(!empty($_GET["cp_module"])) {

$sql = "SELECT * FROM vtiger_customerportal_columns WHERE module = ?  ORDER BY sort";
$result = $adb->pquery($sql, array($_GET["cp_module"]));

$moduleName = $_GET["cp_module"];

$lastModule = "";
$lastFieldset = "";

?>
<div class="dvInnerHeader">
    <b>&nbsp;<? echo isset($app_strings[$_GET["cp_module"]])?$app_strings[$_GET["cp_module"]]:$_GET["cp_module"] ?></b>
</div>
<div style="background-color:#fff;">
<div style="margin-left:70px;">
    <div class="row Field">Field</div>
    <div class="row Title">Label</div>
    <div class="row Checkbox">Show</div>
    <div class="row Checkbox">Readonly</div>
    <div class="row Checkbox">Create</div>
    <div class="row DefaultValue">default Value</div>
</div>
<div style="clear:both;"></div>

<ul class="sortable">
<?
while($row = $adb->fetch_array($result)) {
    $existingFields[] = $row["field"];

    if($lastFieldset != $row["fieldset"]) {
        $lastFieldset = $row["fieldset"];

?>
        <li class="fieldsetHead notSortable" title="<?=$row["fieldset"] ?>">
            <?=$row["fieldset"] ?> (<a href='#' onclick='moveFieldset("up", "<?php echo $row["fieldset"] ?>","<?php echo $moduleName ?>", this); return false;'>up</a>)
        </li>

    <?
    }

    ?>
        <li class="sortableDiv" id="field_<?php echo $row["id"] ?>">
            <img src='modules/Customerportal2/text_align_justify.png' class="sortableHandle">
            <div class="row Field valueField"><?php echo $row["field"] ?></div>
            <div class="row Title valueLabel"><input type="text" onchange="saveValue(this, 'label', this.value);" name="field[<?php echo $row["id"] ?>][label]" value="<?php echo $row["label"] ?>" /></div>
            <div class="row Checkbox valueShow"><input type="checkbox" onchange="saveValue(this, 'show', this.checked?1:0);" name="field[<?php echo $row["id"] ?>][show]" <?php echo $row["show"]=="1"?'checked="checked"':"" ?> /></div>
            <div class="row Checkbox valueReadonly"><input type="checkbox" onchange="saveValue(this, 'readonly', this.checked?1:0);" name="field[<?php echo $row["id"] ?>][readonly]" <?php echo $row["readonly"]=="1"?'checked="checked"':"" ?> /></div>
            <div class="row Checkbox valueCreate"><input type="checkbox" onchange="saveValue(this, 'create', this.checked?1:0);" name="field[<?php echo $row["id"] ?>][create]" <?php echo $row["create"]=="1"?'checked="checked"':"" ?> /></div>
            <div class="row DefaultValue"><input type="text" onchange="saveValue(this, 'default', this.value);" name="field[<?php echo $row["id"] ?>][default]" value="<?php echo $row["default"] ?>" /></div>
            <div class="row DefaultValue valueSetFieldset" onclick="createFieldset(this);">Change Fieldset</div>
        </li>
    <?
}
echo "</ul></div>";

$fields = VtUtils::getFieldsWithBlocksForModule($moduleName);

echo '<div style="padding:10px;margin:10px 0 20px 0;border:1px solid #ccc;background-color:#fff;" >Add new field: ';
echo "<select name='newField' id='newField'>";
foreach($fields as $blockLabel => $block) {
    echo "<optgroup label='".$blockLabel."'>";
    foreach($block as $field) {
        if(!in_array($field->name, $existingFields))
            echo "<option value='".$field->name."'>".$field->label."</option>";
    }
    echo "</optgroup>";
}
echo '</select>';
echo '<input type="button" class="button small edit" onclick="addField();" value="add Field" />';
echo '</div>';
}
?>
    </div>
<br>
<div class="copyRight">Customer Portal <?php echo Customerportal2::VERSION ?> by <a href="http://vtiger.stefanwarnat.de">Stefan Warnat</a><br>
    <?php Customerportal2::updateCheck() ?>
    <input type="button" class="crmbutton edit small"  value="Update Module" onclick="if(confirm('Start update?\n\nYou should update the customerportal, too!')) window.location.href='index.php?module=Customerportal2&action=autoupdate&parenttab=Settings';">
</div>


        </td></tr>
        </table>