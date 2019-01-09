<?php

namespace Application\Controllers;

use Application\Models\Backhaul;
use Application\Utils\View;
use Aura\Session\Session;

class BackhaulController {

    protected $backhaul;
    protected $view;
    protected $session;

    public function __construct (Backhaul $backhaul, View $view, Session $session) {
        $this->backhaul = $backhaul;
        $this->view = $view;
        $this->session = $session;
    }

    public function recursos () {
        $equipos = $this->backhaul->getAll();
        echo $this->view->render('templates/backhaul/recursos.twig',compact("equipos"));
    }

    public function generar () {
        echo $this->backhaul->generar();
    }

    public function guardar () {
        $guardar = $this->backhaul->guardar();
        if($guardar['success'])
            $this->session->getSegment('InWeb')->setFlash('notificacion', 'Se ha guardado correctamente la información');
        else
            $this->session->getSegment('InWeb')->setFlash('error', $guardar['error']);
        return redirect(base_url('backhaul/recursos'));
    }

    public function getByID (int $id) {
        $this->session->getSegment('InWeb')->set('backhaul_editar_equipo', $id);
        echo $this->backhaul->getByID($id);
}

    public function editar () {
        $usuarios_validos = ['jonathan.arancibia@entel.pe','fernando.huarcaya@entel.pe'];
        if(in_array($this->session->getSegment('InWeb')->get('user')['email'],$usuarios_validos)) {
            echo $this->session->getSegment('InWeb')->get('backhaul_editar_equipo');
            $this->backhaul->editar($this->session->getSegment('InWeb')->get('backhaul_editar_equipo'));
            $this->session->getSegment('InWeb')->setFlash('notificacion', 'Se ha editado correctamente la información');
        } else {
            $this->session->getSegment('InWeb')->setFlash('error', 'Usted no tiene Privilegios para editar equipo alguno.');
        }
        return redirect(base_url('backhaul/recursos'));
    }

    public function monitor (int $id) {
        $data = [];
        $detalles = $this->backhaul->getByID($id);
    }
}