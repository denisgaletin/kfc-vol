<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\town\town as model;
		
	class town extends generic {
		public static function model(){
			return new model();
		}
	}