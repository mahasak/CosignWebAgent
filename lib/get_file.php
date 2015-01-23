<?php
// get_file.php
//
// Copyright 2013 (c) by Larry Kluger
// License: The MIT License. See http://opensource.org/licenses/MIT
//
// Retrieve the signed file from the CSWA server.
// Store it in signed_files directory
// Garbage collect the signed_files directory
//
// Error if called separately
if (!defined('INDEX')) {
   die('You cannot call this script directly!');
}

//============================================================+
// Ajax operation to get the file from the Web Agent
//
// Arguments: 
//   No direct arguments. The Web Agent sessionID and the 
//   original file name are taken from the session info
// Success Results:
//   HTTP: 200
//   json associative array with the following keys and data:
//      version -- 1
//      error -- null
//      sessionID -- sessionID for the file
//      docId
//      fieldName -- field that was signed
//      x  -- where the document was signed
//      y
//      width
//      height
//      pageNumber -- page where the doc was signed
//      dateFormat
//      timeformat
//      graphicalImage
//      signer
//      date
//      showTitle
//      showReason
//      title
//      reason
//		filename -- the recommended name for the signed file
//      file_url -- url to download the signed file. It uses a
//                  guid so it can't be guessed
//                  The file will be garbage collected, it won't
//                  be stored forever
// Failure Results
//   HTTP: 403 Forbidden
//   The session is not correct
//
//   HTTP: 500 Internal Server Error
//   Some other problem
//   json associative array with the following keys and data:
//      version -- 1
//      errorMsg -- the error message
//		errorCode

// MAINLINE
require_once ('xml.php');
require_once ('unirest-php-master/lib/Unirest.php');

get_file();

function get_file() {
	global $cookie_info;
	send_cookie();
	header('Content-type: application/json');
	
	if ($cookie_info['web_agent_session'] === false) {
		// 403 error
		header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden -- Bad session', true, 403);
		return;
	}

	$info = fetch_signed_file($cookie_info['web_agent_session']);
	$good_signing = $info['errorMsg'] === null;
	if (!$good_signing) {
		// 500 error
		$result = array(
			'version' => AJAX_RESP_VER,
			'errorMsg' => $info['errorMsg'],
			'errorCode' => $info['errorCode']);
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		echo json_encode($result);
		return;
	}
	
	// Good signing!
	$info['version'] = AJAX_RESP_VER;
	echo json_encode($info);
}

//============================================================+
// fetch_signed_file -- fetch the signed file and details from the CSWA server
// ARGS
//   $sessionID
// RETURNS
//   $info associative array with keys:
//      errorCode -- either null or an error code set by the Web Agent server
//      errorMsg -- either null or an error msg set by the Web Agent server
//      sessionID -- sessionID for the file
//      docId
//      fieldName -- field that was signed
//      x  -- where the document was signed
//      y
//      width
//      height
//      pageNumber -- page where the doc was signed
//      dateFormat
//      timeformat
//      graphicalImage
//      signer
//      date
//      showTitle
//      showReason
//      title
//      reason
//		filename -- the recommended name for the signed file
//      file_url -- url to download the signed file. It uses a
//                  guid so it can't be guessed
//                  The file will be garbage collected, it won't
//                  be stored forever
//============================================================+
function fetch_signed_file($sessionID)
{
    // This function is the entry point for fetching the signing
	// information and signed file.
	//
	// First, garbage collect the old signed files in the directory
	gc_files_dir();
	
	// Next, try to fetch the signed file and signing information
    try {
      $response = Unirest::get(DOWNLOAD_SIGNED_FILE . '?sessionId=' . $sessionID);
    } catch (Exception $e) {
        return array('errorMsg' => $e->getMessage(),
					 'errorCode' => -500 );
    }
    	
	// everything ok at the http level?
    $http_code = $response->code;
    if ($http_code <> '200') {
        return array('errorMsg' => 'HTTP Error when contacting server. Code: ' . $http_code,
					 'errorCode' =>  $http_code );
    }
    
	// return the info object to the caller after processing the xml body
    return signed_file_xml($response->raw_body);
}

