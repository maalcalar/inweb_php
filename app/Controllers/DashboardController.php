<?php

namespace Application\Controllers;

use Application\Utils\View;

class DashboardController {

    public function __construct () {
    }

    public function index (View $view) {
        echo $view->render('dashboard.twig');
    }
}