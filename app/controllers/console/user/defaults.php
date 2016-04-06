<?php

	namespace app\controllers\console\user;
	use Symfony\Component\Console\Command\Command,
		Symfony\Component\Console\Input\InputArgument,
		Symfony\Component\Console\Input\InputInterface,
		Symfony\Component\Console\Output\OutputInterface,
		Symfony\Component\Console\Question\Question,
		Symfony\Component\Console\Question\ConfirmationQuestion,
		Symfony\Component\Filesystem\Exception\IOExceptionInterface,
		app\models\helper,
		app\models\role\role,
		app\models\user\user;

	class defaults extends Command{
		private $app;
		protected function configure(){
			global $app;
			$this->app = $app;
			$this
				->setName("user:defaults")
				->setDescription("Create default roles and users");
		}
		protected function execute(InputInterface $input, OutputInterface $output){
			$roleAdmin = (new role())->set(array(
				'title'		=> 'Администратор',
				'name'		=> 'admin',
				'enabled'	=> true,
				'acl'		=> array(
					'index'		=> array(
						'read'		=> true
					),
					'atom'	=> array(
						'read'		=> true
					),
					'setting'	=> array(
						'read'		=> true,
						'create'	=> true,
						'update'	=> true,
						'delete'	=> true
					),
					'page'	=> array(
						'read'		=> true,
						'create'	=> true,
						'update'	=> true,
						'delete'	=> true
					),
					'block'=> array(
						'read'		=> true,
						'create'	=> true,
						'update'	=> true,
						'delete'	=> true
					),
					'user'	=> array(
						'read'		=> true,
						'create'	=> true,
						'update'	=> true,
						'delete'	=> true
					),
					'role'	=> array(
						'read'		=> true,
						'create'	=> true,
						'update'	=> true,
						'delete'	=> true
					),
					'entity'	=> array(
						'read'		=> true
					)
				)
			));
			$roleAdmin->save();
			$userAdmin = (new user())->set(array(
				'title'		=> 'Администратор',
				'email'		=> 'admin@sorry.ru',
				'password'	=> 'multipass',
				'enabled'	=> true,
				'role'		=> array($roleAdmin->get('_id'))
			))->save();

			$roleGuest = (new role())->set(array(
				'title'		=> 'Гость',
				'name'		=> 'guest',
				'enabled'	=> true,
				'acl'		=> array(
					'index'		=> array('read'	=> true),
					'page'		=> array('read'	=> true),
					'block'		=> array('read'	=> true),
					'user'		=> array('read'	=> true),
					'role'		=> array('read'	=> true),
					'entity'	=> array('read'	=> true)
				)
			))->save();
			$output->writeln('<options=bold>Default roles and users just created!</options=bold>');
		}
	}
