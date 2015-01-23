<?php
// page.php

function html_head($title="", $css=""){

?><!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <!-- Force latest IE rendering engine or ChromeFrame if installed -->
    <!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
    <meta charset="utf-8">
    <title><?php echo $title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
	<link rel="stylesheet" href="css/spinner.css">
	<?php if (!empty($css)) {
		echo '<style type="text/css">' . $css . '</style>';
	}
	?>
  </head>
  <body>
    <!-- Docs master nav -->
    <header class="navbar navbar-inverse navbar-fixed-top bs-docs-nav" role="banner">
  <div class="container">
    <div class="navbar-header">
      <button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".bs-navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a href="../" class="navbar-brand">CoSign Signature Web Agent</a>
    </div>
    <nav class="collapse navbar-collapse bs-navbar-collapse" role="navigation">
      <ul class="nav navbar-nav">
        <li>
          <a href="index.php">Quick Start Example</a>
        </li>
      </ul>
    </nav>
  </div>
</header>
<?php
}

function footer(){

?>
	<hr>
	<footer>
	</footer>
<?php
}
function html_foot(){

?>
	</body>
</html>
<?php
}
function add_spinner(){
	add_circle_spinner();
}
function add_circle_spinner(){
?>
<div class="spinner"><!-- from http://tobiasahlin.com/spinkit/ -->
	<div class="spinner-container container1">
		<div class="circle1"></div>
		<div class="circle2"></div>
		<div class="circle3"></div>
		<div class="circle4"></div>
	</div>
	<div class="spinner-container container2">
		<div class="circle1"></div>
		<div class="circle2"></div>
		<div class="circle3"></div>
		<div class="circle4"></div>
	</div>
	<div class="spinner-container container3">
		<div class="circle1"></div>
		<div class="circle2"></div>
		<div class="circle3"></div>
		<div class="circle4"></div>
	</div>
</div>
<?php
}

