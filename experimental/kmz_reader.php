<?php

$xml_data = file_get_contents("kmz/analisis.kml");
//file_put_contents("/tmp/kmz_temp",$data);
//ob_start();
//passthru('unzip -p /tmp/kmz_temp');
//$xml_data = ob_get_clean();

//header("Content-type: text/xml");
//echo $xml_data;

$xml = simplexml_load_string($xml_data, "SimpleXMLElement", LIBXML_NOCDATA);

echo json_encode($xml);

/*
$json = json_encode($xml);
$array = json_decode($json,TRUE);

$placemark = $array['Document']['Folder']['Placemark'];
$sites = [];
for($i = 0 ; $i < count($placemark) ; $i++) {
    $sites[] = array(
        "NAME" => $placemark[$i]['name'],
        "LATITUD" => explode(',',$placemark[$i]['Point']['coordinates'])[1],
        "LONGITUD" => explode(',',$placemark[$i]['Point']['coordinates'])[0],
    );
}

echo json_encode($sites);