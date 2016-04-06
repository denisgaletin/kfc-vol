<?php

	namespace app\controllers\web;
	use Silex\Application,
		Symfony\Component\HttpFoundation\Request,
		app\models\volunteer\volunteer as model,
		app\models\helper;
		
	class volunteer extends generic {
		public static function model(){
			return new model();
		}

		public static function create(Request $request, Application $app){
			$success = new helper\iterator();
			$error = new helper\iterator();
			$volunteer = static::model()->set($request->request->all());


			if(($result = $volunteer->save()) === true)
				$result = $volunteer->sentNotificationVolunteer();

			static::handleActionResult($volunteer, $result, $success, $error);
			return static::makeResponse($app, $success, $error);
		}
	}