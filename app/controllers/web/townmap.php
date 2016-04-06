<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\townmap\townmap as model;
		
	class townmap extends generic {
		public static function model(){
			return new model();
		}
	}