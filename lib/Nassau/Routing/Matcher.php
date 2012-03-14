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
      if (empty($route['Route'])) continue;
      if (empty($route['Controller'])) continue;

      $routeStr = '/' . ltrim($route['Route'], '/');
      $regexp = preg_quote($routeStr, '/');
      
      $this->params = array ();
      $regexp = $this->parseParams($regexp, $route['Params']);

    // not formated params:
      $regexp = preg_replace('/\/?\\\\:([a-z]+)/', '/(?<\1>.+)', $regexp);
        
      if (preg_match(sprintf('/^%s$/', $regexp), $this->request, $match)) {
        foreach ($match as $key => $value) {
          if (!is_numeric($key)) {
            $this->params[$key] = $value;
          }
        }
        
        $data['route'] = $route['Route'];
        $data['name'] = $name;
        $data['action'] = $route['Action'];
        $data['controller'] = $route['Controller'];
        $data['method'] = $route['Method'];
        $data['params'] = $this->params + $query;

        return $data;
      }
    
    endforeach;
    throw new \Exception("No route found for request: $this->request");
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
    if ($params) foreach ($params as $name => $value) {
      if ($value instanceof \Nassau\Config\Config) $value = $value->toArray();
      $value = is_array($value) ? $value : explode(' ', $value);
      list ($format, $default) = array_pad($value, 2, null);
      
      $optional = substr($format, -1) == '?';
      if ($optional) {
        $format = substr($format, 0, -1);
      }
      
      $optional |= ($default !== null);
      
      $this->params[$name] = $default;
      
      $part_re = sprintf('\/(?<%s>%s)', $name, $format);
      if ($optional) $part_re = sprintf('(%s)?', $part_re);
      
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
