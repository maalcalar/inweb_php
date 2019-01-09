<?php

namespace Application\Models;

use Aura\Session\Session;
use Nette\Database\Connection;

class Datafill extends Oracle {
    protected $session;
    protected $admin;

    public function __construct (Session $session) {
        $this->session = $session;
        $this->admin = ['jonathan.arancibia@entel.pe','gustavo.calvo@entel.pe','oswaldo.espana@entel.pe','romelia.santiago@osctelecoms.com'];
        $this->connect();
    }

    public function get_solicitudes () {
        $sql = 'SELECT SOLICITUD_ID,PROYECTO,NE,FE,SOLICITANTE,TO_CHAR(FECHA,\'YYYY/MM/DD HH24:MI\') AS FECHA,ESTADO FROM DVL_DATAFILL_SOLICITUD';
        if(!in_array($this->session->getSegment('InWeb')->get('user')['email'],$this->admin))
            return $this->database->fetchAll($sql.' WHERE SOLICITANTE_EMAIL = ?', $this->session->getSegment('InWeb')->get('user')['email']);
        else
            return $this->database->fetchAll($sql);
    }

    public function add_solicitud () {
        date_default_timezone_set('America/Lima');
        $agregar = $_POST['agregar'];

        $response['message'] = '';
        $response['status'] = false;

        $pattern = "/[0-9]{7}_[A-Z]{2}_[a-zA-Z0-9_]{1,}/";
        $codigos_validos = ['010','014','016','188'];

        if(!in_array(substr($agregar['ne'],0,3),$codigos_validos))
            $response['message'] .= '<li><span class="text-warning">NE</span> no empieza con los formatos válidos de código de Sitio [010,014,016,188]</li>';
        if(!preg_match($pattern,$agregar['ne']))
            $response['message'] .= '<li><span class="text-warning">NE</span> no cumple con el formato válido de Sitio [XXXXXXX_YY_Nombre_Site_NE]</li>';
        if(!in_array(substr($agregar['fe'],0,3),$codigos_validos))
            $response['message'] .= '<li><span class="text-warning">FE</span> no empieza con los formatos válidos de código de Sitio [010,014,016,188]</li>';
        if(!preg_match($pattern,$agregar['fe']))
            $response['message'] .= '<li><span class="text-warning">FE</span> no cumple con el formato válido de Sitio [XXXXXXX_YY_Nombre_Site_NE]</li>';

        if(is_null($response['message']) || empty($response['message'])) {
            $response['status'] = true;

            $agregar['id'] = $this->database->fetchField('SELECT NVL(MAX(SOLICITUD_ID)+1,1) AS MAX FROM DVL_DATAFILL_SOLICITUD');
            $agregar['fecha'] = date('d/m/Y H:i:s');
            $agregar['solicitante'] = clear_string($this->session->getSegment('InWeb')->get('user')['nombre']);
            $agregar['solicitante_email'] = $this->session->getSegment('InWeb')->get('user')['email'];

            $this->database->query('INSERT INTO DVL_DATAFILL_SOLICITUD', [[
                'SOLICITUD_ID' => $agregar['id'],
                'SOLICITANTE' => $agregar['solicitante'],
                'SOLICITANTE_EMAIL' => $agregar['solicitante_email'],
                'FECHA' => $this->database::literal('TO_DATE(?,\'DD/MM/YYYY HH24:MI:SS\')', $agregar['fecha']),
                'ESTADO' => 'SIN ATENDER',
                'PROYECTO' => $agregar['proyecto'],
                'NE' => clear_string($agregar['ne']),
                'FE' => clear_string($agregar['fe']),
                'DESCRIPCION' => clear_string($agregar['descripcion']),
                'WEEK' => $agregar['week'],
            ]]);
            $this->session->getSegment('InWeb')->setFlash('notificacion', 'Se ha ingresado la Solicitud de Datafill correctamente.');
        }
        $response['data'] = $agregar;
        return $response;
    }

    public function del_solicitud (int $id) {
        $this->database->query('DELETE FROM DVL_DATAFILL_SOLICITUD WHERE SOLICITUD_ID = ?', $id);
        redirect('../../solicitudes');
    }

    public function get_solicitud (int $id) {
        return $this->database->fetch('SELECT SOLICITUD_ID,SOLICITANTE,SOLICITANTE_EMAIL,TO_CHAR(FECHA,\'DD/MM/YYYY HH24:MI\') AS FECHA,ESTADO,PROYECTO,NE,FE,DESCRIPCION,WEEK FROM DVL_DATAFILL_SOLICITUD WHERE SOLICITUD_ID = ?',$id);
    }

