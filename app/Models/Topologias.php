<?php

namespace Application\Models;


use Application\Models\OracleDB;
use Aura\Session\Session;

class Topologias extends OracleDB {

    protected $session;

    public function __construct (Session $session) {
        $this->session = $session;
    }

    public function getAllSites () {
        $sql = "SELECT DISTINCT SITE_NOMBRE FROM (SELECT DISTINCT SOURCE_SITE AS SITE_NOMBRE FROM DVL_U2000_ENLACES UNION SELECT DISTINCT SINK_SITE AS SITE_NOMBRE FROM DVL_U2000_ENLACES) WHERE SITE_NOMBRE IS NOT NULL ORDER BY SITE_NOMBRE";
        self::ejecutar($sql);
        return self::$results;
    }

    public function constelacion_estrella (string $site) {
        $gateways = [];
        $sql = "SELECT DISTINCT SITE_NOMBRE FROM DVL_NE WHERE GATEWAY_TYPE = 'Gateway' AND SITE_NOMBRE IS NOT NULL";
        self::ejecutar($sql);
        for($i = 0 ; $i < self::$nrows ; $i++) {
            $gateways[] = self::$results[$i]['SITE_NOMBRE'];
        }

        $response = [];
        $nodos = [];
        $sql = "SELECT * FROM DVL_ENLACES WHERE SOURCE_SITE = '$site' OR SINK_SITE = '$site'";
        self::ejecutar($sql);
        if(self::$nrows > 0) {
            $response['nodes'] = null;
            $response['edges'] = null;

            for($i = 0 ; $i < self::$nrows ; $i++) {
                if(!in_array(self::$results[$i]['SOURCE_SITE'],$nodos)) {
                    $nodos[] = self::$results[$i]['SOURCE_SITE'];
                    $response['nodes'][] = array(
                        'id' => self::$results[$i]['SOURCE_SITE'],
                        'label' => self::$results[$i]['SOURCE_SITE'],
                        'size' => (strcmp($site,self::$results[$i]['SOURCE_SITE']) == 0 ? 25 : 20),
                        'font' => array(
                            'size' => (strcmp($site,self::$results[$i]['SOURCE_SITE']) == 0 ? 24 : 22),
                            'color' => '#fff'
                        ),
                        'shape' => (in_array(self::$results[$i]['SOURCE_SITE'],$gateways) ? 'dot' : 'diamond'),
                        'color' => array(
                            'background' => (strcmp($site,self::$results[$i]['SOURCE_SITE']) == 0 ? '#4DD0E1' : '#0091EA'),
                            'border' => 'white',
                            'highlight' => (strcmp($site,self::$results[$i]['SOURCE_SITE']) == 0 ? '#4DD0E1' : '#0091EA'),
                        ),
                        'borderWidth' => 2
                    );
                }
                if(!in_array(self::$results[$i]['SINK_SITE'],$nodos)) {
                    $nodos[] = self::$results[$i]['SINK_SITE'];
                    $response['nodes'][] = array(
                        'id' => self::$results[$i]['SINK_SITE'],
                        'label' => self::$results[$i]['SINK_SITE'],
                        'size' => (strcmp($site,self::$results[$i]['SINK_SITE']) == 0 ? 25 : 20),
                        'font' => array(
                            'size' => (strcmp($site,self::$results[$i]['SINK_SITE']) == 0 ? 24 : 22),
                            'color' => '#fff'
                        ),
                        'shape' => (in_array(self::$results[$i]['SINK_SITE'],$gateways) ? 'dot' : 'diamond'),
                        'color' => array(
                            'background' => (strcmp($site,self::$results[$i]['SINK_SITE']) == 0 ? '#4DD0E1' : '#0091EA'),
                            'border' => 'white',
                            'highlight' => (strcmp($site,self::$results[$i]['SINK_SITE']) == 0 ? '#4DD0E1' : '#0091EA'),
                        ),
                        'borderWidth' => 2
                    );
                }

                $edge_color = null;
                $edge_label_color = null;
                if(empty(self::$results[$i]['RESOURCE_NAME'])) {
                    $edge_color = 'black';
                } else if(self::$results[$i]['UTILIZACION'] > 80) {
                    $edge_color = '#F44336';
                    $edge_label_color = '#FFEBEE';
                } else if(self::$results[$i]['UTILIZACION'] > 40) {
                    $edge_color = '#FFFF00';
                    $edge_label_color = '#FFF59D';
                } else if(self::$results[$i]['UTILIZACION'] > 1) {
                    $edge_color = '#76FF03';
                    $edge_label_color = '#C5E1A5';
                } else {
                    $edge_color = 'white';
                }
                $response['edges'][] = array(
                    'id' => self::$results[$i]['ENLACE_ID'],
                    'from' => self::$results[$i]['SOURCE_SITE'],
                    'to' => self::$results[$i]['SINK_SITE'],
                    'label' => self::$results[$i]['MEDIO'] . ' ( ' . self::$results[$i]['UTILIZACION'] . ' % )',
                    'font' => array(
                        'size' => 16,
                        'align' => 'top',
                        'color' => $edge_label_color,
                        'strokeWidth' => 0
                    ),
                    'borderWidth' => 0,
                    'color' => array(
                        'color' => $edge_color,
                        'highlight' => $edge_color
                    ),
                    'width' => 2
                );
            }

            //$response['nodes'] = (object) $response['nodes'];
            //$response['edges'] = (object) $response['edges'];
            $response['success'] = true;
        } else {
            $response['success'] = false;
        }

        echo json_encode($response);
    }

