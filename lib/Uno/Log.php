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


class Log
{
    const HAYATE_LOG_OFF = 0;
    const HAYATE_LOG_ERROR = 1;
    const HAYATE_LOG_DEBUG = 2;
    const HAYATE_LOG_INFO = 3;

    protected static $log_types = array(self::HAYATE_LOG_OFF => '',
                                        self::HAYATE_LOG_ERROR => 'ERROR',
                                        self::HAYATE_LOG_DEBUG => 'DEBUG',
                                        self::HAYATE_LOG_INFO => 'INFO');

    private function __construct() {}

    public static function error($msg)
    {
        if ($msg instanceof Exception)
        {
            $msg = $msg->getMessage()."\nFile: ".$msg->getFile() ."\nLine: ".$msg->getLine()."\n".$msg->getTraceAsString();
        }
        self::write(self::HAYATE_LOG_ERROR, $msg);
    }

    public static function info($msg)
    {
        self::write(self::HAYATE_LOG_INFO, $msg);
    }

    public static function debug($msg)
    {
        self::write(self::HAYATE_LOG_DEBUG, $msg);
    }

    protected static function write($type, $msg)
    {
        $config = \Config::getConfig();
        $error_level = $config->get('log_level', self::HAYATE_LOG_OFF);
        if ($type <= $error_level)
        {
            $logdir = $config->get('log_dir', dirname($_SERVER['DOCUMENT_ROOT']) . '/logs');
            try {
                if (is_dir($logdir) || @mkdir($logdir))
                {
                    $filename = rtrim($logdir, '\//') . '/log-'.date('Y-m-d').'.log';
                    $logfile = new \SplFileObject($filename, 'a');
                    self::header($type, $logfile);
                    if (! is_string($msg))
                    {
                        $msg = print_r($msg, TRUE);
                    }
                    $logfile->fwrite($msg);
                    self::footer($logfile);
                }
                else {
                    throw new \Exception(sprintf(_('Log directory %s does not exists and could not be created.'), $logdir));
                }
            }
            catch (\Exception $ex)
            {
                trigger_error(sprintf(_('Failed to write to log file: "%s", %s'), $filename, $ex->getMessage()), E_USER_NOTICE);
            }
        }
    }

    protected static function header($type, \SplFileObject $logfile)
    {
        $file = '';
        $line = '';
        $backtrace = debug_backtrace(FALSE);
        while (($trace = array_shift($backtrace)) != NULL  && $trace['file'] == __FILE__);
        if ($trace != NULL)
        {
            $file = $trace['file'];
            $line = $trace['line'];
        }
        $logfile->fwrite(self::$log_types[$type].' - '.date('r').' - File: '.$file.' - Line: '.$line."\n");
    }

    protected static function footer(\SplFileObject $logfile)
    {
        $logfile->fwrite("\n");
    }
}