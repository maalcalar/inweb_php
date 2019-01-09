<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$container = require __DIR__ . '/../bootstrap/container.php';

$container->get('SharedContainerTwig');

$dispatcher = require base_path('routes/web.php');

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/*
 * SUBDIR INWEB
 */
$uri = str_replace('inweb/','',$uri);

$route = $dispatcher->dispatch($httpMethod, $uri);

switch ($route[0]) {
	case \FastRoute\Dispatcher::NOT_FOUND: {
		echo "Ruta no encontrada";
		break;
	}
	case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED: {
		echo "Método HTTP no permitido";
		break;
	}
	case \FastRoute\Dispatcher::FOUND: {
		$controller = $route[1];
		$params = $route[2];
		if (isset($route[1][2]) && $route[1][2] === 'auth') {
			$session = $container->get(\Aura\Session\Session::class);
			$segment = $session->getSegment('InWeb');
			if ( ! $segment->get('user')) {
				$segment->setFlash('errors', 'Aún no ha iniciado sesión');
				return redirect(base_url('login'));
			} else {
				$controller = [$route[1][0], $route[1][1]];
				$parameters = $route[2];
			}
 		}
		$container->call($controller, $params);
		break;
	}
}
