<?php namespace Nassau\Config;

class Config implements \IteratorAggregate {
	private $_ = Array ();
	
	public function __construct($source) {
		$this->_ = \Symfony\Component\Yaml\Yaml::parse($source);
	}
	public function getIterator () {
	  return new \ArrayIterator($this->_);
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
		return $val;
	}

  public function __get($key) {
  	return $this->read($key);	
  }
}

