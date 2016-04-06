<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\setting\setting as model;
		
	class setting extends generic {
		public static function model(){
			return new model();
		}
	}