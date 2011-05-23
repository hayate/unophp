<?php
return array(

    /**
     * Can be "module" or "controller"
     *
     * if "module" then Uno\ModuleRouter
     * and Uno\ModuleDispatcher in uno lib
     * directory are going to be used
     */
    'dispatch' => 'controller',

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
            'dsn' => 'mysql:host=192.168.11.2;port=3306;dbname=unodb',
            'username' => 'nameOfUser',
            'password' => 'userPassword',
            // will be set automatically with
            // mysql, pgsql and sqlite(2) drivers
            'charset' => 'UTF8',
            // extra driver options
            // @see: http://php.net/manual/en/pdo.construct.php
            'options' => array()
            )
        )
    );
