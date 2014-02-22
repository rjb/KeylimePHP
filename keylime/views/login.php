<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title>Login</title>
	<link rel="stylesheet" href="/css/style.css" type="text/css" media="screen" title="no title" charset="utf-8" />
</head>

<body>
	<div id="content_main">
		<div id="login_left">
			<?php if (!$loggedIn) { ?>
				<h2>Login in to CityEndz!</h2>
				
				<?php if ($loginFailure) { ?>
					<div id="login_failure"><?php print $loginFailure;?></div>
				<?php } ?>
				<form action="/login" method="post">
					<div class="row">
						<span class="label" id="required">Screen Name:</span>
						<span class="formw">
							<input type="text" size="25" maxlength="35" name="screenname" />
							<span class="empty"><?=$emptyFields[0]?></span>
						</span>
					</div>
					<div class="row">
						<span class="label" id="required">Password:</span>
						<span class="formw">
							<input type="password" size="25" maxlength="35" name="password" />
							<span class="empty"><?=$emptyFields[2]?></span><br>
						</span>
					</div>
					<div class="row">
						<span class="label">
							<input type="checkbox" name="persistent-cookie" value="yes">
						</span>
						<span class="formw">
							Remember me on this computer.
						</span>
					</div>
					<div class="row">
						<span class="label">&nbsp;</span>
						<input type="hidden" name="red" value="<?=$_GET['red'];?>" />
						<input type="hidden" name="auth" value="<?=$auth;?>" />
						<span class="formw"><input type="submit" value="Login to CityEndz!" /></span>
					</div>
					
					<div class="row" id="divider"></div>
					
					<div class="row">
						<span class="label" id="required">&nbsp;</span>
						<span class="formw">
							<a href="/retreivepassword">Forgot your username or password?</a>
						</span>
					</div>
				</form>
			<?php } else { ?>
				<h2>You are logged in as: <?php print $loggedInScreenname;?> </h2>
				<p>You may <a href="/logout">Logout</a> if you like.</p>
			<?php } ?>
		</div>
		<div id="login_right">
			<h2>Don't have an account?</h2>
			<h3><a href="/signup">Signup!</a></h3>
		</div>
	</div>
</body>
</html>