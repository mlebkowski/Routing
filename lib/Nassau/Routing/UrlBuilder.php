<?php namespace Nassau\Routing;

class UrlBuilder {
  private $routes;
  
  const MODE_USE_DEFAULT		= 0x01;
  const MODE_IGNORE_FORMAT	= 0x02;

  public function __construct(\Nassau\Config\Config $routes) {
    $this->routes = $routes;
  }
  
  public function build($name, $params = array (), $mode = null) {
  	$useDefault		= $mode & self::MODE_USE_DEFAULT;
  	$ignoreFormat	= $mode & self::MODE_IGNORE_FORMAT; 
  
    $route = $this->routes->read($name);
    if (!$route || empty($route['Route'])) {
      throw new \InvalidArgumentException("Invalid route: $name");
    }

    $routeName = $name;
    $url = '/' . ltrim($route['Route'], '/');

    
    $placeholders = array();
    
    if (preg_match_all('/\/?\:([a-z]+)/', $url, $m)) {
      foreach ($m[1] as $name) {
        $placeholders[$name] = '.+';
      }
    }
    
    if ($route['Params']) foreach ($route['Params'] as $name => $value) {
      $value = ($value instanceof \Nassau\Config\Config) ? $value->toArray()
         : (is_array($value) ? $value : explode(' ', $value, 2));
       
      $placeholders[$name] = $value;
    }
    
    
    foreach ($placeholders as $name => $value) {
      list ($format, $default) = array_pad((array)$value, 2, null);
      $optional = (substr($format, -1) == '?') || ($default !== null);
      
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
      $url = preg_replace($e='/\\/:' . $name . '/', $p ? '/' . $p : '', $url);
      
      unset($params[$name]);
    }
    if (sizeof($params)) $url .= '?' . http_build_query($params);
    return $url;
  }
}
