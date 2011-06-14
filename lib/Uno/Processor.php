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

abstract class Processor
{
    protected $val;
    protected $props;

    public function __construct(array $input = array())
    {
        $this->val = new Validator($input);
        $this->props = array();
    }

    abstract function process($action = NULL);

    /**
     * @param string $field The field error, if NULL return all the errors
     * @return array|string If $field does not have errors or does not exist
     * an empty array is returned, otherwise the error string
     */
    public function errors($field = NULL)
    {
        return $this->val->errors($field);
    }

    public function addError($field, $error = NULL)
    {
        if (NULL === $error)
        {
            $this->val->addError('_error', $field);
        }
        else {
            $this->val->addError($field, $error);
        }
    }

    public function getProperty($name, $default = NULL)
    {
        if (array_key_exists($name, $this->props))
        {
            return $this->props[$name];
        }
        return $default;
    }

    public function setProperty($name, &$prop)
    {
        $this->props[$name] = $prop;
    }

    public function hasProperty($name)
    {
        return array_key_exists($name, $this->props);
    }

    public function removeProperty($name)
    {
        if (array_key_exists($name, $this->props))
        {
            unset($this->props[$name]);
        }
    }

    public function __get($name)
    {
        return $this->val->$name;
    }

    public function __isset($name)
    {
        return isset($this->val->$name);
    }

    public function asArray()
    {
        return $this->val->asArray();
    }
}