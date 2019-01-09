<?php

namespace Application\Utils;

use Aura\Session\Session;

class Auth {

	protected $session;

	public function __construct (Session $session) {
		$this->session = $session;
	}

	public function generateUserSession ($user) {
		$this->session->getSegment( 'InWeb')->set( 'user', [
			"nombre" => $user['nombre'],
            "email" => $user['email'],
            "gerencia" => $user['gerencia'],
            "descripcion" => $user['descripcion'],
            "cargo" => $user['cargo']
		]);
	}
}