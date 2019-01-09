<?php
set_time_limit(0);
date_default_timezone_set('America/Lima');

const DB_USER = 'SISREDMW';
const DB_PASS = 'SISREDMW';
const DB_CONNSTRING = '//10.30.17.71/SISREDMW.ENTEL.PE';
$results = [];
$nrows = 0;
$oci_execute = false;
$oci_error = '';
$conn = oci_connect(DB_USER, DB_PASS, DB_CONNSTRING);

$community = 'ngn3NT3LP3RU-RO';
$pag = null;
$is_time = null;

$granularity = 5;
$timeout = 8000;
snmp_set_oid_output_format(SNMP_OID_OUTPUT_SUFFIX);

/*
while(true) {

    $nowtime = intval(date('Hi'));

    if(($nowtime+1)%$granularity == 0 && is_null($is_time)) {*/
        $pag = null;
        $sql = "SELECT HOSTNAME FROM DVL_PAG WHERE HOSTNAME LIKE '%NVG8%PAG%'";
        $prs = oci_parse($conn,$sql); $r = oci_execute($prs); $nrows = oci_fetch_all($prs, $results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
        for($i = 0 ; $i < $nrows ; $i++) {
            $pag[] = $results[$i]['HOSTNAME'];
        }
        $is_time = true;/*
    }

    if($nowtime%$granularity == 0 && $is_time) {*/
        $dir = date('Ymd');
        $filename = date('YmdHi');
        $data = null;
        $status = null;

        if (!file_exists("/var/www/new_inweb/inweb/scripts/data/pag/$dir") && !is_dir("/var/www/new_inweb/inweb/scripts/data/pag/$dir")) {
            mkdir("/var/www/new_inweb/inweb/scripts/data/pag/$dir",0775);
        }
        if (!file_exists("/var/www/new_inweb/inweb/scripts/data/pag_status/$dir") && !is_dir("/var/www/new_inweb/inweb/scripts/data/pag_status/$dir")) {
            mkdir("/var/www/new_inweb/inweb/scripts/data/pag_status/$dir",0775);
        }
        echo var_dump($pag);
        for($i = 0 ; $i < count($pag) ; $i++) {
            $data[$pag[$i]] = null;
            $datetime = date('d/m/Y H:i');
            echo $pag[$i]."\n";
            $oid = 'ifIndex';
            $output = snmp2_walk($pag[$i], $community, $oid);

            if($output === false) {
                $status[$pag[$i]] = "offline";
            } else {
                $status[$pag[$i]] = "online";

                for ($j = 0; $j < count($output); $j++) {
                    $data[$pag[$i]][intval(str_replace("INTEGER: ", "", $output[$j]))] = null;
                }

                foreach ($data[$pag[$i]] as $ifIndex => $details) {
                    $data[$pag[$i]][$ifIndex]['ifDescr'] = str_replace("STRING: ", "", snmp2_get($pag[$i], $community, 'ifDescr.' . $ifIndex));
                    $data[$pag[$i]][$ifIndex]['ifAlias'] = str_replace("STRING: ", "", snmp2_get($pag[$i], $community, 'ifAlias.' . $ifIndex));
                    $data[$pag[$i]][$ifIndex]['ifType'] = str_replace("INTEGER: ", "", snmp2_get($pag[$i], $community, 'ifType.' . $ifIndex));
                    $data[$pag[$i]][$ifIndex]['ifOperStatus'] = str_replace("INTEGER: ", "", snmp2_get($pag[$i], $community, 'ifOperStatus.' . $ifIndex));
                    $data[$pag[$i]][$ifIndex]['ifMtu'] = str_replace("INTEGER: ", "", snmp2_get($pag[$i], $community, 'ifMtu.' . $ifIndex));
                    $data[$pag[$i]][$ifIndex]['ifPhysAddress'] = str_replace("STRING: ", "", snmp2_get($pag[$i], $community, 'ifPhysAddress.' . $ifIndex));
                    $data[$pag[$i]][$ifIndex]['ifHCInOctets'] = str_replace("Counter64: ", "", snmp2_get($pag[$i], $community, 'ifHCInOctets.' . $ifIndex));
                    $data[$pag[$i]][$ifIndex]['ifHCOutOctets'] = str_replace("Counter64: ", "", snmp2_get($pag[$i], $community, 'ifHCOutOctets.' . $ifIndex));
                    $data[$pag[$i]][$ifIndex]['datetime'] = $datetime;
                }

            }
            echo $status[$pag[$i]]."\n\n";
        }

        $file = fopen("/var/www/new_inweb/inweb/scripts/data/pag/$dir/$filename.json",'w');
        fwrite($file, json_encode($data));
        fclose($file);

        $file = fopen("/var/www/new_inweb/inweb/scripts/data/pag_status/$dir/$filename.json",'w');
        fwrite($file, json_encode($status));
        fclose($file);

        $is_time = null;

        /*
    }
}