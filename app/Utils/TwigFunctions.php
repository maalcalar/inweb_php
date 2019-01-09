<?php

namespace Application\Utils;

use Aura\Session\Session;

class TwigFunctions {

	static $container;

	public static function setContainer (\Psr\Container\ContainerInterface $container) {
		self::$container = $container;
	}

	/*
	 * SUBDIR INWEB
	 */
    public static function app_root() {
	    return base_url().'/inweb/';
    }

    public static function session (): Session {
        return self::$container->get(Session::class);
    }

    public static function flash ($params) {
        $session = self::session();
        $flash = $session->getSegment( 'InWeb')->getFlash( $params[0]);

        if ($flash) {
            if ($params[0] === 'post') {
                return $flash;
            }

            return sprintf(
                "<div width='%s' class='alert alert-alt alert-%s alert-dismissible m-0 my-20' role='alert'><button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button><h5 class='m-0 text-%s'>%s</h5></div>",
                '100%',
                $params[1],
                $params[1],
                $flash
            );
        }

        return null;
    }

    public static function sweetalert ($params) {
        $session = self::session();
        $flash = $session->getSegment( 'InWeb')->getFlash($params[0]);

        if ($flash) {
            return sprintf(
                'swal({title: "", text: "%s", type: "%s", timer: 2000, showConfirmButton: false});',
                $flash,
                $params[1]
            );
        }

        return null;
    }

    public static function user () {
        return self::session()->getSegment( 'InWeb')->get( 'user');
    }

	public static function first_uri_segment ()  {
		$page = explode('/', substr($_SERVER['REQUEST_URI'], 1), 2);
		return str_replace("-"," ", $page[0]);
	}
}