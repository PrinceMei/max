<?php
namespace Max;

use \Max\Config;

class App extends \Slim\Slim {
    private $db;
    protected static $ALLOWED_HTTP_METHODS = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');
    protected $routeNames = array();

    //从app根目录下的.json文件中解析JSON
    private function getJSONFromFile($file) {
        $file = __DIR__ . '/../../' . basename($file);
        if(file_exists($file)) {
            //格式化json为php内置对象
            $jsonData = json_decode(file_get_contents($file), true);
            if(!is_null($jsonData)) {
                return $jsonData;
            }
        }
        //文件不存在或json解析错误时返回null
        return null;
    }

    public function __construct() {
        $configData = $this->getJSONFromFile('config.json');
        $middlewareFile = __DIR__ . '/../../middleware.php';
        //导入中间件
        if(file_exists($middlewareFile)) {
            require $middlewareFile;
        }
        if(!is_null($configData)) {
            //保存用户的配置项
            foreach($configData as $key => $val) {
                \Max\Config::set($key, $val);
            }
            //为app的运行增加固定的配置
            $configData['app']['view'] = new \Slim\Views\Twig();
            //使用绝对路径，避免index.php被移动到子目录导致访问不到的问题
            $configData['app']['templates.path'] = __DIR__ . '/../../Views';
            //手动调用父类构造函数
            parent::__construct($configData['app']);
            //父类初始化后处理路由
            $this->getRoutes();
        } else {
            throw new \RuntimeException("缺少配置文件config.json，或配置文件JSON格式错误");
        }
    }

    private function getRoutes() {
        $routes = $this->getJSONFromFile('route.json');
        if(!is_null($routes)) {
            //遍历路由
            foreach($routes as $path => $routeArgs) {
                //把$routeArgs中全部的key取出来，判断是否为rout group
                $argKeys = array_keys($routeArgs);
                //因为path都是以/开始的，所以只需要判断第一个
                if(strpos($argKeys[0], '/') === 0){
                    foreach($routeArgs as $subPath => $val){
                        $subPath = $path . $subPath;
                        $this->parseRouteArgs($subPath, $val);
                    }
                }else{
                    $this->parseRouteArgs($path, $routeArgs);
                }
            }
        } else {
            throw new \RuntimeException("缺少配置文件config.json，或配置文件JSON格式错误");
        }
    }

    //解析路由path后的值（不处理分组的情况）
    private function parseRouteArgs($path, $routeArgs){
        if(is_string($routeArgs)) {
            //简单的路由："path": "Class:action",
            $this->addControllerRoute('GET', $path, $routeArgs);
        } else {
            if(!is_null($routeArgs['middleware'])) {
                /*
                 * 匹配
                 * "path": {
                 *  "middleware": ["auth"],
                 *  "action": "Class:action"
                 * }
                 * 或者
                 * "path": {
                 *  "middleware": ["auth"],
                 *  "action": {
                 *      "get": "Class:action",
                 *      "post": "Class:action",
                 *      ...
                 *  }
                 * }
                 *},
                 */
                $routeMiddleware = $routeArgs['middleware'];
                if(is_string($routeArgs['action'])) {
                    //action为字符串时视为带中间件的get请求
                    $this->addControllerRoute('GET', $path, $routeArgs['action'], $routeMiddleware);
                } else {
                    //遍历action
                    foreach($routeArgs['action'] as $method => $action) {
                        //转换method为大写
                        $method = strtoupper($method);
                        //只处理$ALLOWED_HTTP_METHODS定义的方法
                        if(in_array($method, static::$ALLOWED_HTTP_METHODS)) {
                            $this->addControllerRoute($method, $path, $action, $routeMiddleware);
                        }
                    }
                }
            } else {
                foreach($routeArgs as $method => $val) {
                    //转换method为大写
                    $method = strtoupper($method);
                    if(in_array($method, static::$ALLOWED_HTTP_METHODS)) {
                        /*
                         * 匹配
                         * "/xx": {
                         *       "get": "Home:getIndex",
                         *       "post": {
                         *           "middleware": [],
                         *           "action": "Home:getIndex"
                         *       }
                         *   },
                         */
                        if(is_string($val)) {
                            //方法后面直接是Controller:method形式
                            $this->addControllerRoute($method, $path, $val);
                        } else {
                            //方法后面包括中间件和action，此时中间件和action都必须存在
                            if(!is_null($val['middleware']) && !is_null($val['action']) &&
                                !empty($val['action'])
                            ) {
                                $this->addControllerRoute($method, $path, $val['action'], $val['middleware']);
                            }
                        }
                    }
                }
            }
        }
    }
    /*
     * @param method http请求方法（$ALLOWED_HTTP_METHODS中的定义）
     * @param path 请求路径
     * @param $controller 请求对应的controller
     * @middleware 中间件
     */
    private function addControllerRoute($method, $path, $controller, $middleware = null) {
        $routeMiddleware = array();
        if(is_string($middleware)) {
            array_push($routeMiddleware, $middleware);
        }
        if(is_array($middleware)) {
            array_merge($routeMiddleware, $middleware);
        }
        //controller匿名函数
        $callback = $this->buildCallbackFromControllerRoute($controller);
        //匿名函数之前加入route url
        array_unshift($routeMiddleware, $path);
        //匿名函数之前加入controller执行函数
        array_push($routeMiddleware, $callback);
        //调用this->map方法传入中间件
        $router = call_user_func_array(array($this, 'map'), $routeMiddleware);
        if(!isset($this->routeNames[$controller])) {
            $router->name($controller);
            $this->routeNames[$controller] = 1;
        }
        $router->via($method);
    }

    //从route创建匿名函数
    private function buildCallbackFromControllerRoute($route) {
        //获得补全后的controller类与方法
        list($controller, $methodName) = $this->determineClassAndMethod($route);
        $app = &$this;
        $callable = function () use ($app, $controller, $methodName) {
            // Get action arguments
            $args = func_get_args();
            // Try to fetch the instance from Slim's container, otherwise lazy-instantiate it
            $instance = $app->container->has($controller) ? $app->container->get($controller) : new $controller($app);
            return call_user_func_array(array($instance, $methodName), $args);
        };
        return $callable;
    }

    //返回包含正确命名控件的Class及方法
    private function determineClassAndMethod($classMethod) {
        //controller前缀处理
        $classNamePrefix = "\\Controller\\";
        $realClassMethod = $classMethod;
        //controller不是以\开始的绝对路径时补全默认controller
        if(strpos($realClassMethod, '\\') !== 0) {
            $realClassMethod = $classNamePrefix . $classMethod;
        }
        //匹配出router中的Class:method
        if(preg_match('/^([a-zA-Z0-9\\\\_]+):([a-zA-Z0-9_]+)$/', $realClassMethod, $match)) {
            $className = $match[1];
            $methodName = $match[2];
        } else {
            throw new \InvalidArgumentException("'$classMethod' 格式错误，参考格式：Class:method。");
        }
        return array($className, $methodName);
    }

    public static function getInstance(){
        return \Slim\Slim::getInstance();
    }
}