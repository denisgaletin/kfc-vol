<?php

	namespace app\controllers\console\entity;
	use Symfony\Component\Console\Command\Command,
		Symfony\Component\Console\Input\InputArgument,
		Symfony\Component\Console\Input\InputInterface,
		Symfony\Component\Console\Output\OutputInterface,
		Symfony\Component\Console\Question\Question,
		Symfony\Component\Console\Question\ConfirmationQuestion,
		Symfony\Component\Filesystem\Exception\IOExceptionInterface,
		app\models\helper,
		app\models\role\role;
		
	class create extends Command{
		private $app;
		protected function configure(){
			global $app;
			$this->app = $app;
			$this
				->setName("entity:create")
				->setDescription("Create new entity infrastructure");
		}
		protected function execute(InputInterface $input, OutputInterface $output){
			$atomRoot = $this->app['config']->get('paths')->get('atom');
			$helper = $this->getHelper('question');
			do {
				$question = new Question('Enter entity name (internal): ');
				$entity = $helper->ask($input, $output, $question);
				
				$question = new Question('Enter entity title for listing (russian): ');
				$titleList = $helper->ask($input, $output, $question);
				$question = new Question('Enter entity title for item (russian): ');
				$titleItem = $helper->ask($input, $output, $question);
			} while(!$entity || !$titleList || !$titleItem);
			if ($this->app['fs']->exists($atomRoot.'/app/models/'.$entity)){
				$question = new ConfirmationQuestion('Entity "'.$entity.'" already exist. Override? [y/N]: ', false);
				if (!$helper->ask($input, $output, $question)) {
					return $this->execute($input, $output);
				}
			}

			$infrastructure = (new helper\proto())->set(array(
				'model'			=> array(
					'source'		=> $atomRoot.'/app/sample/model.php',
					'destination'	=> $atomRoot.'/app/models/'.$entity.'/'.$entity.'.php'
				),
				'scheme'		=> array(
					'source'		=> $atomRoot.'/app/sample/scheme.yml',
					'destination'	=> $atomRoot.'/app/models/'.$entity.'/'.$entity.'.yml'
				),
				'controllers'	=> array(
					'source'		=> $atomRoot.'/app/sample/controllers.php',
					'destination'	=> $atomRoot.'/app/controllers/web/'.$entity.'.php'
				),
				'routes'		=> array(
					'source'		=> $atomRoot.'/app/sample/routes.yml',
					'destination'	=> $atomRoot.'/app/routes/'.$entity.'.yml'
				),
				'routes_inc'	=> array(
					'source'		=> $atomRoot.'/app/sample/routes.inc.yml',
					'destination'	=> $atomRoot.'/app/routes/base.yml'
				)
			));
			$replacement = array(
				'{{entity}}'	=> $entity,
				'{{Entity}}'	=> ucfirst($entity),
				'{{titleList}}'	=> $titleList,
				'{{titleItem}}'	=> $titleItem
			);
			$this->app['fs']->mkdir($atomRoot.'/app/models/'.$entity);
			foreach($infrastructure->get() as $target => $files){
				$content = file_get_contents($files['source']);
				$content = str_replace(array_keys($replacement), $replacement, $content);
				file_put_contents($files['destination'], $content, FILE_APPEND | LOCK_EX);
			}
			
			$role = (new role())->loadOne('name', 'admin');
			$acl = $role->get('acl')->all();
			$acl[$entity] = array(
				'create'	=> true,
				'read'		=> true,
				'update'	=> true,
				'delete'	=> true
			);
			$role->set('acl', $acl)->save();

			$output->writeln('<options=bold>Entity "'.$entity.'" just created!</options=bold>');
		}
	}