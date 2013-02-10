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

$app['checkers'] = $app->share(function() use ($app) {
	return new Seer\Checker\CheckerProvider($app['db']);
});


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
	$username = $app['security']->getToken()->getUser()->getUserName();

	try {
		$checkers = $app['checkers']->getCheckersByUserName($username);
	} catch (Seer\Checker\CheckerNotFoundException $e) {
		$checkers = array ();
	}

	return $app['twig']->render('dashboard.html.twig', array (
		'checkers' => $checkers
	));
});

$app->get('/dashboard/view/{id}', function($id) use ($app) {
	$checker = $app['checkers']->getCheckerById($id);

	return $app['twig']->render('single-checker.html.twig', array (
		'checker' => $checker
	));
});


$app->get('/dashboard/create', function() use ($app) {
	return $app['twig']->render('create-checker.html.twig');
});

$app->get('/account/logout', function() use ($app) {
	return $app['twig']->render('logout.html.twig');
});

$app->post('/dashboard/checker/save', function (Request $request) use ($app) {
	// TODO: if (checker->belongsTo(user))
	// $checker->update()
	$username = $app['security']->getToken()->getUser()->getUserName();

	if ($request->get('id') !== null) {
		$checker = $app['checkers']->getCheckerByIdAndUserName($request->get('id'), $username);
	} else {
		$checker = $app['checkers']->createCheckerForUserName ($username);
	}

	$checker->name = $request->get('name');
	$checker->url = $request->get('url');
	$checker->text = $request->get('text');
	$checker->invert = $request->get('invert') ? true : false;
	$checker->is_active = $request->get('is_active') ? true : false;

	$checker->save($app['db']);

	return $app->redirect('/dashboard/view/' . $checker->id());
});

$app->get('/dashboard/checker/delete/{id}', function ($id) use ($app) {
	$username = $app['security']->getToken()->getUser()->getUserName();
	$checker = $app['checkers']->getCheckerByIdAndUserName($id, $username);

	return $app['twig']->render('delete-checker.html.twig', array (
		'checker' => $checker
	));
});

$app->post('/dashboard/checker/delete/{id}', function(Request $request, $id) use ($app) {
	$username = $app['security']->getToken()->getUser()->getUserName();
	if ($request->get('submit') === 'yes') {
		$checker = $app['checkers']->getCheckerByIdAndUserName($id, $username);
		$app['db']->delete('checkers', array('id' => $checker->id()));
	}
	return $app->redirect('/dashboard');
});

return $app;
