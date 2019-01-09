<?php

use Application\Controllers\LoginController;
use Application\Controllers\DashboardController;
use Application\Controllers\BackhaulController;
use Application\Controllers\EnlacesController;
use Application\Controllers\TopologiasController;
use Application\Controllers\MapsController;
use Application\Controllers\DatafillController;

use Application\Models\Backhaul;
use Application\Models\Enlaces;
use Application\Models\Topologias;
use Application\Models\Map;
use Application\Models\Datafill;

use Application\Utils\Auth;
use Application\Utils\View;
use Application\Utils\TwigFunctions;

use Aura\Session\Session;

return [
    View::class => \DI\create(View::class),
    'SharedContainerTwig' => function (\Psr\Container\ContainerInterface $container) {
        TwigFunctions::setContainer($container);
    },
    Session::class => function (): Session {
        return (new \Aura\Session\SessionFactory())->newInstance($_COOKIE);
    },
    Auth::class => \DI\create(Auth::class)->constructor(
        \DI\get(Session::class)
    ),
    LoginController::class => \DI\create()->constructor(
        \DI\get(View::class),
        \DI\get(Session::class)
    ),
    DashboardController::class => \DI\create()->constructor(),
    BackhaulController::class => \DI\create()->constructor(
        \DI\get(Backhaul::class),
        \DI\get(View::class),
        \DI\get(Session::class)
    ),
    Backhaul::class => \DI\create()->constructor(
        \DI\get(Session::class)
    ),
    EnlacesController::class => \DI\create()->constructor(
        \DI\get(Enlaces::class),
        \DI\get(View::class),
        \DI\get(Session::class)
    ),
    Enlaces::class => \DI\create()->constructor(
        \DI\get(Session::class)
    ),
    TopologiasController::class => \DI\create()->constructor(
        \DI\get(Topologias::class),
        \DI\get(View::class),
        \DI\get(Session::class)
    ),
    Topologias::class => \DI\create()->constructor(
        \DI\get(Session::class)
    ),
    MapsController::class => \DI\create()->constructor(
        \DI\get(Map::class),
        \DI\get(View::class),
        \DI\get(Session::class)
    ),
    Map::class => \DI\create()->constructor(
        \DI\get(Session::class)
    ),
    DatafillController::class => \DI\create()->constructor(
        \DI\get(Datafill::class),
        \DI\get(View::class),
        \DI\get(Session::class)
    ),
    Datafill::class => \DI\create()->constructor(
        \DI\get(Session::class)
    ),

];