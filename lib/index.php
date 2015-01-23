<?php
// Error if called separately
if (!defined('INDEX')) {
   die('You cannot call this script directly!');
}

// generate output
send_cookie();
html_head("Quick Start PHP", "#start_signing div.modal-header button {display:none;}");
?>
<!-- Main jumbotron -->
<div class="jumbotron">
	<div class="container">
		<h1>Sign a PDF</h1>
		<p>This Quick Start example signs a <a href="assets/tosign.pdf">sample PDF document</a>.</p>
		<p><a id="sign_btn" class="btn btn-primary btn-lg" role="button">Sign the PDF &raquo;</a></p>
		<p>Use your email and password to sign.</p>
	</div>
</div>
	
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
      function add_events(){
        $('#sign_btn').on('click', function (e) {
		  window.location="index.php?op=sign_start";
		  // See http://goo.gl/ltHjD
		  $('#start_signing').modal({keyboard: false, backdrop: 'static'});
		});
      }
	  add_events();
    });
    </script>

<?php
html_foot();