    public function constelacion_rutas (string $site) {
        $gateways = [];
        $sql = "SELECT DISTINCT SITE_NOMBRE FROM DVL_NE WHERE GATEWAY_TYPE = 'Gateway' AND SITE_NOMBRE IS NOT NULL";
        self::ejecutar($sql);
        for($i = 0 ; $i < self::$nrows ; $i++) {
            $gateways[] = self::$results[$i]['SITE_NOMBRE'];
        }

        $response = [];
        $nodos = [];
        $sql = "SELECT DISTINCT SOURCE_SITE,SINK_SITE,MEDIO,UTILIZACION,ENLACE_ID,RESOURCE_NAME FROM DVL_RUTAS NATURAL JOIN DVL_ENLACES WHERE ROUTE_ID IN (SELECT DISTINCT ROUTE_ID FROM DVL_RUTAS WHERE SOURCE_SITE = '$site' OR SINK_SITE = '$site')";
        self::ejecutar($sql);
        if(self::$nrows > 0) {
            $response['nodes'] = null;
            $response['edges'] = null;

            for($i = 0 ; $i < self::$nrows ; $i++) {
                if(!in_array(self::$results[$i]['SOURCE_SITE'],$nodos)) {
                    $nodos[] = self::$results[$i]['SOURCE_SITE'];
                    $response['nodes'][] = array(
                        'id' => self::$results[$i]['SOURCE_SITE'],
                        'label' => self::$results[$i]['SOURCE_SITE'],
                        'size' => (strcmp($site,self::$results[$i]['SOURCE_SITE']) == 0 ? 25 : 20),
                        'font' => array(
                            'size' => (strcmp($site,self::$results[$i]['SOURCE_SITE']) == 0 ? 24 : 22),
                            'color' => '#fff'
                        ),
                        'shape' => (in_array(self::$results[$i]['SOURCE_SITE'],$gateways) ? 'dot' : 'diamond'),
                        'color' => array(
                            'background' => (strcmp($site,self::$results[$i]['SOURCE_SITE']) == 0 ? '#4DD0E1' : '#0091EA'),
                            'border' => 'white',
                            'highlight' => (strcmp($site,self::$results[$i]['SOURCE_SITE']) == 0 ? '#4DD0E1' : '#0091EA'),
                        ),
                        'borderWidth' => 2
                    );
                }
                if(!in_array(self::$results[$i]['SINK_SITE'],$nodos)) {
                    $nodos[] = self::$results[$i]['SINK_SITE'];
                    $response['nodes'][] = array(
                        'id' => self::$results[$i]['SINK_SITE'],
                        'label' => self::$results[$i]['SINK_SITE'],
                        'size' => (strcmp($site,self::$results[$i]['SINK_SITE']) == 0 ? 25 : 20),
                        'font' => array(
                            'size' => (strcmp($site,self::$results[$i]['SINK_SITE']) == 0 ? 24 : 22),
                            'color' => '#fff'
                        ),
                        'shape' => (in_array(self::$results[$i]['SINK_SITE'],$gateways) ? 'dot' : 'diamond'),
                        'color' => array(
                            'background' => (strcmp($site,self::$results[$i]['SINK_SITE']) == 0 ? '#4DD0E1' : '#0091EA'),
                            'border' => 'white',
                            'highlight' => (strcmp($site,self::$results[$i]['SINK_SITE']) == 0 ? '#4DD0E1' : '#0091EA'),
                        ),
                        'borderWidth' => 2
                    );
                }

                $edge_color = null;
                $edge_label_color = null;
                if(empty(self::$results[$i]['RESOURCE_NAME'])) {
                    $edge_color = 'black';
                } else if(self::$results[$i]['UTILIZACION'] > 80) {
                    $edge_color = '#F44336';
                    $edge_label_color = '#FFEBEE';
                } else if(self::$results[$i]['UTILIZACION'] > 40) {
                    $edge_color = '#FFFF00';
                    $edge_label_color = '#FFF59D';
                } else if(self::$results[$i]['UTILIZACION'] > 1) {
                    $edge_color = '#76FF03';
                    $edge_label_color = '#C5E1A5';
                } else {
                    $edge_color = 'white';
                    $edge_label_color = 'white';
                }
                $response['edges'][] = array(
                    'id' => self::$results[$i]['ENLACE_ID'],
                    'from' => self::$results[$i]['SOURCE_SITE'],
                    'to' => self::$results[$i]['SINK_SITE'],
                    'label' => self::$results[$i]['MEDIO'] . ' ( ' . self::$results[$i]['UTILIZACION'] . ' % )',
                    'font' => array(
                        'align' => 'top',
                        'color' => $edge_label_color,
                        'strokeWidth' => 0,
                        'size' => 16
                    ),
                    'borderWidth' => 0,
                    'color' => array(
                        'color' => $edge_color,
                        'highlight' => $edge_color
                    ),
                    'width' => 2
                );
            }

            //$response['nodes'] = (object) $response['nodes'];
            //$response['edges'] = (object) $response['edges'];
            $response['success'] = true;
        } else {
            $response['success'] = false;
        }

        echo json_encode($response);
    }

