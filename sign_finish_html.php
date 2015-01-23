<?php
define ('INDEX', true);
// Copyright 2013 (c) by Larry Kluger
// License: The MIT License. See http://opensource.org/licenses/MIT


//fix title depending on signing status
//in case of error, change sign button to be a link to the main index file



require_once ('lib/config.php');
require_once ('lib/sign_finish.php');
require_once ('lib/unirest-php/lib/Unirest.php');

// .../sign_finish1.php?sessionId=1491552566&docId=123&returnCode=0
parse_str($_SERVER['QUERY_STRING'], $params);
$returnCode = $params['returnCode'];
$good_signing = $returnCode == '0';
if (!$good_signing) {$errorMessage = $params['errorMessage'] . ' Code: ' . $returnCode;}
$op = $params['op'];
$sessionID = $params['sessionId'];
$docId = $params['docId'];

if ($good_signing) {
  // retrieve signing info and file
  $info = fetch_signed_file($sessionID);
  $good_signing = $info['error'] == null;
  if (!$good_signing) {$errorMessage = $info['error'];}
}

?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Document Signed!</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="css/bootstrap.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-top: 20px;
        padding-bottom: 40px;
      }

      /* Custom container */
      .container-narrow {
        margin: 0 auto;
        max-width: 700px;
      }
      .container-narrow > hr {
        margin: 30px 0;
      }

      /* Main marketing message and sign up button */
      .jumbotron {
        margin: 60px 0;
        text-align: center;
      }
      .jumbotron h1 {
        font-size: 72px;
        line-height: 1;
      }
      .jumbotron .btn {
        font-size: 21px;
        padding: 14px 24px;
      }
	  #sigInfo pre {
        text-align: left;
		padding-left: 100px;
      }
	  #download_btn {
	    margin-right: 50px;
	  }
	  #signedDiv h1 {
	    margin-bottom: 50px;
      }
	  
    </style>
    <link href="css/bootstrap-responsive.css" rel="stylesheet">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="ico/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="ico/favicon.png">
  </head>

  <body>

    <div class="container-narrow">
      <div class="masthead">
        <ul class="nav nav-pills pull-right">
          <li class="active"><a href="#">Home</a></li>
          <li><a href="#">About</a></li>
          <li><a href="#">Contact</a></li>
        </ul>
        <h3 class="muted">CoSign Signature Web Agent Example</h3>
      </div>

      <hr>

      <div class="jumbotron">
	    <div id="signedDiv" class="hide">
			<h1>Signed!</h1>
        	<a id='download_btn' class='btn btn-large btn-success' href='#'>Download the file!</a>
			<a href="#sigInfo" role="button" class="btn btn-large btn-info" data-toggle="modal">Signature information</a>
		</div>
		<div id="noSignDiv" class="hide">
			<h1>Problem!</h1>
			<p class='lead'><?php echo $errorMessage; ?></p> 
		</div>
		<div id="cancelledDiv" class="hide">
			<h1>Cancelled!</h1>
			<a id="sign_btn" class="btn btn-large btn-success" href="#">Sign!</a>
		</div>
      </div>
    </div> <!-- /container -->

	<!-- Modal -->
	<div id="start_signing" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	  <div class="modal-header">
		<h3 id="myModalLabel">Processing…</h3>
	  </div>
	  <div class="modal-body">
		<p>We're processing your request.</p>
		<p>Remember to authenticate as John Miller, password 12345678</p>
	  </div>
	</div>
		
<div id="sigInfo" class="modal hide fade">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Signature Information</h3>
  </div>
  <div class="modal-body">
    <p><?php print_info($info); ?></p>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">Close</button>
  </div>
</div>
	
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
	<script>
	$(document).ready(function () {
      // Use .ready may not be necessary, see http://goo.gl/aAhs  but we'll be
	  // conservative and include it.
      function add_events(){
	    var good_signing = <?php echo ($good_signing ? 'true' : 'false'); ?>,
		    file_url = "<?php echo $info['pdf_url']; ?>";
        if (good_signing)
		{
			$("#signedDiv").removeClass('hide');
			$('#download_btn').on('click', function (e) {
			  window.location=file_url;
			});
		} else if ("<?php echo $returnCode; ?>" == '-2') {
			$("#cancelledDiv").removeClass('hide');
			$('#sign_btn').on('click', function (e) {
		       window.location="sign_start.php";
		       $('#start_signing').modal({keyboard: false, backdrop: 'static'});
		    });
		} else {
			$("#noSignDiv").removeClass('hide');
		}
	  }
	  add_events();
    });
    </script>	
  </body>
</html>