    public function get_solicitud_extras (int $id) {
        return $this->database->fetchAll('SELECT ARCHIVO,TO_CHAR(FECHA,\'DD/MM/YYYY HH24:MI\') AS FECHA,ARCHIVO_ID FROM DVL_DATAFILL_SOLICITUD_EXTRA WHERE SOLICITUD_ID = ? ORDER BY FECHA DESC',$id);
    }

    public function get_solicitud_tx (int $id) {
        return $this->database->fetchAll('SELECT ARCHIVO,TO_CHAR(FECHA,\'DD/MM/YYYY HH24:MI\') AS FECHA,ARCHIVO_ID,RESPONSABLE FROM DVL_DATAFILL_SOLICITUD_TX WHERE SOLICITUD_ID = ? ORDER BY FECHA DESC',$id);
    }

    public function get_solicitud_servicios (int $id) {
        $servicios['ot_able'] = 'none';
        $servicios['data'] = $this->database->fetchAll('SELECT SOLICITUD_ID,SERVICIO_ID,SERVICIO_NOMBRE,SERVICIO_TIPO,INFORMACION,ESTADO,TO_CHAR(RECIBIDO,\'DD/MM/YYYY HH24:MI\') AS RECIBIDO,TO_CHAR(SOLICITADO,\'DD/MM/YYYY HH24:MI\') AS SOLICITADO,VALIDADO FROM DVL_DATAFILL_SERVICIOS NATURAL JOIN DVL_DATAFILL_SOLICITUD_OYM WHERE SOLICITUD_ID = ? UNION SELECT SOLICITUD_ID,SERVICIO_ID,SERVICIO_NOMBRE,SERVICIO_TIPO,\'NO REQUIERE OT\' AS INFORMACION,\'-\' AS ESTADO,\'-\' AS RECIBIDO,\'-\' AS SOLICITADO,VALIDADO FROM DVL_DATAFILL_SERVICIOS WHERE SERVICIO_ID NOT IN ( SELECT SERVICIO_ID FROM DVL_DATAFILL_SERVICIOS NATURAL JOIN DVL_DATAFILL_SOLICITUD_OYM WHERE SOLICITUD_ID = ? ) AND SOLICITUD_ID = ? ORDER BY INFORMACION',$id,$id,$id);
        for($i = 0 ; $i < count($servicios['data']) ; $i++)
            if (in_array($servicios['data'][$i]['SERVICIO_TIPO'], ['BAFI', 'GUL', 'GESTION', 'Gestion'])) {
                $servicios['ot_able'] = 'ok';
                break;
            }
        return $servicios;
    }

    public function get_solicitud_datafills (int $id) {
        # obtenemos todos los servicios
        $servicios = $this->database->fetchAll('SELECT * FROM DVL_DATAFILL_SERVICIOS WHERE SOLICITUD_ID = ?',$id);
        return $this->tabla_datafill($id, $servicios);
    }

