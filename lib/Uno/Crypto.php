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


class Crypto
{
    private static $instance = NULL;

    const ALGO = MCRYPT_RIJNDAEL_256;
    const MODE = MCRYPT_MODE_CBC;
    private $mcrypt;
    private $key;
    private $ivsize;
    private $maxKeysize;
    private $keysize;
    private $iv;


    private function __construct()
    {
        if (! function_exists('mcrypt_module_open'))
        {
            throw new \UnoException(sprintf(_('%s: mcrypt extension is missing.'), __CLASS__));
        }
        // initialize mcrypt
        $this->mcrypt = mcrypt_module_open(Crypto::ALGO, '', Crypto::MODE, '');

        // calculate IV size
        $this->ivsize = mcrypt_enc_get_iv_size($this->mcrypt);

        // calculate key max key length
        $this->maxKeysize = mcrypt_enc_get_key_size($this->mcrypt);

        $config = \Config::getConfig();
        if ($config->get('secret_key', FALSE))
        {
            $this->setKey($config->secret_key);
        }
    }

    public function __destruct()
    {
        if (isset($this->mcrypt))
        {
            mcrypt_module_close($this->mcrypt);
        }
    }

    public static function getInstance()
    {
        if (NULL === static::$instance)
        {
            static::$instance = new Crypto();
        }
        return static::$instance;
    }

    public function setKey($secret)
    {
        $key = '';
        $keyblocks = ceil(($this->maxKeysize * 2) / 32);

        // obfuscate secret key
        for ($i = 0; $i < $keyblocks; $i++)
        {
            $key .= md5($i.$secret, TRUE);
        }
        // resize key to correct length
        $this->key = substr($key, 0, $this->maxKeysize);
        $this->keysize = strlen($this->key);
    }

    public function encrypt($data)
    {
        if (empty($data)) return '';

        // generate initialization vector
        $this->iv = mcrypt_create_iv($this->ivsize, MCRYPT_RAND);

        // initialize mcrypt
        $ret = mcrypt_generic_init($this->mcrypt, $this->key, $this->iv);
        if ((false === $ret) || ($ret < 0))
        {
            throw new \Uno\Exception(_('Failed to initialize mcrypt.'));
        }

        // encrypt
        $ciphertext = mcrypt_generic($this->mcrypt, $data);

        // de-initialize mcrypt
        mcrypt_generic_deinit($this->mcrypt);

        // prepend IV
        // (IV is only used to create entropy, and is of no use to an attacker);
        $ans = $this->iv.$ciphertext;

        // base64 encode
        $ans = chunk_split(base64_encode($ans), 64);
        return $ans;
    }

    /**
     * @param string $data The base 64 encrypted data
     */
    public function decrypt($data)
    {
        if (empty($data)) return '';

        $in = base64_decode($data);

        // retrieve IV from decoded $data
        $this->iv = substr($in, 0, $this->ivsize);
        $ciphertext = substr($in, $this->ivsize);

        // initialize mcrypt
        $ret = mcrypt_generic_init($this->mcrypt, $this->key, $this->iv);
        if ((false === $ret) || ($ret < 0))
        {
            throw new \Uno\Exception(_('Failed to initialize mcrypt.'));
        }

        // decrypt
        $ans = mdecrypt_generic($this->mcrypt, $ciphertext);

        // de-initialize mcrypt
        mcrypt_generic_deinit($this->mcrypt);

        return $ans;
    }
}