//
// signed_file_xml -- decodes the xml and stores the file
// arg -- the raw incoming xml
// returns -- an info associative array as shown above
// side effect -- stores the signed file on the disk as 
// 
function signed_file_xml($raw)
{
    // incoming xml has a bug, the first element is sent as
    //    <?xml version="1.0" encoding="utf-16"
    // but it should be 
    //    <?xml version="1.0" encoding="utf-8"
    // So we hack at the incoming payload to fix it
	//
	// parse the xml
    //$xml = new SimpleXMLElement(str_replace ("utf-16", "utf-8", $raw));
    $xml = new SimpleXMLElement(str_replace ("utf-16", "utf-8", $raw));
    
	// check the returnCode
    $return_code = (string)$xml->Error->returnCode;
    if ($return_code != SUCCESS) {
        return array(
			'errorMsg' => (string)$xml->Error->errorMessage . ' Code: ' . $return_code,
			'errorCode' => $return_code);
    }
    
    // All's good! Populate the info associative array
    $info = array();
    $info['errorMsg'] = null;
    $info['errorCode'] = null;
    $info['sessionID'] = (string)$xml->Session->sessionId;
    $info['docId'] = (string)$xml->Session->docId;
    $info['fieldName'] = (string)$xml->SigDetails->fieldName;
    $info['x'] = (integer)$xml->SigDetails->x;
    $info['y'] = (integer)$xml->SigDetails->y;
    $info['width'] = (integer)$xml->SigDetails->width;
    $info['height'] = (integer)$xml->SigDetails->height;
    $info['pageNumber'] = (integer)$xml->SigDetails->pageNumber;
    $info['dateFormat'] = (string)$xml->SigDetails->dateFormat;
    $info['timeformat'] = (string)$xml->SigDetails->timeformat;
    $info['graphicalImage'] = strtolower((string)$xml->SigDetails->graphicalImage) == 'true';
    $info['signer'] = strtolower((string)$xml->SigDetails->signer) == 'true';
    $info['date'] = strtolower((string)$xml->SigDetails->date) == 'true';
    $info['showTitle'] = strtolower((string)$xml->SigDetails->showTitle) == 'true';
    $info['showReason'] = strtolower((string)$xml->SigDetails->showReason) == 'true';
    $info['title'] = (string)$xml->SigDetails->title;
    $info['reason'] = (string)$xml->SigDetails->reason;
	
	// write the file
	global $cookie_info;
	$signed_file_name = diskfile();
    $info['filename'] = SIGNED_FILE_PREFIX . $cookie_info['filename'];
	$info['file_url'] = url_for(FILES_DIR . '/' . $signed_file_name);
    $fh = fopen(files_dir() . '/' . $signed_file_name, "wb+");
	fwrite ($fh, base64_decode((string)$xml->Document->content));
	fclose($fh);

    return $info;   
}

//
// print_info function -- a pretty printer for the $info associative array
function print_info($info) 
{
  $graphicalImage = $info['graphicalImage'] ? 'Y' : 'N';
  $signer = $info['signer'] ? 'Y' : 'N';
  $date = $info['date'] ? 'Y' : 'N';
  $showTitle = $info['showTitle'] ? 'Y' : 'N';
  $showReason = $info['showReason'] ? 'Y' : 'N';

  echo <<<__HTML__
<pre>
               x {$info['x']}
               y {$info['y']}
           width {$info['width']}
          height {$info['height']}
      pageNumber {$info['pageNumber']}
  graphicalImage $graphicalImage
          signer $signer
            date $date
       showTitle $showTitle
      showReason $showReason
           title {$info['title']}
          reason {$info['reason']}
</pre>           
__HTML__;
} 

