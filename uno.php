<?php
/**
 * MIT License
 * @see: http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright (c) <2011> <Andrea Belvedere> <scieck@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
*/

class URI
{
    protected $scheme;
    protected $hostname;
    protected $port;
    protected $path;
    protected $query;
    protected $current;

    protected static $instance = NULL;


    protected function __construct()
    {
        $this->current = $this->scheme().'://'.$this->hostname();
        $this->current .= strlen($this->port()) ? ':'.$this->port() : '';
        $this->current .= '/'.$this->path();
        $this->current .= strlen($this->query()) ? '?'.$this->query() : '';
    }

    public static function getInstance()
    {
        if (NULL === static::$instance)
        {
            static::$instance = new URI();
        }
        return static::$instance;
    }

    public function scheme()
    {
        if (isset($this->scheme)) return $this->scheme;

        $this->scheme = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && ('off' != $_SERVER['HTTPS'])) ? 'https' : 'http';
        return $this->scheme;
    }

    public function hostname()
    {
        if (isset($this->hostname)) return $this->hostname;

        $this->hostname = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] :
            isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : isset($_SERVER['HTTP_HOST']) ?
            $_SERVER['HTTP_HOST'] : '';

        return $this->hostname;
    }

    public function port()
    {
        if (isset($this->port)) return $this->port;

        $this->port = isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] != '80') ? $_SERVER['SERVER_PORT'] : '';
        return $this->port;
    }

    public function path()
    {
        if (isset($this->path)) return $this->path;

        switch (true)
        {
        case isset($_SERVER['PATH_INFO']):
            $this->path = $_SERVER['PATH_INFO'];
            break;
        case isset($_SERVER['ORIG_PATH_INFO']):
            $this->path = $_SERVER['ORIG_PATH_INFO'];
            break;
        default:
            $this->path = '';
        }
        $this->path = trim($this->path, '/');
        return $this->path;
    }

    public function query()
    {
        if (isset($this->query)) return $this->query;

        $this->query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
        return $this->query;
    }

    public function current()
    {
        return $this->current;
    }
}

class Config
{
    protected static $config = array();
    protected $name;
    protected $params;
    protected $editable;

    protected function __construct(array $params, $editable = FALSE, $name = 'uno')
    {
        $this->name = $name;
        $this->params = $params;
        $this->editable = (bool)$editable;
    }

    public static function factory(array $params, $editable = FALSE, $name = 'uno')
    {
        if (array_key_exists($name, static::$config))
        {
            return static::$config[$name];
        }
        static::$config[$name] = new Config($params, $editable, $name);
        return static::$config[$name];
    }

    public static function getConfig($name = 'uno')
    {
        return array_key_exists($name, static::$config) ? static::$config[$name] : NULL;
    }

    public function set($name, $value)
    {
        if (! $this->editable)
        {
            throw new Exception(sprintf(_('Config file: "%s" cannot be edited'), $this->name));
        }
        $this->params[$name] = $value;
    }

    public function get($name, $default = NULL)
    {
        if (array_key_exists($name, $this->params))
        {
            return $this->params[$name];
        }
        return $default;
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->params);
    }
}

/**
 * Maps URI path to controller and action
 */
class Router
{
    protected $path;
    protected $routes; // not yet used

    protected $module;
    protected $controller;
    protected $action;
    protected $args;

    protected $config;


    public function __construct()
    {
        $this->path = URI::getInstance()->path();
        $this->config = Config::getConfig();
        $this->args = array(); // action arguments

        $this->module = $this->config->module;
        $this->controller = $this->config->controller;
        $this->action = $this->config->action;

        if (! empty($this->path))
        {
            $this->route();
        }
    }

    protected function route()
    {
        $parts = preg_split('/\//', $this->path, -1, PREG_SPLIT_NO_EMPTY);

        // the first segment in the path is the controller
        $this->controller = array_shift($parts);
        // if there are more segments, the next one is the action
        if (count($parts))
        {
            $this->action = array_shift($parts);
        }
        // anything left are parameters
        $this->args = $parts;
    }

    public function controller()
    {
        return $this->controller;
    }

    public function action()
    {
        return $this->action;
    }

    public function args()
    {
        return $this->args;
    }

    public function addRoute(array $route)
    {
        throw new Exception(__METHOD__.' '._('Not Implemented'));
    }
}


class Dispatcher
{
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function dispatch()
    {
        $controllerFile = APPPATH .'controllers/'. $this->router->controller() .'.php';
        if (! is_file($controllerFile))
        {
            $this->show404(URI::getInstance()->current());
        }
        require_once $controllerFile;

        $classname = $this->router->controller().'Controller';
        $controller = new $classname();

        $action = $this->router->action();
        $parts = $this->router->args();

        switch (count($parts))
        {
        case 0:
            $controller->$action();
            break;
        case 1:
            $controller->$action($parts[0]);
            break;
        case 2:
            $controller->$action($parts[0], $parts[1]);
            break;
        case 3:
            $controller->$action($parts[0], $parts[1], $parts[2]);
            break;
        case 4:
            $controller->$action($parts[0], $parts[1], $parts[2], $parts[3]);
            break;
        case 5:
            $controller->$action($parts[0], $parts[1], $parts[2], $parts[3], $pargs[4]);
            break;
        default:
            // all right then
            call_user_func_array(array($controller, $action), $parts);
        }
    }

