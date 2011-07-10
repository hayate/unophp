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

        $this->port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '';
        return $this->port;
    }

    public function path()
    {
        if (isset($this->path)) return $this->path;

        switch (true)
        {
        case isset($_SERVER['REQUEST_URI']):
            $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            break;
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

    /**
     * @param bool $withPort If TRUE returns current URI with port
     * @return string The current request URI
     */
    public function current($withPort = FALSE)
    {
        if (! $withPort)
        {
            return $this->current;
        }
        $port = $this->port();
        if (empty($port))
        {
            return $this->current;
        }
        $uri = $this->scheme().'://'.$this->hostname();
        $uri .= (':'. $port);
        $uri .= '/'.$this->path();
        $uri .= strlen($this->query()) ? '?'.$this->query() : '';
        return $uri;
    }

    /**
     * @param int $seg A 1 base index of this urs path segments, if the index is negative
     * counting starts from the end
     *
     * @return array|string Returns an array containing URI path segments when $seg is NULL
     * or a string when $seg is numeric, is $seg is out of range an empty string is returned
     */
    public function segment($seg = NULL)
    {
        $segs = explode('/', $this->path());
        if (NULL === $seg)
        {
            return $segs;
        }
        if ($seg > 0 && $seg <= count($segs))
        {
            return $segs[$seg -1];
        }
        else if ($seg < 0)
        {
            $seg *= -1;
            if ($seg <= count($segs))
            {
                $segs = array_reverse($segs, FALSE);
                return $segs[$seg - 1];
            }
        }
        return '';
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

    public function exists($name)
    {
        return array_key_exists($name, $this->params);
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

interface IDispatcher
{
    function dispatch();
}

class Dispatcher implements IDispatcher
{
    protected static $instance = NULL;

    const PreDispatch = 'PreDispatch';
    const PostDispatch = 'PostDispatch';

    protected $routes;
    protected $path;
    protected $args;
    protected $module;
    protected $controller;
    protected $action;


    protected function __construct()
    {
        $this->path = URI::getInstance()->path();
        $this->args = array();
        $this->routes = array();

        // set defaults from config
        $config = Config::getConfig();
        $this->module = $config->get('module', FALSE);
        $this->controller = $config->get('controller', 'Index');
        $this->action = $config->get('action', 'index');
    }

    public static function getInstance()
    {
        if (NULL === static::$instance)
        {
            static::$instance = new Dispatcher();
        }
        return static::$instance;
    }

    /**
     * @return bool TRUE on success FALSE on failure
     */
    protected function route()
    {
        if (! empty($this->routes))
        {
            // found perfect match
            if (isset($this->routes[$this->path]))
            {
                $this->path = $this->routes[$this->path];
            }
            else {
                // check predefined routes
                foreach ($this->routes as $route => $destination)
                {
                    if (preg_match('|^'.$route.'$|iu', $this->path))
                    {
                        if (FALSE !== strpos($destination, '$'))
                        {
                            $this->path = preg_replace('|^'.$route.'$|iu', $destination, $this->path);
                        }
                        else {
                            $this->path = $destination;
                        }
                        // found a route, break the loop
                        break;
                    }
                }
            }
        }

        if (empty($this->path))
        {
            // priority on application default controller
            if ($this->isController($this->controller))
            {
                $classname = $this->classname($this->controller);
                if (method_exists($classname, $this->action) && is_callable(array($classname, $this->action)))
                {
                    $this->module = FALSE;
                    return TRUE;
                }
            }
            // check application default module and controller
            if (! empty($this->module) && $this->isController($this->controller, $this->module))
            {
                $classname = $this->classname($this->controller, $this->module);
                if (method_exists($classname, $this->action) && is_callable(array($classname, $this->action)))
                {
                    return TRUE;
                }
            }
            return FALSE;
        }
        $parts = preg_split('/\//', str_replace(SITE_ROOT, '', $this->path), -1, PREG_SPLIT_NO_EMPTY);

        // priority is on application controllers rather than modules controllers
        if ($this->isController($parts[0]))
        {
            $this->controller = array_shift($parts);
            if (! empty($parts))
            {
                $classname = $this->classname($this->controller);
                if (method_exists($classname, $parts[0]) && is_callable(array($classname, $parts[0])))
                {
                    $this->module = FALSE;
                    $this->action = array_shift($parts);
                    $this->args = $parts;
                    return TRUE;
                }
            }
            // check the default action
            if (method_exists($classname, $this->action) && is_callable(array($classname, $this->action)))
            {
                $this->module = FALSE;
                $this->args = $parts;
                return TRUE;
            }
            array_unshift($parts, $this->controller);
        }

        // found a module
        if ($this->isModule($parts[0]))
        {
            $module = array_shift($parts);
            if (! empty($parts))
            {
                // found a controller
                if ($this->isController($parts[0], $module))
                {
                    $controller = array_shift($parts);
                    $classname = $this->classname($controller, $module);
                    $ref = new ReflectionClass($classname);
                    if (! empty($parts))
                    {
                        // found action
                        if ($ref->hasMethod($parts[0]) && ($method = $ref->getMethod($parts[0])) && $method->isPublic())
                        {
                            $action = array_shift($parts);
                            if ($method->getNumberOfParameters() == count($parts))
                            {
                                $this->module = $module;
                                $this->controller = $controller;
                                $this->action = $action;
                                $this->args = $parts;
                                return TRUE;
                            }
                            array_unshift($parts, $action);
                        }
                    }
                    // found default action
                    if ($ref->hasMethod($this->action) && ($method = $ref->getMethod($this->action)) && $method->isPublic())
                    {
                        if ($method->getNumberOfParameters() == count($parts))
                        {
                            $this->module = $module;
                            $this->controller = $controller;
                            $this->args = $parts;
                            return TRUE;
                        }
                    }
                    array_unshift($parts, $controller);
                }
                // check the default controller
                if ($this->isController($this->controller, $module))
                {
                    $classname = $this->classname($this->controller, $module);
                    $ref = new ReflectionClass($classname);
                    if ($ref->hasMethod($parts[0]) && ($method = $ref->getMethod($parts[0])) && $method->isPublic())
                    {
                        $action = array_shift($parts);
                        if ($method->getNumberOfParameters() == count($parts))
                        {
                            $this->module = $module;
                            $this->action = $action;
                            $this->args = $parts;
                            return TRUE;
                        }
                        array_unshift($parts, $action);
                    }
                    // check the default action
                    if ($ref->hasMethod($this->action) && ($method = $ref->getMethod($this->action)) && $method->isPublic())
                    {
                        if ($method->getNumberOfParameters() == count($parts))
                        {
                            $this->module = $module;
                            $this->args = $parts;
                            return TRUE;
                        }
                    }
                }
            }
            // check default controller
            else if ($this->isController($this->controller, $module))
            {
                $classname = $this->classname($this->controller, $module);
                // as parts is empty check the default action
                if (method_exists($classname, $this->action) && is_callable(array($classname, $this->action)))
                {
                    $this->module = $module;
                    return TRUE;
                }
            }
            return FALSE;
        }

        if ($this->isController($this->controller))
        {
            $classname = $this->classname($this->controller);
            if (method_exists($classname, $parts[0]) && is_callable(array($classname, $parts[0])))
            {
                $this->module = FALSE;
                $this->action = array_shift($parts);
                $this->args = $parts;
                return TRUE;
            }
            $ref = new ReflectionClass($classname);
            if ($ref->hasMethod($this->action) && ($method = $ref->getMethod($this->action)) && $method->isPublic())
            {
                if ($method->getNumberOfParameters() == count($parts))
                {
                    $this->module = FALSE;
                    $this->args = $parts;
                    return TRUE;
                }
            }
        }
        // check default module and part[0] as controller
        if (! empty($this->module))
        {
            if ($this->isController($parts[0], $this->module))
            {
                $controller = array_shift($parts);
                $classname = $this->classname($controller, $this->module);
                if (! empty($parts))
                {
                    if (method_exists($classname, $parts[0]) && is_callable(array($classname, $parts[0])))
                    {
                        $this->controller = $controller;
                        $this->action = array_shift($parts);
                        $this->args = $parts;
                        return TRUE;
                    }
                }
                // check default action
                if (method_exists($classname, $this->action) && is_callable(array($classname, $this->action)))
                {
                    $this->controller = $controller;
                    $this->args = $parts;
                    return TRUE;
                }
            }
            // check default module and controller
            if ($this->isController($this->controller, $this->module))
            {
                $classname = $this->classname($this->controller, $this->module);
                if (method_exists($classname, $parts[0]) && is_callable(array($classname, $parts[0])))
                {
                    $this->action = array_shift($parts);
                    $this->args = $parts;
                    return TRUE;
                }
                $ref = new ReflectionClass($classname);
                if ($ref->hasMethod($this->action) && ($method = $ref->getMethod($this->action)) && $method->isPublic())
                {
                    if ($method->getNumberOfParameters() == count($parts))
                    {
                        $this->args = $parts;
                        return TRUE;
                    }
                }
            }
        }
        return FALSE;
    }

    public function dispatch()
    {
        if (! $this->route())
        {
            return $this->show404(URI::getInstance()->current());
        }
        // if we dispatching to a module check if we have a bootstrap.php file
        if ($this->module())
        {
            $bootstrap = APPPATH .'Model/'. $this->module() .'/bootstrap.php';
            if (is_file($bootstrap))
            {
                include_once $bootstrap;
            }
        }
        Event::fire(self::PreDispatch);

        $classname = $this->classname($this->controller(), $this->module());

        $controller = new $classname();
        $action = $this->action();
        $args = $this->args();

        switch (count($args))
        {
        case 0:
            $controller->$action();
            break;
        case 1:
            $controller->$action($args[0]);
            break;
        case 2:
            $controller->$action($args[0], $args[1]);
            break;
        case 3:
            $controller->$action($args[0], $args[1], $args[2]);
            break;
        case 4:
            $controller->$action($args[0], $args[1], $args[2], $args[3]);
            break;
        case 5:
            $controller->$action($args[0], $args[1], $args[2], $args[3], $args[4]);
            break;
        default:
            // all right then
            call_user_func_array(array($controller, $action), $args);
        }
        Event::fire(self::PostDispatch);
    }

    public function module()
    {
        return is_string($this->module) ? $this->capitalize($this->module) : FALSE;
    }

    public function controller()
    {
        return $this->capitalize($this->controller);
    }

    public function action()
    {
        return $this->action;
    }

    public function args()
    {
        return $this->args;
    }

    public function addRoute($route, $destination = NULL)
    {
        if (! empty($destination))
        {
            $route = array($route => $destination);
        }
        $this->routes = array_merge($this->routes, $route);
    }

    protected function isController($controller, $module = NULL)
    {
        if (! empty($module))
        {
            return is_file(APPPATH .'Module/'. $this->capitalize($module) .'/Controller/'. $this->capitalize($controller) .'.php');
        }
        return is_file(APPPATH .'Controller/'. $this->capitalize($controller) .'.php');
    }

    protected function isModule($module)
    {
        return is_dir(APPPATH .'Module/'. $this->capitalize($module));
    }

    protected function classname($controller, $module = NULL)
    {
        if (empty($module))
        {
            return 'Controller\\'.$this->capitalize($controller);
        }
        return '\\Module\\'.$this->capitalize($module).'\\Controller\\'.$this->capitalize($controller);
    }

    protected function capitalize($value)
    {
        return ucfirst(strtolower($value));
    }

    /**
     * Looks for a 404.php controller having class name FOFController
     * and a default action, if not found it echo a 404 message
     * and exits
     *
     * @param string $url The Not Found URL
     */
    protected function show404($url)
    {
        $filepath = APPPATH .'Controller/404.php';
        if (is_file($filepath))
        {
            include_once $filepath;
            $classname = 'FOFController';

            $controller = new $classname();
            $action = Config::getConfig()->action;
            $controller->$action($url);
        }
        else {
            header('HTTP/1.1 404 Not Found');
            if ('HEAD' != $_SERVER['REQUEST_METHOD'])
            {
                exit("<h1>404 Not Found</h1><p>The following URL address could not be found on this server: {$url}</p>");
            }
            exit();

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

    protected function isHead()
    {
        return $this->request->isHead();
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
    protected $view;
    protected $template;

    protected $style;
    protected $jscript;
    protected $meta;
    protected $hequiv;


    public function __construct($template)
    {
        $this->view = static::factory();
        $this->template = $template;
        $this->style = array();
        $this->jscript = array();
        $this->meta = array();
        $this->hequiv = array();
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
        $vars['style'] = $this->style();
        $vars['jscript'] = $this->jscript();
        $vars['meta'] = $this->meta();
        $vars['hequiv'] = $this->hequiv();
        $this->view->render($this->template, $vars);
    }

    public function fetch(array $vars = array())
    {
        $vars['style'] = $this->style();
        $vars['jscript'] = $this->jscript();
        $vars['meta'] = $this->meta();
        $vars['hequiv'] = $this->hequiv();
        return $this->view->fetch($this->template, $vars);
    }

    public function __toString()
    {
        return $this->fetch();
    }

    public function style($href = NULL, $media = 'screen', $type = 'text/css')
    {
        if(NULL === $href)
        {
            return implode("\n", $this->style) ."\n";
        }
        $this->style[] = '<link rel="stylesheet" type="' . $type .
                '" media="' . $media . '" href="' . $href . '" />';
        return TRUE;
    }

    public function jscript($src = NULL, $type = 'text/javascript', $charset = 'UTF-8')
    {
        if(NULL === $src)
        {
            return implode("\n", $this->jscript) ."\n";
        }
        $this->jscript[] = '<script type="' . $type . '" src="' . $src .
                '" charset="' . $charset . '"></script>';
        return TRUE;
    }

    public function meta($name = NULL, $content = NULL, $scheme = NULL)
    {
        if(NULL === $name)
        {
            return implode("\n", $this->meta) ."\n";
        }
        $meta = '<meta name="' . $name . '" content="' . $content . '"';
        $meta .= is_null($scheme) ? ' />' : ' scheme="' . $scheme . '" />';
        $this->meta[] = $meta;
    }

    public function hequiv($name = NULL, $content = NULL)
    {
        if(NULL === $name)
        {
            return implode("\n", $this->hequiv) ."\n";
        }
        $this->hequiv[] = '<meta http-equiv="'.$name.'" content="'.$content.'" />';
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

    public function __construct(array $config)
    {
        $this->vars = array();
        $this->config = $config;
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

    public function merge($template)
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
        $ext = pathinfo($template, PATHINFO_EXTENSION);
        if (Uno::dispatcher()->module())
        {
            $filepath = APPPATH .'Module/'. Uno::dispatcher()->module() .'/View/'. $template . (empty($ext) ? $this->config['ext'] : $ext);
            if (is_file($filepath))
            {
                return $filepath;
            }
        }
        return APPPATH .'View/'. $template . (empty($ext) ? $this->config['ext'] : $ext);
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
    protected $changed = array();
    protected $loaded;
    protected $connection;
    protected $where;

    const PreCreate = 'PreCreate';
    const PreUpdate = 'PreUpdate';

    const PostCreate = 'PostCreate';
    const PostUpdate = 'PostUpdate';

    const PreLoad = 'PreLoad';
    const PostLoad = 'PostLoad';

    const PreDelete = 'PreDelete';
    const PostDelete = 'PostDelete';


    public function __construct($tableName, $connection = 'default')
    {
        $this->db = Database::getInstance($connection);
        $this->tableName = $tableName;
        $this->where = array();

        $this->loaded = FALSE;
        $this->connection = $connection;
    }

    public static function factory($tableName, $id = NULL, $connection = 'default')
    {
        $orm = new ORM($tableName, $connection);
        if (NULL === $id)
        {
            return $orm;
        }
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
        $query .= ' LIMIT 1';

        $stm = $this->db->prepare($query);
        $values = array_values($this->where);
        for ($i = 0; $i < count($values); $i++)
        {
            $stm->bindValue(($i + 1), $values[$i], $this->type($values[$i]));
        }
        $stm->execute();

        $this->where = array();
        $this->field = array();

        $stm->setFetchMode( PDO::FETCH_INTO, $this);
        $stm->fetch(PDO::FETCH_INTO);
        return $this;
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
        if (is_subclass_of($this, 'ORM'))
        {
            return $stm->fetchAll(PDO::FETCH_CLASS, get_class($this), array($this->connection));
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
        if (empty($this->field[$this->primaryKey()]))
        {
            return $this->create();
        }
        return $this->update();
    }

    /**
     * Updates existing record
     */
    public function update()
    {
        if (! empty($this->changed))
        {
            Event::fire(ORM::PreUpdate, $this);

            $query = 'UPDATE ' .$this->tableName. ' SET ';
            $size = count($this->changed);
            for ($i = 0; $i < $size; $i++)
            {
                $query .= ($this->changed[$i]. '=?,');
            }
            $query = (substr($query, 0, -1) .' WHERE '. $this->primaryKey() .'=?');

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
            $query .= '?,';
        }
        $query = (substr($query, 0, -1) . ')');

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
        $query = 'DELETE FROM ' .$this->tableName;
        if (NULL !== $id)
        {
            $this->where[$this->primaryKey($id)] = $id;
        }
        else if (! empty($this->field[$this->primaryKey()]))
        {
            $this->where[$this->primaryKey()] = $this->field[$this->primaryKey()];
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
        else {
            trigger_error(sprintf(_('Sorry but refusing to delete all records from: %s'), $this->tableName), E_USER_ERROR);
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

    public function where($field, $value = NULL)
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
        $this->set($name, $value);
    }

    /**
     * @param string $name The table field name
     * @param mixed $value The table field value
     * @return void
     */
    public function set($name, $value)
    {
        if ($this->loaded())
        {
            if (! isset($this->field[$name]) || ($this->field[$name] != $value))
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
        if (! $this->loaded)
        {
            $this->loaded = !empty($this->field[$this->primaryKey()]);
        }
        return $this->loaded;
    }

    /**
     * @return array An associative array of table fields and
     * respective values
     */
    public function asArray()
    {
        return $this->field;
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

class Autoloader
{
    protected static $instance = NULL;
    protected $paths;

    protected function __construct()
    {
        $this->paths = array();

        // register autoloader
        spl_autoload_register(array($this, 'autoload'), FALSE);

        // register __autoload if defined
        // as spl_autoload_register disables
        // __autoload
        if (function_exists('__autoload'))
        {
            spl_autoload_register('__autoload', FALSE);
        }
    }

    public static function getInstance()
    {
        if (NULL === static::$instance)
        {
            static::$instance = new Autoloader();
        }
        return static::$instance;
    }

    public function addDir($path)
    {
        if (is_dir($path))
        {
            $this->paths[] = rtrim($path, '\\/') .'/';
        }
    }

    protected function autoload($classname)
    {
        $parts = preg_split('/\\\|_/', $classname, -1, PREG_SPLIT_NO_EMPTY);
        $path = implode(DIRECTORY_SEPARATOR, $parts);
        $filepath = APPPATH . $path .'.php';
        if (is_file($filepath))
        {
            return include_once $filepath;
        }
        // try in lib directory
        $filepath = LIBPATH . $path .'.php';
        if (is_file($filepath))
        {
            return include_once $filepath;
        }
        // last check the given paths
        foreach ($this->paths as $fullpath)
        {
            $filepath = $fullpath . $path .'.php';
            if (is_file($filepath))
            {
                return include_once $filepath;
            }
        }
    }
}

final class Uno
{
    const REQUIRED_PHP_VERSION = '5.3.0';

    protected static $dispatcher = NULL;

    public static function setDispatcher(IDispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    public static function run(array $config)
    {
        if (version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION) < 0)
        {
            exit(sprintf('Uno requires PHP version %s or greater.', self::REQUIRED_PHP_VERSION));
        }
        // setup autoloader
        Autoloader::getInstance();
        // load uno default config file
        Config::factory($config);

        // include application bootstrap file if present
        $bootstrap = APPPATH . 'bootstrap.php';
        if (is_file($bootstrap))
        {
            include_once $bootstrap;
        }
        // set application timezone
        date_default_timezone_set(Config::getConfig()->get('timezone', 'Asia/Tokyo'));
        // set application charset
        mb_internal_encoding(Config::getConfig()->get('charset', 'UTF-8'));

        // dispatch request
        static::dispatcher()->dispatch();
    }

    public static function dispatcher()
    {
        return (NULL !== static::$dispatcher) ? static::$dispatcher : Dispatcher::getInstance();
    }
}
