<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		Silex\Application,
		Symfony\Component\HttpFoundation\Request;
		
	class filemanager extends generic {
		public static function proxy(Request $request, Application $app){
			if (!$app['user']->isAdmin()){
				return $app->abort(403, $app['translator']->trans('403'));
			}
			return require_once $app['config']->get('paths')->get('root').'/themes/backend/filemanager/'.$request->get('query');
		}
	}