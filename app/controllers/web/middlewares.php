<?php

	namespace App\Controllers\frontend;
    use Silex\Application;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;


	class middlewares{
		public static function before(Request $request, Application $app){
		}

		public static function after(Request $request, Response $response, Application $app){
		}
	}