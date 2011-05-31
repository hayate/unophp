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

class Validator
{
    protected $rules = array('(required)','(email)','(url)','(numeric)','(boolean)',
                             '(length)\[(\d+)\]','(range)\[([+-]?\d+)-([+-]?\d+)\]',
                             '(between)\[(\d+)-(\d+)\]');
    protected $rule;
    protected $input;
    protected $errors;
    protected $prefilter;



    /**
     * @param array $input key/value pair array
     */
    public function __construct(array $input = array())
    {
        $this->input = $input;
        $this->rule = array();
        $this->errors = array();
        $this->prefilter = array();
    }

    /**
     * @param string $field The field name
     * @param array $rules One or more rules
     * @param array $msg Error messages associated with rules
     */
    public function addRule($field, array $rules, array $msg = array())
    {
        for ($i = 0; $i < count($rules); $i++)
        {
            foreach ($this->rules as $rule)
            {
                $match = array();
                if ((preg_match('#'.$rule.'#', $rules[$i], $match) == 1) && (count($match) >= 2))
                {
                    switch($match[1])
                    {
                    case 'required':
                        $error = sprintf(_('"%s" is a required field.'), $this->fieldName($field));
                        $this->rule[$field][] = array('rule' => $match[1],
                                                      'param' => NULL,
                                                      'error' => isset($msg[$i]) ? $msg[$i] : $error);
                        break;
                    case 'email':
                        $error = _('Invalid Email address.');
                        $this->rule[$field][] = array('rule' => $match[1],
                                                      'param' => NULL,
                                                      'error' => isset($msg[$i]) ? $msg[$i] : $error);
                        break;
                    case 'url':
                        $error = _('Invalid URL address.');
                        $this->rule[$field][] = array('rule' => $match[1],
                                                      'param' => NULL,
                                                      'error' => isset($msg[$i]) ? $msg[$i] : $error);
                        break;
                    case 'numeric':
                        $error = sprintf(_('%s must be a numeric field.'), $this->fieldName($field));
                        $this->rule[$field][] = array('rule' => $match[1],
                                                      'param' => NULL,
                                                      'error' => isset($msg[$i]) ? $msg[$i] : $error);
                        break;
                    case 'boolean':
                        $error = sprintf(_('Invalid "%s" value.'), $this->fieldName($field));
                        $this->rule[$field][] = array('rule' => $match[1],
                                                      'param' => NULL,
                                                      'error' => isset($msg[$i]) ? $msg[$i] : $error);
                        break;
                    case 'length':
                        $error = sprintf(_('%s must be %d characters long.'), $this->fieldName($field), $match[2]);
                        $this->rule[$field][] = array('rule' => $match[1],
                                                      'param' => $match[2],
                                                      'error' => isset($msg[$i]) ? $msg[$i] : $error);
                        break;
                    case 'range':
                        $error = sprintf(_('%s must be between %d and %d inclusive.'),
                                         $this->fieldName($field), $match[2], $match[3]);
                        $this->rule[$field][] = array('rule' => $match[1],
                                                      'param' => array($match[2],$match[3]),
                                                      'error' => isset($msg[$i]) ? $msg[$i] : $error);
                        break;
                    case 'between':
                        $error = sprintf(_('%s must be between %d and %d (inclusive) characters long.'),
                                         $this->fieldName($field), $match[2], $match[3]);
                        $this->rule[$field][] = array('rule' => $match[1],
                                                      'param' => array($match[2],$match[3]),
                                                      'error' => isset($msg[$i]) ? $msg[$i] : $error);
                        break;
                    }
                }
            }
        }
    }


    public function addCallback($field, $callback, $param = NULL)
    {
        if (array_key_exists($field, $this->input))
        {
            $this->rule[$field][] = array('callback' => array($callback, $param));
        }
    }

    /**
     * @return bool, TRUE if there are no erros, FALSE otherwise.
     */
    public function validate()
    {
        // applying pre filters before validating
        $this->pre_filter();

        foreach ($this->rule as $field => $rules)
        {
            // if $field already has an error skip all other rules for that field
            if (array_key_exists($field, $this->errors)) continue;

            foreach ($rules as $rule)
            {
                if (isset($rule['rule']))
                {
                    $method = $rule['rule'];
                    if (! $this->$method($field, $rule['param']))
                    {
                        $this->addError($field, $rule['error']);
                        break;
                    }
                }
                else if (isset($rule['callback']))
                {
                    $params = array(&$this, $field, $rule['callback'][1]);
                    if (! is_callable($rule['callback'][0], FALSE, $cb))
                    {
                        trigger_error(sprintf(_('Callback: %s not callable.'), $cb), E_USER_ERROR);
                    }
                    call_user_func_array($rule['callback'][0], $params);
                }
            }
        }
        return ($this->errors == array());
    }

    public function addError($field, $error = NULL)
    {
        if (! isset($this->errors[$field]) || empty($this->errors[$field]))
        {
            $this->errors[$field] = $error;
        }
    }

