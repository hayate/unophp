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

class UnoException extends Exception
{}

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

        $this->scheme = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) &&
                         ('off' != $_SERVER['HTTPS'])) ? 'https' : 'http';
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

        $this->port = isset($_SERVER['SERVER_PORT']) &&
            ($_SERVER['SERVER_PORT'] != '80') ? $_SERVER['SERVER_PORT'] : '';
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
            throw new UnoException(sprintf(_('Config file: "%s" cannot be edited'), $this->name));
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


class Event
{
    protected static $events = array();

    public static function register($name, $callback, array $args = array(), &$ret = NULL)
    {
        $event = new stdClass();
        $event->callback = $callback;
        $event->args = $args;
        $event->ret = $ret;
        static::$events[$name][] = $event;
    }

    public static function unregister($name)
    {
        if (isset(static::$events[$name]))
        {
            unset(static::$events[$name]);
        }
    }

    public static function fire($name, $arg = NULL)
    {
        if (isset(static::$events[$name]))
        {
            for ($i = 0; $i < count(static::$events[$name]); $i++)
            {
                $event = static::$events[$name][$i];
                if (NULL !== $arg)
                {
                    $event->args[] = $arg;
                }
                switch (count($event->args))
                {
                case 0:
                    $event->ret = call_user_func($event->callback);
                    break;
                case 1:
                    $event->ret = call_user_func($event->callback, $event->args[0]);
                    break;
                case 2:
                    $event->ret = call_user_func($event->callback, $event->args[0], $event->args[1]);
                    break;
                case 3:
                    $event->ret = call_user_func($event->callback, $event->args[0], $event->args[1], $event->args[2]);
                    break;
                case 4:
                    $event->ret = call_user_func($event->callback, $event->args[0], $event->args[1], $event->args[2], $event->args[3]);
                    break;
                case 5:
                    $event->ret = call_user_func($event->callback, $event->args[0], $event->args[1], $event->args[2], $event->args[3], $event->args[4]);
                    break;
                default:
                    $event->ret = call_user_func_array($event->callback, $event->args);
                }

            }
            unset(static::$events[$name]);
        }
    }
}

interface IRouter
{
    /**
     * route the request to a controller
     *
     * @return void
     */
    public function route();

    /**
     * @return string The name of the module
     */
    public function module();

    /**
     * @return string The name of the controller
     */
    public function controller();

    /**
     * @return string The name of the action
     */
    public function action();

    /**
     * @return array Parameters passsed from the url
     */
    public function args();

    /**
     * @param array $route Add user defined routes
     * @return void
     */
    public function addRoute(array $route);

    /**
     * @return bool True if this application supports modules, false otherwise
     */
    public function hasModules();
}

class Router implements IRouter
{
    const PreRoute = 'PreRoute';
    const PostRoute = 'PostRoute';

    protected static $instance = NULL;
    protected $router;


    protected function __construct()
    {
        $this->router = Router::factory();
    }

    public static function getInstance()
    {
        if (NULL === static::$instance)
        {
            static::$instance = new Router();
        }
        return static::$instance;
    }

    public function route()
    {
        Event::fire(self::PreRoute, $this);
        $this->router->route();
        Event::fire(self::PostRoute, $this);
    }

    public function module()
    {
        return $this->router->module();
    }

    public function controller()
    {
        return $this->router->controller();
    }

    public function action()
    {
        return $this->router->action();
    }

    public function args()
    {
        return $this->router->args();
    }

    public function addRoute(array $route)
    {
        $this->router->addRoute($route);
    }

    public function hasModules()
    {
        return $this->router->hasModules();
    }

    protected static function factory()
    {
        $config = Config::getConfig();
        switch ($config->dispatch)
        {
        case 'Module':
        case 'module':
            $classname = 'Uno\\ModuleRouter';
            break;
        default:
            $classname = 'ControllerRouter';
        }
        return new $classname();
    }
}

/**
 * Maps URI path to controller and action
 */
class ControllerRouter implements IRouter
{
    protected $path;
    protected $routes; // not yet used

    protected $controller;
    protected $action;
    protected $args;


