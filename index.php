<?php
// == | Setup | =======================================================================================================

// ROOT_PATH is defined as the absolute path (without a trailing slash) of the document root or the scriptdir if cli.
// NOTE: It does not have a trailing slash.
define('ROOT_PATH', empty($_SERVER['DOCUMENT_ROOT']) ? __DIR__ : $_SERVER['DOCUMENT_ROOT']);

// Load fundamental utils
require_once('./base/src/utils.php');

gOutput(gPath(ROOT_PATH, 'base', 'src', 'app.php'));

// ====================================================================================================================

?>