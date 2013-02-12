<?php

use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Igorw\Silex\ConfigServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

// service providers
$env = getenv('APP_ENV') ?: 'prod';
$app->register(new ConfigServiceProvider(__DIR__ . "/../config/$env.yml"));

$app->register(new FormServiceProvider());

$app->register(new TranslationServiceProvider());

$app->register(new UrlGeneratorServiceProvider());

$app->register(new SessionServiceProvider());

$app->register(new DoctrineServiceProvider());

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
	$user = $app['security']->getToken()->getUser();

	try {
		$checkers = $app['checkers']->getCheckersByUser($user);
	} catch (Seer\Checker\CheckerNotFoundException $e) {
		$checkers = array ();
	}

	return $app['twig']->render('dashboard.html.twig', array (
		'checkers' => $checkers
	));
});

$app->get('/dashboard/view/{id}', function($id) use ($app) {
	$user = $app['security']->getToken()->getUser();

	try {
		$checker = $app['checkers']->getCheckerByIdAndUser($id, $user);
	} catch (Exception $e) {
		return "You don't have access to this page.";
	}

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
	$user= $app['security']->getToken()->getUser();

	if ($request->get('id') !== null) {
		$checker = $app['checkers']->getCheckerByIdAndUser ($request->get('id'), $user);
	} else {
		$checker = $app['checkers']->createCheckerForUser($user);
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
	$user= $app['security']->getToken()->getUser();
	$checker = $app['checkers']->getCheckerByIdAndUser ($id, $user);

	return $app['twig']->render('delete-checker.html.twig', array (
		'checker' => $checker
	));
});

$app->post('/dashboard/checker/delete/{id}', function(Request $request, $id) use ($app) {
	$user = $app['security']->getToken()->getUser();
	if ($request->get('submit') === 'yes') {
		$checker = $app['checkers']->getCheckerByIdAndUser ($id, $user);
		$app['db']->delete('checkers', array('id' => $checker->id()));
	}
	return $app->redirect('/dashboard');
});

$app->get('/dashboard/profile', function () use ($app) {
	$user = $app['security']->getToken()->getUser();
	return $app['twig']->render('profile.html.twig', array(
		'user' => $user
	));
});

$app->post('/dashboard/profile/save', function (Request $request) use ($app) {
	if ($request->get('password')) {
		$user = $app['security']->getToken()->getUser();

		$password = $app['security.encoder.digest']->encodePassword($request->get('password'), '');
		$app['db']->update('users', array(
			'password' => $password
		), array (
			'id' => $user->getId()
		));
	}
	return $app->redirect('/dashboard');
});

$app->get('/service-status', function () use ($app) {


	return $app['twig']->render('service-status.html.twig', array(
		'frontend' => true,
		'checker' => strpos(file_get_contents('http://127.0.0.1:5338'), 'All systems go') !== false
	));
});


return $app;
