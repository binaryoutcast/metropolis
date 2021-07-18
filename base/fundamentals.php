<?php
// == | Primitives | ==================================================================================================

const NEW_LINE              = "\n";
const EMPTY_STRING          = "";
const EMPTY_ARRAY           = [];
const SPACE                 = " ";
const DOT                   = ".";
const SLASH                 = "/";
const DASH                  = "-";
const WILDCARD              = "*";

const SCHEME_SUFFIX         = "://";

const PHP_EXTENSION         = DOT . 'php';
const INI_EXTENSION         = DOT . 'ini';
const XML_EXTENSION         = DOT . 'xml';
const JSON_EXTENSION        = DOT . 'json';

const JSON_ENCODE_FLAGS     = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
const FILE_WRITE_FLAGS      = "w+";
const XML_TAG               = '<?xml version="1.0" encoding="utf-8" ?>';

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
  $pageHeader = array(
    'default' => 'Unable to Comply',
    'fatal'   => 'Fatal Error',
    'php'     => 'PHP Error',
    'output'  => 'Output'
  );

  $externalOutput = function_exists('gfGenContent');
  $isCLI = (php_sapi_name() == "cli");

  if (is_string($aValue) || is_int($aValue)) {
    $errorContentType = 'text/xml';
    $errorPrefix = $phpError ? $pageHeader['php'] : $pageHeader['default'];

    if ($externalOutput || $isCLI) {
      $errorMessage = $aValue;
    }
    else {
      $errorMessage = XML_TAG . NEW_LINE . '<error title="' . $errorPrefix . '">' . $aValue . '</error>';
    }
  }
  else {
    $errorContentType = 'application/json';
    $errorPrefix = $pageHeader['output'];
    $errorMessage = json_encode($aValue, JSON_ENCODE_FLAGS);
  }

  if ($externalOutput) {
    if ($phpError) {
      gfGenContent($errorPrefix, $errorMessage, null, true, true);
    }

    gfGenContent($errorPrefix, $errorMessage);
  }
  elseif ($isCLI) {
    print('========================================' . NEW_LINE .
          $errorPrefix . NEW_LINE .
          '========================================' . NEW_LINE .
          $errorMessage . NEW_LINE);
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

  gfError($errorMessage, true);
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
    if (DEBUG_MODE) {
      gfError($headers[$aHeader]);
    }
    else {
      header($headers[$aHeader]);

      if (in_array($aHeader, [404, 501])) {
        exit();
      }
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

/**********************************************************************************************************************
* ---
*
* @param $--   --
* @returns     --
***********************************************************************************************************************/
function gfLocalAuth($aTobinOnly = null) {
  global $gaRuntime;

  $promptCreds = function() {
    header('WWW-Authenticate: Basic realm="' . SOFTWARE_NAME . '"');
    header('HTTP/1.0 401 Unauthorized');   
    gfError('You need to enter a valid username and password.');
  };

  $username = gfSuperVar('server', 'PHP_AUTH_USER');
  $password = gfSuperVar('server', 'PHP_AUTH_PW');

  if (!$username || !$password) {
    $promptCreds();
  }

  if ($aTobinOnly && $username != 'mattatobin') {
    $promptCreds();
  }

  $configPath = gfBuildPath(ROOT_PATH, DOT . DOT, 'storage', 'config' . JSON_EXTENSION);
  $userdb = gfReadFile($configPath);
  
  if (!$userdb) {
    gfError('Could not read configuration.');
  }

  $userdb = $userdb['userdb'];
  
  if (!array_key_exists($username, $userdb) || !password_verify($password, $userdb[$username])) {
    $promptCreds();
  }

  $gaRuntime['authentication']['username'] = $username;
}

// ====================================================================================================================
?>