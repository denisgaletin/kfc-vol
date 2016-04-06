<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\direction\direction as model;
		
	class direction extends generic {
		public static function model(){
			return new model();
		}
	}