    public function preFilter($field, $callback, array $params = array())
    {
        if (array_key_exists($field, $this->input) || ('*' == $field))
        {
            $this->prefilter[$field][] = array($callback, $params);
        }
    }

    /**
     * @param string $field The field error, if NULL return all the errors
     * @return array|string If $field does not have errors or does not exist
     * an empty array is returned, otherwise the error string
     */
    public function errors($field = NULL)
    {
        if (NULL === $field)
        {
            return $this->errors;
        }
        if (array_key_exists($field, $this->errors))
        {
            return $this->errors[$field];
        }
        return array();
    }

    public function asArray()
    {
        return $this->input;
    }

    public function get($name, $default = NULL)
    {
        if (array_key_exists($name, $this->input))
        {
            return $this->input[$name];
        }
        return $default;
    }

    protected function fieldName($field)
    {
        $field = preg_replace('/[-_]+/', ' ', $field);
        return ucwords(strtolower(trim($field)));
    }

    protected function pre_filter()
    {
        foreach ($this->prefilter as $field => $filters)
        {
            foreach ($filters as $filter)
            {
                if ('*' == $field)
                {
                    foreach ($this->input as $key => $val)
                    {
                        $params = $filter[1];
                        if (! in_array($this->input[$key], $params))
                        {
                            array_unshift($params, $this->input[$key]);
                        }
                        $this->input[$key] = call_user_func_array($filter[0], $params);
                    }
                }
                else if (isset($this->input[$field]))
                {
                    // filter[1] are the array of parameters to be passed to the method (fileter[0])
                    if (! in_array($this->input[$field], $filter[1]))
                    {
                        array_unshift($filter[1], $this->input[$field]);
                    }
                    $this->input[$field] = call_user_func_array($filter[0], $filter[1]);
                }
            }
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->input))
        {
            return $this->input[$name];
        }
        return NULL;
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->input);
    }

    /**
     * @param string $field The field that is required
     * @param mixed $param Not Used
     * @return bool TRUE if the field is present !empty or == 0
     */
    public function required($field, $param = NULL)
    {
        if (! array_key_exists($field, $this->input))
        {
            return FALSE;
        }
        if ($this->input[$field] == 0)
        {
            return TRUE;
        }
        return FALSE === empty($this->input[$field]);
    }

    /**
     * @param string $field The field pointing at the email to verify
     * @param mixed $param Not Used
     * @return bool TRUE if the email is valid FALSE otherwise
     */
    public function email($field, $param = NULL)
    {
        if (array_key_exists($field, $this->input))
        {
            return FALSE !== filter_var($this->field[$field], FILTER_VALIDATE_EMAIL);
        }
        return FALSE;
    }

    /**
     * @param string $field The field pointing at the url to verify
     * @param mixed $param Not Used
     * @return bool TRUE if the url is valid FALSE otherwise
     */
    public function url($field, $param = NULL)
    {
        if (array_key_exists($field, $this->input))
        {
            return FALSE !== filter_var($this->input[$field], FILTER_VALIDATE_URL);
        }
        return FALSE;
    }

    /**
     * @param string $field The field pointing a the numeric value to verify
     * @param mixed $param Not Used
     */
    public function numeric($field, $param = NULL)
    {
        if (array_key_exists($field, $this->input))
        {
            return is_numeric($this->input[$field]);
        }
        return FALSE;
    }

    /**
     * @param string $field The field pointing at the boolean to verify
     * @param mixed $param Not Used
     * @return bool TRUE for "1" and "0", "true" and "false", "on" and "off", "yes" and "no"
     */
    protected function boolean($field, $param = NULL)
    {
        if (array_key_exists($field, $this->input))
        {
            // empty string is not FALSE for us
            if ("" == $this->input[$field])
            {
                return FALSE;
            }
            return NULL !== filter_var($this->input[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        return FALSE;
    }

    /**
     * @param string $field The field pointing at the string to verify
     * @param int $param The lenght the string should be
     */
    public function length($field, $param = NULL)
    {
        if (array_key_exists($field, $this->input))
        {
            if (! is_numeric($param))
            {
                trigger_error("Invalid non numeric param in ".__METHOD__);
                return FALSE;
            }
            return (mb_strlen($this->input[$field]) == $param);
        }
        return FALSE;
    }

    /**
     * @param string $field The field name
     * @param array $params Min and Max values respective at position 0 and 1 of the array
     */
    public function range($field, array $params)
    {
        if (array_key_exists($field, $this->input))
        {
            $min = $params[0];
            $max = $params[1];
            return (($this->input[$field] >= $min) && ($this->input[$field] <= $max));
        }
        return FALSE;
    }

    /**
     * @param string $field The field name
     * @param array $params Min and Max values respective at position 0 and 1 of the array
     */
    public function between($field, array $params)
    {
        if (array_key_exists($field, $this->input))
        {
            $min = $params[0];
            $max = $params[1];
            return ((mb_strlen($this->input[$field]) >= $min) &&
                    (mb_strlen($this->input[$field]) <= $max));
        }
        return FALSE;
    }
}