<?php

	namespace app\controllers\web;
	use Silex\Application,
		Symfony\Component\HttpFoundation\Request,
		app\controllers\web\generic,
		app\models\setting\setting,
		app\models\captcha\captcha,
		app\models\user\user as model;

	class user extends generic {
		public static function model(){
			return new model();
		}

		public static function read(Request $request, Application $app){
			if (!$app['user']->isAuth()){
				return $app->abort(403, $app['translator']->trans('403'));
			}
			parent::read($request, $app);
			if (!$app['user']->isAdmin()){
				$data = array();
				foreach($app['page']->get('data')->all() as $key => $user){
					unset($user['token']);
					unset($user['password']);
					$data[$key] = $user;
				}
				$app['page']->set('data', $data);
			}
			return $app['page'];
		}

		public static function authByToken(Request $request, Application $app){
			if ($token = $app['request']->headers->get('token', $app['request']->cookies->get('token'))){
				$isAuth = $app['user']->authByToken($token);
				if ($request->get('_route') == 'user.authByToken'){
					if ($isAuth){
						return $app['page']->set(array(
							'success'	=> array(
								'message'	=> $app['translator']->trans('user.login')
							)
						));
					} else {
						return $app->abort(400, $app['translator']->trans('user.wrong.token'));
					}
				}
				if ($isAuth){
					return true;
				}
			}
			return false;
		}

		public static function authByPass(Request $request, Application $app){
			if ($app['user']->authByPass($request->get('email'), $request->get('password'))){
				return $app['page']->set(array(
					'success'	=> array(
						'message'		=> $app['translator']->trans('user.login'),
						'user'			=> $app['user']->pop('password')->get()
					)
				));
			}
			return $app->abort(400, $app['translator']->trans('user.wrong.login'));
		}

		public static function logout(Request $request, Application $app){
			if ($app['user']->isAuth()){
				$app['user']->logout();
				return $app['page']->set(array(
					'success'	=> array(
						'message'	=> $app['translator']->trans('user.logout')
					)
				));
			}
			return $app->abort(401, $app['translator']->trans('401'));
		}

		public static function checkPermissions(Request $request, Application $app){
			static::authByToken($request, $app);
			if (!$app['user']->hasAccess($request->get('_route'))){
				if ($app['user']->isAuth()){
					return $app->abort(403, $app['translator']->trans('403'));
				} else {
					return $app->abort(401, $app['translator']->trans('401'));
				}
			}
		}
	}