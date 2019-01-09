<?php

return \FastRoute\simpleDispatcher( function ( \FastRoute\RouteCollector $route ) {
    /*
     * TEST (DASHBOARD)
     */
    $route->addRoute('GET', '/',
        ['Application\Controllers\DashboardController', 'index', 'auth']
    );
    /*
     * LOGIN
     */
    $route->addRoute('GET', '/login',
        ['Application\Controllers\LoginController', 'showLoginForm']
    );
    $route->addRoute('POST', '/login',
        ['Application\Controllers\LoginController', 'login']
    );
    $route->addRoute('GET', '/logout',
        ['Application\Controllers\LoginController', 'logout', 'auth']
    );
    /*
     * BACKHAUL
     */
    $route->addRoute('GET', '/backhaul/recursos',
        ['Application\Controllers\BackhaulController', 'recursos', 'auth']
    );
    $route->addRoute('POST', '/backhaul/recursos/generar',
        ['Application\Controllers\BackhaulController', 'generar', 'auth']
    );
    $route->addRoute('POST', '/backhaul/recursos/guardar',
        ['Application\Controllers\BackhaulController', 'guardar', 'auth']
    );
    $route->addRoute('GET', '/backhaul/recursos/getByID/{id}',
        ['Application\Controllers\BackhaulController', 'getByID', 'auth']
    );
    $route->addRoute('POST', '/backhaul/recursos/editar',
        ['Application\Controllers\BackhaulController', 'editar', 'auth']
    );
    $route->addRoute('GET', '/backhaul/recursos/monitor/{id}',
        ['Application\Controllers\BackhaulController', 'monitor', 'auth']
    );
    /*
     * ENLACES
     */
    $route->addRoute('GET', '/enlaces/lista',
        ['Application\Controllers\EnlacesController', 'lista', 'auth']
    );
    /*
     * TOPOLOGIA CONSTELACION
     */
    $route->addRoute('GET', '/topologias/constelacion',
        ['Application\Controllers\TopologiasController', 'constelacion_site', 'auth']
    );
    $route->addRoute('GET', '/topologias/constelacion/Estrella/{site}',
        ['Application\Models\Topologias', 'constelacion_estrella',]
    );
    $route->addRoute('GET', '/topologias/constelacion/Rutas/{site}',
        ['Application\Models\Topologias', 'constelacion_rutas',]
    );
    $route->addRoute('GET', '/topologias/constelacion/linkSparkline/{id}',
        ['Application\Models\Topologias', 'constelacion_sparkline',]
    );
    /*
     * TOPOLOGIA MAPA
     */
    $route->addRoute('GET', '/topologias/map',
        ['Application\Controllers\MapsController', 'map_site', 'auth']
    );
    $route->addRoute('GET', '/topologias/map/agregadores',
        ['Application\Models\Map', 'agregadores', 'auth']
    );
    /*
     * DATAFILL
     */
    $route->addRoute('GET', '/datafill/solicitudes',
        ['Application\Controllers\DatafillController', 'solicitudes', 'auth']
    );
    $route->addRoute('POST', '/datafill/solicitudes/crear',
        ['Application\Controllers\DatafillController', 'solicitudes_crear', 'auth']
    );
    $route->addRoute('GET', '/datafill/solicitudes/eliminar/{id}',
        ['Application\Models\Datafill', 'del_solicitud', 'auth']
    );
    $route->addRoute('POST', '/datafill/solicitudes/detalles',
        ['Application\Controllers\DatafillController', 'solicitudes_detalles', 'auth']
    );
    $route->addRoute('POST', '/datafill/solicitudes/extra_archivo',
        ['Application\Models\Datafill', 'extra_archivo', 'auth']
    );
});