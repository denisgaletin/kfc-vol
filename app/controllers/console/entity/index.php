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
		
	class index extends Command{
		private $app;
		protected function configure(){
			global $app;
			$this->app = $app;
			$this
				->setName("entity:index")
				->setDescription("Rebuild datebase indexes");
		}
		protected function execute(InputInterface $input, OutputInterface $output){
			$entitiesSchemes = (new helper\helper)->glob($this->app['config']->get('paths')->get('atom').'/app/models/*.yml');
			foreach($entitiesSchemes as $scheme){
				$modelName = '\app\models\\'.pathinfo($scheme, PATHINFO_FILENAME).'\\'.pathinfo($scheme, PATHINFO_FILENAME);
				$model = new $modelName;
				$scheme = $model->getScheme();
				if (is_null($scheme->get('fields'))){
					continue;
				}
				$this->app['db']->selectCollection($scheme->get('name'))->deleteIndexes();
				$search = array();
				foreach($scheme->get('fields')->get() as $fieldName => $fieldProperties){
					if (isset($fieldProperties['search']) && $fieldProperties['search']){
						$search[$fieldName] = 'text';
					}
					if (isset($fieldProperties['index']) && ($fieldProperties['index'] == 1 || $fieldProperties['index'] == -1)){
						$this->app['db']->selectCollection($scheme->get('name'))->ensureIndex(array($fieldName => $fieldProperties['index']));
					}
				}
				if ($search !== array()){
					$this->app['db']->selectCollection($scheme->get('name'))->ensureIndex($search, array('default_language' => 'russian'));
				}
			}
			$output->writeln('<options=bold>Database indexes just rebuilded!</options=bold>');
		}
	}