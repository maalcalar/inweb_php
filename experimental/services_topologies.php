<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../libs/excel/autoload.php';
require __DIR__ . '/../libs/mail/PHPMailer/PHPMailerAutoload.php';

use Nette\Database\Connection;
use PhpOffice\PhpSpreadsheet\IOFactory;

$db = new Connection('oci:host=10.30.17.71;dbname=//10.30.17.71/SISREDMW.ENTEL.PE','SISREDMW','SISREDMW');

/*
 *
 *
 *
 *
 *  PARTE 1
 *
 *
 *
 */

/*
 * ETH
 */

$file = 'services.xlsx'; $workbook = IOFactory::load($file); $worksheet = $workbook->getSheetByName('eth')->toArray(null, true, true, false);

$servicios = [];
$tunneles_servicios = [];
$tunneles_disponibles = [];

$servicio_id = 1;

for($t = 3 ; $t < count($worksheet) ; $t++) {
    echo $worksheet[$t][3]."\n";
    if(strcmp($worksheet[$t][3],'') == 0 || is_null($worksheet[$t][3]) || empty($worksheet[$t][3]))
        continue;
    else {
        $servicios[] = array(
            'SERVICIO_ID' => $servicio_id,
            'OID' => $worksheet[$t][2],
            'SERVICE_NAME' => $worksheet[$t][3],
            'SERVICE_ID' => $worksheet[$t][4],
            'SOURCE_NE' => $worksheet[$t][12],
            'SOURCE_PORT' => $worksheet[$t][13],
            'SOURCE_PORT_DESCRIPTION' => $worksheet[$t][14],
            'SOURCE_VLAN' => $worksheet[$t][16],
            'SINK_NE' => $worksheet[$t][21],
            'SINK_PORT' => $worksheet[$t][22],
            'SINK_PORT_DESCRIPTION' => $worksheet[$t][23],
            'SINK_VLAN' => $worksheet[$t][25]
        );
        $temp = explode("\n",$worksheet[$t][35]);
        for($x = 0 ; $x < count($temp) ; $x++) {
            if(strcmp($temp[$x],'') == 0 || is_null($temp[$x]) || empty($temp[$x]))
                continue;
            $tunneles_servicios[] = array(
                'TUNNEL_NAME' => $temp[$x],
                'SERVICIO_ID' => $servicio_id
            );
            $tunneles_disponibles[] = $temp[$x];
        }
        $servicio_id++;
    }
}

/*
 * CES
 */

$file = 'services.xlsx'; $workbook = IOFactory::load($file); $worksheet = $workbook->getSheetByName('ces')->toArray(null, true, true, false);

for($t = 3 ; $t < count($worksheet) ; $t++) {
    echo $worksheet[$t][3]."\n";
    if(strcmp($worksheet[$t][3],'') == 0 || is_null($worksheet[$t][3]) || empty($worksheet[$t][3]))
        continue;
    else {
        $servicios[] = array(
            'SERVICIO_ID' => $servicio_id,
            'OID' => $worksheet[$t][2],
            'SERVICE_NAME' => $worksheet[$t][3],
            'SERVICE_ID' => $worksheet[$t][4],
            'SOURCE_NE' => $worksheet[$t][12],
            'SOURCE_PORT' => $worksheet[$t][13],
            'SOURCE_PORT_DESCRIPTION' => $worksheet[$t][14],
            'SOURCE_VLAN' => '',
            'SINK_NE' => $worksheet[$t][20],
            'SINK_PORT' => $worksheet[$t][21],
            'SINK_PORT_DESCRIPTION' => $worksheet[$t][22],
            'SINK_VLAN' => ''
        );
        $temp = explode("\n",$worksheet[$t][33]);
        for($x = 0 ; $x < count($temp) ; $x++) {
            if(strcmp($temp[$x],'') == 0 || is_null($temp[$x]) || empty($temp[$x]))
                continue;
            $tunneles_servicios[] = array(
                'TUNNEL_NAME' => $temp[$x],
                'SERVICIO_ID' => $servicio_id
            );
            $tunneles_disponibles[] = $temp[$x];
        }
        $servicio_id++;
    }
}

$json = json_encode($tunneles_servicios);
$file = fopen('tunneles_servicios.json', 'w');
fwrite($file, $json);
fclose($file);

$json = json_encode($servicios);
$file = fopen('servicios.json', 'w');
fwrite($file, $json);
fclose($file);