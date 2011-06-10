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
            ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') ? $_SERVER['SERVER_PORT'] : '';
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
            trigger_error(sprintf(_('Config file: "%s" cannot be edited'), $this->name), E_USER_NOTICE);
        }
        else {
            $this->params[$name] = $value;
        }
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
        $event->ret = &$ret;
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
        // trying to include the required controller
        $filepath = APPPATH .'controllers/'. $this->router->controller() .'.php';
        if (! is_file($filepath))
        {
            return $this->show404(URI::getInstance()->current());
        }

        include_once $filepath;
        // the controller class name
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
        $filepath = APPPATH .'controllers/404.php';
        if (is_file($filepath))
        {
            include_once $filepath;
            $classname = 'FOFController';

            $controller = new $classname();
            $action = Config::getConfig()->action;
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
    protected $ip;

    protected function __construct()
    {
        $this->method = strtolower($_SERVER['REQUEST_METHOD']);
        $this->ip = $_SERVER['REMOTE_ADDR'];
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

    public function ip()
    {
        return $this->ip;
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
        if (array_key_exists($key, $this->params['post']))
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
        if (array_key_exists($key, $this->params['get']))
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

    public function set($name, $value = NULL)
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
        require($this->template($template));
        ob_end_flush();
    }

    public function fetch($template, array $vars = array())
    {
        $params = array_merge($vars, $this->vars);
        extract($params, EXTR_SKIP);
        ob_start();
        require($this->template($template));
        return ob_get_clean();
    }

    public function combine($template)
    {
        $this->render($template);
    }

    public function get($name, $default = '')
    {
        if (array_key_exists($name, $this->vars))
        {
            return $this->vars[$name];
        }
        return $default;
    }

    public function set($name, $value = NULL)
    {
        if (is_array($name))
        {
            foreach ($name as $key => $val)
            {
                $this->vars[$key] = $val;
            }
        }
        else {
            $this->vars[$name] = $value;
        }
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
            $filepath = APPPATH .'modules/'. $this->router->module() .'/views/'. $template . $this->config['ext'];
            if (is_file($filepath))
            {
                return $filepath;
            }
        }
        return APPPATH . 'views/' . $template . $this->config['ext'];
    }
}

class Database
{
    protected static $db = array();
    protected $pdo;

    protected function __construct($name = 'default')
    {
        $config = Config::getConfig()->database[$name];
        $this->pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_CURSOR, PDO::CURSOR_SCROLL);

        if (isset($config['charset']))
        {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            switch ($driver)
            {
            case 'mysql':
                $stm = $this->pdo->prepare("SET NAMES ?");
                break;
            case 'pgsql':
                $stm = $this->pdo->prepare("SET NAMES '?'");
                break;
            case 'sqlite':
            case 'sqlite2':
                $stm = $this->pdo->prepare("PRAGMA encoding='?'");
                break;
            }
            if (isset($stm))
            {
                $stm->bindValue(1, $config['charset'], PDO::PARAM_STR);
                $stm->execute();
            }
        }
    }

    public static function getInstance($name = 'default')
    {
        if (! array_key_exists($name, static::$db))
        {
            static::$db[$name] = new Database($name);
        }
        return static::$db[$name];
    }

    public function driver()
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function __call($method, array $args = array())
    {
        switch (count($args))
        {
        case 0:
            return $this->pdo->$method();
        case 1:
            return $this->pdo->$method($args[0]);
        case 2:
            return $this->pdo->$method($args[0], $args[1]);
        default:
            // should never be here as pdo methods don't have more
            // then 2 parameters
            return call_user_func_array($method, $args);
        }
    }
}

class ORM
{
    protected $db;
    protected $primaryKey = 'id';
    protected $tableName;
    protected $field = array();
    protected $changed;
    protected $loaded;
    protected $connection;

    protected $where;


    const PreCreate = 'PreCreate';
    const PreUpdate = 'PreUpdate';

    const PostCreate = 'PostCreate';
    const PostUpdate = 'PostUpdate';

    const PreLoad = 'PreLoad';
    const PostLoad = 'PostLoad';


