<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\block\block as model;
		
	class block extends generic {
		public static function model(){
			return new model();
		}
	}