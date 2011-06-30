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
namespace Uno;

class ModuleDispatcher implements \IDispatcher
{
    protected $router;

    public function __construct()
    {
        $this->router = \Router::getInstance();
    }

    public function dispatch()
    {
        // trying to include this module bootstrap.php file if present
        $bootstrap = APPPATH .'modules/'. $this->router->module() .'/bootstrap.php';
        if (is_file($bootstrap))
        {
            include_once $bootstrap;
        }
        // trying to include the required controller
        $filepath = APPPATH .'modules/'. $this->router->module() .'/controllers/'. $this->router->controller() .'.php';
        if (! is_file($filepath))
        {
            return $this->show404(\URI::getInstance()->current());
        }
        include_once $filepath;

        // the controller class name
        $classname = $this->router->module() .'\\'. $this->router->controller() .'Controller';

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
            call_user_func_array(array($controller, $action), $parts);
        }
    }

    /**
     * Looks for a 404.php controller
     * inside the current module, if not found
     * it looks inside the default module
     * finally it echos a 404 message
     *
     * @param string $url The Not Found URL
     */
    public function show404($url)
    {
        // look in current module
        $filepath = APPPATH .'modules/'. $this->router->module() .'/controllers/404.php';
        if (! is_file($filepath))
        {
            // look in default module
            $filepath = APPPATH . 'modules/' . \Config::getConfig()->module .'/controllers/404.php';
        }
        if (is_file($filepath))
        {
            include_once $filepath;

            $classname = 'FOFController';
            $action = \Config::getConfig()->action;

            $controller = new $classname();
            $controller->$action($url);
        }
        else {
            exit("<h1>404 Not Found</h1><p>The following URL address could not be found on this server: {$url}</p>");
        }
    }
}