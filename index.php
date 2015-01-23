<?php
define ('INDEX', true);
// Copyright 2013 (c) by Larry Kluger
// License: The MIT License. See http://opensource.org/licenses/MIT
//
// Expects op parameter. If missing then starting page
require_once ('lib/config.php');

find_cookie();

// Process request
$op = isset($_GET["op"]) ? $_GET["op"] : 'index';
$ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
if (!$ajax) {
	require ('templates/page.php');
}
require ('lib/' . $op . '.php'); // lib/index.php, etc

