<?php namespace Nassau\Routing;

class UrlBuilder {
	protected $routes;

	const MODE_USE_DEFAULT		= 0x01;
	const MODE_IGNORE_FORMAT	= 0x02;
	const MODE_DROP_UNKNOWN		= 0x04;

	public function __construct(\Nassau\Config\Config $routes) {
		$this->routes = $routes;
	}

	public function build($name, $params = array (), $mode = null) {
		$useDefault		= $mode & self::MODE_USE_DEFAULT;
		$ignoreFormat	= $mode & self::MODE_IGNORE_FORMAT;
		$dropUnknown	= $mode & self::MODE_DROP_UNKNOWN;

		$route = $this->routes->read($name);
		if (!$route || empty($route['route'])) {
			throw new \InvalidArgumentException("Invalid route: $name");
		}

		$routeName = $name;
		$url = '/' . ltrim($route['route'], '/');

		$placeholders = self::getRouteParams($route);

		foreach ($placeholders as $name => $value) {
			extract($value, EXTR_OVERWRITE);
			// format, optional, name, default

			$p = isset($params[$name]) ? $params[$name] : ($useDefault ? $default : null);
			if (is_null($p) && !$optional) {
				throw new \InvalidArgumentException("Argument $name is required for route $routeName");
			}
			if (!$ignoreFormat && !preg_match('/^' . $format . '$/', $p)) {
				if ($optional) {
					$p = null;
				} else {
					throw new \InvalidArgumentException("Argument $name ($p) doesn`t match format"
					. " \"$format\" for route \"$routeName\"");
				}
			}
			$url = preg_replace('/\\/:' . $name . '/', $p ? '/' . $p : '', $url);

			unset($params[$name]);
		}
		
		if (false == $dropUnknown && sizeof($params)) {
			$url .= '?' . http_build_query($params);
		}
		
		return $url;
	}
	
	public static function getRouteParams($route) {
		$params = $route['params'];	
	
		$url = '/' . ltrim($route['route'], '/');
		
		$placeholders = array ();

		if (preg_match_all('/\/?\:([a-z0-9_]+)/', $url, $m)) {
			foreach ($m[1] as $name) {
				if (false === isset($params[$name])) {
					$params[$name] = '.+';
				}
			}
		}

		if ($params) foreach ($params as $name => $value) {
			$value = ($value instanceof \Nassau\Config\Config) ? $value->toArray()
				: (is_array($value) ? $value : explode(' ', $value, 2));
			
			list ($format, $default) = array_pad((array)$value, 2, null);
			
			$placeholders[$name] = array (
				'name'		=> $name,
				'default'	=> $default,
				'format'	=> $format,
				'optional'	=> (substr($format, -1) == '?') || ($default !== null),
			);
		}
		return $placeholders;
	}
}
