<?php

	namespace app\models\view;

	use Symfony\Component\HttpKernel\HttpKernelInterface,
		Symfony\Component\HttpFoundation\Request,
		Symfony\Component\Templating\PhpEngine,
		Symfony\Component\Templating\TemplateNameParser,
		Symfony\Component\Templating\Loader\FilesystemLoader,
		Symfony\Component\Process\Process;

	class view extends PhpEngine{
		private $app;

		public function __construct(){
			global $app;
			$this->app = $app;
			$loader = new FilesystemLoader($app['config']->get('paths')->get('root').'/themes/%name%');
			return parent::__construct(new TemplateNameParser(), $loader);
		}

		public function renderController($route, $requestData = array(), $method = 'GET'){
			$subRequest = Request::create(
				$route, $method, $requestData, $this->app['request']->cookies->all(),
				array(), $this->app['request']->server->all()
			);
			if ($this->app['request']->getSession()){
				$subRequest->setSession($this->app['request']->getSession());
			}
			$response = $this->app->handle(
				$subRequest, HttpKernelInterface::SUB_REQUEST, false
			);
			if (!$response->isSuccessful()){
				throw new \RuntimeException(
					sprintf('Error when rendering "%s" (Status code is %s).', $this->app['request']->getUri(), $response->getStatusCode())
				);
			}
			return $response->getContent();
		}
	}
