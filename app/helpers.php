<?php

if ( ! function_exists('base_path')) {
	function base_path (string $path): string  {
		return __DIR__ . '/..//' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}
}

if ( ! function_exists('redirect')) {
	function redirect (string $path) {
		header('Location: ' . $path);
	}
}

if ( ! function_exists('base_url')) {
	function base_url (string $url = ''): string {
		$baseUrl = sprintf(
			"%s://%s:%s",
			isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
			$_SERVER['SERVER_NAME'],
			$_SERVER['SERVER_PORT']
		);

		/*
		 * SUBDIR INWEB
		 */
		if ($url) {
			return sprintf(
				'%s/inweb/%s',
				$baseUrl,
				$url
			);
		}

		return $baseUrl;
	}
}

if ( ! function_exists('clear_string')) {
    function clear_string (string $word): string {
        $word = ltrim($word);
        $word = rtrim($word);
        $word = str_replace('á', 'a', $word);
        $word = str_replace('Á', 'A', $word);
        $word = str_replace('é', 'e', $word);
        $word = str_replace('É', 'E', $word);
        $word = str_replace('í', 'i', $word);
        $word = str_replace('Í', 'I', $word);
        $word = str_replace('ó', 'o', $word);
        $word = str_replace('Ó', 'O', $word);
        $word = str_replace('ú', 'u', $word);
        $word = str_replace('Ú', 'U', $word);
        $word = str_replace('&', 'y', $word);
        $word = str_replace('ñ', 'n', $word);
        return $word;
    }
}
