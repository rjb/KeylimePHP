<?php
// put DB info here
define('DB_DATABASE', '');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_SERVER', '');

class Config {			
	// Error messages to be displayed next to bad form fields
	static $formErrorMessages = array('field'      => array('empty' => 'Oops! Field can not be empty',
															'non-char-int' => '"A-Z, 0-9, -, _" only please'),
	
									  'screenname' => array('taken' => 'Sorry... This name is taken',
															'too-short' => 'Screen Name is too short (3 character min.)',
															'too-long' => 'Screen Name is too long (12 character max.)'),
	
									  'password'   => array('too-short' => 'Min. Password length is 6',
															'dont-match' => 'Passwords do not match'),
									  'email'	   => array('incorrect-format' => 'Incorrect email format',
															'taken'=>'This email is used by another account',
															'non-existent'=>'Email address does not exist in our system')
							 		  );
}
?>