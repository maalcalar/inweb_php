<?php

namespace Application\Models;

use Application\Models\OracleDB;

use Aura\Session\Session;

class Backhaul extends OracleDB {

    protected $session;

    private $loopbacks;
    private $segundo_octeto;

    public function __construct (Session $session) {
        $this->session = $session;
        $this->generar_helper();
    }

    public function getAll () {
        $sql = "SELECT * FROM DVL_PAG ORDER BY PAG_ID";
        self::ejecutar($sql);
        return self::$results;
    }

    public function generar () {
        $agregar = $_POST['agregar'];

        $response['message'] = '';
        $response['success'] = false;

        if(!isset($agregar['site_nombre']) || empty($agregar['site_nombre'])) $response['message'] .= '<li>Falta el <span class="text-warning">Site</span></li>';
        if(!isset($agregar['hostname']) || empty($agregar['hostname'])) $response['message'] .= '<li>Falta el <span class="text-warning">Hostname</span></li>';
        if(!isset($agregar['zona']) || empty($agregar['zona'])) $response['message'] .= '<li>Falta la <span class="text-warning">Zona</span></li>';

        $sql = "SELECT * FROM DVL_PAG WHERE HOSTNAME = '".$agregar['hostname']."'";
        self::ejecutar($sql);
        if(self::$nrows > 0) {
            $response['message'] .= '<li>Ya existe un elemento con el mismo Hostname <span class="text-warning">[ DUPLICADO ]</span></li>';
            $agregar['hostname'] = null;
        }

        $type = substr($agregar['hostname'],9,3);
        if(in_array($type,['PAG','CSR']) && !is_null($agregar['hostname'])) {
            $loopback0 = [10,74];
            $loopback0[2] = $this->loopbacks[$agregar['zona']][$type];
            $sql = "SELECT TO_NUMBER(SUBSTR(SUBSTR(LOOPBACK0,0,INSTR(LOOPBACK0,'.',-1)-1),INSTR(SUBSTR(LOOPBACK0,0,INSTR(LOOPBACK0,'.',-1)-1),'.',-1)+1)) AS TERCER,TO_NUMBER(SUBSTR(LOOPBACK0,INSTR(LOOPBACK0,'.',-1)+1)) AS CUARTO FROM DVL_PAG WHERE LOOPBACK0 LIKE '".implode(".",$loopback0)."%' OR LOOPBACK0 LIKE '".$loopback0[0].".".$loopback0[1].".".($loopback0[2]+1)."%' ORDER BY TERCER,CUARTO DESC";
            self::ejecutar($sql);
            if(self::$nrows > 0) {
                $loopback0[2] = self::$results[0]['TERCER'];
                $loopback0[3] = self::$results[0]['CUARTO']+1;
            } else {
                $loopback0[3] = 1;
            }

            $loopback1 = [10,75];
            $loopback1[2] = $this->loopbacks[$agregar['zona']][$type];
            $sql = "SELECT TO_NUMBER(SUBSTR(SUBSTR(LOOPBACK1,0,INSTR(LOOPBACK1,'.',-1)-1),INSTR(SUBSTR(LOOPBACK1,0,INSTR(LOOPBACK1,'.',-1)-1),'.',-1)+1)) AS TERCER,TO_NUMBER(SUBSTR(LOOPBACK1,INSTR(LOOPBACK1,'.',-1)+1)) AS CUARTO FROM DVL_PAG WHERE LOOPBACK1 LIKE '".implode(".",$loopback1)."%' OR LOOPBACK1 LIKE '".$loopback1[0].".".$loopback1[1].".".($loopback1[2]+1)."%' ORDER BY TERCER,CUARTO DESC";
            self::ejecutar($sql);
            if(self::$nrows > 0) {
                $loopback1[2] = self::$results[0]['TERCER'];
                $loopback1[3] = self::$results[0]['CUARTO']+1;
            } else {
                $loopback1[3] = 1;
            }

            $ospf = [];
            $sql = "SELECT AREA_OSPF FROM DVL_PAG WHERE ANILLO = '".$agregar['anillo']."' AND ZONA = '".$agregar['zona']."'";
            self::ejecutar($sql);
            if(self::$nrows > 0) {
                $ospf = explode(".", self::$results[0]['AREA_OSPF']);
            } else {
                switch ($type) {
                    case 'PAG':
                        $ospf[0] = 51;
                        break;
                    case 'CSR':
                        $ospf[0] = 52;
                        break;
                }

                $ospf[1] = $this->segundo_octeto[$agregar['zona']];

                $ospf[2] = 1;
                if(strcmp($agregar['zona'], 'Lima Norte') == 0)         $ospf[2] = 0;
                else if(strcmp($agregar['zona'], 'Lima Oeste') == 0)    $ospf[2] = 2;
                else if(strcmp($agregar['zona'], 'Lima Este') == 0)     $ospf[2] = 4;
                else if(strcmp($agregar['zona'], 'Lima Sur') == 0)      $ospf[2] = 6;

                $sql = "SELECT TO_NUMBER(SUBSTR(SUBSTR(AREA_OSPF,0,INSTR(AREA_OSPF,'.',-1)-1),INSTR(SUBSTR(AREA_OSPF,0,INSTR(AREA_OSPF,'.',-1)-1),'.',-1)+1)) AS TERCER,TO_NUMBER(SUBSTR(AREA_OSPF,INSTR(AREA_OSPF,'.',-1)+1)) AS CUARTO FROM DVL_PAG WHERE AREA_OSPF LIKE '".implode(".",$ospf)."%' OR AREA_OSPF LIKE '".$ospf[0].".".$ospf[1].".".($ospf[2]+1)."%' ORDER BY TERCER,CUARTO DESC";
                self::ejecutar($sql);
                if(self::$nrows > 0) {
                    $ospf[2] = self::$results[0]['TERCER'];
                    $ospf[3] = self::$results[0]['CUARTO']+1;
                } else {
                    $ospf[3] = 1;
                }
            }

            $response['loopback0'] = implode('.', $loopback0);
            $response['loopback1'] = implode('.', $loopback1);
            $response['area_ospf'] = implode('.', $ospf);

            $response['success'] = true;
            $response['message'] .= '<li>Se han generado los campos faltantes, puede editarlos. Click en <span class="text-success">Guardar</span> para confirmar. </li>';
        } else {
            $response['message'] .= '<li>No se identifica el tipo de equipo <span class="text-warning">[ CSR o PAG ]</span></li>';
        }

        return json_encode($response);
    }

