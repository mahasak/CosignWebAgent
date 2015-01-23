<?php
// config.php
//
//
// Copyright 2013 (c) by Larry Kluger
// License: The MIT License. See http://opensource.org/licenses/MIT

// Error if called separately
if (!defined('INDEX')) {
   die('You cannot call this script directly!'); 
}

// Always load the following:
require_once ('lib/utils.php');

// cookie definition
// Serialize the following
// cookie_info [cookie_ver => COOKIE_VER,
//              filename => string,
//              web_agent_session => string,
//              guid => string]



// Constants
define ('CSWA_URL', 'https://webagentdev.arx.com/');
define ('SIGN_FINISH', 'index.php?op=file_signed');
define ('FILES_DIR', 'files'); // last part of dir for caching signed files
define ('GC_TIME', 60 * 60); // in seconds -- how long should signed files sit in the cache dir before being reaped?
define ('SUCCESS', 0);
define ('AJAX_RESP_VER', 1); // version of the response Ajax protocol
define ('COOKIE_NAME', 'qs_session');
define ('COOKIE_VER', 1);
define ('SAMPLE_FILE', 'sample.pdf');
define ('SIGNED_FILE_PREFIX', 'signed '); // add the prefix to the signed file so
                                          // the download won't overwrite the 
										  // original

// please change to your own secret!
define ('COOKIE_SECRET', '4ZJyrL384z39n4JG0hklu190y3pky5h1'); // see http://randomkeygen.com/


// Constants for CoSign Signature Web Agent
// layoutMask values
define ('SETTINGS_CHNG_PW', 8);         // Settings and change password
define ('SETTINGS_GRAPHICAL_SIGS', 16); // Graphical Signatures
define ('SIG_FIELD_NAME', 'signature 1');
define ('UPLOAD_DOC', CSWA_URL . 'Sign/UploadFileToSign');
define ('DOWNLOAD_SIGNED_FILE', CSWA_URL . 'Sign/DownloadSignedFileG');
define ('SIGNING_CEREMONY', CSWA_URL . 'Sign/SignCeremony');
