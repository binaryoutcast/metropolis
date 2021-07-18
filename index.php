<?php
// == | Setup | =======================================================================================================

error_reporting(E_ALL);
ini_set("display_errors", "on");

define('DEBUG_MODE', $_GET['debug'] ?? null);

// This has to be defined using the function at runtime because it is based
// on a variable. However, constants defined with the language construct
// can use this constant by some strange voodoo. Keep an eye on this.
// NOTE: DOCUMENT_ROOT does NOT have a trailing slash.
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

const NEW_LINE              = "\n";
const EMPTY_STRING          = "";
const EMPTY_ARRAY           = [];
const SPACE                 = " ";
const DOT                   = ".";
const SLASH                 = "/";
const DASH                  = "-";
const WILDCARD              = "*";

const JSON_EXTENSION        = DOT . 'json';

const XML_TAG               = '<?xml version="1.0" encoding="utf-8" ?>';

const JSON_ENCODE_FLAGS     = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
const FILE_WRITE_FLAGS      = "w+";

// ====================================================================================================================

// == | Global Functions | ============================================================================================

/**********************************************************************************************************************
* Polyfills for missing/proposed functions
* str_starts_with, str_ends_with, str_contains
*
* @param $haystack  string
* @param $needle    substring
* @returns          true if substring exists in string else false
**********************************************************************************************************************/
if (!function_exists('str_starts_with')) {
  function str_starts_with($haystack, $needle) {
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
  }
}

// --------------------------------------------------------------------------------------------------------------------

if (!function_exists('str_ends_with')) {
  function str_ends_with($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
      return true;
    }

    return (substr($haystack, -$length) === $needle);
  }
}

// --------------------------------------------------------------------------------------------------------------------

if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle) {
    if (strpos($haystack, $needle) > -1) {
      return true;
    }
    else {
      return false;
    }
  }
}

/**********************************************************************************************************************
* Error function that will display data (Error Message)
**********************************************************************************************************************/
function gfError($aValue, $phpError = false) { 
  if (is_string($aValue) || is_int($aValue)) {
    $errorContentType = 'text/xml';
    $errorPrefix = $phpError ? 'PHP' : 'Unable to Comply';
    $errorMessage = XML_TAG . NEW_LINE . '<error>' . $errorPrefix . ':' . SPACE . $aValue . '</error>';
  }
  else {
    $errorContentType = 'application/json';
    $errorMessage = json_encode($aValue, JSON_ENCODE_FLAGS);
  }

  if (function_exists('gfGenContent')) {
    gfGenContent($errorMessage, true);
  }
  else {
    header('Content-Type: ' . $errorContentType, false);
    print($errorMessage);
  }

  // We're done here.
  exit();
}

/**********************************************************************************************************************
* PHP Error Handler
**********************************************************************************************************************/
function gfErrorHandler($errno, $errstr, $errfile, $errline) {
  $errorCodes = array(
    E_ERROR               => 'Fatal Error',
    E_WARNING             => 'Warning',
    E_PARSE               => 'Parse',
    E_NOTICE              => 'Notice',
    E_CORE_ERROR          => 'Fatal Error (Core)',
    E_CORE_WARNING        => 'Warning (Core)',
    E_COMPILE_ERROR       => 'Fatal Error (Compile)',
    E_COMPILE_WARNING     => 'Warning (Compile)',
    E_USER_ERROR          => 'Fatal Error (User Generated)',
    E_USER_WARNING        => 'Warning (User Generated)',
    E_USER_NOTICE         => 'Notice (User Generated)',
    E_STRICT              => 'Strict',
    E_RECOVERABLE_ERROR   => 'Fatal Error (Recoverable)',
    E_DEPRECATED          => 'Deprecated',
    E_USER_DEPRECATED     => 'Deprecated (User Generated)',
    E_ALL                 => 'All',
  );

  $errorType = $errorCodes[$errno] ?? $errno;
  $errorMessage = $errorType . ': ' . $errstr . SPACE . 'in' . SPACE .
                  str_replace(ROOT_PATH, '', $errfile) . SPACE . 'on line' . SPACE . $errline;

  if (!(error_reporting() & $errno)) {
    // Don't do jack shit because the developers of PHP think users shouldn't be trusted.
    return;
  }

  gfError($errorMessage, 1);
}

set_error_handler("gfErrorHandler");

