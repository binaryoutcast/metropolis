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

// Load fundamental constants and global functions
// This is so we can arbitrarily reuse them in adhoc situations which is why they are not following
// the globalConstants/globalFunctions scheme used by other BinOC Applications.
require_once('./base/fundamentals.php');

const SOFTWARE_NAME       = 'Metropolis';
const SOFTWARE_VERSION    = '1.0.0a1';

const HTTPS_SCHEME          = 'https' . SCHEME_SUFFIX;
const BINOC_DOMAIN          = 'binaryoutcast.com';

// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Adhoc load a "component" and ensure that the script exits after execution if not terminated earlier
*
* @param $aComponentPath  string
**********************************************************************************************************************/
function gfLoadComponent($aComponentPath) {
  require_once($aComponent);
  exit();
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// Define an array that will hold the current application state
$gaRuntime = array(
  'currentScheme'     => gfSuperVar('server', 'SCHEME') ?? gfSuperVar('server', 'HTTPS') ? 'https' : 'http',
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

// Perform actions based on the domain or optionally the subdomain
switch ($gaRuntime['currentDomain']) {
  case 'fossamail.org':
    gfRedirect(HTTPS_SCHEME . BINOC_DOMAIN . gfBuildPath('projects', 'interlink'));
  case BINOC_DOMAIN:
    switch ($gaRuntime['currentSubDomain']) {
      case 'metropolis':
        $gaRuntime['qComponent'] = 'special';
        require_once(gfBuildPath(ROOT_PATH, 'base', $gaRuntime['qComponent'] . PHP_EXTENSION));
        exit();
      case 'go':
        $gaRuntime['qDirect'] = $_GET['direct'] ?? null;
        $gaRuntime['qAlias'] = gfSuperVar('get', 'alias');
        $gaGoAliases = [];

        if ($gaRuntime['qDirect']) {
          gfRedirect($gaRuntime['qDirect']);
        }

        if (array_key_exists($gaRuntime['qAlias'], $gaGoAliases)) {
          gfRedirect($gaGoAliases[$gaRuntime['qAlias']]);
        }

        gfHeader(404);
      case 'repository':
          if (str_starts_with($gaRuntime['qPath'], '/projects/interlink')) {
            gfRedirect($gaRuntime['currentScheme'] . SCHEME_SUFFIX .
                       'projects' . BINOC_DOMAIN .
                       str_replace(HTTPS_SCHEME . BINOC_DOMAIN . SLASH . 'projects', '', $gaRuntime['qPath']));
          }
          else {
            gfRedirect(HTTPS_SCHEME . BINOC_DOMAIN);
          }
        break;
      case 'irc': gfRedirect(HTTPS_SCHEME . BINOC_DOMAIN . SLASH . 'interact' . SLASH);
      case 'git': gfRedirect('https://repo.palemoon.org/binaryoutcast' . $gaRuntime['qPath']);
      case 'interlink-addons': gfRedirect('https://addons.binaryoutcast.com/interlink' . $gaRuntime['qPath']);
    }
  default:
    gfRedirect(HTTPS_SCHEME . BINOC_DOMAIN);
}

// ====================================================================================================================
?>