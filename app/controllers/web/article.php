<?php

	namespace app\controllers\web;
	use Silex\Application,
		Symfony\Component\HttpFoundation\Request,
		app\controllers\web\generic,
		app\models\program\program,
		app\models\helper,
		app\models\article\article as model;
		
	class article extends generic {
		public static function model(){
			return new model();
		}

		/*public static function read(Request $request, Application $app){
			if(strpos($_SERVER['HTTP_REFERER'],'/atom/') != false && (strpos($request->getPathInfo(),'article/page') != false || $request->getPathInfo() == '/article/')) {
				if ($request->get('_id')) {
					$request->query->set('condition', array('_id' => static::model()->mongoid($request->get('_id'))));
				}
				$condition = static::prepareCondition($request->get('condition', array()));
				$sort = static::prepareSort($request->get('sort', array()));
				$limit = (int)$request->get('limit', 10);
				$skip = ($request->get('skip') ? (int)$request->get('skip', 0) : (int)$request->get('page', 0) * $limit);
				$items = static::model()->load($condition, $sort, $limit, $skip);
				foreach ($items as $item) {
					//program
					static::convertEntityField($item, 'program', new program());
					if(!empty($item->get('program')))
						$item->set('program',$item->get('title').' ['.$item->get('program').']');
				}

				return $app['page']->set(array(
					'total' => $items->getTotal(),
					'data' => $items->all()
				))->response()->setStatusCode($items->count() ? 200 : 204);
			} else
				return parent::read($request,$app);
		}*/

		public static function create(Request $request, Application $app){
			$success = new helper\iterator();
			$error = new helper\iterator();
			$item = static::model()->set($request->request->all());
			if(count($request->files->all())){
				$item->setFile($request->files->all());
			}
			$item->set('sort_program',static::getEntityField($item, 'program', new program()));
			$result = $item->save();
			static::handleActionResult($item, $result, $success, $error);
			return static::makeResponse($app, $success, $error);
		}

		public static function update(Request $request, Application $app){
			$success = new helper\iterator();
			$error = new helper\iterator();
			if ($request->get('_id')){
				$item = static::model()->loadById($request->get('_id'))->set($request->request->all());
				if(count($request->files->all())){
					$item->setFile($request->files->all());
				}
				$item->set('sort_program',static::getEntityField($item, 'program', new program()));
				$result = $item->save();
				static::handleActionResult($item, $result, $success, $error);
			}

			return static::makeResponse($app, $success, $error);
		}

	}