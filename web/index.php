<?php
/**
 * define the site base path
 * this is the name of the folder where this index.php file is
 * i.e.
 * if index.php is in the document root, (i.e. http://www.example.com/index.php)
 * then SITE_ROOT should be an empty string ''
 * if however index.php is in a sub-folder of document root (i.e. http://www.example.com/some/path/index.php
 * then SITE_ROOT should be 'some/path' (Note: no leading or trailing slashes)
 */
define('SITE_ROOT', str_replace($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR, '', dirname(__FILE__)));

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

