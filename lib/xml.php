<?php
// xml.php
//
// Library to deal with xml aspects of CoSign Signature Web Agent
//
// Copyright 2013 (c) by Larry Kluger
// License: The MIT License. See http://opensource.org/licenses/MIT
//
// Docs: http://board.phpbuilder.com/showthread.php?10356853-A-quick-PHP-XMLWriter-Class-Tutorial-(XML-amp-RSS)
//

// Error if called separately
if (!defined('INDEX')) {
   die('You cannot call this script directly!');
}

//============================================================+
// make_file_upload_request -- create minimal upload xml
// ARGS
//   $file - contents of the file
//   $file_type - 'pdf' etc
//   $filename
//   $file_id - client cookie
//============================================================+
function make_file_upload_request($file, $file_type, $filename, $file_id)
{
	@date_default_timezone_set("GMT"); 

	$x = new XMLWriter;
	$x->openMemory();
	$x->startDocument('1.0', 'UTF-8');
	$x->startElement('request');
		$x->startElement('Logic');
			$x->writeElement('allowAdHoc', 'true');
		$x->endElement();
		$x->startElement('Url');
			$x->writeElement('finishURL', url_for(SIGN_FINISH));
			$x->writeElement('redirectIFrame', 'false');
		$x->endElement();
		$x->startElement('Layout');
			$x->writeElement('layoutMask', SETTINGS_CHNG_PW + SETTINGS_GRAPHICAL_SIGS);
		$x->endElement();
		$x->startElement('SignReasons');
			$x->writeElement('signReason', 'Approved');
			$x->writeElement('signReason', 'Certified');
			$x->writeElement('signReason', 'Confirmed');
		$x->endElement();
		$x->startElement('Document');
			$x->writeElement('contentType', $file_type);
			$x->writeElement('filename', $filename); 
			$x->writeElement('fileID', $file_id);
			$x->writeElement('content', base64_encode ($file));
		$x->endElement();
	$x->endElement();
	$x->endDocument();
	return $x->outputMemory(TRUE);
}
  
//============================================================+
// handle_file_upload_response -- process response. Error page
//   if appropriate
// ARGS
//   response - response from sending file upload
// RETURNS
//   url - the redirect url
//============================================================+
function handle_file_upload_response($response)
{
	if ($response->code != 200) {
	  die ("HTTP Response code from CSWA server was " . $response->code);
	}

	// http://www.php.net/manual/en/simplexml.examples-basic.php
	$payload = $response->raw_body;
	//echo '<html><body><textarea rows="20" cols="120" style="border:none;">'. $payload . '</textarea>';
    
	// incoming xml has a bug, the first element is sent as
	//    <?xml version="1.0" encoding="utf-16"
	// but it should be 
	//    <?xml version="1.0" encoding="utf-8"
	// So we hack at the incoming payload to fix it
	//$payload = str_replace ("utf-16", "utf-8", $payload);
	
	$xml = new SimpleXMLElement($payload);
    $return_code = (string)$xml->Error->returnCode;
	$sessionID = (string)$xml->Session->sessionId;
	if($return_code != SUCCESS) {
		die ("CoSign Signature Web Agent error! Return code = " . 
		  $return_code . ' -- ' . (string)$xml->Error->errorMessage);
	}
	return SIGNING_CEREMONY . "?sessionId=" . $sessionID;
}