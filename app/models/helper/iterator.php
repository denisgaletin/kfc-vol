<?php

	namespace app\models\helper;

	class iterator implements \Iterator{

		use \app\models\traits\prototype;

		private $total = 0;

		private $part = 0;

		private $range = 0;

		public function setTotal($count){
			$this->total = (int)$count;
			return $this;
		}

		public function setRange($range){
			$this->range = (int)$range;
			return $this;
		}

		public function setPart($part){
			$this->part = (int)$part;
			return $this;
		}

		public function getTotal(){
			return $this->total;
		}

		public function getRange(){
			return $this->range;
		}

		public function getPart(){
			return $this->part;
		}

		public function getTotalParts(){
			return floor($this->getTotal() / $this->getRange());
		}

		public function getNextPart($from = null){
			$from = (is_null($from) ? $this->getPart() : $from);
			return ($from == $this->getPartTotal() ? null : $from + 1);
		}

		public function getPrevPart($from = null){
			$from = (is_null($from) ? $this->getPart() : $from);
			return ($from ? $from - 1 : null);
		}

		public function getNearParts($range = 2, $from = null){
			$from = (is_null($from) ? $this->getPart() : $from);
			$parts = array();
			for($i = $range; $i; --$i){
				if ($this->getPartPrev($from - $i)){
					$parts[] = $this->getPartPrev($from - $i);
				}
			}
			$parts[] = $from;
			for($i = 1; $i <= $range; ++$i){
				if ($this->getPartNext($from + $i)){
					$parts[] = $this->getPartNext($from + $i);
				}
			}
			return $parts;
		}

		public function getFirst(){
			return $this->get(0);
		}

		public function getLast(){
			return $this->get($this->getCount() - 1);
		}

		public function count(){
			return count($this->data);
		}

		public function add($data){
			array_push($this->data, $data);
			return $this;
		}
		
		public function merge(iterator $iterator){
			foreach($iterator as $data){
				$this->add($data);
			}
			return $this;
		}

		public function all(){
			$array = array();
			foreach($this->get() as $key => $item){
				$array[$key] = (is_object($item) && get_class($item) != 'MongoId' ? $item->get() : $item);
			}
			return $array;
		}

		public function rewind(){
			reset($this->data);
			return $this;
		}

		public function current(){
			return current($this->data);
		}

		public function key(){
			return key($this->data);
		}

		public function next(){
			return next($this->data);
		}

		public function prev(){
			return prev($this->data);
		}

		public function valid(){
			return is_null(key($this->data)) == false;
		}
	}