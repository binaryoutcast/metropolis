<?php
// == | Entry Point | =================================================================================================

// ROOT_PATH is defined as the absolute path (without a trailing slash) of the document root or the scriptdir if cli.
// NOTE: We use this as the current working directory may change for the lifetime of the script in cli.
// With the addition of gPath() from the utils will normalize all paths making cross-platform possible without much
// trouble.
define('ROOT_PATH', empty($_SERVER['DOCUMENT_ROOT']) ? __DIR__ : $_SERVER['DOCUMENT_ROOT']);
const kDebugDomain = 'preview.binaryoutcast.com';

// These define the Vendor, Name, and Version of this Application
// The utils will supply values if they are not defined.
// However, if the values matter to you then defining them is a GOOD idea!
/* const kAppVendor = 'Binary Outcast'; */
const kAppName = 'Metropolis';
/* const kAppVersion = '1.0.0a1'; */
/* const kAppRepository = 'https://code.binaryoutcast.com/projects/metropolis/'; */

// These are flags that will either default to or disable the special component.
// They are optional and do NOT need to be defined if not set to true.
const kAppIsSpecialComponent = false;
/* const kDisableSpecialComponent = true; */

// Load fundamental utils
require_once(ROOT_PATH . '/base/src/utils.php');

// ====================================================================================================================

?>