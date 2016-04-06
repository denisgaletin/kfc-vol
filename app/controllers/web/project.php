<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\project\project as model;
		
	class project extends generic {
		public static function model(){
			return new model();
		}
	}