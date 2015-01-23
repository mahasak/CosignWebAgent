<?php
// Copyright 2013 (c) by Larry Kluger
// License: The MIT License. See http://opensource.org/licenses/MIT

// sign_start.php
//
// 1. Create the xml parameter file for the CoSign Signature Web Agent,
// 2. Send it to the web agent out our back door using https,
// 3. Get back the session id from the web agent,
// 4. Send a re-direct to the human's browser

// Error if called separately
if (!defined('INDEX')) {
   die('You cannot call this script directly!');
}

define ('SAMPLE_FILE_LOCATION', 'assets/'); 
require_once ('xml.php');
require_once ('unirest-php-master/lib/Unirest.php');

// By default, we're signing the sample.pdf file. We'll store its name in the cookie.
// The cookie will be sent as part of the redirect.
$cookie_info['filename'] = SAMPLE_FILE;
send_cookie();
  
//  die(SAMPLE_FILE_LOCATION . SAMPLE_FILE);
$xml =  make_file_upload_request(file_get_contents(SAMPLE_FILE_LOCATION . SAMPLE_FILE), 'pdf',
                                                   SAMPLE_FILE, SAMPLE_FILE);
												   // file_get_contents is a php function

try {
  $response = Unirest::post(UPLOAD_DOC, array("Content-Type" => "application/x-www-form-urlencoded"),
    "inputXML=" . urlencode($xml));
  $redirect = handle_file_upload_response($response);
  send_redirect($redirect);
} catch (Exception $e) {
    echo '<h2>Problem: ',  $e->getMessage(), '</h2>';
	var_dump($e->getTrace());
}


