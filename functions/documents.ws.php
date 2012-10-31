<?php
function getDocument($attachmentid, $crmid, $hash) {
    global $adb;

    if($hash != sha1($attachmentid."#".intval($crmid)."#".date("Y-m-d-H")."#".SECURITY_SALT)) {
        return false;
    }

    $sql = "SELECT * FROM
            vtiger_attachments
        INNER JOIN vtiger_seattachmentsrel ON(vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid)
        INNER JOIN vtiger_notes ON(vtiger_notes.notesid = vtiger_seattachmentsrel.crmid)
    WHERE vtiger_attachments.attachmentsid = ".intval($attachmentid);
    $result = $adb->query($sql);

    $row = $adb->fetch_array($result);

    $filecontent = base64_encode(file_get_contents($row["path"].$attachmentid."_".$row["name"]));

    return array("data" => $filecontent, "filesize" => $row["filesize"], "type" => $row["filetype"], "filename" => $row["filename"]);
}

$server->wsdl->addComplexType(
    'document',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'data' => array('data' => 'result', 'type' => 'xsd:base64Binary'),
        'filesize' => array('filesize' => 'contact_id', 'type' => 'xsd:int'),
        'type' => array('type' => 'wscontact_id', 'type' => 'xsd:string'),
        'filename' => array('filename' => 'module', 'type' => 'xsd:string'),
    )
);

$server->register('getDocument',
			array('attachmentid' => 'xsd:string', 'crmid' => 'xsd:string', 'hash' => 'xsd:string'),
            array('return' => 'tns:document'),
			'urn:server',
			'urn:server#getDocument');

function createDocument($crmid, $folderid, $file) {
    global $adb, $current_user;


    if(strpos($crmid, "x") !== false) {
        $crmid = explode("x", $crmid);
        $crmid = $crmid[1];
    }
    $filedata = $file;

    $tmpFile = tempnam(sys_get_temp_dir(), "CP_UPLOAD");

    file_put_contents($tmpFile, ($filedata["data"]));

    $filename = $filedata["filename"];
    $filetype = $filedata["type"];
    $filesize = filesize($tmpFile);

    $upload_filepath = decideFilePath();

    $current_id = $adb->getUniqueID("vtiger_crmentity");
    $date_var = $adb->formatDate(date('Y-m-d H:i:s'), true);

    $query = "insert into vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) values(?,?,?,?,?,?,?)";
    $qparams = array($current_id, $current_user->id, $current_user->id, 'Documents Attachment', 'Uploaded from webdav', $date_var, $date_var);
    $result = $adb->pquery($query, $qparams);

    $sql = "insert into vtiger_attachments (attachmentsid,name,description,type,path) values(?,?,?,?,?)";
    $params = array($current_id, $filename, 'Uploaded '.$filename.' from webdav', $filetype, $upload_filepath);
    $result = $adb->pquery($sql, $params);

    if(!empty($result)){
        // Create document record
        $document = new Documents();
        $document->column_fields['notes_title']      = $filename;
        $document->column_fields['filename']         = $filename;
        $document->column_fields['filesize']		 = empty($filesize) ? 1 : $filesize;
        $document->column_fields['filetype']		 = $filetype;
        $document->column_fields['filestatus']       = 1;
        $document->column_fields['filelocationtype'] = 'I';
        $document->column_fields['folderid']         = $folderid; // Default Folder
        $document->column_fields['assigned_user_id'] = $current_user->id;
        $document->save('Documents');

        $sql1 = "insert into vtiger_senotesrel values(?,?)";
        $params1 = array($crmid, $document->id);
        $result = $adb->pquery($sql1, $params1);

        $sql1 = "insert into vtiger_seattachmentsrel values(?,?)";
        $params1 = array($document->id, $current_id);
        $result = $adb->pquery($sql1, $params1);

        if(!empty($recordid)) {
            $sql = "INSERT INTO vtiger_senotesrel VALUES(?,?)";
            $params = array($recordid, $document->id);
            $result = $adb->pquery($sql, $params);
        }

        //we have to add attachmentsid_ as prefix for the filename
        $move_filename = $upload_filepath.'/'.$current_id.'_'.$filename;

        rename($tmpFile, $move_filename);

    }

    return true;

}

$server->register('createDocument',
			array('crmid' => 'xsd:string', 'folderid' => 'xsd:int', 'file' => 'tns:document'),
            array('return' => 'xsd:boolean'),
			'urn:server',
			'urn:server#createDocument');