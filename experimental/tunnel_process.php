<?php

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../libs/excel/autoload.php';
require __DIR__ . '/../libs/mail/PHPMailer/PHPMailerAutoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

const DB_USER = 'SISREDMW';
const DB_PASS = 'SISREDMW';
const DB_CONNSTRING = '//10.30.17.71/SISREDMW.ENTEL.PE';

$results = [];
$nrows = 0;
$oci_execute = false;
$oci_error = '';
$conn = oci_connect(DB_USER, DB_PASS, DB_CONNSTRING);

$gateways_site = [];
$sql = "SELECT DISTINCT SITE_NOMBRE FROM (SELECT DISTINCT SITE_NOMBRE FROM DVL_NE WHERE GATEWAY_TYPE = 'Gateway' UNION SELECT SITE_NOMBRE FROM DVL_AGG UNION SELECT SITE_NOMBRE FROM DVL_PAG) WHERE SITE_NOMBRE IS NOT NULL ORDER BY SITE_NOMBRE";
$prs = oci_parse($conn,$sql); $r = oci_execute($prs); $nrows = oci_fetch_all($prs, $results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
for($i = 0 ; $i < $nrows ; $i++)  $gateways_site[] = $results[$i]['SITE_NOMBRE'];

$ne_site = [];
$ne_list = [];
$sql = "SELECT NE_NAME,SITE_NOMBRE FROM DVL_NE WHERE TOPOLOGY_VALIDATE = '-' ORDER BY NE_NAME";
$prs = oci_parse($conn,$sql); $r = oci_execute($prs); $nrows = oci_fetch_all($prs, $results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
for($i = 0 ; $i < $nrows ; $i++) {
  $ne_site[$results[$i]['NE_NAME']] = $results[$i]['SITE_NOMBRE'];
  $ne_list[] = $results[$i]['NE_NAME'];
}


$file = 'tunnel_servicios.xlsx';

$workbook = IOFactory::load($file);
$worksheet = $workbook->getActiveSheet()->toArray(null, true, true, false);

echo "Starting...\n";

$topology = [];
$route_id = 130000;

$routes_log = [];
$tunnel_log = [];
$routes_id_log = [];

$tunnel_unknown = [];
$links_core = [];

for($row = 2 ; $row < count($worksheet) ; $row++) {
  echo "Row... $row\n";
  echo $worksheet[$row][9] . ' <-> ' . $worksheet[$row][15] . "\n";

  $transit = [];
  $transit['NE']   = explode("\n", $worksheet[$row][9]   . "\n" . (empty($worksheet[$row][24]) ? "" : $worksheet[$row][24] . "\n" ) . $worksheet[$row][15]);
  $transit['IN']   = explode("\n", ""                    . "\n" . (empty($worksheet[$row][25]) ? "" : $worksheet[$row][25] . "\n" ) . $worksheet[$row][16]);
  $transit['OUT']  = explode("\n", $worksheet[$row][10]  . "\n" . (empty($worksheet[$row][27]) ? "" : $worksheet[$row][27] . "\n" ) . "");
  $transit['NAME']  = $worksheet[$row][3];
  $transit['OID']   = $worksheet[$row][2];
  $transit['ID']    = $worksheet[$row][4];

  if(count(array_diff($transit['NE'],$ne_list)) == 0) {

    if((in_array($ne_site[$worksheet[$row][9]],$gateways_site) && in_array($ne_site[$worksheet[$row][15]],$gateways_site)) || (!in_array($ne_site[$worksheet[$row][9]],$gateways_site) && !in_array($ne_site[$worksheet[$row][15]],$gateways_site))) {
      if(!in_array($ne_site[$worksheet[$row][9]],$gateways_site) && !in_array($ne_site[$worksheet[$row][15]],$gateways_site)) {
        $tunnel_unknown[] = array(
          'NE' => implode("\n",$transit['NE']),
          'IN' => implode("\n",$transit['IN']),
          'OUT' => implode("\n",$transit['OUT']),
          'NAME' => $worksheet[$row][3],
          'OID' => $worksheet[$row][2],
          'ID' => $worksheet[$row][4]
        );
      } else {
        continue;
      }
    } else if(in_array($ne_site[$worksheet[$row][9]],$gateways_site) && !in_array($ne_site[$worksheet[$row][15]],$gateways_site)) {
      $transit['NE'] = array_reverse($transit['NE']);
      $temp = $transit['IN'];
      $transit['IN'] = array_reverse($transit['OUT']);
      $transit['OUT'] = array_reverse($temp);
    }

    $routes_log_element = [];
    $position = 1;

    $link_core = false;

    for($i = 0 ; $i < count($transit['NE'])-1 ; $i++) {

      if(strcmp($ne_site[$transit['NE'][$i]],$ne_site[$transit['NE'][$i+1]]) == 0) {
        continue;
      }
      else
      if(in_array($ne_site[$transit['NE'][$i]],$gateways_site) || $link_core) {
        $link_core = true;
        $links_core[] = array(
          'SOURCE_SITE' => $ne_site[$transit['NE'][$i]],
          'SOURCE_NE' => $transit['NE'][$i],
          'SOURCE_PORT' => $transit['OUT'][$i],
          'SINK_SITE' => $ne_site[$transit['NE'][$i+1]],
          'SINK_NE' => $transit['NE'][$i+1],
          'SINK_PORT' => $transit['IN'][$i+1]
        );
      } else {
        $routes_log_element['SRC_NE'][] = $transit['NE'][$i];
        $routes_log_element['SRC_PORT'][] = $transit['OUT'][$i];
        $routes_log_element['SNK_NE'][] = $transit['NE'][$i+1];
        $routes_log_element['SNK_PORT'][] = $transit['IN'][$i+1];

        $topology[] = array(
          'ROUTE_ID' => $route_id,
          'POSITION' => $position,
          'SOURCE_SITE' => $ne_site[$transit['NE'][$i]],
          'SOURCE_NE' => $transit['NE'][$i],
          'SOURCE_PORT' => $transit['OUT'][$i],
          'SINK_SITE' => $ne_site[$transit['NE'][$i+1]],
          'SINK_NE' => $transit['NE'][$i+1],
          'SINK_PORT' => $transit['IN'][$i+1]
        );

        $position++;
      }
    }

    $routes_log_element['SRC_NE'] = implode("\n",$routes_log_element['SRC_NE']);
    $routes_log_element['SRC_PORT'] = implode("\n",$routes_log_element['SRC_PORT']);
    $routes_log_element['SNK_NE'] = implode("\n",$routes_log_element['SNK_NE']);
    $routes_log_element['SNK_PORT'] = implode("\n",$routes_log_element['SNK_PORT']);

    if(!in_array($routes_log_element,$routes_log)) {
      $routes_log[] = $routes_log_element;
      $routes_id_log[$route_id] = $routes_log_element;
      $tunnel_log[] = array(
        'TUNNEL_NAME' => $transit['NAME'],
        'TUNNEL_OID' => $transit['OID'],
        'TUNNEL_ID' => $transit['ID'],
        'ROUTE_ID' => $route_id
      );
      $route_id++;
    } else {
      $tunnel_log[] = array(
        'TUNNEL_NAME' => $transit['NAME'],
        'TUNNEL_OID' => $transit['OID'],
        'TUNNEL_ID' => $transit['ID'],
        'ROUTE_ID' => array_search($routes_id_log,$routes_log_element)
      );
      while(end($topology)['ROUTE_ID'] == $route_id) array_pop($topology);
    }

  }


}

$json = json_encode($topology);
$file = fopen('AAtopologia_final.json', 'w');
fwrite($file, $json);
fclose($file);

$json = json_encode($tunnel_log);
$file = fopen('AAtunnel_final.json', 'w');
fwrite($file, $json);
fclose($file);

$json = json_encode($tunnel_unknown);
$file = fopen('AAtunnel_unknown.json', 'w');
fwrite($file, $json);
fclose($file);

$json = json_encode($links_core);
$file = fopen('AAlinks_core.json', 'w');
fwrite($file, $json);
fclose($file);
