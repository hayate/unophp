<?php
return array(
    /**
     * Whether to use modules
     * If set to TRUE controllers and views are
     * expected to be found inside a module directory
     * For example for a module called admin the
     * controllers directory will be in:
     * APPPATH . modules/admin/controllers/
     * and views in:
     * APPPATH . modules/admin/controllers/
     */
    'modules' => FALSE, // not used yet

    /**
     * the default module (name of directory)
     * only used if USE_MODULES is TRUE
     */
    'module' => 'default', // not used yet

    /**
     * the default controller
     */
    'controller' => 'home',

    /**
     * the default action
     */
    'action'=> 'index',

    /**
     * template file extension
     */
    'ext' => '.html.php',

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

    // database, uses PDO
    'database' => array(
        // default configuration is assumed if none
        // is passed to Database::getInstance() method
        'default' => array(
            // supported all databases supported by PDO
            // i.e.
            // mysql  - mysql:host=127.0.0.1;port=3306;dbname=hayate
            // pgsql  - pgsql:host=127.0.0.1 port=5432 dbname=hayate
            'dsn' => 'mysql:host=192.168.11.2;port=3306;dbname=hayate',
            'username' => 'andrea',
            'password' => 'donkey',
            // will be set automatically with
            // mysql, pgsql and sqlite(2) drivers
            'charset' => 'UTF8',
            // extra driver options
            // @see: http://php.net/manual/en/pdo.construct.php
            'options' => array()
            )
        )
    );
