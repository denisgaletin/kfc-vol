<?php

	namespace app\models\helper;

	class helper{
		public function __construct(){
			global $app;
			$this->app = $app;
		}

		/**
		 * Recursive scan directories by pattern
		 *
		 * @param string $patterns Simple pattern
		 * @param conts $flags Glob function flags
		 * @return array
		 */
		public function glob($patterns, $flags = 0){
			$files = array();
			foreach((array)$patterns as $pattern){
				$files = array_merge($files, glob($pattern, $flags));
				$dirs = glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT);
				if (empty($dirs)) continue;
				foreach ($dirs as $dir){
					$files = array_merge($files, $this->glob($dir.'/'.basename($pattern), $flags));
				}
			}
			return $files;
		}

		/**
		 * Password generator
		 *
		 * @param integer $length Length of password, max 32
		 * @return string
		 */
		public function pwgen($length = 6){
			return substr(md5(uniqid()), 0, ($length <= 32 ? $length : 32));
		}

		/**
		 * Russian word forms
		 *
		 * @param integer $number Number used for word
		 * @param array $words Array of word in 3 cases
		 * @return string
		 */
		public function wordForm($number, $words){
			$cases = array (2, 0, 1, 1, 1, 2);
			return $words[ ($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)] ];
		}

		/**
		 * XML generator from Object
		 */
		public static function generateValidXmlFromObj($obj, $node_block='nodes', $node_name='node') 
		{
	        $arr = get_object_vars($obj);
	        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
	    }

	    /**
		 * XML generator from Array
		 */
	    public static function generateValidXmlFromArray($array, $node_block='nodes', $node_name='node') 
	    {
	        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

	        $xml .= '<' . $node_block . '>';
	        $xml .= self::generateXmlFromArray($array, $node_name);
	        $xml .= '</' . $node_block . '>';

	        return $xml;
	    }

	    /**
		 * XML generator from Array
		 */
	    private static function generateXmlFromArray($array, $node_name) 
	    {
	        $xml = '';

	        if (is_array($array) || is_object($array)) {
	            foreach ($array as $key=>$value) {
	                if (is_numeric($key)) {
	                    $key = $node_name;
	                }

	                $xml .= '<' . $key . '>' . self::generateXmlFromArray($value, $node_name) . '</' . $key . '>';
	            }
	        } else {
	            $xml = htmlspecialchars($array, ENT_QUOTES);
	        }

	        return $xml;
	    }
	}