/**********************************************************************************************************************
* Unified Var Checking
*
* @param $_type           Type of var to check
* @param $_value          GET/SERVER/EXISTING Normal Var
* @param $_allowFalsy     Optional - Allow falsey returns (really only works with case var)
* @returns                Value or null
**********************************************************************************************************************/
function gfSuperVar($_type, $_value, $_allowFalsy = null) {
  $errorPrefix = __FUNCTION__ . SPACE . DASH . SPACE;
  $finalValue = null;

  switch ($_type) {
    case 'get':
      $finalValue = $_GET[$_value] ?? null;

      if ($finalValue) {
        $finalValue = preg_replace('/[^-a-zA-Z0-9_\-\/\{\}\@\.\%\s\,]/', '', $_GET[$_value]);
      }

      break;
    case 'post':
      $finalValue = $_POST[$_value] ?? null;
      break;
    case 'server':
      $finalValue = $_SERVER[$_value] ?? null;
      break;
    case 'files':
      $finalValue = $_FILES[$_value] ?? null;
      if ($finalValue) {
        if (!in_array($finalValue['error'], [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE])) {
          gfError($errorPrefix . 'Upload of ' . $_value . ' failed with error code: ' . $finalValue['error']);
        }

        if ($finalValue['error'] == UPLOAD_ERR_NO_FILE) {
          $finalValue = null;
        }
        else {
          $finalValue['type'] = mime_content_type($finalValue['tmp_name']);
        }
      }
      break;
    case 'cookie':
      $finalValue = $_COOKIE[$_value] ?? null;
      break;
    case 'var':
      $finalValue = $_value ?? null;
      break;
    default:
      gfError($errorPrefix . 'Incorrect var check');
  }

  if (!$_allowFalsy && (empty($finalValue) || $finalValue === 'none' || $finalValue === '')) {
    return null;
  }

  return $finalValue;
}

/**********************************************************************************************************************
* Sends HTTP Headers to client using a short name
*
* @param $aHeader    Short name of header
**********************************************************************************************************************/
function gfHeader($aHeader) {
  $headers = array(
    404             => 'HTTP/1.1 404 Not Found',
    501             => 'HTTP/1.1 501 Not Implemented',
    'text'          => 'Content-Type: text/plain',
    'xml'           => 'Content-Type: text/xml',
  );
  
  if (!headers_sent() && array_key_exists($aHeader, $headers)) {   
    header($headers[$aHeader]);

    if (in_array($aHeader, [404, 501])) {
      exit();
    }
  }
}

/**********************************************************************************************************************
* Sends HTTP Header to redirect the client to another URL
*
* @param $_strURL   URL to redirect to
**********************************************************************************************************************/
// This function sends a redirect header
function gfRedirect($aURL) {
	header('Location: ' . $aURL , true, 302);
  
  // We are done here
  exit();
}

/**********************************************************************************************************************
* ---
*
* @param $--   --
* @param $--   --
* @returns     --
***********************************************************************************************************************/
function gfExplodeString($aSeparator, $aString) {
  $errorPrefix = __FUNCTION__ . SPACE . DASH . SPACE;

  if (!is_string($aString)) {
    gfError($errorPrefix . 'Specified string is not a string type');
  }

  if (!str_contains($aString, $aSeparator)) {
    gfError($errorPrefix . 'String does not contain the seperator');
  }

  $explodedString = array_values(array_filter(explode($aSeparator, $aString), 'strlen'));

  return $explodedString;
}

/**********************************************************************************************************************
* ---
*
* @param $--   --
* @param $--   --
* @returns     --
***********************************************************************************************************************/
function gfGetDomain($aHost, $aReturnSub = null) {
  $host = gfExplodeString(DOT, $aHost);
  $domainSlice = $aReturnSub ? array_slice($host, 0, -2) : array_slice($host, -2, 2);
  $domainString = implode(DOT, $domainSlice);
  return $domainString;
}

/**********************************************************************************************************************
* Splits a path into an indexed array of parts
*
* @param $aPath   URI Path
* @returns        array of uri parts in order
***********************************************************************************************************************/
function gfExplodePath($aPath) {
  if ($aPath == SLASH) {
    return ['root'];
  }

  return gfExplodeString(SLASH, $aPath);
}

/**********************************************************************************************************************
* Builds a path from a list of arguments
*
* @param        ...$aPathParts  Path Parts
* @returns                      Path string
***********************************************************************************************************************/
function gfBuildPath(...$aPathParts) {
  $path = implode(SLASH, $aPathParts);
  $filesystem = str_starts_with($path, ROOT_PATH);
  
  // Add a prepending slash if this is not a filesystem path
  if (!$filesystem) {
    $path = SLASH . $path;
  }

  // Add a trailing slash if the last part does not contain a dot
  // If it is a filesystem path then we will also add a trailing slash if the last part starts with a dot
  if (!str_contains(basename($path), DOT) || ($filesystem && str_starts_with(basename($path), DOT))) {
    $path .= SLASH;
  }

  return $path;
}

