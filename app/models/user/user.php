<?php

	namespace app\models\user;
	use app\models\generic\generic,
		app\models\role\role;

	/**
	 * User
	 */
	class user extends generic{
		public function isAuth(){
			return ($this->get('_id') && $this->get('token') ? true : false);
		}
		
		public function isAdmin(){
			$isAdmin = false;
			$roles = $this->getRelated('role');
			foreach($roles as $role){
				if ($role->get('name') == 'admin'){
					$isAdmin = true;
				}
			}
			return $isAdmin;
		}
		
		public function hasAccess($route){
			$roles = $this->getRelated('role');
			foreach($roles as $role){
				if ($role->hasAccess($route)){
					return true;
				}
			}
			return (new role())->loadOne(array(
				'name'		=> 'guest',
				'enabled'	=> true
			))->hasAccess($route);
		}
		
		public function authByToken($token){
			return (is_null($this->loadOne(array(
				'token'		=> $token,
				'enabled'	=> true
			))->get('_id')) ? false : true);
		}

		public function authByPass($email, $password){
			$this->loadOne(array(
				'email'		=> $email,
				'password'	=> $this->hash($password),
				'enabled'	=> true
			));
			if ($this->get('_id') && $this->setToken() === true){
				return true;
			}
			return false;
		}
		
		public function logout(){
			return $this->set('token', null)->save();
		}
		
		public function setToken(){
			return $this->set('token', $this->hash(time().$this->get('password')))->save();
		}
	}