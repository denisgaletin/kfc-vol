<?php

	namespace app\controllers\web;
	use Silex\Application,
		Symfony\Component\HttpFoundation\Request,
		app\models\helper;
		
	class main {
		public static function null(Request $request, Application $app){
			return true;
		}
		
		public static function error404(Request $request, Application $app){
			return $app->abort(404, $app['translator']->trans('404'));
		}
	}