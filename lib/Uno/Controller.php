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


abstract class Controller extends \Controller
{
    protected $template = FALSE;
    protected $render = FALSE;

    public function __construct()
    {
        parent::__construct();

        if (TRUE === $this->render)
        {
            if (FALSE === $this->template)
            {
                $router = \Router::getInstance();
                $this->template = $router->controller() .'/'. $router->action();
            }
            if (is_string($this->template))
            {
                $this->template = new \View($this->template);
                $ref = new \ReflectionMethod($this, 'render');
                $ref->setAccessible(TRUE);
                \Event::register(\Dispatcher::PostDispatch, array($ref, 'invoke'), array($this));
            }
        }
    }

    public function __set($name, $value)
    {
        if (FALSE === $this->template)
        {
            $router = \Router::getInstance();
            $this->template = $router->controller() .'/'. $router->action();
        }
        if (is_string($this->template))
        {
            $this->template = new \View($this->template);
            if (! $this->render)
            {
                $ref = new \ReflectionMethod($this, 'render');
                $ref->setAccessible(TRUE);
                \Event::register(\Dispatcher::PostDispatch, array($ref, 'invoke'), array($this));
                $this->render = TRUE;
            }
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

    protected function render()
    {
        if (TRUE === $this->render)
        {
            $this->template->render();
        }
    }
}
