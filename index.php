<?php
// == | Setup | =======================================================================================================

// Enable Error Reporting
error_reporting(E_ALL);
ini_set("display_errors", "on");

// This has to be defined using the function at runtime because it is based
// on a variable. However, constants defined with the language construct
// can use this constant by some strange voodoo. Keep an eye on this.
// NOTE: DOCUMENT_ROOT does NOT have a trailing slash.
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

// Debug flag
define('DEBUG_MODE', $_GET['debug'] ?? null);

// Software Name and Version
const SOFTWARE_NAME       = 'Metropolis';
const SOFTWARE_VERSION    = '1.0.0a1';

// Load fundamental constants and global functions
// This is so we can arbitrarily reuse them in adhoc situations which is why they are not following
// the globalConstants/globalFunctions scheme used by other BinOC Applications.
require_once('./fundamentals.php');

// ====================================================================================================================

// == | Global Functions | ============================================================================================

/**********************************************************************************************************************
* Local Authentication from a pre-defined json file with user and hashed passwords
*
* @dep ROOT_PATH
* @dep DOTDOT
* @dep JSON_EXTENSION
* @dep gfError()
* @dep gfSuperVar()
* @dep gfBuildPath()
* @dep gfBasicAuthPrompt()
* @param $aTobinOnly   Only Tobin's username is valid
***********************************************************************************************************************/
function gfLocalAuth($aTobinOnly = null) {
  global $gaRuntime;

  $username = gfSuperVar('server', 'PHP_AUTH_USER');
  $password = gfSuperVar('server', 'PHP_AUTH_PW');

  if ((!$username || !$password) || ($aTobinOnly && $username != 'mattatobin')) {
    gfBasicAuthPrompt();
  }

  $configPath = gfBuildPath(ROOT_PATH, DOTDOT, 'storage', 'config' . JSON_EXTENSION);
  $userdb = gfReadFile($configPath);
  
  if (!$userdb) {
    gfError('Could not read configuration.');
  }

  $userdb = $userdb['userdb'];
  
  if (!array_key_exists($username, $userdb) || !password_verify($password, $userdb[$username])) {
    gfBasicAuthPrompt();
  }

  $gaRuntime['authentication']['username'] = $username;
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// Define an array that will hold the current application state
$gaRuntime = array(
  'currentScheme'     => gfSuperVar('server', 'SCHEME') ?? (gfSuperVar('server', 'HTTPS') ? 'https' : 'http'),
  'phpServerName'     => gfSuperVar('server', 'SERVER_NAME'),
  'phpRequestURI'     => gfSuperVar('server', 'REQUEST_URI'),
  'remoteAddr'        => gfSuperVar('server', 'HTTP_X_FORWARDED_FOR') ?? gfSuperVar('server', 'REMOTE_ADDR'),
  'userAgent'         => gfSuperVar('server', 'HTTP_USER_AGENT'),
  'qComponent'        => gfSuperVar('get', 'component'),
  'qPath'             => gfSuperVar('get', 'path'),
);

// --------------------------------------------------------------------------------------------------------------------

// Deny some UAs
foreach (['NT 5', 'NT 6.0', 'curl/', 'wget/'] as $_value) {
  if (str_contains($gaRuntime['userAgent'], $_value)) {
    gfError('Reference Code - ID-10-T');
  }
}

// --------------------------------------------------------------------------------------------------------------------

// Root (/) won't set a component or path
if (!$gaRuntime['qComponent'] && !$gaRuntime['qPath']) {
  $gaRuntime['qComponent'] = 'site';
  $gaRuntime['qPath'] = SLASH;
}
 
// Explode the path
$gaRuntime['explodedPath'] = gfExplodePath($gaRuntime['qPath']);

// From this point we are just allowing the site "component" to be valid so if it is anything else just fuck off
if ($gaRuntime['qComponent'] != 'site') {
  gfError('Invalid Component');
}

// Set the current domain and subdomain
$gaRuntime['currentDomain'] = gfSuperVar('var', gfGetDomain($gaRuntime['phpServerName']));
$gaRuntime['currentSubDomain'] = gfSuperVar('var', gfGetDomain($gaRuntime['phpServerName'], true));

// --------------------------------------------------------------------------------------------------------------------

// Switch to the special component internally
$gaRuntime['qComponent'] = 'special';
require_once(gfBuildPath(ROOT_PATH, 'base', $gaRuntime['qComponent'] . PHP_EXTENSION));
exit();

// ====================================================================================================================
?>