    public function tabla_datafill (int $id, $servicios) {
        # array donde se almacenaran los datafills $datafills
        $datafills = null;
        # máximo número de saltos $datafills['max_idus']
        $datafills['max_idus'] = $this->database->fetchField('SELECT MAX(STEPS) AS STEPS FROM DVL_DATAFILL_SERVICIOS WHERE SOLICITUD_ID = ?',$id);
        # cabeceras de los recursos ip $datafills['ip_headers']
        $datafills['ip_headers'] = '';
        $recursos_columns = $this->database->fetchAll('SELECT COLUMN_NAME FROM USER_TAB_COLUMNS WHERE TABLE_NAME = \'DVL_DATAFILL_RECURSOS_IP\' ORDER BY COLUMN_ID');
        for($c = 0 ; $c < count($recursos_columns) ; $c++) {
            if(strcmp($recursos_columns[$c]['COLUMN_NAME'], 'SERVICIO_ID') == 0) continue;
            else if(strpos($recursos_columns[$c]['COLUMN_NAME'], 'RECT') !== false)    $datafills['ip_headers'] .= '<th class="text-center white bg-cyan-500">'.$recursos_columns[$c]['COLUMN_NAME'].'</th>';
            else if(strpos($recursos_columns[$c]['COLUMN_NAME'], 'OYM') !== false)     $datafills['ip_headers'] .= '<th class="text-center white bg-blue-grey-500">'.$recursos_columns[$c]['COLUMN_NAME'].'</th>';
            else if(strpos($recursos_columns[$c]['COLUMN_NAME'], 'GSM') !== false)     $datafills['ip_headers'] .= '<th class="text-center white bg-orange-500">'.$recursos_columns[$c]['COLUMN_NAME'].'</th>';
            else if(strpos($recursos_columns[$c]['COLUMN_NAME'], 'UMTS') !== false)    $datafills['ip_headers'] .= '<th class="text-center white bg-red-500">'.$recursos_columns[$c]['COLUMN_NAME'].'</th>';
            else if(strpos($recursos_columns[$c]['COLUMN_NAME'], 'LTE') !== false)     $datafills['ip_headers'] .= '<th class="text-center white bg-blue-500">'.$recursos_columns[$c]['COLUMN_NAME'].'</th>';
            else $datafills['ip_headers'] .= '<th class="text-center white bg-green-500">'.$recursos_columns[$c]['COLUMN_NAME'].'</th>';
        }
        # completar idus y puertos en los servicios de cada datafill
        for($i = 0 ; $i < count($servicios) ; $i++) {
            # setear los campos del servicio
            $datafill = null;   foreach ($servicios[$i] as $key => $value)  $datafill[$key] = $value;
            # buscar las idus que pertenecen a la ruta del servicio
            $idu_rows = $this->database->fetchAll('SELECT * FROM (SELECT * FROM DVL_DATAFILL_ROUTES NATURAL JOIN DVL_DATAFILL_STEPS WHERE SERVICIO_ID = ?) NATURAL JOIN DVL_DATAFILL_IDUS ORDER BY POSITION ASC',$servicios[$i]['SERVICIO_ID']);
            $j = 0; $idus = null;
            for(; $j < count($idu_rows) ; $j++)
                $idus[] = array('IDU' => $idu_rows[$j]['IDU'], 'PORT_IN' => $idu_rows[$j]['STEP_IN'], 'PORT_OUT' => $idu_rows[$j]['STEP_OUT']);
            $datafill['idus'] = $idus;
            # recursos ip
            $datafill['recursos_ip'] = $this->database->fetch('SELECT * FROM DVL_DATAFILL_RECURSOS_IP WHERE SERVICIO_ID = ?',$servicios[$i]['SERVICIO_ID']);
            if(count($datafill) > 0)
                unset($datafill['recursos_ip']['SERVICIO_ID']);
            $datafills['data'][] = $datafill;
        }
        return $datafills;
    }

    public function extra_archivo() {
        $response['status'] = false;
        if(isset($_POST)) {
            date_default_timezone_set('America/Lima');
            $fecha 				= date('YmdHi');
            $fecha_db			= date('d/m/Y H:i');
            $fileName 			= $fecha.'_'.clear_string($_FILES["file"]["name"]);
            $targetDir 			= "/var/www/html/inweb/public/files/datafill/solicitudes/extras/";
            $targetFilePath 	= $targetDir . $fileName;
            $solicitud_id       = $_POST['id'];
            if(isset($solicitud_id)) {
                if(move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)){
                    $archivo_id = $this->database->fetchField('SELECT NVL(MAX(ARCHIVO_ID),0)+1 AS MAX FROM DVL_DATAFILL_SOLICITUD_EXTRA');
                    $this->database->query('INSERT INTO DVL_DATAFILL_SOLICITUD_EXTRA', [[
                        'SOLICITUD_ID' => $solicitud_id,
                        'ARCHIVO' => $fileName,
                        'FECHA' => $this->database::literal('TO_DATE(?,\'DD/MM/YYYY HH24:MI\')', $fecha_db),
                        'ARCHIVO_ID' => $archivo_id
                    ]]);
                    $response['status'] = true;
                    $response['mensaje'] = '<p>Éxito al cargar el archivo.<br>Click para seleccionar otro archivo</p>';
                } else {
                    $response['mensaje'] = '<p>Ha ocurrido un problema al guardar el archivo, seleccione el archivo y vuelva a intentarlo.</p>';
                }
            } else {
                $response['mensaje'] =  '<p>Hubo un problema al encontrar el ID de la solicitud, actualice la página y vuelva a intentarlo.</p>';
            }
        }
    }
}