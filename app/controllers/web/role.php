<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\role\role as model;
		
	class role extends generic {
		public static function model(){
			return new model();
		}
	}