<?php defined('BLUDIT') or die('Bludit CMS.');

// Session
Session::start();
if (Session::started()===false) {
	exit('Bludit CMS. Session initialization failure.');
}

$login = $Login = new Login();

$layout = array(
	'controller'=>null,
	'view'=>null,
	'template'=>'index.php',
	'slug'=>null,
	'parameters'=>null,
	'title'=>'Bludit'
);

// Get the view, controller, and the parameters from the URL.
$explodeSlug = $url->explodeSlug();
$layout['controller'] = $layout['view'] = $layout['slug'] = empty($explodeSlug[0])?'dashboard':$explodeSlug[0];
unset($explodeSlug[0]);
$layout['parameters'] = implode('/', $explodeSlug);

// Disable Magic Quotes.
// Thanks, http://stackoverflow.com/questions/517008/how-to-turn-off-magic-quotes-on-shared-hosting
if ( in_array( strtolower( ini_get( 'magic_quotes_gpc' ) ), array( '1', 'on' ) ) ) {
    $_POST	= array_map('stripslashes', $_POST);
    $_GET	= array_map('stripslashes', $_GET);
    $_COOKIE	= array_map('stripslashes', $_COOKIE);
}

// Boot plugins rules
include(PATH_RULES.'60.plugins.php');

// --- AJAX ---
if ($layout['slug']==='ajax') {
	if ($login->isLogged()) {
		// Rules: Security check CSRF
		include(PATH_RULES.'99.security.php');

		// Load the ajax file
		if (Sanitize::pathFile(PATH_AJAX, $layout['parameters'].'.php')) {
			include(PATH_AJAX.$layout['parameters'].'.php');
		}
	}
	header('HTTP/1.1 401 User not logged.');
	exit(0);
}
// --- ADMIN AREA ---
else
{
	// Boot rules
	include(PATH_RULES.'69.pages.php');
	include(PATH_RULES.'99.header.php');
	include(PATH_RULES.'99.paginator.php');
	include(PATH_RULES.'99.themes.php');
	include(PATH_RULES.'99.security.php');

	// Page not found.
	// User not logged.
	// Slug is login.
	// Slug is login-email.
	if($url->notFound() || !$login->isLogged() || ($url->slug()==='login') || ($url->slug()==='login-email') ) {
		$layout['controller']	= 'login';
		$layout['view']		= 'login';
		$layout['template']	= 'login.php';

		if ($url->slug()==='login-email') {
			$layout['controller']	= 'login-email';
			$layout['view']		= 'login-email';
		}

		// Generate the tokenCSRF for the user not logged, when the user log-in the token will be change.
		$security->generateTokenCSRF();
	}

	// Define variables
	$ADMIN_CONTROLLER 	= $layout['controller'];
	$ADMIN_VIEW 		= $layout['view'];

	// Load plugins before the admin area will be load.
	Theme::plugins('beforeAdminLoad');

	// Load init.php if the theme has one.
	if( Sanitize::pathFile(PATH_ADMIN_THEMES, $site->adminTheme().DS.'init.php') ) {
		include(PATH_ADMIN_THEMES.$site->adminTheme().DS.'init.php');
	}

	// Load controller.
	if( Sanitize::pathFile(PATH_ADMIN_CONTROLLERS, $layout['controller'].'.php') ) {
		include(PATH_ADMIN_CONTROLLERS.$layout['controller'].'.php');
	}

	// Load view and theme.
	if( Sanitize::pathFile(PATH_ADMIN_THEMES, $site->adminTheme().DS.$layout['template']) ) {
		include(PATH_ADMIN_THEMES.$site->adminTheme().DS.$layout['template']);
	}

	// Load plugins after the admin area is loaded.
	Theme::plugins('afterAdminLoad');
}
