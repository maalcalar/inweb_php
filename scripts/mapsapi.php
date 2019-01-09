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

function getCoordinates($address,$locality,$administrative_area){

    $address = str_replace(" ", "+", $address); // replace all the white space with "+" sign to match with google search pattern
    $locality = str_replace(" ", "+", $locality);
    $administrative_area = str_replace(" ", "+", $administrative_area);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?language=es&address=$address&components=administrative_area:$administrative_area|locality:$locality|country:PE&key=AIzaSyCtdryLDHIql6lmW0qsy7HhLXXCGBtDR_U";

    $proxy = '10.30.17.74:443';

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

    $output = curl_exec($ch);

    curl_close($ch);

    return $output;
}

$sql = "SELECT FACTIBILIDAD,DISTRITO,DIRECCION,DEPARTAMENTO,DIRECCION ||', '|| DISTRITO ||', '|| DEPARTAMENTO AS ADDRESS FROM DVL_SAF_FO ORDER BY FACTIBILIDAD";
$prs = oci_parse($conn,$sql); $r = oci_execute($prs); $nrows = oci_fetch_all($prs, $results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);

$export = null;

for($i = 0 ; $i < $nrows ; $i++) {
    $location = json_decode(getCoordinates($results[$i]['DIRECCION'],$results[$i]['DISTRITO'],$results[$i]['DEPARTAMENTO']), true);
    if(strcmp($location['status'],'OK') == 0) {
        for($j = 0 ; $j < count($location['results']) ; $j++) {
            $location_address = $location['results'][$j]['formatted_address'];
            $location_lat = floatval($location['results'][$j]['geometry']['location']['lat']);
            $location_lng = floatval($location['results'][$j]['geometry']['location']['lng']);

            $export[] = array(
                'FACTIBILIDAD' => $results[$i]['FACTIBILIDAD'],
                'FORMATTED_ADDRESS' => $location_address,
                'LAT' => $location_lat,
                'LNG' => $location_lng
            );/*
            $sql = "INSERT INTO DVL_SAF_FO_LOCATIONS (FACTIBILIDAD,FORMATTED_ADDRESS,LAT,LNG) VALUES (".$results[$i]['FACTIBILIDAD'].",'$location_address',$location_lat,$location_lng)";
            $prs = oci_parse($conn,$sql); $r = oci_execute($prs);*/
        }
        $file = fopen("factibilidad.json",'w');
        fwrite($file, json_encode($export));
        fclose($file);
    } else {

        //echo '['.$results[$i]['FACTIBILIDAD'].'] Not Found';
    }
}