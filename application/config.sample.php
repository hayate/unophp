<?php
/**
 * Uno main configuration
 */
$config = array(

    /**
     * Can be "module" or "controller"
     *
     * if "module" then Uno\ModuleRouter
     * and Uno\ModuleDispatcher in uno lib
     * directory are going to be used
     */
    'dispatch' => 'module',

    /**
     * the default module (name of directory)
     * is only used by Uno\Dispatcher in the lib
     * directory
     */
    'module' => 'welcome',

    /**
     * the default controller
     */
    'controller' => 'home',

    /**
     * the default action
     */
    'action'=> 'index',

    /**
     * template engine configuration
     */
    'view' => array (

        /**
         * class name of the template engine to use
         * Native is uno default native php template engine
         */
        'name' => 'Native',

        /**
         * template file extension
         */
        'ext' => '.html.php',
        ),

    /**
     * protect against Cross Site Scripting
     * if true then $_POST and $_GET parameters
     * accessed via the Input class wrapper
     * will be filtered.
     */
    'xss' => TRUE,

    /**
     * default site charset
     */
    'charset' => 'UTF-8',

    /**
     * application timezone
     *
     * @see http://php.net/manual/en/timezones.php
     */
    'timezone' => 'Asia/Tokyo',

    // database, uses PDO
    'database' => array(
        // default configuration is assumed if none
        // is passed to Database::getInstance() method
        'default' => array(
            // supported all databases supported by PDO
            // i.e.
            // mysql  - mysql:host=127.0.0.1;port=3306;dbname=unodb
            // pgsql  - pgsql:host=127.0.0.1 port=5432 dbname=unodb
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=unodb',
            'username' => 'the_username',
            'password' => 'the_password',
            // will be set automatically with
            // mysql, pgsql and sqlite(2) drivers
            'charset' => 'UTF8',
            // extra driver options
            // @see: http://php.net/manual/en/pdo.construct.php
            'options' => array()
            )
        )
    );


/**
 * Uno configuration only required
 * when using some of uno lib classes
 */
$libconfig = array(

    /**
     * 0 = OFF
     * 1 = log errors only
     * 2 = debugging log
     * 4 = info log
     */
    'log_level' => 2,

    /**
     * absolute path to log files directory
     * The directory must be writable by the webserver
     */
    'log_dir' => dirname($_SERVER['DOCUMENT_ROOT']) .'/logs',

    /**
     * This key is used by the Crypto class
     * for encryption/decription operations
     * If you select to encrypt cookies and session
     * data the Crypto class and this key will be
     * used.
     * Please change this key with some of your own randomness
     */
    'secret_key' => '0N<y&zt>OgA|VRLhKP6$9uGb&%R2PtQW&ZEXoplKf$9(&A=ud[y7-NXjW!~rP`u?',

    /**
     * If set to TRUE, cookies and session data (in database)
     * will be encrypted
     */
    'encrypt' => TRUE,

    /**
     * Session
     * Scheme:
     *
     CREATE TABLE sessions (
     id CHAR(32) NOT NULL PRIMARY KEY,
     data TEXT,
     expiry INT UNSIGNED NOT NULL
     );
     *
     */
    'session' => array(
        /**
         * native or database
         * "native" sessions are
         * the default i.e. handled
         * by php, "database" sessions
         * are useful when multiple
         * web server need to share session
         */
        'type' => 'database',

        /**
         * name overwrites default name: PHPSESSID
         */
        'name' => 'UNOSESSID',

        /**
         * use_cookie
         * this should be always set to TRUE
         * so that the session token is
         * passed via cookie rather the via $_GET
         * helping preventing session fixation
         * and hijacking
         */
        'use_cookie' => TRUE,

        /**
         * @see http://php.net/manual/en/function.session-set-cookie-params.php
         */
        /**
         * session lifetime
         * 0 means "until the browser is closed"
         */
        'lifetime' => 0,

        /**
         * path from which the session
         * cookie will be available
         */
        'path' => '/',

        /**
         * domain to set in the session cookie
         */
        'domain' => $_SERVER['SERVER_NAME'],

        /**
         * should the cookie only sent
         * over secure connection (ssl)
         */
        'secure' => FALSE,

        /**
         * @see http://www.php.net/manual/en/session.configuration.php#ini.session.cookie-httponly
         *
         * should the cookie only be accessible through the HTTP protocol ?
         * i.e. cookie won't be accessible by scripting languages, such as JavaScript
         */
        'httponly' => TRUE,

        /**
         * extra options:
         * @see http://php.net/manual/en/session.configuration.php
         */
        'options' => array()
        ),


    /**
     * Cookies configuration section
     */
    'cookie' => array(

        /**
         * number of seconds since the epoch
         * if set to 0 the cookie
         * will expire at the end of the
         * session (when the browser closes)
         *
         * 1209600 == 2 Weeks
         */
        'expire' => 1209600,

        /**
         * path where the cookie is available
         * set to '/' for the entire site
         */
        'path' => '/',

        /**
         * domain that the cookie is available, prefixing the domain with a
         * dot .example.com will make the cookie available to all subdomains
         * while without a . cookie will be available only for that domain
         */
        'domain' => '',

        /**
         * set to true indicates that the client should only send the cookie
         * back over a secure httpS connection
         */
        'secure' => FALSE,

        /**
         * not supported by all browser, when set to TRUE the cookie
         * will only be accessible via http protocol i.e. cookies will not be
         * accessible by scripting languages such as JavaScript
         */
        'httponly' => TRUE
        )
    );

return array_merge($config, $libconfig);