<?php

	namespace app\models\generic;
	use app\models\helper;
	use Symfony\Component\Yaml\Yaml;
	use Symfony\Component\Validator\ConstraintViolation as Alert;
	use Symfony\Component\Validator\ConstraintViolationList as Alerts;
	use Symfony\Component\Validator\Constraints\Collection as Asserts;
	use Symfony\Component\ExpressionLanguage\ExpressionLanguage as Explan;
    use Symfony\Component\HttpFoundation\File\File as File;
    use Symfony\Component\HttpFoundation\File\Exception\FileException;

	/**
	 * Generic
	 */
	class generic{

		use \app\models\traits\prototype;

		/**
		 * @var Application
		 */
		public $app;

		/**
		 * @var Data params
		 */
		private $scheme = null,
				$routes = null;



		/**
		 * Create new object
		 */
		public function __construct(){
			global $app;
			$this->app =& $app;
		}

		/**
		 * Load items
		 *
		 * @param array $condition Condition
		 * @param array $sort Sort
		 * @param integer $limit Limit items
		 * @param integer $skip Skiping position
		 * @return iterator
		 */
		public function load($condition = array(), $sort = array(), $limit = 0, $skip = 0){
			$options = array();
			if(isset($condition['$search']) && $condition['$search']){
				$condition = array_merge($condition, array('$text' => array('$search' => $condition['$search'])));
				unset($condition['$search']);
				$options = array('_score' => array('$meta' => 'textScore'));
				$sort = array_merge($options, $sort);
			}
			foreach($condition as $key => $value){
				if (is_array($value) && key($value) == '$regex'){
					$regex = new \MongoRegex('/'.$value['$regex'].'/i');
					$condition[$key] = $regex;
				}
			}
			$dataset = $this->app['db']
				->selectCollection($this->getScheme()->get('name'))
				->find($condition, $options)
				->sort($sort)
				->limit($limit)
				->skip($skip);
			$response = @(new helper\iterator())
				->setTotal($dataset->count())
				->setRange($limit)
				->setPart($skip / $limit);
			foreach($dataset->toArray() as $data){
				$response->add((new static)->set($data));
			}
			return $response;
		}

		/**
		 * Load one item
		 *
		 * @param string $field Field
		 * @param mixed $value Value
		 * @return this
		 */
		public function loadOne($condition, $value = null){
			if (!is_array($condition)){
				$condition = array($condition => $value);
			}
			$data = $this->load($condition, array('_id' => -1), 1, 0);
			$object = ($data->getFirst() instanceof self ? $data->getFirst() : new static);
			return $this->set($object->get());
		}

		/**
		 * Load item by id
		 *
		 * @param string $id MongoId
		 * @return this
		 */
		public function loadById($id = null){
			return $this->loadOne('_id', $this->mongoid($id));
		}

		public function prepareValues(){
			if ($this->getScheme() !== null){
				foreach($this->getScheme()->get('fields')->all() as $field => $properties){
					$value = $this->get($field);
					switch($properties['type']){
						case 'integer':
							$this->set($field, (int)$value);
						break;
						case 'numeric':
							$this->set($field, $value * 1);
						break;
						case 'boolean':
							$value = ($value === true || $value === 'true' || (int)$value === 1 ? true : false);
							$this->set($field, $value);
						break;
						case 'date':
							if (@get_class($value) != 'MongoDate'){
								$this->set($field, new \MongoDate(strtotime($value)));
							}
						break;
						case 'password':
							if (password_get_info($value)['algo'] == 0){
								$this->set($field, $this->hash($value));
							}
						break;
						case 'entity':
							if (@$properties['multiple'] === true){
								$value = $value->all();
								foreach($value as $key => $val){
									if ((string)$val === (string)$this->mongoid($val)){
										$value[$key] = $this->mongoid($val);
									}
								}
							} else {
								if ((string)$value === (string)$this->mongoid($value)){
									$value = $this->mongoid($value);
								}
							}
							$this->set($field, $value);
						break;
						case 'acl':
							if (@get_class($value) != 'app\models\helper\proto'){
								$value = array();
							} else {
								$value = $value->all();
							}
							foreach($value as $entity => $actions){
								foreach($actions as $action => $permission){
									$actions[$action] = ($permission === true || $permission === 'true' || (int)$permission === 1 ? true : false);
								}
								$value[$entity] = $actions;
							}
							$this->set($field, $value);
						break;
					}
				}
			}
			return $this;
		}

		public function setFile($data, $file = null){
			$files = array();
			if (is_array($data)){
				$files = $data;
			} else {
				$files[$data] = $file;
			}
			$folder = $this->app['config']->get('routes')->get('upload').DIRECTORY_SEPARATOR.$this->getScheme()->get('name');
			$path = $this->app['config']->get('paths')->get('root').$folder;
			$fileArray = array();
			foreach($files as $key => $value){
				if (!is_array($value)){
					$value = array($value);
				}
				// TODO: Check entity properties for multiple
				foreach($value as $file){
					$filename = uniqid('f-'.$key.'-').'.'.$file->guessExtension();
					$fileProperties = array(
						'title'		=> $file->getClientOriginalName(),
						'route'		=> $folder.DIRECTORY_SEPARATOR.$filename,
						'file'		=> $filename,
						'size'		=> filesize($file->getPathname()),
						'mime'		=> $file->getMimeType()
					);
					$file->move($path, $filename);
					if (
						$this->getScheme()->get('fields')->get($key) &&
						$this->getScheme()->get('fields')->get($key)->get('type') == 'image'
					){
						list($width, $height) = getimagesize($path.DIRECTORY_SEPARATOR.$filename);
						if ($width && $height){
							$fileProperties['width'] = $width;
							$fileProperties['height'] = $height;
						}
					}
					$fileArray[$key][] = $fileProperties;
				}
			}
			return $this->set($fileArray);
		}

		/**
		 * Check item data for save
		 *
		 * @return array|true Array of alerts or True if accepted
		 */
		public function check(){
			$this->prepareValues();
			$alerts = new Alerts();
			$validate = function($field, $value, $asserts) use($alerts){
				$validators = $custom = array();
				foreach($asserts as $assertName => $assertProperties){
					$className = 'Symfony\Component\Validator\Constraints\\'.$assertName;
					if (class_exists($className)){
						$validators[] = new $className($assertProperties);
					} else {
						$custom[$assertName] = $assertProperties;
					}
				}
				$fieldAlerts = $this->app['validator']->validateValue(array($field => $value), new Asserts(array($field => $validators)));
				foreach($fieldAlerts as $alert){
					$alerts->add(new Alert($alert->getMessage(), null, array(), null, trim($alert->getPropertyPath(), '[]'), $alert->getInvalidValue()));
				}
				if (count($custom)){
					foreach($custom as $assertName => $assertProperties){
						$explan = new Explan();
						$expression = $explan->evaluate($assertProperties['expression'], array(
							'this'	=> $this,
							'value'	=> $value
						));
						if(!$expression){
							$alerts->add(new Alert($assertProperties['message'], null, array(), null, $field, $value));
						}
					}
				}
			};

			// TODO: DRY!
			foreach($this->getScheme()->get('fields')->get() as $field => $properties){
				if (isset($properties['validation']) && is_array($properties['validation'])){
					if (@$properties['multiple']){
						if (in_array($properties['type'], array('file', 'image'))){
							if (is_null($this->get($field)) || get_class($this->get($field)) != 'app\models\helper\iterator'){
								$value = null;
								$id = null;
							} else {
								foreach($this->get($field) as $id => $item){
									$item = (new helper\proto())->set($item);
									if (!file_exists($this->app['config']->get('paths')->get('root').$item->get('route'))){
										$value = null;
									} else {
										$value = new File($this->app['config']->get('paths')->get('root').$item->get('route'));
									}
								}
							}
							$validate($field.'.'.$id, $value, $properties['validation']);
						} else {
                            $fieldData = $this->get($field);
                            if ($fieldData && ($fieldData instanceof helper\proto) && $fieldData->get()) {
                                foreach($fieldData->get() as $id => $item){
                                    $validate($field.'.'.$id, $item, $properties['validation']);
                                }
                            } else {
                            	$validate($field, $this->get($field), $properties['validation']);
                            }
						}
					} else {
						if (in_array($properties['type'], array('file', 'image'))){
							if (is_null($this->get($field)) || get_class($this->get($field)) != 'app\models\helper\iterator' || !file_exists($this->app['config']->get('paths')->get('root').$this->get($field)->getFirst()->get('route'))){
								$value = null;
							} else {
								$value = new File($this->app['config']->get('paths')->get('root').$this->get($field)->getFirst()->get('route'));
							}
						} else {
							$value =  $this->get($field);
						}
						$validate($field, $value, $properties['validation']);
					}
				}
            }

            return ($alerts->count() > 0 ? $alerts : true);
		}

		/**
		 * Base check one item data
		 *
		 * @param string $field name check field
		 * @param string $valid Advance type validation, if null then use validation with NotBlank
		 * @return array|true Array of alerts or True if accepted
		 */
		public function checkOne($field,$valid = null){
			$alerts = new Alerts();
			$validate = function($field, $value, $asserts) use($alerts){
				$validators = array();
				foreach($asserts as $assertName => $assertProperties){
					$className = 'Symfony\Component\Validator\Constraints\\'.$assertName;
					if (class_exists($className)){
						$validators[] = new $className($assertProperties);
					}
				}
				$fieldAlerts = $this->app['validator']->validateValue(array($field => $value), new Asserts(array($field => $validators)));
				foreach($fieldAlerts as $alert) {
					$alerts->add(new Alert($alert->getMessage(), null, array(), null, trim($alert->getPropertyPath(), '[]'), $alert->getInvalidValue()));
				}
			};

			// TODO: BASE DRY!
            $value = $this->get($field);
			$validation = $valid ? array( 'NotBlank' => null, $valid => null ): array( 'NotBlank' => null );
			$validate($field, $value,$validation);
			return ($alerts->count() > 0 ? $alerts : true);
		}

		/**
		 * Save item
		 *
		 * @return bolean|array Return boolean status of action or Array of check alerts
		 */
		public function save(){
			if (($check = $this->check()) !== true){
				return $check;
			}
			if (is_null($this->get('_created'))){
				$this->set('_created', new \MongoDate());
			}
			$this->set(array(
				'_id'		=> $this->mongoid(),
				'_edited'	=> new \MongoDate()
			));

			$status = @$this->app['db']
				->selectCollection($this->getScheme()->get('name'))
				->save($this->toArray());

			if (@$this->app['db']->lastError()['err']){
				throw new \Exception($this->app['db']->lastError()['err'], $this->app['db']->lastError()['code']);
			}
			if ($status['n']) {
				return true;
			}
			return false;
		}

		/**
		 * Remove item
		 *
		 * @return bolean|array Return boolean status of action or Array of check alerts
		 */
		public function remove(){
			$files = array();
			foreach($this->getScheme()->get('fields')->get() as $field => $properties){
				if (in_array($properties['type'], array('file', 'image')) && $this->get($field)){
					if (@$properties['multiple'] === true){
						foreach($this->get($field) as $file){
							if (file_exists($this->app['config']->get('paths')->get('root').$file['route'])){
								$files[] = $this->app['config']->get('paths')->get('root').$file['route'];
							}
						}
					} else {
						if (method_exists($this->get($field), 'getFirst') && file_exists($this->app['config']->get('paths')->get('root').$this->get($field)->getFirst()->get('route'))){
							$files[] = $this->app['config']->get('paths')->get('root').$this->get($field)->getFirst()->get('route');
						}
					}
				}
			}
			if (count($files)){
				$this->app['fs']->remove($files);
			}
			$status = $this->app['db']
				->selectCollection($this->getScheme()->get('name'))
				->remove(array(
					'_id' => $this->mongoid()
				));

			if (@$this->app['db']->lastError()['err']){
				throw new \Exception($this->app['db']->lastError()['err'], $this->app['db']->lastError()['code']);
			}
			if ($status['n']) {
				return true;
			}
			return false;
		}


		/**
		 * Helper for MongoId
		 *
		 * @param object|string $id MongoId
		 * @return object MongoId
		 */
		public function mongoid($id = null){
			$id = (is_null($id) ? $this->get('_id') : $id);
			try {
				$id = new \MongoId($id);
			} catch(\MongoException $e){
				$id = new \MongoId();
			}
			return $id;
		}

		/**
		 * Load scheme
		 *
		 * @return this
		 */
		private function loadScheme(){
			$calledClass = get_called_class();
			$classPath   = str_replace('\\', '/', $calledClass);
			$schemeFile  = $this->app['config']->get('paths')->get('atom').'/'.$classPath.'.yml';

			if (file_exists($schemeFile)){
				$this->scheme = (new helper\proto())->set(Yaml::parse(file_get_contents($schemeFile)));
			} else {
				$this->scheme = new helper\proto();
			}
			return $this;
		}

		/**
		 * Get scheme
		 *
		 * @return array
		 */
		public function getScheme(){
			if (is_null($this->scheme)){
				$this->loadScheme();
			}
			return $this->scheme;
		}

		/**
		 * Load routes
		 *
		 * @return this
		 */
		private function loadRoutes(){
			$routesFile  = $this->app['config']->get('paths')->get('atom').'/app/routes/'.$this->getScheme()->get('name').'.yml';
			if (file_exists($routesFile)){
				$this->routes = (new helper\proto())->set(Yaml::parse(file_get_contents($routesFile)));
			} else {
				$this->routes = (new helper\proto());
			}
			return $this;
		}

		/**
		 * Get routes
		 *
		 * @return array
		 */
		public function getRoutes(){
			if (is_null($this->routes)){
				$this->loadRoutes();
			}
			return $this->routes;
		}

		/**
		 * Get related data
		 *
		 * @return mixed
		 */
		public function getRelated($entity){
			$modelName = '\app\models\\'.$entity.'\\'.$entity;
			$id = $this->get($entity);
			if (method_exists($id, 'all')){
				$id = $id->all();
			} else {
				$id = array($id);
			}
			return (new $modelName())->load(array(
				'_id'		=> array('$in' => $id),
				'enabled'	=> true
			));
		}

        /**
         * Hash value by bcrypt
         *
         * @param string $value Value for hash
         * @param array properties Properties for hash. Need cost and salt keys
         * @returen string Hash
         */

        public function hash($value, $properties = array()){
			return password_hash($value, PASSWORD_BCRYPT, (is_array($properties) && !empty($properties) ? $properties : $this->app['config']->get('hash')->all()));
		}

		/**
		 * Check entity unique
		 *
		 * @param string|array $fields One or array of fields name for searching
		 * @return boolean Return true if object is really unique in collection, return false if not
		 */
		public function isUnique($fields){
			$values = array_map(function($field){
				return $this->get($field);
			}, (array)$fields);
			if(in_array('emailtoreport',$values)) return true;
			$data = (new static)->load(array_combine((array)$fields, $values));
			if ($data->count()){
				$diffObject = ($data->getFirst() instanceof self ? $data->getFirst() : new static);
				if (is_null($diffObject->get('_id')) == false && $this->get('_id') != $diffObject->get('_id')){
					return false;
				}
			}
			return true;
		}

		/**
		 * Check date actual
		 *
		 * @param string $field Field name for check
		 * @param string $compareDate Date for compare, if null then array('<=' => 'now')
		 * @return boolean
		 */
		public function isActualDate($field, $compareDate = null){
			$timestamp = (get_class($this->get($field)) == 'MongoDate' ? $this->get($field)->sec : strtotime($this->get($field)));
			if (is_null($compareDate)){
				$compareDate = array('<=' => 'now');
			}
			foreach($compareDate as $compare => $datetime){
				$compareTimestamp = strtotime($datetime);
				if (!eval("return $timestamp $compare $compareTimestamp;")){
					return false;
				}
			}
			return true;
		}

		/**
		 * Check value required field and validation field
		 *
		 * @param string $fieldRequire Field name which required
		 * @param string $fieldCheck Field name for check
		 * @param string $valid Advance type validation, if null then use validation with NotBlank
		 * @return boolean
		 */
		public function isValueFormIsTrue($fieldRequire, $fieldCheck, $valid = null){
			if ($this->get($fieldRequire))
				return ($this->checkOne($fieldCheck, $valid) === true)? true : false;

			return true;
		}
	}