    public function __construct($tableName, $connection = 'default')
    {
        $this->db = Database::getInstance($connection);
        $this->tableName = $tableName;
        $this->changed = array();
        $this->where = array();

        $this->loaded = FALSE;
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
        Event::fire(ORM::PreLoad, $this);

        $query = 'SELECT * FROM ' .$this->tableName. ' WHERE ' . $this->primaryKey($id) . '=? LIMIT 1';
        $stm = $this->db->prepare($query);
        $stm->bindValue(1, $id, $this->type($id));
        $stm->execute();

        $this->field = $stm->fetch(PDO::FETCH_ASSOC);
        if (FALSE === $this->field)
        {
            $this->field = array();
        }
        else {
            $this->loaded = TRUE;
            Event::fire(ORM::PostLoad, $this);
        }
        return $this;
    }

    public function find()
    {
        $orm = $this->findAll(1);
        if (empty($orm))
        {
            return new ORM($this->tableName, $this->connection);
        }
        $pro = new ReflectionProperty($orm[0], 'loaded');
        $pro->setAccessible(TRUE);
        $pro->setValue($orm[0], TRUE);
        return $orm[0];
    }

    public function findAll($limit = NULL, $offset = NULL)
    {
        $query = 'SELECT * FROM ' .$this->tableName;
        if (! empty($this->where))
        {
            $query .= ' WHERE ';
            $fields = array_keys($this->where);
            $size = count($fields);
            for ($i = 0; $i < $size; $i++)
            {
                $query .= ($fields[$i] . '=?');
                if (($i + 1) < $size)
                {
                    $query .= ' AND ';
                }
            }
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
        $values = array_values($this->where);
        for ($i = 0; $i < count($values); $i++)
        {
            $stm->bindValue(($i + 1), $values[$i], $this->type($values[$i]));
        }
        $stm->execute();

        if (! empty($this->where))
        {
            $this->where = array();
        }

        return $stm->fetchAll(PDO::FETCH_CLASS, get_class($this), array($this->tableName, $this->connection));
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

    /**
     * Updates existing record
     */
    public function update()
    {
        if (! empty($this->changed))
        {
            Event::fire(ORM::PreUpdate, $this);

            $query = 'UPDATE ' .$this->tableName;
            $size = count($this->changed);
            for ($i = 0; $i < $size; $i++)
            {
                $query .= (' SET ' .$this->changed[$i]. '=?');
                if (($i + 1) < $size)
                {
                    $query .= ',';
                }
            }
            $query .= ' WHERE '. $this->primaryKey() . '=?';

            $stm = $this->db->prepare($query);
            $i = 1;
            foreach ($this->changed as $name)
            {
                $stm->bindValue($i++, $this->field[$name], $this->type($this->field[$name]));
            }
            $stm->bindValue($i, $this->field[$this->primaryKey()], $this->type($this->field[$this->primaryKey()]));
            $stm->execute();
            $this->changed = array();

            Event::fire(ORM::PostUpdate, $this);
        }
        return $this;
    }

    /**
     * Inserts a new record
     */
    public function create()
    {
        Event::fire(ORM::PreCreate, $this);

        $query = 'INSERT INTO '. $this->tableName .'('. implode(',', array_keys($this->field)) .') VALUES (';
        $size = count($this->field);
        for ($i = 0; $i < $size; $i++)
        {
            $query .= '?';
            if (($i + 1) < $size)
            {
                $query .= ',';
            }
        }
        $query .= ')';

        $stm = $this->db->prepare($query);
        $i = 1;
        foreach ($this->field as $value)
        {
            $stm->bindValue($i++, $value, $this->type($value));
        }
        $stm->execute();

        $seq = NULL;
        if ($this->db->driver() == 'pgsql')
        {
            $seq = $this->tableName .'_'. $this->primaryKey() .'_seq';
        }
        $this->field[$this->primaryKey()] = $this->db->lastInsertId($seq);
        $this->loaded = TRUE;
        $this->changed = array();

        Event::fire(ORM::PostCreate, $this);
        return $this;
    }

    /**
     * @param mixed $id The unique identifier of the record to delete
     * @return void
     */
    public function delete($id = NULL)
    {
        if (NULL === $id && empty($this->where))
        {
            trigger_error(sprintf(_('Cowardly refusing to delete all records from: %s'), $this->tableName), E_USER_ERROR);
        }
        $query = 'DELETE FROM ' .$this->tableName;
        if (NULL !== $id)
        {
            $this->where[$this->primaryKey($id)] = $id;
        }
        if (! empty($this->where))
        {
            $query .= ' WHERE ';
            $fields = array_keys($this->where);
            $size = count($fields);
            for ($i = 0; $i < $size; $i++)
            {
                $query .= ($fields[$i] . '=?');
                if (($i + 1) < $size)
                {
                    $query .= ' AND ';
                }
            }
        }
        $stm = $this->db->prepare($query);

        $values = array_values($this->where);
        for ($i = 0; $i < count($values); $i++)
        {
            $stm->bindValue(($i + 1), $values[$i], $this->type($values[$i]));
        }
        Event::fire(ORM::PreDelete, $this);
        $stm->execute();
        Event::fire(ORM::PostDelete, $this);

        if (! empty($this->where))
        {
            $this->where = array();
        }
    }

    public function where($field, $value)
    {
        if (is_array($field))
        {
            foreach ($field as $key => $val)
            {
                $this->where[$key] = $val;
            }
        }
        else {
            $this->where[$field] = $value;
        }
        return $this;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function get($name, $default = NULL)
    {
        if (array_key_exists($name, $this->field))
        {
            return $this->field[$name];
        }
        return $default;
    }

    public function __set($name, $value)
    {
        var_dump(array_shift(debug_backtrace(FALSE)));
        $this->set($name, $value);
    }

    /**
     * @param string $name The table field name
     * @param mixed $value The table field value
     * @return void
     */
    public function set($name, $value)
    {
        if (array_key_exists($name, $this->field))
        {
            if ($this->field[$name] != $value)
            {
                if (! in_array($name, $this->changed))
                {
                    $this->changed[] = $name;
                }
            }
        }
        $this->field[$name] = $value;
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

        // register uno autoload
        spl_autoload_register('Uno::autoload', FALSE);

        // include application bootstrap file if present
        $bootstrap = APPPATH . 'bootstrap.php';
        if (is_file($bootstrap))
        {
            include_once $bootstrap;
        }

        // register __autoload if defined
        // as spl_autoload_register disables
        // __autoload
        if (function_exists('__autoload'))
        {
            spl_autoload_register('__autoload', FALSE);
        }

        // set application timezone
        if (isset($config['timezone']))
        {
            date_default_timezone_set($config['timezone']);
        }
        // set application charset
        if (isset($config['charset']))
        {
            mb_internal_encoding($config['charset']);
        }

        // load uno default config file
        Config::factory($config);
        // create dispatcher
        $dispatcher = new Dispatcher();
        // dispatch the request
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
                    include_once strtolower($filepath);
                }
            }
            else {
                $filename = substr($classname, 0, -10);
                $filepath = APPPATH .'controllers/'. $filename .'.php';
                include_once strtolower($filepath);
            }
        }
        else if ('Model' == substr($classname, 0, 5))
        {
            // try in APPPATH models directory
            $parts = preg_split('/\\\|_/', $classname, -1, PREG_SPLIT_NO_EMPTY);
            // remove the Model part
            array_shift($parts);
            $classpath = implode('/', $parts);
            $filepath = APPPATH .'models/'. $classpath .'.php';
            if (is_file($filepath))
            {
                include_once $filepath;
            }
            // try in modules models directory
            else if (Router::getInstance()->hasModules())
            {
                $filepath = APPPATH .'modules/'. Router::getInstance()->module() .'/models/'. $classpath .'.php';
                if (is_file($filepath))
                {
                    include_once $filepath;
                }
            }
        }
        else {
            // try in LIBPATH
            $parts = preg_split('/\\\|_/', $classname, -1, PREG_SPLIT_NO_EMPTY);
            $classpath = implode('/', $parts);
            $filepath = LIBPATH . $classpath .'.php';
            if (is_file($filepath))
            {
                include_once $filepath;
            }
            // try in modules lib directory
            else if (Router::getInstance()->hasModules())
            {
                $filepath = APPPATH .'modules/'. Router::getInstance()->module() .'/lib/'. $classpath .'.php';
                if (is_file($filepath))
                {
                    include_once $filepath;
                }
            }
        }
    }
}
