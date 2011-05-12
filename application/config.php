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
     * protect against Cross Site Scripting
     * if true then $_POST and $_GET parameters
     * accessed via the Input class wrapper
     * will be filtered.
     */
    'xss' => TRUE,
    /**
     * default site charset
     */
    'charset' => 'UTF-8'
    );
