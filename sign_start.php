<?php
define ('INDEX', true);
// Copyright 2013 (c) by Larry Kluger
// License: The MIT License. See http://opensource.org/licenses/MIT

// sign_start.php
//
// 1. Create the xml parameter file for the CoSign Signature Web Agent,
// 2. Send it to the web agent out our back door using https,
// 3. Get back the session id from the web agent,
// 4. Send a re-direct to the human's browser

require_once ('lib/config.php');
require_once ('lib/xml.php');
require_once ('lib/pdf.php');
require_once ('lib/unirest-php/lib/Unirest.php');

parse_str($_SERVER['QUERY_STRING'], $params); 
$file_info = get_file_info($params['fn']); // lib/utils
if (!file_exists ($file_info['full_fn_w_path']))
  {
  exit("<h2>Problem: the file was not uploaded. Were you uploading a pdf, Word or Excel file? Only those file types are supported.</h2>");
  };
  
$xml =  make_file_upload_request(file_get_contents($file_info['full_fn_w_path']), 'pdf',
                                                   $file_info['full_fn'], $file_info['full_fn']);
												   // file_get_contents is a php function

try {
  $response = Unirest::post(UPLOAD_DOC, array("Content-Type" => "application/x-www-form-urlencoded"),
    "inputXML=" . urlencode($xml));
  $redirect = handle_file_upload_response($response);
  send_redirect($redirect);
} catch (Exception $e) {
    echo '<h2>Problem: ',  $e->getMessage(), '</h2>';
}


