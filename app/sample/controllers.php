<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\{{entity}}\{{entity}} as model;
		
	class {{entity}} extends generic {
		public static function model(){
			return new model();
		}
	}