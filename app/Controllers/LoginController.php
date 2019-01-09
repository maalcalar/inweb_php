<?php

namespace Application\Controllers;

use Application\Utils\Auth;
use Application\Utils\View;

use Aura\Session\Session;

class LoginController {

	protected $view;

	protected $session;

	public function __construct (View $view, Session $session) {
		$this->view = $view;
		$this->session = $session;
	}

	public function showLoginForm () {
		echo $this->view->render('base/web_login.twig');
	}

	private function ldap ($usuario, $ldappass) {
        if(!is_null($usuario) and !is_null($ldappass)) {
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
        } else {
            $respuesta = array('error' => 'Falta el usuario o la contraseña', 'success' => false);
        }
        return $respuesta;
    }

	public function login (Auth $auth) {
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $user = $_POST['user'];
        $pass = $_POST['pass'];

        $response = $this->ldap($user,$pass);

        if($response['success']) {
            $auth->generateUserSession($response);
            //\Kint::dump($this->session->getSegment( 'InWeb')->get('user'));
            $this->session->getSegment('InWeb')->setFlash('notificacion', 'Welcome! '.$this->session->getSegment('InWeb')->get('user')['nombre']);
            return redirect('.');
        }

        $this->session->getSegment('InWeb')->setFlash('errors', $response['error']);
        $this->session->getSegment( 'InWeb')->setFlash( 'post', $_POST);
        return redirect('login');
	}

	public function logout () {
        $this->session->getSegment('InWeb')->clear();
        $this->session->getSegment('InWeb')->setFlash('notificacion', 'Has cerrado sesión correctamente');
        return redirect('login');
    }
}