<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../libs/excel/autoload.php';
require __DIR__ . '/../libs/mail/PHPMailer/PHPMailerAutoload.php';

use Nette\Database\Connection;
use PhpOffice\PhpSpreadsheet\IOFactory;

date_default_timezone_set('America/Lima');
$today = date('d/m/Y');

$db = new Connection('oci:host=10.30.17.71;dbname=//10.30.17.71/SISREDMW.ENTEL.PE','SISREDMW','SISREDMW');

# Lista de Sites que son Gateways ( AGG - PAG )
$gateway_site = null;
# Lista de pares NE - Site al que pertenece
$ne_site = null;
# Lista de NEs
$ne_list = null;

$results = $db->fetchAll('SELECT DISTINCT SITE_NOMBRE FROM (SELECT SITE_NOMBRE FROM DVL_U2000_NE WHERE (NE_TYPE LIKE \'%RTN%\' AND SITE_NOMBRE IS NOT NULL) AND (GATEWAY_TYPE = \'Gateway\' AND FECHA = TO_DATE(?,\'DD/MM/YYYY\')) UNION SELECT SITE_NOMBRE FROM DVL_AGG UNION SELECT SITE_NOMBRE FROM DVL_PAG WHERE HOSTNAME LIKE \'%PAG%\' AND ESTADO = \'ON-Air\') ORDER BY SITE_NOMBRE',$today);
for ($i = 0 ; $i < count($results) ; $i++)  $gateway_site[] = $results[$i]['SITE_NOMBRE'];

$results = $db->fetchPairs('SELECT NE_NAME,SITE_NOMBRE FROM DVL_U2000_NE WHERE NE_TYPE LIKE \'%RTN%\' AND ( SITE_NOMBRE IS NOT NULL AND FECHA = TO_DATE(?,\'DD/MM/YYYY\'))',$today);
$ne_site = $results;
$ne_list = array_keys($ne_site);

$file = 'tunnel.xlsx'; $workbook = IOFactory::load($file); $worksheet = $workbook->getActiveSheet()->toArray(null, true, true, false);

echo 'Iniciando...';

$tunneles_validos = [];
$tunneles_sin_sentido = [];

for($t = 3 ; $t < count($worksheet) ; $t++) {
    echo "Tunnel... $t\n";
    echo $worksheet[$t][9].' <-> '.$worksheet[$t][15]."\n";

    $tunnel_route = [];
    $tunnel_route['OID']    = $worksheet[$t][2];
    $tunnel_route['NAME']   = $worksheet[$t][3];
    $tunnel_route['ID']     = $worksheet[$t][4];
    $tunnel_route['NE']     = explode("\n", $worksheet[$t][9]   . "\n" . (empty($worksheet[$t][24]) ? "" : $worksheet[$t][24] . "\n" ) . $worksheet[$t][15]);
    $tunnel_route['IN']     = explode("\n", ""                    . "\n" . (empty($worksheet[$t][25]) ? "" : $worksheet[$t][25] . "\n" ) . $worksheet[$t][16]);
    $tunnel_route['OUT']    = explode("\n", $worksheet[$t][10]  . "\n" . (empty($worksheet[$t][27]) ? "" : $worksheet[$t][27] . "\n" ) . "");

    # Validar IDUs
    if(!in_array($tunnel_route['NE'][0],$ne_list) && !in_array($tunnel_route['NE'][count($tunnel_route['NE'])-1],$ne_list)) {
        continue;
    } else {
        # Tunneles que empiezan y terminan por elementos que están en el NE_LIST
        # Invertimos la ruta en caso sea necesario
        if(!in_array($tunnel_route['NE'][0],$ne_list)) {
            $tunnel_route['NE'] = array_reverse($tunnel_route['NE']);
            $temp = $tunnel_route['IN'];
            $tunnel_route['IN'] = array_reverse($tunnel_route['OUT']);
            $tunnel_route['OUT'] = array_reverse($temp);
        }
        $ne = 0;
        for(; $ne < count($tunnel_route['NE']) ; $ne++)
            if(!in_array($tunnel_route['NE'][$ne],$ne_list))
                break;
        $tunnel_route['OUT'][$ne-1] = "";
        $temp = count($tunnel_route['NE']);
        for(; $ne < $temp ; $ne++) {
            unset($tunnel_route['NE'][$ne]);
            unset($tunnel_route['IN'][$ne]);
            unset($tunnel_route['OUT'][$ne]);
        }
    }

    if(count($tunnel_route['NE']) < 2)
        continue;

    if(in_array($ne_site[$tunnel_route['NE'][0]],$gateway_site) && in_array($ne_site[$tunnel_route['NE'][count($tunnel_route['NE'])-1]],$gateway_site))
        continue;
    else if(in_array($ne_site[$tunnel_route['NE'][0]],$gateway_site)) {
        $tunnel_route['NE'] = array_reverse($tunnel_route['NE']);
        $temp = $tunnel_route['IN'];
        $tunnel_route['IN'] = array_reverse($tunnel_route['OUT']);
        $tunnel_route['OUT'] = array_reverse($temp);
    } else if(!in_array($ne_site[$tunnel_route['NE'][count($tunnel_route['NE'])-1]],$gateway_site)) {
        $tunnel_route['NE'] = implode("\n",$tunnel_route['NE']);
        $tunnel_route['IN'] = implode("\n",$tunnel_route['IN']);
        $tunnel_route['OUT'] = implode("\n",$tunnel_route['OUT']);
        $tunneles_sin_sentido[] = $tunnel_route;
        continue;
    }

    $tunnel_route['NE'] = implode("\n",$tunnel_route['NE']);
    $tunnel_route['IN'] = implode("\n",$tunnel_route['IN']);
    $tunnel_route['OUT'] = implode("\n",$tunnel_route['OUT']);

    $tunneles_validos[] = $tunnel_route;
}

