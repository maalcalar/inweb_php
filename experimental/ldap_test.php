<?php

$usuario = 'jarancibias';
$ldappass = 'Sophia2019';

$dominio = "nextelperu.net";
$dominio1 = "14.240.4.146";
$connect = ldap_connect($dominio1);

if ($connect) {
    $ldaprdn = $usuario . "@" . $dominio;
    $base_dn = "DC=nextelperu,DC=net";
    $filter = "(samaccountname=$usuario)";
    $justthese = array('cn', 'mail', 'samaccountname', 'description', 'department', 'title');
    ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
    if (@ldap_bind($connect, $ldaprdn, $ldappass)) {
        $read = ldap_search($connect, $base_dn, $filter, $justthese);
        if ($read) {
            $info = ldap_get_entries($connect, $read);
            if ($info['count'] > 0) {
                $respuesta = array(
                    'success' => true,
                    'nombre' => @$info[0]['cn'][0],
                    'email' => @$info[0]['mail'][0],
                    'gerencia' => @$info[0]['department'][0],
                    'descripcion' => @$info[0]['description'][0],
                    'cargo' => @$info[0]['title'][0]
                );
            } else {
                $respuesta = array('error' => 'Error Inesperado al obtener datos en el servidor LDAP', 'success' => false);
            }
        } else {
            $respuesta = array('error' => 'No hay información del usuario en el servidor LDAP', 'success' => false);
        }
    } else {
        $respuesta = array('error' => 'Credenciales incorrectas', 'success' => false);
    }
    ldap_unbind($connect);
} else {
    $respuesta = array('error' => 'Imposible conectar al servidor LDAP.', 'success' => false);
}