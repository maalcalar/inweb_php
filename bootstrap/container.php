<?php

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../libs/excel/autoload.php';
require __DIR__ . '/../libs/mail/PHPMailer/PHPMailerAutoload.php';

$containerBuilder = new \DI\ContainerBuilder;
$containerBuilder->useAutowiring( false);

$containerBuilder->addDefinitions(base_path('bootstrap/config.php'));

try {
	$container = $containerBuilder->build();
	return $container;
} catch ( Exception $e ) {
}
