<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title>Signup!</title>
	<link rel="stylesheet" href="/css/style.css" type="text/css" media="screen" title="no title" charset="utf-8" />
</head>

<body>
	<div id="content_main">
		<?php if  (!$joinSuccess) { ?>
		<div id="join">
			<h2>Signup</h2>

			<form action="/join" method="post">
				<div class="row" id="divider"></div>
				<div class="row">
					<span class="label" id="required">Screen Name:</span>
					<span class="formw">
						<input type="text" size="25" maxlength="35" name="join-screenname" value="<?php print $screenname;?>" />
						<span class="empty"><?php print $errorMessages['screenname']?></span>
					</span>
				</div>
				<div class="row">
					<span class="label" id="required">Email:</span>
					<span class="formw">
						<input type="email" size="25" maxlength="35" name="join-email" value="<?php print $email?>"/>
						<span class="empty"><?php print $errorMessages['email']?></span>
					</span>
				</div>
				<div class="row">
					<span class="label" id="required">Password:</span>
					<span class="formw">
						<input type="password" size="25" maxlength="35" name="join-password" />
						<span class="empty"><?php print $errorMessages['password1']?></span>
					</span>
				</div>
				<div class="row">
					<span class="label" id="required">Verify Password:</span>
					<span class="formw">
						<input type="password" size="25" maxlength="35" name="join-password-2" />
						<span class="empty"><?php print $errorMessages['password2']?></span>
					</span>
				</div>
				<div class="row">
					<span class="label">&nbsp;</span>
					<input type="hidden" name="auth" value="<?php print $auth;?>" />
					<span class="formw"><input type="submit" value="Signup!" /></span>
				</div>
			</form>
		</div>
		<?php } elseif ($joinSuccess) { ?>
			<h1>Welcome!</h1>
			<p>You should receive an activation email shortly...</p>
		<?php } ?>
	</div>
</body>
</html>