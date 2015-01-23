<?php
// Error if called separately
if (!defined('INDEX')) {
   die('You cannot call this script directly!');
}

// This page is the target of a redirect from the CoSign Signature Web Agent
// Our query string will look something like: 
// .../index.php?op=file_signed&sessionId=1491552566&docId=123&returnCode=0
parse_str($_SERVER['QUERY_STRING'], $params);
$returnCode = $params['returnCode']; // returncode from the WebAgent
$good_signing = $returnCode == '0';

// From the manual:
// returnCode – sign operation outcome: 
//  0 – success
// -1 – general error
// -2 – cancelled
// -3 – rejected
// other number – signing error code (refer to CoSign Web App API Error Codes).

if (!$good_signing) {
	if ($returnCode == '-2') {
		$softError = true;
		$errorClass = 'msg';
		$errorH1 = "Cancelled" . unicode("\u2026");
		$errorMessage = "You cancelled the signing request";
	} elseif ($returnCode == '-3') {
		$softError = true;
		$errorClass = 'msg';
		$errorH1 = "Rejected" . unicode("\u2026");
		$errorMessage = "You rejected the signing request";
	} else {
		$softError = false;
		$errorClass = 'error';
		$errorMessage = $params['errorMessage'] . ' Code: ' . $returnCode;
		$errorH1 = "Signing Problem";
	}
}

$sessionID = $params['sessionId'];
$docId = $params['docId'];

// We could retrieve the signed file from the Web Agent server right now. But then this page would not 
// display results to the user until after the Web Agent responded to our request.
// Instead, we immediately return a page to the user. The page initiates, via Ajax, the file retrieval.
// This pattern provides the fastest UI to the user.

// We save the sessionId in our session (here we're using a cookie).
// In production, php sessions should be used to safeguard the information.
// The sessionId should be saved in a session to guard against someone 
// else trying to read our user's signed document
$cookie_info['web_agent_session'] = $sessionID;

// generate output
send_cookie();
if ($good_signing) {
	html_head("PDF Signed", "");
?>
<div class="jumbotron">
	<div class="container">
		<h1>PDF Signed!</h1>
		<div id="feedback">
			<h2>Processing the file</h2>
			<div id="loadspinner">
			<?php add_spinner(); ?>
			</div>
			<div id="file_show">
			</div>
		</div>
	</div>
</div>
<?php 
} else {   // ---------- A problem: give info and let user sign again -------------
	html_head(errorH1, "");
?>
<div class="jumbotron">
	<div class="container">			
		<div class="<?php echo($errorClass); ?>">
			<h1><?php echo($errorH1); ?></h1>
			<p>Problem: <?php echo($errorMessage); ?></p>
		</div>
		<p>This Quick Start example signs a <a href="assets/sample.pdf">sample PDF document</a>.</p>
		<p><a id="sign_btn" class="btn btn-primary btn-lg" role="button">Sign the PDF &raquo;</a></p>
		<p>Use your email and password to sign.</p>
	</div>
</div>
<?php 
} 
?>
	
<!-- Modal - See http://getbootstrap.com/javascript/#modals  -->
<div id="start_signing" class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Processing…</h4>
      </div>
      <div class="modal-body">
		<p>We're processing your signing request.</p>
		<p>Remember to use your DevPortal email and password to sign.</p>
		<?php add_spinner(); ?>
		</div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
	
<?php
footer();
?>
    <script src="http://code.jquery.com/jquery.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>
	<script>
	$(document).ready(function () {
      // Use .ready may not be necessary, see http://goo.gl/aAhs  but we'll be
	  // conservative and include it.
	  
	  <?php
		// add some JS from the php script
		echo "var good_signing = " . ($good_signing ? 'true' : 'false') . "; // Set by php app\n";
	  ?>
	  
      function add_events(){
        $('#sign_btn').on('click', function (e) {
		  window.location="index.php?op=sign_start";
		  // See http://goo.gl/ltHjD
		  $('#start_signing').modal({keyboard: false, backdrop: 'static'});
		});
      }
	  
	  function retrieve_file(){
	    if (!good_signing) {
			return;
		}
		// ajax to tell server to retrieve the file from the web agent
		var url = "index.php?op=get_file";
		jQuery.ajax( url, {
			dataType: 'json',
			error: function( jqXHR, textStatus, errorThrown ) {
			
			alert(textStatus);

			
			},
			success: function( data, textStatus, jqXHR ) {
				console.log(data);
				 $('#loadspinner').hide();
				  $('#file_show').append("<a href=\""+data.file_url+"\" target=\"_blank\">Show File</a>");
				alert('completed');
			},
			timeout: 20000, // 20 sec
			type: 'POST', // ensure no caching
		})
      }
	  
	  add_events();
	  retrieve_file();
    });
    </script>

<?php
html_foot();
