<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\action\action as model;

	class action extends generic {
		public static function model(){
			return new model();
		}
	}