    /**
     * Looks for a 404.php controller having class name FOFController
     * and a default action, if not found it echo a 404 message
     * and exits
     *
     * @param string $url The Not Found URL
     */
    public function show404($url)
    {
        $controller404 = APPPATH .'controllers/404.php';
        if (is_file($controller404))
        {
            $classname = 'FOFController';
            $action = $this->config->action;

            $controller = new $classname();
            $controller->$action($url);
        }
        else {
            exit("<h1>404 Not Found</h1><p>The following URL address could not be found on this server: {$url}</p>");
        }
    }
}

class Request
{
    protected static $instance = NULL;
    protected $method;

    protected function __construct()
    {
        $this->method = strtolower($_SERVER['REQUEST_METHOD']);
    }

    public static function getInstance()
    {
        if (NULL === static::$instance)
        {
            static::$instance = new Request();
        }
        return static::$instance;
    }

    public function method()
    {
        return $this->method;
    }

    public function isHead()
    {
        return 'head' == $this->method;
    }

    public function isGet()
    {
        return 'get' == $this->method;
    }

    public function isPost()
    {
        return 'post' == $this->method;
    }

    public function isPut()
    {
        return 'put' == $this->method;
    }

    public function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    public function redirect($location)
    {
        if (FALSE === stripos($location, 'http', 0))
        {
            $location = URI::getInstance()->scheme() .
                '://' . URI::getInstance()->hostname() .
                '/' . ltrim($location, '/');
        }
        header('Location: '.$location);

        if (! $this->isHead())
        {
            exit('<h1>302 - Found</h1><p><a href="'.$location.'">'.$location.'</a>');
        }
        exit();
    }

    public function refresh()
    {
        $this->redirect(URI::getInstance()->current());
    }
}

class Input
{
    protected static $instance = NULL;
    protected $params;

    protected function __construct()
    {
        $this->params = array(
            'get' => $_GET,
            'post' => $_POST
            );
        if (TRUE === Config::getConfig()->get('xss', FALSE))
        {
            foreach ($_GET as $key => $value)
            {
                $this->params['get'][$key] = htmlspecialchars($value, ENT_QUOTES, Config::getConfig()->get('charset', 'UTF-8'));
            }
            foreach ($_POST as $key => $value)
            {
                $this->params['post'][$key] = htmlspecialchars($value, ENT_QUOTES, Config::getConfig()->get('charset', 'UTF-8'));
            }
        }
    }

    public static function getInstance()
    {
        if (NULL === static::$instance)
        {
            static::$instance = new Input();
        }
        return static::$instance;
    }

    public function post($key = NULL, $default = NULL)
    {
        if (NULL === $key)
        {
            return $this->params['post'];
        }
        if (array_key_exists($key, $this->params['post'][$key]))
        {
            return $this->params['post'][$key];
        }
        return $default;
    }

    public function get($key = NULL, $default = NULL)
    {
        if (NULL === $key)
        {
            return $this->params['get'];
        }
        if (array_key_exists($key, $this->params['get'][$key]))
        {
            return $this->params['get'][$key];
        }
        return $default;
    }
}

abstract class Controller
{
    protected $uri;
    protected $request;
    protected $input;

    public function __construct()
    {
        $this->uri = URI::getInstance();
        $this->request = Request::getInstance();
        $this->input = Input::getInstance();
    }

    protected function refresh()
    {
        $this->request->refresh();
    }

    protected function redirect($location)
    {
        $this->request->redirect($location);
    }

    protected function isGet()
    {
        return $this->request->isGet();
    }

    protected function isPost()
    {
        return $this->request->isPost();
    }

    protected function isAjax()
    {
        return $this->request->isAjax();
    }

    protected function method()
    {
        return $this->request->method();
    }

    protected function post($key = NULL, $default = NULL)
    {
        return $this->input->post($key, $default);
    }

    protected function get($key = NULL, $default = NULL)
    {
        return $this->input->get($key, $default);
    }

    public function __call($method, array $args)
    {
        exit("<h1>404 Not Found</h1><p>The following URL address could not be found on this server: {$this->uri->current()}</p>");
    }
}

class View
{

}

class Database extends PDO
{

}

class ORM
{
    protected $db;
    protected $primaryKey = 'id';
    protected $tableName;
    protected $field;
    protected $changed;
    protected $loaded;

    protected function __construct($tableName)
    {
        $this->tableName = $tableName;
        $this->field = array();
        $this->changed = array();
        $this->loaded = false;
    }

    public static function factory($tableName, $id = NULL)
    {
        if (NULL === $id)
        {
            return new ORM($tableName);
        }
        $orm = new ORM($tableName);
        $orm->load($id);
        return $orm;
    }

    public function load($id)
    {

    }

    public function save()
    {

    }

    public function delete($id = NULL)
    {

    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->field))
        {
            return $this->field[$name];
        }
        return NULL;
    }

    public function __set($name, $value)
    {
        $this->field[$name] = $value;
        if (! in_array($name, $this->changed))
        {
            $this->changed[] = $name;
        }
    }

    public function loaded()
    {
        return $this->loaded;
    }

    protected function primaryKey($field = NULL)
    {
        return $this->primaryKey;
    }
}


class Uno
{
    const REQUIRED_PHP_VERSION = '5.3.0';

    public static function run(array $config)
    {
        if (version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION) < 0)
        {
            exit(sprintf('Uno requires PHP version %s or greater.', self::REQUIRED_PHP_VERSION));
        }
        Config::factory($config);
        $dispatcher = new Dispatcher(new Router());
        $dispatcher->dispatch();
    }
}