/**********************************************************************************************************************
* ---
*
* @param $--   --
* @returns     --
***********************************************************************************************************************/
function gfStripRootPath($aPath) {
  return str_replace(ROOT_PATH, EMPTY_STRING, $aPath);
}

/**********************************************************************************************************************
* Read file (decode json if the file has that extension or parse install.rdf if that is the target file)
*
* @param $aFile     File to read
* @returns          file contents or array if json
                    null if error, empty string, or empty array
**********************************************************************************************************************/
function gfReadFile($aFile) {
  $file = @file_get_contents($aFile);

  if (str_ends_with($aFile, JSON_EXTENSION)) {
    $file = json_decode($file, true);
  }

  return gfSuperVar('var', $file);
}

/**********************************************************************************************************************
* Read file from zip-type archive
*
* @param $aArchive  Archive to read
* @param $aFile     File in archive
* @returns          file contents or array if json
                    null if error, empty string, or empty array
**********************************************************************************************************************/
function gfReadFileFromArchive($aArchive, $aFile) {
  return gfReadFile('zip://' . $aArchive . "#" . $aFile);
}

/**********************************************************************************************************************
* Write file (encodes json if the file has that extension)
*
* @param $aData     Data to be written
* @param $aFile     File to write
* @returns          true else return error string
**********************************************************************************************************************/
function gfWriteFile($aData, $aFile, $aRenameFile = null) {
  if (!gfSuperVar('var', $aData)) {
    return 'No useful data to write';
  }

  if (file_exists($aFile)) {
    return 'File already exists';
  }

  if (str_ends_with($aFile, JSON_EXTENSION)) {
    $aData = json_encode($aData, JSON_ENCODE_FLAGS);
  }

  $file = fopen($aFile, FILE_WRITE_FLAGS);
  fwrite($file, $aData);
  fclose($file);

  if ($aRenameFile) {
    rename($aFile, $aRenameFile);
  }

  return true;
}

/**********************************************************************************************************************
* Generate a random hexadecimal string
*
* @param $aLength   Desired number of final chars
* @returns          Random hexadecimal string of desired lenth
**********************************************************************************************************************/
function gfHexString($aLength = 40) {
  if ($aLength <= 1) {
    $length = 1;
  }
  else {
    $length = (int)($aLength / 2);
  }

  return bin2hex(random_bytes($length));
}

/**********************************************************************************************************************
* Basic Filter Substitution of a string
*
* @param $aSubsts               multi-dimensional array of keys and values to be replaced
* @param $aString               string to operate on
* @param $aRegEx                set to true if pcre
* @returns                      bitwise int value representing applications
***********************************************************************************************************************/
function gfSubst($aSubsts, $aString, $aRegEx = null) {
  if (!is_array($aSubsts)) {
    gfError('$aSubsts must be an array');
  }

  if (!is_string($aString)) {
    gfError('$aString must be a string');
  }

  $string = $aString;

  if ($aRegEx) {
    foreach ($aSubsts as $_key => $_value) {
      $string = preg_replace('/' . $_key . '/iU', $_value, $string);
    }
  }
  else {
    foreach ($aSubsts as $_key => $_value) {
      $string = str_replace('{%' . $_key . '}', $_value, $string);
    }
  }

  if (!$string) {
    gfError('Something has gone wrong with' . SPACE . __FUNCTION__);
  }

  return $string;
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
    gfRedirect('https://binaryoutcast.com/projects/interlink/');
  case 'binaryoutcast.com':
    switch ($gaRuntime['currentSubDomain']) {
      case 'metropolis':
        gfheader(501);
        /*
        $gaRuntime['qComponent'] = 'special';
        require_once(gfBuildPath(ROOT_PATH, 'base', 'special.php');
        exit();
        */
        break;
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
            gfRedirect($gaRuntime['currentScheme'] .
                       '://projects.binaryoutcast.com' .
                       str_replace('/projects', '', $gaRuntime['qPath']));
          }
          else {
            gfRedirect('https://binaryoutcast.com/');
          }
        break;
      case 'irc': gfRedirect('https://binaryoutcast.com/interact/');
      case 'git': gfRedirect('https://repo.palemoon.org/binaryoutcast' . $gaRuntime['qPath']);
      case 'interlink-addons': gfRedirect('https://addons.binaryoutcast.com/interlink' . $gaRuntime['qPath']);
    }
  case 'binocnetwork.com':
  case 'mattatobin.com':
  default:
    gfRedirect('https://binaryoutcast.com/');
}

// ====================================================================================================================
?>