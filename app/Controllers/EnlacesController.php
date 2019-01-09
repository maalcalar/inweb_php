<?php

namespace Application\Controllers;

use Application\Models\Enlaces;
use Application\Utils\View;
use Aura\Session\Session;

class EnlacesController {

    protected $enlaces;
    protected $view;
    protected $session;

    public function __construct (Enlaces $enlaces, View $view, Session $session) {
        $this->enlaces = $enlaces;
        $this->view = $view;
        $this->session = $session;
    }

    public function lista () {
        $enlaces = $this->enlaces->getAll();
        echo $this->view->render('templates/enlaces/lista.twig',compact("enlaces"));
    }
}