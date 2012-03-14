<?php

  include 'lib/Spl/SplClassLoader.php';
  $o = new SplClassLoader(null, __DIR__ . '/lib');
  $o -> register();
  
  use \Nassau\Config\Config,
      \Nassau\Routing\Matcher,
      \Nassau\Routing\UrlBuilder;
  
  $routes = new Config('etc/routes.yaml');
  $routing = new Matcher($routes);
  $builder = new UrlBuilder($routes);
  
  foreach (array (
//    'plan/1', 'plan/xxx', 'plan/1/edit', '/plan/xxx/edit',
    'plan/1/cost', 'plan/1/cost/2012-03-03',  'plan/1/cost/2010',
//    'log', 'log.html', '/log/1',
//    'zarzadzaj', 
    'raport?a=b'
  ) as $request):
  
    try {
    
      $m = $routing->match($request);
      $url = $builder->build($m['name'], array("filter" => rand(1,10)) + $m['params']);
    
      echo implode(' => ', array($request, $url, $m['route'], print_r($m['params'], true)));
    
    } catch (Exception $E) {
    
      echo $E->getMessage() . "\n";
    }
    
  endforeach;
echo "\n\n";