    public function constelacion_sparkline (int $id) {
        $response = [];
        $response['success'] = true;

        $sql = "SELECT * FROM DVL_ENLACES WHERE ENLACE_ID = $id";
        self::ejecutar($sql);

        $response['info']['source-site'] = '<span class="text-success">SrcSite: </span>' . self::$results[0]['SOURCE_SITE'];
        $response['info']['source-ne'] = '<span class="text-success">SrcNE: </span>' . self::$results[0]['SOURCE_NE'];
        $response['info']['source-port'] = '<span class="text-success">SrcPort: </span>' . self::$results[0]['SOURCE_PORT'];

        $response['info']['sink-site'] = '<span class="text-primary">SnkSite: </span>' . self::$results[0]['SINK_SITE'];
        $response['info']['sink-ne'] = '<span class="text-primary">SnkNE: </span>' . self::$results[0]['SINK_NE'];
        $response['info']['sink-port'] = '<span class="text-primary">SnkPort: </span>' . self::$results[0]['SINK_PORT'];

        $response['info']['utilizacion'] = '<span class="text-warning">Utilizacion: </span>' . self::$results[0]['UTILIZACION'] . ' %';
        $response['info']['medio'] = '<span class="text-warning">Medio: </span>' . self::$results[0]['MEDIO'];
        $response['info']['capacidad'] = '<span class="text-warning">Capacidad: </span>' . self::$results[0]['CAPACIDAD'] . ' Mbps';
        $response['info']['resource-name'] = '<span class="text-warning">RsrcName: </span>' . self::$results[0]['RESOURCE_NAME'];

        $resource_name = self::$results[0]['RESOURCE_NAME'];

        $response['max'] = [];
        $response['avg'] = [];
        $response['min'] = [];

        $sql = "SELECT PORT_RX_BW_UTILIZATION_MAX,PORT_RX_BW_UTILIZATION_AVG,PORT_RX_BW_UTILIZATION_MIN FROM TX_HUAWEI.U2000_RTN_PTN_HH@DBGENLNK WHERE COLLECTION_TIME > TO_DATE('01/11/18','DD/MM/YY') AND RESOURCE_NAME = '$resource_name' ORDER BY COLLECTION_TIME ASC";
        self::ejecutar($sql);
        if(self::$nrows > 0) {
            for($i = 0 ; $i < self::$nrows ; $i++) {
                $response['max'][] = self::$results[$i]['PORT_RX_BW_UTILIZATION_MAX'];
                $response['avg'][] = self::$results[$i]['PORT_RX_BW_UTILIZATION_AVG'];
                $response['min'][] = self::$results[$i]['PORT_RX_BW_UTILIZATION_MIN'];
            }
        }

        echo json_encode($response);
    }
}