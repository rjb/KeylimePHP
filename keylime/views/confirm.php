<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title>Confirmation success!</title>
	<link rel="stylesheet" href="/css/style.css" type="text/css" media="screen" title="no title" charset="utf-8" />
</head>

<body>
	<div id="content_main">
		<?php if ($confirmActivation) { ?>
			<h1>Congratulations <?php print $confirmPersonScreenName; ?>! Your Account has Been Activated</h1>
		<?php } ?>
	</div>
</body>
</html>