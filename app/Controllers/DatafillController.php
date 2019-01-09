<?php

namespace Application\Controllers;

use Application\Models\Datafill;
use Application\Utils\View;
use Application\Utils\MailService;
use Aura\Session\Session;

class DatafillController {
    protected $datafill;
    protected $view;
    protected $session;

    public function __construct (Datafill $datafill, View $view, Session $session) {
        $this->datafill = $datafill;
        $this->view = $view;
        $this->session = $session;
    }

    public function solicitudes () {
        $solicitudes = $this->datafill->get_solicitudes();
        echo $this->view->render('templates/datafill/solicitudes.twig', compact("solicitudes"));
    }

    public function solicitudes_crear () {
        $response = $this->datafill->add_solicitud();
        if($response['status']) {
            $solicitud = $response['data'];
            MailService::send(
                'inweb.datafill@entel.pe',
                'InWeb | Datafill',
                [],//['gustavo.calvo@entel.pe','oswaldo.espana@entel.pe'],
                $this->view->render('templates/datafill/mail_solicitud_creada.twig', compact("solicitud")),
                'Datafill '.$solicitud['id'].' | '.$solicitud['proyecto'].' [ '.$solicitud['ne'].' - '.$solicitud['fe'].' ]'
            );
        }
        echo json_encode($response);
    }

    public function solicitudes_detalles () {
        $id = $_POST['id'];
        $bitacora['id'] = $id;
        $bitacora['solicitud'] = $this->datafill->get_solicitud($id);
        $bitacora['extras'] = $this->datafill->get_solicitud_extras($id);
        $bitacora['tx'] = $this->datafill->get_solicitud_tx($id);
        $bitacora['servicios'] = $this->datafill->get_solicitud_servicios($id);
        $bitacora['datafills'] = $this->datafill->get_solicitud_datafills($id);
        $response['html'] = $this->view->render('templates/datafill/bitacora.twig',compact("bitacora"));
        $response['status'] = true;
        echo json_encode($response);
    }
}