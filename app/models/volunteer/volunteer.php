<?php

	namespace app\models\volunteer;
	use app\models\generic\generic,
		app\models\project\project,
		app\models\setting\setting;

	/**
	 * Volunteer
	 */
	class volunteer extends generic{

		public function sentNotificationVolunteer()
		{

			if($this->get('projectid') && $emailProject = (new project)->loadOne('_id', $this->mongoid($this->get('projectid')))->get('email')){
				$emails[] = $emailProject;
			} else {
				if($emailSetting = (new setting)->load(array('key'=> 'emailtoreport'))){
					foreach($emailSetting as $v)
						$emails[] = $v->get('value');
				} else {
					$role = (new role)->loadOne('name', 'admin');
					$users = (new user)->load(array(
						'role' => array('$in' => array($role->get('_id')))
					));
					foreach ($users as $user)
						$emails[] = $user->get('email');
				}
			}

			$message = \Swift_Message::newInstance()
					->setSubject('Появился новый волонтер')
					->setFrom($this->app['config']->get('mailer')->get('from')->get())
					->setTo($emails)
					->setBody(
						'<html>' .
						' <head></head>' .
						' <body>' .
						'<p><strong>О волонтере</strong></p>' .
						'<table>' .
						'<tr><td>Имя</td><td>'. $this->get('name') .'</td></tr>' .
						'<tr><td>О себе</td><td>'. preg_replace('/(\\\r\\\n)|(\\\n)+/','',preg_replace('/[0-9](\\\r\\\n)|[0-9](\\\n)/', '<br>', $this->get('about'))) .'</td></tr>' .
						'</table>' .
						' </body>' .
						'</html>',
						'text/html');

				if ($this->app['mailer']->send($message))
					return true;
				else
					return false;

		}
	}