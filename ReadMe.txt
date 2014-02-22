KeylimePHP is a micro framework for quickly writing simple and clean PHP applications.

It was created in 2006 so it is in need of some overhalling.

The only dependency is PHP5 and Pear.

Have a look around, get your feet wet, and check out _config.php to add your DB particulars.

Getting Started:

1. You first need to create an array of page URLs within the code.php file. Like this:

$urls = array( "home" => "Index",
		   	   "login" => "Login",
		   	   "logout" => "Logout",
		   	   "signup" => "Signup" );

These will give you 

http://your_domain/home
http://your_domain/login
http://your_domain/logout
http://your_domain/signup

2. Now we need to give them some action. Let's add a welcome message to the homepage. It the code.php file, let's add a new class.

class Index {
	public function __index__() {
		print "<h1>Hello, World!</h1>";
	}
}

Now when you go to http://your_domain_or_localhost/home you'll see the hello, world message printed out!

3. Ok, but what about adding a view with a template. No problem:

First let's add a file called home.php into our views/ directory. Then make the Index class look like this:

class Index {
	public function __index__() {
		require_once("views/index.php");
	}
}

That's it!

4. KeylimePHP gives you some fun functionality right out of the box such as simple routing and session management, but it can also be easily extended.




Todo:

- Move more case specific classes from _keylime.php to their own file.
- Better docs