    public function guardar () {
        $guardar = $_POST['agregar'];

        $sql = "SELECT NVL(MAX(PAG_ID),0)+1 AS NEW_ID FROM DVL_PAG";
        self::ejecutar($sql);
        $new_id = self::$results[0]['NEW_ID'];

        extract($guardar);

        $sql = "INSERT INTO DVL_PAG (PAG_ID,HOSTNAME,SITE_NOMBRE,LOOPBACK0,LOOPBACK1,ANILLO,AREA_OSPF,ZONA,ESTADO) VALUES ($new_id,'$hostname','$site_nombre','$loopback0','$loopback1','$anillo','$area_ospf','$zona','TBD')";
        self::ejecutar_save($sql);

        return array('success' => self::$oci_execute,'error' => self::$oci_error);
    }

    public function getByID (int $id) {
        $sql = "SELECT * FROM DVL_PAG WHERE PAG_ID = $id";
        self::ejecutar($sql);

        return json_encode(array_change_key_case(self::$results[0],CASE_LOWER));
    }

    public function editar (int $id) {
        $editar = $_POST['editar'];
        array_change_key_case($editar,CASE_UPPER);

        $sql = "UPDATE DVL_PAG SET";
        foreach ($editar as $k => $v)  $sql .= " $k = '$v',";
        $sql = rtrim($sql,',') . " WHERE PAG_ID = $id";
        self::ejecutar_save($sql);
    }

