<?php

	namespace app\models\report;
	use app\models\generic\generic,
		app\models\role\role,
		app\models\user\user,
		app\models\setting\setting,
		app\models\town\town;

	/**
	 * Report
	 */
	class report extends generic{

		public function sentNotificationReport(){

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

			$town = (new town)->loadOne('_id', $this->mongoid($this->get('town')));

			preg_replace('/(\\\r\\\n)|(\\\n)+/','',preg_replace('/[0-9](\\\r\\\n)|[0-9](\\\n)/', '<br>', $this->get('date')));
			$message = \Swift_Message::newInstance()
				->setSubject('Добавлен новый отчет. ' .
					$this->get('fio') . '. ' .
					date('d.m.Y')
					)
				->setFrom($this->app['config']->get('mailer')->get('from')->get())
				->setTo($emails)
				->setBody(
					'<html>' .
					' <head></head>' .
					' <body>' .
					'<p><strong>Краткая информация</strong></p>' .
					'<table>' .
					'<tr><td>ФИО</td><td>'. $this->get('fio') .'</td></tr>' .
					'<tr><td>Краткое описание</td><td>'. preg_replace('/(\\\r\\\n)|(\\\n)+/','',preg_replace('/[0-9](\\\r\\\n)|[0-9](\\\n)/', '<br>', $this->get('shortdesc'))) .'</td></tr>' .
					'</table>' .
					'<p><strong>Подробная информация</strong></p>' .
					'<table>' .
					'<tr><td>Город</td><td>'. $town->get('title') .'</td></tr>' .
					'<tr><td>Название компании</td><td>'. $this->get('company') .'</td></tr>' .
					'<tr><td>Название проекта</td><td>'. $this->get('projectname') .'</td></tr>' .
					'<tr><td>Цели и значимость</td><td>'. preg_replace('/(\\\r\\\n)|(\\\n)+/','',preg_replace('/[0-9](\\\r\\\n)|[0-9](\\\n)/', '<br>', $this->get('longdesc'))) .'</td></tr>' .
					'<tr><td>Дата проведения</td><td>'. preg_replace('/(\\\r\\\n)|(\\\n)+/','',preg_replace('/[0-9](\\\r\\\n)|[0-9](\\\n)/', '<br>', $this->get('date'))) .'</td></tr>' .
					'</table>' .
					'<p><strong>Капитан команды</strong></p>' .
					'<table>' .
					'<tr><td>ФИО</td><td>'. $this->get('fio') .'</td></tr>' .
					'<tr><td>Телефон</td><td>'. $this->get('phone') .'</td></tr>' .
					'<tr><td>Email</td><td>'. $this->get('email') .'</td></tr>' .
					'</table>' .
					'<p><strong>Участники</strong></p>' .
					'<table>' .
					'<tr><td>'. preg_replace('/(\\\r\\\n)|(\\\n)+/','',preg_replace('/[0-9](\\\r\\\n)|[0-9](\\\n)/', '<br>', $this->get('member'))) .'</td></tr>' .
					'</table>' .
					($this->get('fond')?
					'<p><strong>Благотворительный фонд</strong></p>' .
					'<table>' .
					'<tr><td>Название фонда</td><td>'. $this->get('fondname') .'</td></tr>' .
					'<tr><td>Адрес фонда</td><td>'. $this->get('fondaddress') .'</td></tr>' .
					'<tr><td>ИНН</td><td>'. $this->get('inn') .'</td></tr>' .
					'<tr><td>Контактное лицо фонда</td><td>'. $this->get('contact') .'</td></tr>' .
					'<tr><td>Телефон</td><td>'. $this->get('fondphone') .'</td></tr>' .
					'<tr><td>Email</td><td>'. $this->get('fondemail') .'</td></tr>' .
					'<tr><td>Веб-сайт</td><td>'. $this->get('url') .'</td></tr>' .
					'</table>':'') .
					' </body>' .
					'</html>',
					'text/html');
			if($this->get('image') != null) {
				foreach ($this->get('image')->get() as $i)
					$message->attach(\Swift_Attachment::fromPath($_SERVER['DOCUMENT_ROOT'].'/' . $i['route'], 'image/jpg'));
			}
			if($this->app['mailer']->send($message))
				return true;
			else
				return false;
		}
		
	}