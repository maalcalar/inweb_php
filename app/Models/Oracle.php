<?php

namespace Application\Models;

use Nette\Database\Connection;

abstract class Oracle {
    protected $database;

    public function connect () {
        $this->database = new Connection('oci:host=10.30.17.71;dbname=//10.30.17.71/SISREDMW.ENTEL.PE','SISREDMW','SISREDMW');
    }
}