/*
 *
 * NEW PART
 *
 */

$enlaces = [];
$enlaces_id = 1;

$rutas = [];
$rutas_id = 1;

$tunneles_enlace = [];
$tunneles_ruta = [];

$rutas_revisadas = [];
$rutas_revisadas_id = [];

# Los tunneles válidos ya se encuentran en sentido de Site al -> PAG o AGG
for($i = 0 ; $i < count($tunneles_validos) ; $i++) {
    echo "\n".$tunneles_validos[$i]['NE']."\n\n";
    $ne_saltos = explode("\n",$tunneles_validos[$i]['NE']);
    $in_saltos = explode("\n",$tunneles_validos[$i]['IN']);
    $out_saltos = explode("\n",$tunneles_validos[$i]['OUT']);

    $ruta_a_revisar = '';
    for($n = 0 ; $n < count($ne_saltos)-1 ; $n++) {
        if(strcmp($ne_site[$ne_saltos[$n]],$ne_site[$ne_saltos[$n+1]]) == 0)
            continue;
        else {
            $enlace = $ne_saltos[$n] . $out_saltos[$n] . $in_saltos[$n + 1] . $ne_saltos[$n + 1];
            if (!in_array($enlace,array_keys($enlaces))) {
                $enlaces[$enlace] = [];

                $enlaces[$enlace]['ENLACE_ID'] = $enlaces_id;

                $enlaces[$enlace]['SOURCE_SITE'] = $ne_site[$ne_saltos[$n]];
                $enlaces[$enlace]['SOURCE_NE'] = $ne_saltos[$n];
                $enlaces[$enlace]['SOURCE_PORT'] = $out_saltos[$n];

                $enlaces[$enlace]['SINK_SITE'] = $ne_site[$ne_saltos[$n+1]];
                $enlaces[$enlace]['SINK_NE'] = $ne_saltos[$n+1];
                $enlaces[$enlace]['SINK_PORT'] = $in_saltos[$n+1];

                $enlaces_id++;
            }
            $tunneles_enlace[] = array(
                'OID' => $tunneles_validos[$i]['OID'],
                'NAME' => $tunneles_validos[$i]['NAME'],
                'ID' => $tunneles_validos[$i]['ID'],
                'ENLACE_ID' => $enlaces[$enlace]['ENLACE_ID']
            );
            $ruta_a_revisar .= $enlace."\n";
        }
    }
    if(!in_array($ruta_a_revisar,$rutas_revisadas)) {
        $rutas_revisadas[] = $ruta_a_revisar;
        $rutas_revisadas_id[$ruta_a_revisar] = $rutas_id;
        $temp = explode("\n",$ruta_a_revisar);
        for($position = 1 ; $position < count($temp) ; $position++) {
            $rutas[] = array(
                'RUTA_ID' => $rutas_id,
                'POSITION' => $position,
                'ENLACE_ID' => $enlaces[$temp[$position-1]]['ENLACE_ID']
            );
        }
        $rutas_id++;
    }
    $tunneles_ruta[] = array(
        'OID' => $tunneles_validos[$i]['OID'],
        'NAME' => $tunneles_validos[$i]['NAME'],
        'ID' => $tunneles_validos[$i]['ID'],
        'RUTA_ID' => $rutas_revisadas_id[$ruta_a_revisar]
    );
}


