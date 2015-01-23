<?php
// utils.php
//
// Copyright 2013 (c) by Larry Kluger
// License: The MIT License. See http://opensource.org/licenses/MIT
//
// Utility Library
//
// Error if called separately
if (!defined('INDEX')) {
   die('You cannot call this script directly!');
}

//============================================================+
// find_cookie -- read the session info from the cookie
// We check that the cookie signature matches the content
// Side effect: creates/sets $cookie_info
//============================================================+
function find_cookie()
{
	global $cookie_info;
	if (isset($_COOKIE[COOKIE_NAME])) {
		// cookie available
		$cookie_structure = unserialize (base64_decode($_COOKIE[COOKIE_NAME]));
		if ($cookie_structure['signature'] !== 
			hash ('sha256', COOKIE_SECRET . 
				$cookie_structure['random_info'] . $cookie_structure['serialized_data'])) {
			exit('======= Cookie tamper detected! =======');
		}
		// cookie ok
		$cookie_info = unserialize (base64_decode($cookie_structure['serialized_data']));
		if (!is_array($cookie_info) || !isset($cookie_info['cookie_ver']) || $cookie_info['cookie_ver'] != COOKIE_VER) {
			// bad moon rising: not our cookie
			exit ('=======  Bad cookie! =======');
		}
	} else {
		// create cookie
		$cookie_info = array('cookie_ver' => COOKIE_VER,
			'filename' => false,
			'web_agent_session' => false,
			'guid' => create_guid()
			);
	}
}
//============================================================+
// send_cookie -- must be called before any html output
// We sign the cookie to detect any unauthorized changes
//============================================================+
function send_cookie()
{
	// See Jamie Rumbelow's comment on http://goo.gl/Lp48Sh
	global $cookie_info;
	$serialized_data = base64_encode(serialize($cookie_info));
	$random_info = sha1(rand(0,500) . microtime() . COOKIE_SECRET);
	$signature = hash ('sha256', COOKIE_SECRET . $random_info . $serialized_data);
	$cookie = base64_encode(serialize(array(
		'signature' => $signature,
		'random_info' => $random_info,
		'serialized_data' => $serialized_data)));
	
	setcookie ( COOKIE_NAME, $cookie ); // We can't say that the cookie
	                                    // is only available via http since
										// that would prevent the Ajax call
										// from sending the cookie.
}
//============================================================+
// send_redirect -- send redirect header
// ARGS
//   $url
//============================================================+
function send_redirect($url)
{
	header('Location: ' . $url); /* Redirect browser */
	exit;
}

//============================================================+
// diskfile -- returns the name of the session's file
//             as it is stored on the disk
//============================================================+
function diskfile()
{
  global $cookie_info;
  return "file_" . $cookie_info['guid'] . '.pdf'; 
}
//============================================================+
// url_for -- returns the url for a file
// ARGS
//   $for -- supply the url for this file or path/file
//============================================================+
function url_for($for)
{
  return url_dir() . $for; 
}

// url_dir returns the url path for the current doc's directory.
// It includes the trailing /
function url_dir()
{
  fix_request_uri();
  $server_port = $_SERVER['SERVER_PORT'];
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $server_port == 443);
  $scheme = $https ? "https://" : "http://";
  $host = $_SERVER['SERVER_NAME'];
  
  $port = "";
  if (($https  && $server_port <> 443) || 
      (!$https && $server_port <> 80)) {
    $port = ":" . $server_port;
  }
    
  $parts = parse_url($_SERVER['REQUEST_URI']); // see http://php.net/manual/en/function.parse-url.php  
  $path = $parts['path']; //  /foo.php  or /a/b/foo.php

  $path_parts = preg_split("[\/]", $path);
  array_pop ($path_parts);
  
  return $scheme . $host . $port . implode('/', $path_parts) . (count($path_parts) > 0 ? '/' : '');  
}

// request_uri is not set on some IIS servers
function fix_request_uri() 
{ 
  if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'],0);

    if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
      $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
    }
  }
}  

//============================================================+
// gc_files_dir -- garbage collect the old signed files
// Side effect: create the cache dir for the signed files if needed
//============================================================+
function gc_files_dir()
{
   // functions are in the utils.php file
   make_files_dir(); 
   clean_files_dir();
}

// files_dir -- return full path of the dir for caching signed files
// Does NOT include final /
function files_dir()
{
  return getcwd() . '/' . FILES_DIR;
}

// make_files_dir -- creates the directory for the signed files if it doesn't yet exist
// Requires the right permissions for the script's user id.
function make_files_dir()
{
  $dir_name = files_dir();
  if(! is_dir($dir_name)) {
     $err = mkdir ($dir_name, 0755);
     if ($err) {die("Couldn't create dir " . $dir_name);}
  }	 
}

// clean_files_dir -- remove all files older than GC_TIME
function clean_files_dir()
{
  $dir_name = files_dir();
  // Create recursive dir iterator which skips dot folders
  $it = new RecursiveDirectoryIterator($dir_name, FilesystemIterator::SKIP_DOTS);

  // Maximum depth is 1 level deeper than the base folder
  //$it->setMaxDepth(1);

  $oldest = time() - GC_TIME;
  // Loop and reap
  while($it->valid()) {
    if ($it->isFile() && filemtime($it->key()) < $oldest) {unlink($it->key());}
	$it->next();
  }
}

//============================================================+
// get_file_info -- returns info about a file name that includes the uid info
//   raw filename format: u_7a56673b-8a9f-4191-b3f3-d19106c71614_LICENSE.pdf
// ARGS
//   $fn -- in the raw filename format. Note the filename can included spaces
//          after the uid part
// RETURNS 
// file_info, a hash with elements:
//    full_fn_w_path -- full path of the file
//    real_fn -- the important part of the file name, after the uid
//    full_fn -- the input param of u_<uid>_<real_fn>
//============================================================+
function get_file_info($fn)
{
  $info = array('full_fn'       => $fn,
               'real_fn'        => substr($fn, FN_PREFIX_WIDTH),
               'full_fn_w_path' => files_dir() . '/' . $fn);
  return $info;
}

// See http://www.php.net/manual/en/function.uniqid.php
function create_guid($namespace = '') {     
    static $guid = '';
    $uid = uniqid("", true);
    $data = $namespace;
    $data .= $_SERVER['REQUEST_TIME'];
    $data .= $_SERVER['HTTP_USER_AGENT'];
    $data .= $_SERVER['LOCAL_ADDR'];
    $data .= $_SERVER['LOCAL_PORT'];
    $data .= $_SERVER['REMOTE_ADDR'];
    $data .= $_SERVER['REMOTE_PORT'];
    $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
    $guid = '{' .   
            substr($hash,  0,  8) . 
            '-' .
            substr($hash,  8,  4) .
            '-' .
            substr($hash, 12,  4) .
            '-' .
            substr($hash, 16,  4) .
            '-' .
            substr($hash, 20, 12) .
            '}';
    return $guid;
  }

  
//============================================================+
// unicode -- returns the character from a unicode char
// credit: http://stackoverflow.com/a/6058533/64904
// ARGS
//   $c -- the Unicode character in form \uxxxx eg \u2026 is the ellipses char
// RETURNS 
//   the right character
// eg unicode("\u2026")
//============================================================+
function unicode($c)
{
	// eg Ellipses  \u2026
	return (json_decode('"'.$c.'"'));
}
  
  
  