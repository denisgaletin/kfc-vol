<?php

	namespace app\controllers\web;
	use app\controllers\web\generic,
		app\models\address\address as model;
		
	class address extends generic {
		public static function model(){
			return new model();
		}
	}