    public function __construct()
    {
        $this->path = URI::getInstance()->path();
        $this->args = array(); // action arguments

        $config = Config::getConfig();

        $this->controller = $config->controller;
        $this->action = $config->action;

        if (! empty($this->path))
        {
            $this->route();
        }
    }

    public function route()
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

    public function module()
    {
        return '';
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

    public function hasModules()
    {
        return FALSE;
    }
}

interface IDispatcher
{
    public function dispatch();
}

class Dispatcher implements IDispatcher
{
    const PreDispatch = 'PreDispatch';
    const PostDispatch = 'PostDispatch';

    protected $dispatcher;

    public function __construct()
    {
        $this->dispatcher = static::factory();
    }

    public function dispatch()
    {
        Event::fire(self::PreDispatch, $this);
        $this->dispatcher->dispatch();
        Event::fire(self::PostDispatch, $this);
    }

    protected static function factory()
    {
        $config = Config::getConfig();
        switch ($config->dispatch)
        {
        case 'Module':
        case 'module':
            $classname = 'Uno\\ModuleDispatcher';
            break;
        default:
            $classname = 'ControllerDispatcher';
        }
        return new $classname();
    }
}

class ControllerDispatcher implements IDispatcher
{
    protected $router;

    public function __construct()
    {
        $this->router = Router::getInstance();
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
            $action = Config::getConfig()->action;

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

    protected $template = FALSE;


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

    public function __set($name, $value)
    {
        if (FALSE === $this->template)
        {
            $router = Router::getInstance();
            $this->template = $router->controller() .'/'. $router->action();
        }
        if (is_string($this->template))
        {
            $this->template = new View($this->template);
            Event::register(Dispatcher::PostDispatch, array($this->template, 'render'), array(array()));
        }
        $this->template->set($name, $value);
    }

    public function __get($name)
    {
        return $this->template->get($name);
    }

    public function __isset($name)
    {
        return isset($this->template->$name);
    }

    public function __call($method, array $args)
    {
        exit("<h1>404 Not Found</h1><p>The following URL address could not be found on this server: {$this->uri->current()}</p>");
    }
}

class View
{
    protected $view;
    protected $template;


    public function __construct($template)
    {
        $this->view = static::factory();
        $this->template = $template;
    }

    public function __set($name, $value)
    {
        $this->view->set($name, $value);
    }

    public function __get($name)
    {
        return $this->view->get($name);
    }

    public function set($name, $value)
    {
        $this->view->set($name, $value);
    }

    public function get($name)
    {
        return $this->view->get($name);
    }

    public function __isset($name)
    {
        return isset($this->view->$name);
    }

    public function render(array $vars = array())
    {
        $this->view->render($this->template, $vars);
    }

    public function fetch(array $vars = array())
    {
        return $this->view->fetch($this->template, $vars);
    }

    public function __toString()
    {
        return $this->fetch();
    }

    protected static function factory()
    {
        $config = Config::getConfig()->view;
        $classname = $config['name'];
        return new $classname($config);
    }
}

class Native
{
    protected $vars;
    protected $config;
    protected $router;

    public function __construct(array $config)
    {
        $this->vars = array();
        $this->config = $config;
        $this->router = Router::getInstance();
    }

    public function render($template, array $vars = array())
    {
        $params = array_merge($vars, $this->vars);
        extract($params, EXTR_SKIP);
        ob_start();
        try {
            require($this->template($template));
        }
        catch (Exception $ex)
        {
            ob_end_clean();
            throw new UnoException($ex->getMessage(), $ex->getCode(), $ex);
        }
        ob_end_flush();
    }

    public function fetch($template, array $vars = array())
    {
        $params = array_merge($vars, $this->vars);
        extract($params, EXTR_SKIP);
        ob_start();
        try {
            require($this->template($template));
        }
        catch (Exception $ex)
        {
            ob_end_clean();
            throw new UnoException($ex->getMessage(), $ex->getCode(), $ex);
        }
        return ob_get_clean();
    }

    public function get($name, $default = '')
    {
        if (array_key_exists($name, $this->vars))
        {
            return $this->vars[$name];
        }
        return $default;
    }

