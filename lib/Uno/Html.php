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

class Html
{
    public static function OpenForm($action = NULL, $method = 'post', $upload = FALSE, array $attribs = array())
    {
        if (NULL === $action)
        {
            $action = \URI::getInstance()->current();
        }
        $buf = '<form action="'. $action .'" method="'. $method .'"';
        if ($upload)
        {
            $buf .= ' enctype="multipart/form-data"';
        }
        foreach ($attribs as $key => $val)
        {
            $buf .= " {$key}=\"{$val}\"";
        }
        $buf .= '>';
        echo $buf."\n";
    }

    public static function CloseForm()
    {
        echo '</form>';
    }

    public static function input($name = '', $value = '', $type = 'text', array $attribs = array())
    {
        $input = '<input type="'.$type.'"';
        if (! empty($name))
        {
            $input .= (' name="'.$name.'"');
        }
        $input .= (' value="'.$value.'"');

        foreach ($attribs as $key => $val)
        {
            $input .= " {$key}=\"{$val}\"";
        }
        $input .= '/>';
        echo $input ."\n";
    }
}