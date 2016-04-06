<?php

	namespace app\controllers\web;
	use Silex\Application,
		Symfony\Component\HttpFoundation\Request,
		app\models\helper;
		
	class entities {
		public static function index(Request $request, Application $app){
			$entities = array();
			$entitiesSchemes = (new helper\helper)->glob($app['config']->get('paths')->get('atom').'/app/models/*.yml');
			foreach($entitiesSchemes as $scheme){
				$modelName = '\app\models\\'.pathinfo($scheme, PATHINFO_FILENAME).'\\'.pathinfo($scheme, PATHINFO_FILENAME);
				$model = new $modelName;
				$scheme = $model->getScheme()->get();
				$routes = $model->getRoutes()->get();
				foreach($routes as $routeName => $routeProperties){
					if (!$app['user']->hasAccess($routeName)){
						unset($routes[$routeName]);
					}
				}
				if (count($routes) == 0){
					continue;
				}
				$entities[$model->getScheme()->get('name')] = array(
					'title'		=> $model->getScheme()->get('titles')->get('list'),
					'routes'	=> $routes,
					'scheme'	=> $scheme
				);
			}
			return $app['page']->set($entities);
		}
		
		public static function read(Request $request, Application $app){
			$entities = array();
			$modelName = '\app\models\\'.$request->get('entity').'\\'.$request->get('entity');
			$model = new $modelName;
			return $app['page']->set($model->getScheme()->get());
		}
	}