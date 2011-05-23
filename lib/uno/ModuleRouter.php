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

class ModuleRouter implements \IRouter
{
    protected $path;
    protected $routes; // not yet used

    protected $module;
    protected $controller;
    protected $action;
    protected $args;


    public function __construct()
    {
        $this->path = \URI::getInstance()->path();
        $this->args = array(); // action arguments

        $config = \Config::getConfig();

        $this->module = $config->module;
        $this->controller = $config->controller;
        $this->action = $config->action;
        $this->modules = (bool)$config->modules;

        if (! empty($this->path))
        {
            $this->route();
        }
    }

    public function route()
    {
        $parts = preg_split('/\//', $this->path, -1, PREG_SPLIT_NO_EMPTY);

        $module = array_shift($parts);

        if ($this->isModule($module))
        {
            $this->module = $module;
            if (count($parts) > 0)
            {
                $this->controller = array_shift($parts);
                if (count($parts) > 0)
                {
                    $this->action = array_shift($parts);
                }
            }
        }
        else {
            // $module is considered to be a controller
            $this->controller = $module;
            if (count($parts) > 0)
            {
                $this->action = array_shift($parts);
            }
        }

        // anything left are parameters
        $this->args = $parts;
    }


    public function module()
    {
        return $this->module;
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
        return TRUE;
    }

    protected function isModule($module)
    {
        return is_dir(APPPATH .'modules/'. $module);
    }
}