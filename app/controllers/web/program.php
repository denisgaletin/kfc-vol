<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\program\program as model;
		
	class program extends generic {
		public static function model(){
			return new model();
		}
	}