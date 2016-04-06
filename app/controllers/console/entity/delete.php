<?php

	namespace app\controllers\console\entity;
	use Symfony\Component\Console\Command\Command,
		Symfony\Component\Console\Input\InputArgument,
		Symfony\Component\Console\Input\InputInterface,
		Symfony\Component\Console\Output\OutputInterface,
		Symfony\Component\Console\Question\Question,
		Symfony\Component\Console\Question\ConfirmationQuestion,
		Symfony\Component\Filesystem\Exception\IOExceptionInterface,
		app\models\helper;
		
	class delete extends Command{
		private $app;
		protected function configure(){
			global $app;
			$this->app = $app;
			$this
				->setName("entity:delete")
				->setDescription("Delete entity infrastructure");
		}
		protected function execute(InputInterface $input, OutputInterface $output){
			$atomRoot = $this->app['config']->get('paths')->get('atom');
			$helper = $this->getHelper('question');
			do {
				$question = new Question('Enter entity name (internal): ');
				$entity = $helper->ask($input, $output, $question);
				$exists = $this->app['fs']->exists($atomRoot.'/app/models/'.$entity);
			} while(!$entity || !$exists);
			
			$infrastructure = (new helper\proto())->set(array(
				'model'			=> $atomRoot.'/app/models/'.$entity,
				'controllers'	=> $atomRoot.'/app/controllers/web/'.$entity.'.php',
				'routes'		=> $atomRoot.'/app/routes/'.$entity.'.yml'
			));
			foreach($infrastructure->get() as $target => $path){
				$this->app['fs']->remove($path);
			}
			$routes = file_get_contents($atomRoot.'/app/routes/base.yml');
			$routes = preg_replace('/# '.ucfirst($entity).'(.*?)###/s', '', $routes);
			file_put_contents($atomRoot.'/app/routes/base.yml', $routes, LOCK_EX);
			
			$output->writeln('<options=bold>Entity "'.$entity.'" just deleted!</options=bold>');
		}
	}