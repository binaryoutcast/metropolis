<?php
// == | Setup | =======================================================================================================

// Define basic constants for the software
const kAppVendor          = 'Binary Outcast';
const kAppName            = 'Metropolis';
const kAppVersion         = '1.0.0a1';

const SOFTWARE_VENDOR     = kAppVendor;
const SOFTWARE_NAME       = kAppName;
const SOFTWARE_VERSION    = kAppVersion;

// Load fundamental utils
require_once('./base/src/utils.php');

// Load application entry point
require_once(ROOT_PATH . '/base/src/app.php');

// ====================================================================================================================

?>