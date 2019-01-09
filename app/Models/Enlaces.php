<?php

namespace Application\Models;


use Application\Models\OracleDB;
use Aura\Session\Session;

class Enlaces extends OracleDB {

    protected $session;

    public function __construct (Session $session) {
        $this->session = $session;
    }

    public function getAll () {
        $sql = "SELECT SOURCE_SITE,SINK_SITE,CAPACIDAD,MEDIO,UTILIZACION,SOURCE_PORT||' <> '||SINK_PORT AS LINK_PORTS,RESOURCE_NAME FROM DVL_ENLACES";
        self::ejecutar($sql);
        return self::$results;
    }
}