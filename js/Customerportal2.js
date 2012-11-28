function sendNewPasswort(crmid) {
    jQuery("#loadingIndicator").html("<em>Passwort wird versendet ...</em>");
    jQuery.post("index.php?module=Customerportal2&action=Customerportal2Ajax&file=sendnewpasswort", {
        crmid:crmid
    }, function() {
        jQuery("#loadingIndicator").html("<span style='font-weight:bold;color:darkgreen;'>Passwort erfolgreich versendet!</span>");
    });
}