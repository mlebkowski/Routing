<?php namespace Nassau\Config;

class Config implements \Iterator, \ArrayAccess {
	public $returnConfig = true;

	private $_ = Array ();

  // Iterator
	private $keys;
  private $pos;
	
	public function __construct($source) {
	  if (!is_array($source)) {
		  $source = \Symfony\Component\Yaml\Yaml::parse($source);
	  }
	  
	  $this->_ = (array)$source;

	  // Iterator:
	  $this->rewind();
	}
	public function getIterator () {
	  return new \ArrayIterator($this->_);
	}
	// Iterator 
	public function current () {
	  $val = $this->_[$this->key()];
	  return $this->__return($val);
	}
	public function key () {
	  return $this->keys[$this->pos];
	}
	public function rewind () {
	  $this->keys = array_keys($this->_);
	  $this->pos = 0;
	}
	public function next () {
	  return ++$this->pos;
	}
	public function valid () {
	  return isset($this->keys[$this->pos]);
	}
	
	public function offsetExists ($offset) {
	  return $this->read($offset) !== null;
	}
	public function offsetGet ($offset) {
	  return $this->read($offset);
	}
	public function offsetSet ($offset, $value) {
	  return null;
	}
	public function offsetUnset ($offset) {
	  return null;
	}
	
	public function toArray() {
	  return $this->_;
	}
	
	public function read($key, $default = null) {
		$keys = explode('/', str_replace(":", "/", $key));
		$val = $this->_;
		do {
			$curKey = array_shift($keys);
			if (!is_array($val) || !array_key_exists($curKey, $val)) return $default;
			$val = $val[$curKey];
		} while (sizeof($keys) > 0);
//		if (is_array($val)) $val = new Config($val);
    return $this->__return($val);
	}
  private function __return ($val) {
		return (is_array($val) && $this->returnConfig) ? new self($val) : $val;
  }

  public function __get($key) {
  	return $this->read($key);	
  }
}

