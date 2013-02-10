<?php

use Symfony\Component\HttpFoundation\Request;

use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\DoctrineServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

// service providers
$app->register(new FormServiceProvider());
$app->register(new TranslationServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new SessionServiceProvider());

$app->register(new DoctrineServiceProvider(), array(
	'db.options' => array (
		'driver' => 'pdo_mysql',
		'dbhost' => 'localhost',
		'dbname' => 'seer',
		'user' => 'seer',
		'password' => 'KyFdaqBFVyyLRSXV'
	)
));

$app->register(new SecurityServiceProvider(), array(
	'security.firewalls' => array (
		'dashboard' => array (
			'pattern' => '^/dashboard',
			'form' => array(
				'login_path' => '/login',
				'check_path' => '/dashboard/login_check'
			),
			'logout' => array (
				'logout_path' => '/dashboard/logout'
			),
			'users' => $app->share(function () use ($app) {
				return new Seer\User\UserProvider($app['db']);
			})
		),
		'login' => array(
			'anonymous' => true,
			'pattern' => '^/|^/login'
		)
	)
));

$app->register(new TwigServiceProvider(), array (
	'twig.path' => __DIR__ . '/../templates/',
	'twig.class_path' => __DIR__ . '/../vendor/Twig/lib',
	'twig.options' => array('cache' => __DIR__ . '/../cache')
));


// controllers
$app->match('/generate/{password}', function($password) use ($app) {
	echo $app['security.encoder.digest']->encodePassword($password, '');
	return true;
});

$app->get('/login', function(Request $request) use ($app) {
	return $app['twig']->render('login.html.twig', array(
		'error' => $app['security.last_error']($request),
		'last_username' => $app['session']->get('_security.last_username')
	));
});

$app->get('/', function () use ($app) {
	return $app['twig']->render('index.html.twig');
});

$app->get('/dashboard', function() use ($app) {
	$token = $app['security']->getToken();
	if ($token !== null) {
		$user = $token->getUser();
	}
	$query = 'SELECT * FROM checkers WHERE user_id = (SELECT id FROM users WHERE username = ?)';

	$checkers = $app['db']->fetchAll($query, array($user->getUsername()));
	return $app['twig']->render('dashboard.html.twig', array (
		'checkers' => $checkers
	));
});

$app->get('/dashboard/view/{id}', function($id) use ($app) {
	$query = 'SELECT * FROM checkers WHERE id = ?';
	$checker = $app['db']->fetchAssoc($query, array($id));
	return $app['twig']->render('single-checker.html.twig', array (
		'checker' => $checker
	));
});

$app->get('/account/logout', function() use ($app) {
	return $app['twig']->render('logout.html.twig');
});

$app->post('/dashboard/checker/save', function (Request $request) use ($app) {
	// TODO: if (checker->belongsTo(user))
	// $checker->update()
	return $request->get('id');

	$app['db']->update('checkers', array('id' => $request->get('id')), array(
		'name' => $request->get('name'),
		'url' => $request->get('url'),
		'text' => $request->get('text'),
		'invert' => $request->get('invert') ? 1 : 0
	));

	return $app->redirect('/dashboard/view/' . $request->get('id'));
});

return $app;
