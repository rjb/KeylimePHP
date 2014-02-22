<?php
// This is where you're application code lives
// Need a new route? Simply create an array of ULS:
// $urls = arra("home" => "Index", "login" => "Login");
?>

<?php
require_once ("_keylime.php");

// URLS go here... Here are a few to get you started
$urls = array( "home" => "Index",
			   "about" => "About",
			   "login" => "Login",
			   "logout" => "Logout",
			   "signup" => "Signup" );

// The main index page
class Index {
	public function __index__() {
		// Lets grab the view
		require_once("views/index.php");
	}
}

class About {
	public function __index__() {
		// No view? Print right from the class
		print "<h1>About Us</h1>";
		print "<p>We're INCREDIBLE!</p>"
	}

	// You can even create functions to keep HTML clean
	public function hello() {
		print "Hello, There!"l
	}
}

// Inherit from Controller for session data and actions
class Login extends Controller {
	public function __index__() {
		$sess = parent::get_session();
		$loggedInScreenname = parent::get_screenname();
		
		$loggedIn = false;
		if ($sess->isLoggedIn() != false) {
			$loggedIn = true;
		}
		
		if ($_POST) {
			$sess->logout();
			if ($_POST['persistent-cookie']) {
				setcookie("PHPSESSID", $sess->getSessionIdentifier(), mktime()+(864000*300), "/"); 
			}
			
			// Let's login with screenname (or anything you want)
			$sess->login($_POST['screenname'], $_POST['password']);
			if ($sess->isLoggedIn() != false) {
				header("Location: /$redirect");
			} else {
				$loginFailure = "The Screen Name or Password you entered is incorrect";
			}
		}
		
		require_once("views/login.php");
	}
}

class Logout extends Controller {
	public function __index__() {
		$sess = parent::get_session();
		$sess->logout();
		
		header ("Location: /");
	}
}

// You'll need to add YOUR_DOMAIN for confirmation emails
class Signup extends Controller {
	public function __index__() {
		$sess = parent::get_session();
		$loggedInScreenname = parent::get_screenname();
		
		if ($_POST) {
			try {
				$db = Database::instance();
			} catch (Exception $e) {
				die("Unable to connect to the database");
			}
			
			// Required Fields
			$screenname = trim($_POST['join-screenname']);
			$email = $_POST['join-email'];
			$password1 = $_POST['join-password'];
			$password2 = $_POST['join-password-2'];

			// Run checks on data
			$post = new JoinFormHandler();
			$post->checkScreenName($screenname);
			$post->checkEmail($email);
			$post->checkPasswords($password1, $password2);

			// Check for any errors in form
			if ($post->errors()) {
				$errorMessages = $post->getErrorMessages();
			} else {
				// Generate a random confirmation code
				$confirmationCode = md5(uniqid(rand()));
				
				// Insert into People table
				$item = $db->insert("people", array('person_screenname'=>$screenname,
										  			'person_email'=>$email,
													'person_md5_password'=>md5($password1),
													'person_confirm_code'=>$confirmationCode));
				
				// Check if insert was successful
				if ($item[0]) {
					 $subject="Your confirmation link here";

					// From
					$header="from: YOU <YOU@YOUR_DOMAIN_HERE.com>";

					// Your message
					$message="Your Comfirmation link \r\n";
					$message.="Click on this link to activate your account \r\n";
					$message.="http://YOUR_DOMAIN_HERE/$confirmationCode";

					// send email
					$sentmail = mail($email,$subject,$message,$header);
					
					// Log them in
					$sess = parent::get_session();
					$sess->login($screenname, $password1);
					
					// Get username for template
					$loggedInScreenname = $screenname;
					
					// Join Success
					$joinSuccess = true;					
				}
			}
		}
		
		require_once ("views/signup.php");
	}
}

class Confirm extends Controller {
	public function __index__() {
		$loggedInScreenname = parent::get_screenname();

		try {
			$db = Database::instance();
			$instance = new Keylime();
			$confirmCode = $instance->get_param();
			
			$confirmActivation = false;
		} catch (Exception $e) {
			print "Sorry... there was an error. Please refresh your browser.";
		}
		
		if (!empty($confirmCode)) {
			// See if confirmation exists
			$confirmPerson = $db->select("SELECT * FROM people WHERE person_confirm_code = '$confirmCode'");

			// If person is found then activate their account and remove confirm code
			if ($confirmPerson) {
				$update = $db->update("people", "WHERE person_confirm_code = '$confirmCode'", array( 'person_active'=>1,
				 																					 'person_confirm_code'=> 0));
			
			$confirmActivation = true;
			$confirmPersonScreenName = $confirmPerson[0][1];
			}			
		}

		if ($confirmActivation) {
			require_once ("views/confirm.php");
		} else {
			Header( "HTTP/1.1 404 Not Found" ); 
			require_once ("views/404.php");
		}
	}
}


try {
	$loc = new Location();
	Keylime::run($urls); 
} catch (Exception $e) {
	print "Sorry... There was an error. Please Try Again.";
}
?>