    public function set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function __isset($name)
    {
        return !empty($this->vars[$name]);
    }

    /**
     * find and return the template file path
     *
     * @param string $template The template file
     * @return string
     */
    protected function template($template)
    {
        if ($this->router->hasModules())
        {
            return APPPATH .'modules/'. $this->router->module() .'/views/'. $template . $this->config['ext'];
        }
        return APPPATH . 'views/' . $template . $this->config['ext'];
    }
}

class Database
{
    protected static $db = array();


    public static function getInstance($name = 'default')
    {
        if (array_key_exists($name, static::$db))
        {
            return static::$db[$name];
        }
        $config = Config::getConfig()->database[$name];
        try {
            static::$db[$name] = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
            static::$db[$name]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            static::$db[$name]->setAttribute(PDO::ATTR_CURSOR, PDO::CURSOR_SCROLL);

            if (isset($config['charset']))
            {
                $driver = static::$db[$name]->getAttribute(PDO::ATTR_DRIVER_NAME);
                switch ($driver)
                {
                case 'mysql':
                    $stm = static::$db[$name]->prepare("SET NAMES ?");
                    break;
                case 'pgsql':
                    // TODO: test this
                    $stm = static::$db[$name]->prepare("SET NAMES '?'");
                    break;
                case 'sqlite':
                case 'sqlite2':
                    // TODO: test this
                    $stm = static::$db[$name]->prepare("PRAGMA encoding='?'");
                    break;
                }
                if (isset($stm))
                {
                    $stm->bindValue(1, $config['charset'], PDO::PARAM_STR);
                    $stm->execute();
                }
            }
            return static::$db[$name];
        }
        catch (Exception $ex)
        {
            throw new UnoException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }
}

class ORM
{
    protected $db;
    protected $primaryKey = 'id';
    protected $tableName;
    protected $field;
    protected $changed;
    protected $loaded;
    protected $connection;

    protected $where;
    private static $values = 'values';
    private static $clause = 'clause';


    public function __construct($tableName, $connection = 'default')
    {
        $this->db = Database::getInstance($connection);
        $this->tableName = $tableName;
        $this->field = array();
        $this->changed = array();
        $this->where = array(self::$clause => NULL,
                             self::$values => array());
        $this->loaded = false;
        $this->connection = $connection;
    }

    public static function factory($tableName, $id = NULL, $connection = 'default')
    {
        if (NULL === $id)
        {
            return new ORM($tableName, $connection);
        }
        $orm = new ORM($tableName, $connection);
        return $orm->load($id);
    }

    public function load($id)
    {
        $query = 'SELECT * FROM ' .$this->tableName. ' WHERE ' . $this->primaryKey($id) . '=? LIMIT 1';
        $stm = $this->db->prepare(1, $id, $this->db->type($id));
        if (! $stm->execute())
        {
            $error = $stm->errorInfo();
            throw new UnoException($error[2]);
        }
        $this->field = $stm->fetch(PDO::FETCH_ASSOC);
        $this->loaded = TRUE;
        return $this;
    }

    public function loadAll($limit = NULL, $offset = NULL)
    {
        $query = 'SELECT * FROM ' .$this->tableName;
        if (! empty($this->where[self::$clause]))
        {
            $query .= (' WHERE ' . $this->where[self::$clause]);
        }
        if (is_int($limit))
        {
            $query .= (' LIMIT ' . $limit);
        }
        if (is_int($limit) && is_int($offset))
        {
            $query .= (' OFFSET ' . $offset);
        }

        $stm = $this->db->prepare($query);
        for ($i = 0; $i < count($this->where[self::$values]); $i++)
        {
            $stm->bindValue(($i + 1), $this->where[self::$values][$i], $this->type($this->where[self::$values][$i]));
        }
        if (! $stm->execute())
        {
            $error = $stm->errorInfo();
            throw new UnoException($error[2]);
        }
        if (! empty($this->where[self::$clause]))
        {
            $this->where[self::$clause] = NULL;
            $this->where[self::$values] = array();
        }
        return $stm->fetchAll(PDO::FETCH_CLASS, 'ORM', array($this->tableName, $this->connection));
    }