    private function generar_helper () {
        $this->segundo_octeto = array(
            "Lima Norte"=>1,
            "Lima Oeste"=>1,
            "Lima Este"=>1,
            "Lima Sur"=>1,
            "Lima Provincia"=>1,
            "Amazonas"=>41,
            "Ancash"=>43,
            "Apurimac"=>83,
            "Arequipa"=>54,
            "Ayacucho"=>66,
            "Cajamarca"=>76,
            "Cusco"=>84,
            "Huancavelica"=>67,
            "Huanuco"=>62,
            "Ica"=>56,
            "Junin"=>64,
            "La Libertad"=>44,
            "Lambayeque"=>74,
            "Loreto"=>65,
            "Madre de Dios"=>82,
            "Moquegua"=>53,
            "Pasco"=>63,
            "Piura"=>73,
            "Puno"=>51,
            "San Martin"=>42,
            "Tacna"=>52,
            "Tumbes"=>72,
            "Ucayali"=>61
        );
        $this->loopbacks['Lima Norte']['PAG'] = 10;
        $this->loopbacks['Lima Norte']['CSR'] = 0;
        $this->loopbacks['Lima Oeste']['PAG'] = 12;
        $this->loopbacks['Lima Oeste']['CSR'] = 2;
        $this->loopbacks['Lima Sur']['PAG'] = 16;
        $this->loopbacks['Lima Sur']['CSR'] = 6;
        $this->loopbacks['Lima Este']['PAG'] = 14;
        $this->loopbacks['Lima Este']['CSR'] = 4;
        $this->loopbacks['Lima Provincia']['PAG'] = 18;
        $this->loopbacks['Lima Provincia']['CSR'] = 8;

        $this->loopbacks['Amazonas']['PAG'] = 30;
        $this->loopbacks['Amazonas']['CSR'] = 130;
        $this->loopbacks['Ancash']['PAG'] = 32;
        $this->loopbacks['Ancash']['CSR'] = 132;
        $this->loopbacks['Apurimac']['PAG'] = 34;
        $this->loopbacks['Apurimac']['CSR'] = 134;
        $this->loopbacks['Arequipa']['PAG'] = 36;
        $this->loopbacks['Arequipa']['CSR'] = 136;
        $this->loopbacks['Ayacucho']['PAG'] = 38;
        $this->loopbacks['Ayacucho']['CSR'] = 138;
        $this->loopbacks['Cajamarca']['PAG'] = 40;
        $this->loopbacks['Cajamarca']['CSR'] = 140;
        $this->loopbacks['Cusco']['PAG'] = 42;
        $this->loopbacks['Cusco']['CSR'] = 142;
        $this->loopbacks['Huancavelica']['PAG'] = 44;
        $this->loopbacks['Huancavelica']['CSR'] = 144;
        $this->loopbacks['Huanuco']['PAG'] = 46;
        $this->loopbacks['Huanuco']['CSR'] = 146;
        $this->loopbacks['Ica']['PAG'] = 48;
        $this->loopbacks['Ica']['CSR'] = 148;
        $this->loopbacks['Junin']['PAG'] = 50;
        $this->loopbacks['Junin']['CSR'] = 150;
        $this->loopbacks['La Libertad']['PAG'] = 52;
        $this->loopbacks['La Libertad']['CSR'] = 152;
        $this->loopbacks['Lambayeque']['PAG'] = 54;
        $this->loopbacks['Lambayeque']['CSR'] = 154;
        $this->loopbacks['Loreto']['PAG'] = 56;
        $this->loopbacks['Loreto']['CSR'] = 156;
        $this->loopbacks['Madre de Dios']['PAG'] = 58;
        $this->loopbacks['Madre de Dios']['CSR'] = 158;
        $this->loopbacks['Moquegua']['PAG'] = 60;
        $this->loopbacks['Moquegua']['CSR'] = 160;
        $this->loopbacks['Pasco']['PAG'] = 62;
        $this->loopbacks['Pasco']['CSR'] = 162;
        $this->loopbacks['Piura']['PAG'] = 64;
        $this->loopbacks['Piura']['CSR'] = 164;
        $this->loopbacks['Puno']['PAG'] = 66;
        $this->loopbacks['Puno']['CSR'] = 166;
        $this->loopbacks['San Martin']['PAG'] = 68;
        $this->loopbacks['San Martin']['CSR'] = 168;
        $this->loopbacks['Tacna']['PAG'] = 70;
        $this->loopbacks['Tacna']['CSR'] = 170;
        $this->loopbacks['Tumbes']['PAG'] = 72;
        $this->loopbacks['Tumbes']['CSR'] = 172;
        $this->loopbacks['Ucayali']['PAG'] = 74;
        $this->loopbacks['Ucayali']['CSR'] = 174;
    }
}