# Los tunneles que no tienen dirección seran procesados de forma similiar al bloque anterior
for($i = 0 ; $i < count($tunneles_sin_sentido) ; $i++) {
    echo "\n".$tunneles_sin_sentido[$i]['NE']."\n\n";
    $ne_saltos = explode("\n",$tunneles_sin_sentido[$i]['NE']);
    $in_saltos = explode("\n",$tunneles_sin_sentido[$i]['IN']);
    $out_saltos = explode("\n",$tunneles_sin_sentido[$i]['OUT']);

    $know_exists = false;
    $enlace = null;
    for($n = 0 ; $n < count($ne_saltos)-1 ; $n++) {
        if (strcmp($ne_site[$ne_saltos[$n]], $ne_site[$ne_saltos[$n + 1]]) == 0)
            continue;
        else {
            $enlace = $ne_saltos[$n] . $out_saltos[$n] . $in_saltos[$n + 1] . $ne_saltos[$n + 1];
            $enlace_inverso = $ne_saltos[$n + 1] . $in_saltos[$n + 1] . $out_saltos[$n] . $ne_saltos[$n];
            if (!in_array($enlace, array_keys($enlaces)) && !in_array($enlace_inverso, array_keys($enlaces))) {
                $know_exists = true;
                break;
            }
        }
    }
    if($know_exists) {
        $tunneles_sin_sentido[$i]['EXISTS'] = $enlace;


        /*
         * COMENTAR EN PRIMERA ITERACIÓN PARA CREAR EL TEMP GATEWAYS
         * ELIMINAR EL COMENTARIO EN SEGUNDA ITERACIÓN
         */
        # GATEWAYS PARA LOS TUNNELES A PROCESAR

        $temp_gateways = [
            '0100613_LI_Chicama to Cartavio',
            '0100834_IC_Hospital_Regional to Pozo Victoria',
            '0101009_LA_Picsi to Moshoqueque',
            '0101711_PI_El_Alto to Lobitos',
            '0101311_CS_Churcana to Salida Churcana',
            '0102325_SM_Juanjui to Sacanche',
            '0103675_CS_Quillabamba_Ciudad to Wanchaq',
            '0105452_LM_Unger to Rosa de America',
            '0100609_LI_Huanchaco to Fabricas'
        ];

        if(in_array($ne_saltos[0],$temp_gateways)) {
            $ne_saltos = array_reverse($ne_saltos);
            $temp = $in_saltos;
            $in_saltos = array_reverse($out_saltos);
            $out_saltos = array_reverse($temp);
        }

        $ruta_a_revisar = '';
        for($n = 0 ; $n < count($ne_saltos)-1 ; $n++) {
            if(strcmp($ne_site[$ne_saltos[$n]],$ne_site[$ne_saltos[$n+1]]) == 0)
                continue;
            else {
                $enlace = $ne_saltos[$n] . $out_saltos[$n] . $in_saltos[$n + 1] . $ne_saltos[$n + 1];
                if (!in_array($enlace,array_keys($enlaces))) {
                    $enlaces[$enlace] = [];

                    $enlaces[$enlace]['ENLACE_ID'] = $enlaces_id;

                    $enlaces[$enlace]['SOURCE_SITE'] = $ne_site[$ne_saltos[$n]];
                    $enlaces[$enlace]['SOURCE_NE'] = $ne_saltos[$n];
                    $enlaces[$enlace]['SOURCE_PORT'] = $out_saltos[$n];

                    $enlaces[$enlace]['SINK_SITE'] = $ne_site[$ne_saltos[$n+1]];
                    $enlaces[$enlace]['SINK_NE'] = $ne_saltos[$n+1];
                    $enlaces[$enlace]['SINK_PORT'] = $in_saltos[$n+1];

                    $enlaces_id++;
                }
                $tunneles_enlace[] = array(
                    'OID' => $tunneles_sin_sentido[$i]['OID'],
                    'NAME' => $tunneles_sin_sentido[$i]['NAME'],
                    'ID' => $tunneles_sin_sentido[$i]['ID'],
                    'ENLACE_ID' => $enlaces[$enlace]['ENLACE_ID']
                );
                $ruta_a_revisar .= $enlace."\n";
            }
        }
        if(!in_array($ruta_a_revisar,$rutas_revisadas)) {
            $rutas_revisadas[] = $ruta_a_revisar;
            $rutas_revisadas_id[$ruta_a_revisar] = $rutas_id;
            $temp = explode("\n",$ruta_a_revisar);
            for($position = 1 ; $position < count($temp) ; $position++) {
                $rutas[] = array(
                    'RUTA_ID' => $rutas_id,
                    'POSITION' => $position,
                    'ENLACE_ID' => $enlaces[$temp[$position-1]]['ENLACE_ID']
                );
            }
            $rutas_id++;
        }
        $tunneles_ruta[] = array(
            'OID' => $tunneles_sin_sentido[$i]['OID'],
            'NAME' => $tunneles_sin_sentido[$i]['NAME'],
            'ID' => $tunneles_sin_sentido[$i]['ID'],
            'RUTA_ID' => $rutas_revisadas_id[$ruta_a_revisar]
        );
        /*
         * COMENTAR HASTA ESTE PUNTO
         */
    }
    else
        $tunneles_sin_sentido[$i]['EXISTS'] = null;
}

$json = json_encode($tunneles_validos);
$file = fopen('tunneles_validos.json', 'w');
fwrite($file, $json);
fclose($file);

$json = json_encode($tunneles_sin_sentido);
$file = fopen('tunneles_sin_sentido.json', 'w');
fwrite($file, $json);
fclose($file);

$json = json_encode($rutas);
$file = fopen('rutas.json', 'w');
fwrite($file, $json);
fclose($file);

$json = json_encode(array_values($enlaces));
$file = fopen('enlaces.json', 'w');
fwrite($file, $json);
fclose($file);

$json = json_encode($tunneles_enlace);
$file = fopen('tunneles_enlace.json', 'w');
fwrite($file, $json);
fclose($file);

$json = json_encode($tunneles_ruta);
$file = fopen('tunneles_ruta.json', 'w');
fwrite($file, $json);
fclose($file);