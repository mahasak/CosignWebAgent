<?php
// sign_finish.php
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
// fetch_signed_file -- fetch the signed file and details from the CSWA server
// ARGS
//   $sessionID
// RETURNS
//   $info associative array with keys:
//      error -- either null or an error while fetching from the server
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
//      pdf_url -- url to download the file
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
        return array('error' => $e->getMessage());
    }
    
	// everything ok at the http level?
    $http_code = $response->code;
    if ($http_code <> '200') {
        return array('error' => 'HTTP Error when contacting server. Code: ' . $http_code);
    }
    
	// return the info object to the caller after processing the xml body
    return signed_file_xml($response->raw_body);
}

//
// signed_file_xml -- decodes the xml and stores the file
// arg -- the raw incoming xml
// returns -- an info associative array as shown above
// side effect -- stores the signed file on the disk
function signed_file_xml($raw)
{
    // incoming xml has a bug, the first element is sent as
    //    <?xml version="1.0" encoding="utf-16"
    // but it should be 
    //    <?xml version="1.0" encoding="utf-8"
    // So we hack at the incoming payload to fix it
	//
	// parse the xml
    $xml = new SimpleXMLElement(str_replace ("utf-16", "utf-8", $raw));
    
	// check the returnCode
    $return_code = (string)$xml->Error->returnCode;
    if ($return_code != SUCCESS) {
        return array('error' => (string)$xml->Error->errorMessage . ' Code: ' . $return_code);
    }
    
    // All's good! Populate the info associative array
    $info = array();
    $info['error'] = null;
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
	$unsigned_file_name = $info['docId'];
	$signed_file_name = 's' .  substr ($unsigned_file_name, 1);
    $info['short_fn'] = substr($signed_file_name, FN_PREFIX_WIDTH);
	$info['pdf_url'] = url_for(FILES_DIR . '/' . $signed_file_name);
    $fh = fopen(files_dir() . '/' . $signed_file_name, "wb");
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

