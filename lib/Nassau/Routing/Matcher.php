<?php namespace Nassau\Routing;

class Matcher {
	private $routes;
	private $authManager;

	private $request;
	private $method = 'GET';
	private $params = array ();

	const FORMATS = 'html, js, json, xml, xls, csv';

	public function __construct(\Nassau\Config\Config $routes, $authManager = null) {
		$this->routes = $routes;
	}

	public function match($request) {
		$this->request = '/' . trim($request, '/');

		$query = $this->getQuery();
		$format = $this->matchFormat();
		$data = array (
			'name' => null,
			'action' => null,
			'route' => null,
			'format' => $format,
			'query' => $query,
		);

		foreach ($this->routes as $name => $route):
			$route = $this->prepareRoute($route);
			if (empty($route['route']) || empty($route['action'])) continue;
	
			$params = UrlBuilder::getRouteParams($route);
			$regexp = $this->getRouteRegexp($route['route'], $params);
	
			// reduce verbose description of params to just default value:
			$params = array_map(function ($x) { return $x['default']; }, $params);
			
			if (preg_match(sprintf('/^%s$/', $regexp), $this->request, $match)) {
				foreach ($match as $key => $value) {
					if (!is_numeric($key)) {
						$params[$key] = $value;
					}
				}
	
				$data['route'] = $route;
				$data['name'] = $name;
				$data['action'] = $route['action'];
				$data['params'] = $params + $query;
	
				return $data;
			}

		endforeach;
		// TODO: maybe a dedicated exception or just null?
		throw new \Exception("No route found for request: $this->request");
	}
	
	public function prepareRoute ($route) {
		if ($route instanceof \Nassau\Config\Config) $route = $route->toArray();
		$route = array_merge(array (
			'route' => null,
			'params' => array (),
		), $route);
		
		foreach ($this->prepareCallbacks as $callback) {
			$route = call_user_func($callback, $route);
		}
		return $route;
	} 
	protected $prepareCallbacks = array ();
	public function registerPrepareCallback(\Closure $callback) {
		$this->prepareCallbacks[] = $callback;
	}
	
	public function getRouteRegexp($routeStr, $params) {
		$routeStr = '/' . ltrim($routeStr, '/');

		$regexp = preg_quote($routeStr, '/');
		
		$regexp = $this->parseParams($regexp, $params);
	
		// not formated params:
		// not configured params should already be taken care of :
		// $regexp = preg_replace('/\/?\\\\:([a-zA-Z0-9_]+)/', '/(?<\1>.+)', $regexp);

		return $regexp;
	}

	private function getQuery() {
		$query = parse_url($this->request, PHP_URL_QUERY);
		if ($query) {
			$this->request = str_replace("?$query", '', $this->request);
			parse_str($query, $query);
		}
		return (array)$query;
	}

	private function parseParams($regexp, $params) {
		foreach ($params as $name => $value) {
			extract($value, EXTR_OVERWRITE); // $format, $optional, $name, $default
			
			if ('?' === substr($format, -1)) { 
				$format = substr($format, 0, -1);
			}

			$part_re = sprintf('\/(?<%s>%s)', $name, $format);
			if ($optional) $part_re = sprintf('(?:%s)?', $part_re);

			$regexp = str_replace('\/\\:' . $name , $part_re, $regexp);
		}
		return $regexp;
	}

	private function matchFormat() {
		$formats = array_map('trim', explode(',', self::FORMATS));
		$format = $formats[0];

		if (preg_match(sprintf('/\.(?<ext>%s)$/', implode('|', $formats)), $this->request, $m)) {
			$format = $m['ext'];
			$this->request = substr($this->request, 0, -1 - strlen($format));
		}
		return $format;
	}
}
