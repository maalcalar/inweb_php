<?php

namespace Application\Utils;

use Application\Utils\TwigFunctions;

class View {

	protected $twig;

	public function __construct () {
		$loader = new \Twig_Loader_Filesystem(base_path('resources/views'));
		$twig = new \Twig_Environment($loader, array(
		    'debug' => true
        ));

		$twigFunctions = new \Twig_SimpleFunction(\TwigFunctions::class, function ($method, $params = []) {
			return TwigFunctions::$method($params);
		});

		$twig->addFunction($twigFunctions);
		$twig->addExtension(new \Twig_Extension_Debug());

		$this->twig = $twig;
	}

	public function render (string $view, array $data = []): string {
	    $data['week'] = date('W');
		return $this->twig->render($view, $data);
	}
}