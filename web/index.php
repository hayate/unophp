<?php
/**
 * define application directory path
 */
define('APPPATH', realpath('../application') . DIRECTORY_SEPARATOR);

/**
 * define lib directory path
 */
define('LIBPATH', realpath('../lib') . DIRECTORY_SEPARATOR);

/**
 * load uno
 */
require_once '../uno.php';

/**
 * run it, passing the array config as parameter
 */
Uno::run(require_once APPPATH . 'config.php');

