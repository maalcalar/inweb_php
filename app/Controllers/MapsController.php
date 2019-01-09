<?php

namespace Application\Controllers;

use Application\Models\Map;
use Application\Utils\View;
use Aura\Session\Session;

class MapsController {

    protected $map;
    protected $view;
    protected $session;

    public function __construct (Map $map, View $view, Session $session) {
        $this->map = $map;
        $this->view = $view;
        $this->session = $session;
    }

    public function map_site () {
        echo $this->view->render('templates/topologias/map.twig');
    }
}