    /**
     * Creates or updated a record depending if the record was previously loaded
     *
     * @return $this
     */
    public function save()
    {
        if ($this->loaded)
        {
            return $this->update();
        }
        return $this->create();
    }

    public function update()
    {
        return $this;
    }

    public function create()
    {
        return $this;
    }

    /**
     * @param mixed $id The unique identifier of the record to delete
     * @return void
     */
    public function delete($id = NULL)
    {
        if (NULL === $id && empty($this->where[self::$clause]))
        {
            throw new UnoException('Cowardly refusing to delete all records from: '.$this->tableName);
        }
        $query = 'DELETE FROM ' .$this->tableName;
        if (NULL !== $id)
        {
            $this->where[self::$clause] = $this->primaryKey($id) .'=?';
            $this->where[self::$values] = array($id);
        }
        if (! empty($this->where[self::$clause]))
        {
            $query .= (' WHERE ' . $this->where[self::$clause]);
        }
        $stm = $this->db->prepare($query);

        for ($i = 0; $i < count($this->where[self::$values]); $i++)
        {
            $stm->bindValue(($i + 1), $this->where[self::$values][$i], $this->type($this->where[self::$values][$i]));
        }
        if (! $stm->execute())
        {
            $error = $stm->errorInfo();
            throw new UnoException($error[2]);
        }
        if (! empty($this->where[self::$clause]))
        {
            $this->where[self::$clause] = NULL;
            $this->where[self::$values] = array();
        }
    }

    /**
     * @param string $where The where fields with questions mark value place holders
     * @param array $values The values that will replace the place holders in $where
     *
     * @return $this
     */
    public function where($where, array $values = array())
    {
        $this->where[self::$clause] = $where;
        $this->where[self::$values] = $values;
        return $this;
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
    }

    public function set($name, $value)
    {
        $this->field[$name] = $value;
        if (! in_array($name, $this->changed))
        {
            $this->changed[] = $name;
        }
    }

    public function get($name, $default = NULL)
    {
        if (in_array($name, $this->field))
        {
            return $this->field[$name];
        }
        return $default;
    }

    public function loaded()
    {
        return $this->loaded;
    }

    protected function primaryKey($field = NULL)
    {
        return $this->primaryKey;
    }

    protected function type($value)
    {
        switch (true)
        {
        case is_bool($value):
            return PDO::PARAM_BOOL;
        case is_numeric($value) || is_int($value):
            return PDO::PARAM_INT;
        case is_null($value):
            return PDO::PARAM_NULL;
        default:
            return PDO::PARAM_STR;
        }
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
        if (($autoloads = spl_autoload_functions()) !== FALSE)
        {
            foreach ($autoloads as $autoload)
            {
                spl_autoload_register($autoload, FALSE);
            }
        }
        spl_autoload_register('Uno::autoload', FALSE);

        if (isset($config['timezone']))
        {
            date_default_timezone_set($config['timezone']);
        }

        Config::factory($config);
        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
    }

    private static function autoload($classname)
    {
        if ('Controller' == substr($classname, -10))
        {
            if (Router::getInstance()->hasModules())
            {
                $bits = preg_split('/\\\|_/', $classname, -1, PREG_SPLIT_NO_EMPTY);
                if (is_array($bits))
                {
                    $module = array_shift($bits);
                    $class = substr(array_pop($bits), 0, -10);
                    $path = empty($bits) ? '' : (implode('/', $bits) . '/');

                    $filepath = APPPATH .'modules/'. $module .'/controllers/'. $path . $class .'.php';
                    require_once strtolower($filepath);
                }
            }
            else {
                $filename = substr($classname, 0, -10);
                $filepath = APPPATH .'controllers/'. $filename .'.php';
                require_once strtolower($filepath);
            }
        }
        else if ('Uno\\' == substr($classname, 0, 4))
        {
            $filepath = LIBPATH .'uno/'. substr($classname, 4) .'.php';
            require_once $filepath;
        }
    }
}
