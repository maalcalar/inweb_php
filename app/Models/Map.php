<?php

namespace Application\Models;


use Application\Models\OracleDB;
use Aura\Session\Session;

class Map extends OracleDB {

    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function agregadores() {

        $coords = [];

        $response = [];
        $sql = "SELECT SITE_NOMBRE,LATITUD,LONGITUD FROM DVL_STATUS_SITE WHERE SITE_NOMBRE IS NOT NULL AND (LATITUD IS NOT NULL AND LONGITUD IS NOT NULL)";
        self::ejecutar($sql);
        for ($i = 0 ; $i < self::$nrows ; $i++) {
            $response['sites'][] = [self::$results[$i]['SITE_NOMBRE'],self::$results[$i]['LATITUD'],self::$results[$i]['LONGITUD'],'A','#1A237E'];
            $coords[self::$results[$i]['SITE_NOMBRE']]['LATITUD'] = self::$results[$i]['LATITUD'];
            $coords[self::$results[$i]['SITE_NOMBRE']]['LONGITUD'] = self::$results[$i]['LONGITUD'];
        }
        /*
        $sql = "SELECT SITE_NOMBRE,LATITUD,LONGITUD FROM DVL_STATUS_SITE WHERE SITE_NOMBRE IN (SELECT SITE_NOMBRE FROM DVL_AGG MINUS SELECT SITE_NOMBRE FROM DVL_PAG WHERE HOSTNAME LIKE '%PAG%')";
        self::ejecutar($sql);
        for ($i = 0 ; $i < self::$nrows ; $i++) {
            $response['sites'][] = [self::$results[$i]['SITE_NOMBRE'],self::$results[$i]['LATITUD'],self::$results[$i]['LONGITUD'],'A','#1A237E'];
            $coords[self::$results[$i]['SITE_NOMBRE']]['LATITUD'] = self::$results[$i]['LATITUD'];
            $coords[self::$results[$i]['SITE_NOMBRE']]['LONGITUD'] = self::$results[$i]['LONGITUD'];
        }
        $sql = "SELECT SITE_NOMBRE,LATITUD,LONGITUD FROM DVL_STATUS_SITE WHERE SITE_NOMBRE IN (SELECT SITE_NOMBRE FROM DVL_PAG WHERE HOSTNAME LIKE '%PAG%' MINUS SELECT SITE_NOMBRE FROM DVL_AGG)";
        self::ejecutar($sql);
        for ($i = 0 ; $i < self::$nrows ; $i++) {
            $response['sites'][] = [self::$results[$i]['SITE_NOMBRE'],self::$results[$i]['LATITUD'],self::$results[$i]['LONGITUD'],'P','#304FFE'];
            $coords[self::$results[$i]['SITE_NOMBRE']]['LATITUD'] = self::$results[$i]['LATITUD'];
            $coords[self::$results[$i]['SITE_NOMBRE']]['LONGITUD'] = self::$results[$i]['LONGITUD'];
        }
        $sql = "SELECT SITE_NOMBRE,LATITUD,LONGITUD FROM DVL_STATUS_SITE WHERE SITE_NOMBRE IN (SELECT SITE_NOMBRE FROM DVL_PAG WHERE HOSTNAME LIKE '%PAG%' INTERSECT SELECT SITE_NOMBRE FROM DVL_AGG)";
        self::ejecutar($sql);
        for ($i = 0 ; $i < self::$nrows ; $i++) {
            $response['sites'][] = [self::$results[$i]['SITE_NOMBRE'],self::$results[$i]['LATITUD'],self::$results[$i]['LONGITUD'],'A/P','#8C9EFF'];
            $coords[self::$results[$i]['SITE_NOMBRE']]['LATITUD'] = self::$results[$i]['LATITUD'];
            $coords[self::$results[$i]['SITE_NOMBRE']]['LONGITUD'] = self::$results[$i]['LONGITUD'];
        }*/

        //$sql = "SELECT * FROM DVL_ENLACES WHERE SOURCE_SITE IN ( SELECT SITE_NOMBRE FROM DVL_AGG UNION SELECT SITE_NOMBRE FROM DVL_PAG ) AND SINK_SITE IN ( SELECT SITE_NOMBRE FROM DVL_AGG UNION SELECT SITE_NOMBRE FROM DVL_PAG )";

        $sql = "SELECT * FROM DVL_ENLACES WHERE UTILIZACION IS NOT NULL AND ((SOURCE_SITE IN (SELECT SITE_NOMBRE FROM DVL_STATUS_SITE WHERE SITE_NOMBRE IS NOT NULL) AND SINK_SITE IN (SELECT SITE_NOMBRE FROM DVL_STATUS_SITE WHERE SITE_NOMBRE IS NOT NULL)) AND (CAPACIDAD IS NOT NULL AND MEDIO IS NOT NULL))";
        self::ejecutar($sql);
        for ($i = 0 ; $i < self::$nrows ; $i++) {

            if(self::$results[$i]['UTILIZACION'] > 80) {
                $edge_color = '#F44336';
            } else if(self::$results[$i]['UTILIZACION'] > 40) {
                $edge_color = '#FFFF00';
            } else if(self::$results[$i]['UTILIZACION'] > 1) {
                $edge_color = '#76FF03';
            } else {
                $edge_color = 'white';
            }

            $response['links'][] = [
                array(
                    array('lat' => floatval($coords[self::$results[$i]['SOURCE_SITE']]['LATITUD']), 'lng' => floatval($coords[self::$results[$i]['SOURCE_SITE']]['LONGITUD'])),
                    array('lat' => floatval($coords[self::$results[$i]['SINK_SITE']]['LATITUD']), 'lng' => floatval($coords[self::$results[$i]['SINK_SITE']]['LONGITUD'])),
                ),
                self::$results[$i]['SOURCE_SITE'],
                self::$results[$i]['SINK_SITE'],
                self::$results[$i]['CAPACIDAD'],
                self::$results[$i]['MEDIO'],
                self::$results[$i]['UTILIZACION'],
                $edge_color
            ];
        }
        echo json_encode($response);
    }
}
