<?php

namespace Application\Controllers;

use Application\Models\Topologias;
use Application\Utils\View;
use Aura\Session\Session;

class TopologiasController {

    protected $topologias;
    protected $view;
    protected $session;

    public function __construct (Topologias $topologias, View $view, Session $session) {
        $this->topologias = $topologias;
        $this->view = $view;
        $this->session = $session;
    }

    public function constelacion_site() {
        $sites = $this->topologias->getAllSites();
        echo $this->view->render('templates/topologias/constelacion_site.twig', compact("sites"));
    }
}