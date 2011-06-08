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

class Session
{
    protected static $instance = NULL;
    protected $db;

    protected function __construct()
    {
        ini_set('session.use_only_cookies', TRUE);
        ini_set('session.use_trans_sid', FALSE);

        $config = \Config::factory(\Config::getConfig()->get('session', array()), FALSE, 'session');
        if ('database' == $config->get('type', 'native'))
        {
            $this->setSessionHandler();
        }

        session_name($config->get('name', 'UNOSESSID'));

        // session will not work with a domain without top level
        $domain = $config->get('domain', $_SERVER['SERVER_NAME']);
        if(1 != preg_match('/.+\.[a-z]{2,4}$/i', $domain))
        {
            $domain = '';
        }
        session_set_cookie_params($config->get('lifetime', 0),
                                  $config->get('path', '/'),
                                  $domain,
                                  $config->get('secure', FALSE),
                                  $config->get('httponly', TRUE));
        session_start();
    }

    public function __destruct()
    {
        session_write_close();
    }

    public static function getInstance()
    {
        if (NULL === static::$instance)
        {
            static::$instance = new Session();
        }
        return static::$instance;
    }


    public function get($name, $default = NULL)
    {
        if (array_key_exists($name, $_SESSION))
        {
            return $_SESSION[$name];
        }
        return $default;
    }

    public function getOnce($name, $default = NULL)
    {
        if (array_key_exists($name, $_SESSION))
        {
            $value = $_SESSION[$name];
            unset($_SESSION[$name]);
            return $value;
        }
        return $default;
    }

    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public function exists($name)
    {
        return array_key_exists($name, $_SESSION);
    }

    public function delete($name)
    {
        if (array_key_exists($name, $_SESSION))
        {
            unset($_SESSION[$name]);
        }
    }

    public function regenerate()
    {
        session_regenerate_id();
    }

    /**
     * destroys all of the data associated with the current session
     */
    public function destroy()
    {
        session_destroy();
    }

    public function id()
    {
        return session_id();
    }

    // stop cloning session objects
    private function __clone() {}


    protected function setSessionHandler()
    {
        $encrypt = \Config::getConfig()->get('encrypt', FALSE);
        $db = \Database::getInstance();
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        $crypto = NULL;
        if ($encrypt)
        {
            $crypto = Crypto::getInstance();
        }

        session_set_save_handler(
            function($path, $name) {return TRUE;} // open
            ,
            function() {return TRUE;} // close
            ,
            /**
             * @param string $id The session id
             * @return string
             */
            function($id) use($db, $encrypt, $crypto) // read
            {
                $stm = $db->prepare('SELECT data FROM sessions WHERE id=? LIMIT 1');
                $stm->bindValue(1, $id, \PDO::PARAM_STR);
                if (! $stm->execute())
                {
                    $err = $stm->errorInfo();
                    Log::error($err[2]);
                    return FALSE;
                }
                $ret = $stm->fetch(\PDO::FETCH_ASSOC);
                if (FALSE !== $ret)
                {
                    return $encrypt ? $crypto->decrypt($ret['data']) : $ret['data'];
                }
                return '';
            }
            ,
            /**
             * @param string $id The session id
             * @param string $data The session data to save
             * @return bool TRUE on success FALSE otherwise
             */
            function($id, $data) use($db, $encrypt, $crypto) // write
            {
                $stm = $db->prepare('SELECT COUNT(id) as count FROM sessions WHERE id=?');
                $stm->bindValue(1, $id, \PDO::PARAM_STR);
                if (! $stm->execute())
                {
                    $err = $stm->errorInfo();
                    Log::error($err[2]);
                    return FALSE;
                }
                $ret = $stm->fetch(\PDO::FETCH_ASSOC);

                $query = 'INSERT INTO sessions (data,expiry,id) VALUES(?,?,?)';
                if ($ret['count'] > 0)
                {
                    $query = 'UPDATE sessions SET data=?, expiry=? WHERE id=?';
                }
                $data = $encrypt ? $crypto->encrypt($data) : $data;

                $stm = $db->prepare($query);
                $stm->bindValue(1, $data, \PDO::PARAM_STR);
                $stm->bindValue(2, intval(gmdate('U')), \PDO::PARAM_INT);
                $stm->bindValue(3, $id, \PDO::PARAM_STR);
                if (! $stm->execute())
                {
                    $err = $stm->errorInfo();
                    Log::error($err[2]);
                    return FALSE;
                }
                return TRUE;
            }
            ,
            /**
             * @param string $id The session id
             */
            function($id) use($db) // destroy
            {
                $stm = $db->prepare('DELETE FROM session WHERE id=?');
                $stm->bindValue(1, $id, \PDO::PARAM_STR);
                if (! $stm->execute())
                {
                    Log::error($stm->errorInfo());
                    return FALSE;
                }
                return TRUE;
            }
            ,
            /**
             * @param int $maxtime Max session lifetime
             */
            function($maxtime) use($db) // gc
            {
                $stm = $db->prepare('DELETE FROM session where expiry < ?');
                $stm->bindValue(1, intval(gmdate('U') - $maxtime), \PDO::PARAM_INT);
                if (! $stm->execute())
                {
                    Log::error($stm->errorInfo());
                    return FALSE;
                }
                return TRUE;
            }
            );
    }
}
