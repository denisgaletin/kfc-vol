<?php

	namespace app\controllers\web;
	use Silex\Application,
		Symfony\Component\HttpFoundation\Request,
		app\models\report\report as model,
		app\models\helper;
		
	class report extends generic {
		public static function model(){
			return new model();
		}

		public static function create(Request $request, Application $app){
			$success = new helper\iterator();
			$error = new helper\iterator();
			$report = static::model()->set($request->request->all());
			if(count($request->files->all())){
				$report->setFile($request->files->all());
			}
			$report->set('title', 'Отчет ' . $request->get('fio') . ', '. date('d.m.Y') . '.');
			if(($result = $report->save()) === true)
				$result = $report->sentNotificationReport();

			static::handleActionResult($report, $result, $success, $error);
			return static::makeResponse($app, $success, $error);
		}

	}