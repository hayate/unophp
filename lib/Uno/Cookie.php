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


class Cookie
{
    protected static $instance = NULL;
    protected $expire;
    protected $path;
    protected $domain;
    protected $secure;
    protected $httponly;
    protected $crypto;

    const SESSION = 0;
    const TWENTY_MINS = 1200;
    const FORTY_MINS = 2400;
    const ONE_HOUR = 3600;
    const TWO_HOURS = 7200;
    const ONE_DAY = 86400;
    const ONE_WEEK = 604800;
    const TWO_WEEKS = 1209600;
    const ONE_MONTH = 2419200;
    const FOR_EVER = 87091200;


    protected function __construct()
    {
        $encrypt = \Config::getConfig()->get('encrypt', FALSE);
        if ($encrypt)
        {
            $this->crypto = Crypto::getInstance();
        }
        $config = \Config::factory(\Config::getConfig()->get('cookie', array()), FALSE, 'cookie');

        $this->expire = $config->get('expire', 0);
        $this->path = $config->get('path', '/');
        $this->domain = $config->get('domain', '');
        $this->secure = $config->get('secure', FALSE);
        $this->httponly = $config->get('httponly', FALSE);
    }

    public static function getInstance()
    {
        if (NULL === self::$instance)
        {
            static::$instance = new Cookie();
        }
        return static::$instance;
    }

    /**
     * @param string $name The name of this cookie
     * @param mixed $value The value for this cookie
     * @param integer $expire Time in seconds this cookie should be available
     * @param string $path The path within this hostname the cookie should be available
     * @param string $domain The domain where the cookie is available
     * @param bool $secure If true indicates the client that this cookie should not be sent over an unsecure connection
     * @param bool $httponly When true the cookie is only accessible via http protocol
     */
    public function set($name, $value = FALSE, $expire = NULL, $path = NULL, $domain = NULL, $secure = NULL, $httponly = NULL)
    {
        $expire = is_null($expire) ? $this->expire : $expire;
        $path = is_null($path) ? $this->path : $path;
        $domain = is_null($domain) ? $this->domain : $domain;
        $secure = is_null($secure) ? (bool)$this->secure : $secure;
        $httponly = is_null($httponly) ? (bool)$this->httponly : $httponly;
        // make sure value is a string
        $value = serialize($value);

        if (isset($this->crypto))
        {
            $value = $this->crypto->encrypt($value);
        }
        $expiration = empty($expire) ? 0 : gmdate('U') + $expire;
        if (! empty($domain))
        {
            // make sure there are not staring dot
            $domain = ltrim($domain, '.');
            // make sure it has an acceptable top level domain name
            if (1 != preg_match('/.+\.[a-z]{2,4}$/i', $domain))
            {
                $domain = '';
            }
        }
        setcookie($name, $value, $expiration, $path, $domain, $secure, $httponly);
    }

    /**
     * @param string $name The name of the cookie
     * @param mixed $default If $name is not set this value is returned
     * @param bool $xss_clean If boolean it will overwrite the configuration settings (prevent xss attacts)
     * @return mixed The value of the cookie
     */
    public function get($name, $default = FALSE, $xss_clean = NULL)
    {
        if (! array_key_exists($name, $_COOKIE))
        {
            return $default;
        }
        $ans = $_COOKIE[$name];

        if (isset($this->crypto))
        {
            $ans = $this->crypto->decrypt($ans);
        }
        $ans = unserialize($ans);

        $xss = \Config::getConfig()->get('xss', FALSE);
        if (is_bool($xss_clean))
        {
            $xss = $xss_clean;
        }
        return (is_string($ans) && $xss) ? htmlspecialchars($ans, ENT_QUOTES, \Config::getConfig()->get('charset', 'UTF-8')) : $ans;
    }

    /**
     * @param string $name The name of the cookie
     * @param string $path The path used when the cookie was set
     * @param string $domain The domain used when the cookie was set
     */
    public function delete($name, $path = NULL, $domain = NULL)
    {
        $path = is_null($path) ? $this->path : $path;
        $domain = is_null($domain) ? $this->domain : $domain;
        if (! empty($domain))
        {
            // make sure there are not staring dot
            $domain = ltrim($domain, '.');
            // make sure it has an acceptable top level domain name
            if (1 != preg_match('/.+\.[a-z]{2,4}$/i', $domain))
            {
                $domain = '';
            }
        }
        setcookie($name, '', gmdate('U') - 3600, $path, $domain);
    }

    /**
     * @param string $name The name of the cookie
     * @return bool True if the cookie exists false otherwise.
     */
    public function exists($name)
    {
        return array_key_exists($name, $_COOKIE);
    }
}