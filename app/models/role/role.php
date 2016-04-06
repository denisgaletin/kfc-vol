<?php

	namespace app\models\role;
	use app\models\generic\generic,
		app\models\helper;

	/**
	 * Role
	 */
	class role extends generic{
		public function hasAccess($route){
			@list($entity, $actionName) = explode('.', $route);
			$action = $this->app['routes']->get($route)->getOption('_action');
			return $this->get('acl', new helper\proto())->get($entity, new helper\proto())->get($action, false);
		}
	}