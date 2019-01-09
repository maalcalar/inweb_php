<?php

namespace Application\Models;

abstract class OracleDB {

    protected const DB_USER = 'SISREDMW';
    protected const DB_PASS = 'SISREDMW';
    protected const DB_CONNSTRING = '//10.30.17.71/SISREDMW.ENTEL.PE';

    protected static $connect;
    protected static $sql;
    protected static $parse;

    public static $results = array();
    public static $nrows = 0;
    public static $oci_execute = false;
    public static $oci_error = '';

    protected static function conectar () {
        self::$connect = oci_connect(self::DB_USER, self::DB_PASS, self::DB_CONNSTRING);
    }

    protected static function process () {
        self::$parse = oci_parse(self::$connect, self::$sql);
        $r = oci_execute(self::$parse);
        if(!$r) {
            $e = oci_error(self::$parse);
            self::$oci_error = $e['message'];
            self::$oci_execute = false;
        } else {
            self::$oci_error = '';
            self::$oci_execute = true;
        }
    }

    protected static function get () {
        self::$nrows = oci_fetch_all(self::$parse, self::$results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
        //OCI_FETCHSTATEMENT_BY_COLUMN + OCI_ASSOC : byDefault
    }

    protected static function finalizar() {
        oci_commit(self::$connect);
        oci_free_statement(self::$parse);
        oci_close(self::$connect);
    }

    public static function ejecutar ($sql) {
        self::$sql = $sql;
        self::conectar();
        self::process();
        self::get();
        self::finalizar();
    }

    public static function ejecutar_save ($sql) {
        self::$sql = $sql;
        self::conectar();
        self::process();
        self::finalizar();
    }
}