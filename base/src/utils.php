<?php /****************************************************************************************************************
* This Source Code Form is subject to the terms of the Mozilla Public
* License, v. 2.0. If a copy of the MPL was not distributed with this
* file, You can obtain one at http://mozilla.org/MPL/2.0/.
*
* Portions of this file are under other licenses. This is noted where
* it is relevant.
*********************************************************************************************************************/

namespace {
// == | Setup | =======================================================================================================

// Check that we can run on this version of PHP
if (PHP_MAJOR_VERSION . PHP_MINOR_VERSION < '81') {
  die('BinOC Metropolis Utilities: PHP 8.1 or newer is required.');
}

// --------------------------------------------------------------------------------------------------------------------

// Do not allow this to be included more than once...
if (defined('BINOC_UTILS')) {
  die('BinOC Metropolis Utilities: You may not include this file more than once.');
}

// Define that this is a thing which can double as a version check.
define('BINOC_UTILS', '2.0.0a1');

// --------------------------------------------------------------------------------------------------------------------

if (!defined('kAppVendor')) {
  define('kAppVendor', 'Binary Outcast');
}

if (!defined('kAppName')) {
  define('kAppName', 'Metropolis-based Software');
}

if (!defined('kAppVersion')) {
  define('kAppVersion', BINOC_UTILS);
}

// --------------------------------------------------------------------------------------------------------------------

// We like CLI
define('SAPI_IS_CLI', php_sapi_name() == "cli");
define('CLI_NO_LOGO', in_array('--nologo', $GLOBALS['argv'] ?? []));

// Enable Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);
const E_EXCEPTION = 65536;

// --------------------------------------------------------------------------------------------------------------------

// Debug flag (CLI always triggers debug mode)
define('kDebugMode', $_GET['debug'] ?? SAPI_IS_CLI);

// ====================================================================================================================

// == | Global Constants | ============================================================================================

// Define basic symbol constants
const NEW_LINE              = "\n";
const EMPTY_STRING          = "";
const EMPTY_ARRAY           = [];
const SPACE                 = " ";
const WILDCARD              = "*";
const SLASH                 = "/";
const DOT                   = ".";
const DASH                  = "-";
const UNDERSCORE            = "_";
const PIPE                  = "|";
const AMP                   = "&";
const DOLLAR                = "\$";
const COLON                 = ":";
const SCOPE                 = COLON . COLON;
const DOTDOT                = DOT . DOT;
const DASH_SEPARATOR        = SPACE . DASH . SPACE;
const SCHEME_SUFFIX         = COLON . SLASH . SLASH;

// ------------------------------------------------------------------------------------------------------------------

const FILE_WRITE_FLAGS      = "w+";
const FILE_EXTS             = array(
  'php'                     => DOT . 'php',
  'ini'                     => DOT . 'ini',
  'html'                    => DOT . 'html',
  'xhtml'                   => DOT . 'xhtml',
  'xml'                     => DOT . 'xml',
  'rdf'                     => DOT . 'rdf',
  'json'                    => DOT . 'json',
  'content'                 => DOT . 'content',
  'tpl'                     => DOT . 'tpl',
  'xpinstall'               => DOT . 'xpi',
  'jar'                     => DOT . 'jar',
  'winstaller'              => DOT . 'installer' . DOT . 'exe',
  'winportable'             => DOT . 'portable' . DOT . 'exe',
  'mar'                     => DOT . 'mar',
  'mar-bz2'                 => DOT . 'complete' . DOT . 'mar',
  '7z'                      => DOT . '7z',
  'zip'                     => DOT . 'zip',
  'tz'                      => DOT . 'tz',
  't7z'                     => DOT . 't7z',
  'tgz'                     => DOT . 'tgz',
  'tbz'                     => DOT . 'tbz',
  'txz'                     => DOT . 'txz',
  'tar-zip'                 => DOT . 'tar' . DOT . 'zip',
  'tar-7z'                  => DOT . 'tar' . DOT . '7z',
  'tar-gz'                  => DOT . 'tar' . DOT . 'gz',
  'tar-bz2'                 => DOT . 'tar' . DOT . 'bz2',
  'tar-xz'                  => DOT . 'tar' . DOT . 'xz', 
);

// ------------------------------------------------------------------------------------------------------------------

const XML_TAG               = '<?xml version="1.0" encoding="utf-8" ?>';

// ------------------------------------------------------------------------------------------------------------------

const JSON_FLAGS            = array(
  'display'                 => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
  'storage'                 => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
);

// ------------------------------------------------------------------------------------------------------------------

const REGEX_PATTERNS        = array(
  'query'                   => "/[^-a-zA-Z0-9_\-\/\{\}\@\.\%\s\,]/",
  'yaml'                    => "/\A---(.|\n)*?---/",
  'guid'                    => "/^\{[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\}$/i",
  'host'                    => "/[a-z0-9-\._]+\@[a-z0-9-\._]+/i",
);

// ------------------------------------------------------------------------------------------------------------------

const PASSWORD_CLEARTEXT    = "clrtxt";
const PASSWORD_HTACCESS     = "apr1";
const BASE64_ALPHABET       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
const APRMD5_ALPHABET       = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

// ------------------------------------------------------------------------------------------------------------------

const DEFAULT_HOME_TEXT     = 'Site Root (Home)';

// XXX: Remove these!
const PHP_EXTENSION         = FILE_EXTS['php'];
const JSON_EXTENSION        = FILE_EXTS['json'];
const PALEMOON_GUID         = '{8de7fcbb-c55c-4fbe-bfc5-fc555c87dbc4}';
const REGEX_GET_FILTER      = REGEX_PATTERNS['query'];
const JSON_ENCODE_FLAGS     = JSON_FLAGS['display'];

// ====================================================================================================================
}

namespace mozilla\vc {
// == | nsIVersionComparator | ========================================================================================

/**
* Implements Mozilla Toolkit's nsIVersionComparator
*
* Version strings are dot-separated sequences of version-parts.
*
* A version-part consists of up to four parts, all of which are optional:
* <number-a><string-b><number-c><string-d (everything else)>
* A version-part may also consist of a single asterisk "*" which indicates
* "infinity".
*
* Numbers are base-10, and are zero if left out.
* Strings are compared bytewise.
*
* For additional backwards compatibility, if "string-b" is "+" then
* "number-a" is incremented by 1 and "string-b" becomes "pre".
*
* 1.0pre1
* < 1.0pre2  
*   < 1.0 == 1.0.0 == 1.0.0.0
*     < 1.1pre == 1.1pre0 == 1.0+
*       < 1.1pre1a
*         < 1.1pre1
*           < 1.1pre10a
*             < 1.1pre10
*
* Although not required by this interface, it is recommended that
* numbers remain within the limits of a signed char, i.e. -127 to 128.
*/
class ToolkitVersionPart {
  public $numA = 0;
  public $strB = null;
  public $numC = 0;
  public $extraD = null;
}

class ToolkitVersionComparator {
  public static function compare($a, $b) {
    do {
      $va = new ToolkitVersionPart();
      $vb = new ToolkitVersionPart();
      $a = self::parseVersionPart($a, $va);
      $b = self::parseVersionPart($b, $vb);
      
      $result = self::compareVersionPart($va, $vb);
      
      if ($result != 0){
        break;
      }
    }

    while ($a != null || $b != null);

    if ($result >= 1) { $result = 1; }
    if ($result <= -1) { $result = -1; }

    return $result;
  }
  
  
  private static function parseVersionPart($aVersion, ToolkitVersionPart $result) {
    if ($aVersion === null || strlen($aVersion) == 0) {
      return $aVersion;
    }
    
    $tok = explode(".", trim($aVersion));
    $part = $tok[0];
    
    if ($part == "*") {
      $result->numA = PHP_INT_MAX;
      $result->strB = "";
    }
    else {
      $vertok = new ToolkitVersionPartTokenizer($part);
      $next = $vertok->nextToken();
      if (is_numeric($next)){
        $result->numA = $next;
      }
      else {
        $result->numA = 0;
      }
      
      if ($vertok->hasMoreElements()) {
        $str = $vertok->nextToken();
        // if part is of type "<num>+"
        if ($str[0] == '+') {
          $result->numA++;
          $result->strB = "pre";
        }
        else {
          // else if part is of type "<num><alpha>..."
          $result->strB = $str;
          
          if ($vertok->hasMoreTokens()) {
            $next = $vertok->nextToken();
            if (is_numeric($next)){
              $result->numC = $next;
            }
            else {
              $result->numC = 0;
            }
            if ($vertok->hasMoreTokens()) {
              $result->extraD = $vertok->getRemainder();
            }
          }
        }
      }
    }

    if (sizeOf($tok)>1) {
      // return everything after "."
      return substr($aVersion, strlen($part) + 1);
    }

    return null;
  }
  
  
  private static function compareVersionPart(ToolkitVersionPart $va, ToolkitVersionPart $vb) {
    $res = self::compareInt($va->numA, $vb->numA);
    if ($res != 0) { return $res; }
    
    $res = self::compareString($va->strB, $vb->strB);
    if ($res != 0) { return $res; }
    
    $res = self::compareInt($va->numC, $vb->numC);
    if ($res != 0) { return $res; }
    
    return self::compareString($va->extraD, $vb->extraD);
  }
  
  
  private static function compareInt($n1, $n2) { return $n1 - $n2; }
  
  
  private static function compareString($str1, $str2) {
    // any string is *before* no string
    if ($str1 === null) { return ($str2 !== null) ? 1 : 0; }
    if ($str2 === null) { return -1; }
    return strcmp($str1, $str2);
  } 
}

/**
 * Specialized tokenizer for Mozilla version strings.  A token can
 * consist of one of the four sections of a version string:
 * <number-a><string-b><number-c><string-d (everything else)>
 */
class ToolkitVersionPartTokenizer {
  private $part = '';
  
  public function __construct($aPart) { $this->part = $aPart; }
  
  public function hasMoreElements() { return strlen($this->part) != 0; }
  public function hasMoreTokens() { return strlen($this->part) != 0; }
  public function nextToken() { return $this->nextElement(); }
  
  public function nextElement() {
    if (preg_match('/^[\+\-]?[0-9].*/', $this->part)) {
      // if string starts with a number...
      $index = 0;
      if ($this->part[0] == '+' || $this->part[0] == '-') {
        $index = 1;
      }
      while (($index < strlen($this->part)) && is_numeric($this->part[$index])) {
        $index++;
      }
      $numPart = substr($this->part, 0, $index);
      $this->part = substr($this->part, $index);
      return $numPart;
    }
    else {
      // ... or if this is the non-numeric part of version string
      $index = 0;
      while (($index < strlen($this->part)) && !is_numeric($this->part[$index])) {
        $index++;
      }
      $alphaPart = substr($this->part, 0, $index);
      $this->part = substr($this->part, $index);
      return $alphaPart;
    }
  }

  /**
   * Returns what remains of the original string, without tokenization.  This
   * method is useful for getting the <string-d (everything else)>;
   * section of a version string.
   * 
   * @return remaining version string
   */
  public function getRemainder() { return $this->part; }
}

// ====================================================================================================================
}

namespace Illuminate\Support {
// == | ArrayUtils | ==================================================================================================

/* The Arr class and its methods are under the following license:

  The MIT License (MIT)

  Copyright (c) Taylor Otwell

  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
  documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
  rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
  persons to whom the Software is furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in all copies or substantial portions
  of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
  WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
class Arr {
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (! static::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return $array[$key] ?? value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array  $array
     * @param  string|int|null  $key
     * @param  mixed  $value
     * @return array
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
      }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string|int|float  $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && static::accessible($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  iterable  $array
     * @param  string  $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     *
     * @param  iterable  $array
     * @return array
     */
    public static function undot($array)
    {
        $results = [];

        foreach ($array as $key => $value) {
            static::set($results, $key, $value);
        }

        return $results;
    }
}

// ====================================================================================================================
}

namespace {
// == | Static Registry Class | =======================================================================================

class gRegistryUtils {
  private static $init = false;
  private static $registry = EMPTY_ARRAY;
  private static $remap = ['static', 'general', 'request'];

  /********************************************************************************************************************
  * Init the static class
  ********************************************************************************************************************/
  public static function init() {
    if (self::$init) {
      return;
    }

    $domain = function($aHost, $aReturnSub = false) {
      $host = gSplitString(DOT, $aHost);
      return implode(DOT, $aReturnSub ? array_slice($host, 0, -2) : array_slice($host, -2, 2));
    };

    $path = gSplitPath(self::getGlobalVar('get', 'path', SLASH));
    self::$registry = array(
      'app' => array(
        'component'   => self::getGlobalVar('get', 'component', 'site'),
        'path'        => $path,
        'depth'       => count($path ?? EMPTY_ARRAY),
        'debug'       => kDebugMode,
      ),
      'network' => array(
        'scheme'      => self::getGlobalVar('server', 'SCHEME') ?? (self::getGlobalVar('server', 'HTTPS') ? 'https' : 'http'),
        'baseDomain'  => $domain(self::getGlobalVar('server', 'SERVER_NAME', 'localhost')),
        'subDomain'   => $domain(self::getGlobalVar('server', 'SERVER_NAME', 'localhost'), true),
        'remoteAddr'  => self::getGlobalVar('server', 'HTTP_X_FORWARDED_FOR', self::getGlobalVar('server', 'REMOTE_ADDR', '127.0.0.1')),
        'userAgent'   => self::getGlobalVar('server', 'HTTP_USER_AGENT', 'php' . DASH . PHP_SAPI . SLASH . PHP_VERSION),
      ),
      'output' => array(
        'skin' => 'default',
        'contentType' => ini_get('default_mimetype'),
      ),
    );

    self::$init = true;
  }

  /********************************************************************************************************************
  * Get the registry property and return it
  ********************************************************************************************************************/
  public static function component(?string $aCompareComponent = null) {
    $rv = (self::$init) ? self::get('app.component') : self::getGlobalVar('get', 'component', 'site');

    if ($aCompareComp) {
      $rv = ($rv === $aCompareComponent);
    }

    return $rv;
  }

  /********************************************************************************************************************
  * Get the registry property and return it
  ********************************************************************************************************************/
  public static function debug() {
    return (self::$init) ? self::get('app.debug') : kDebugMode;
  }

  /********************************************************************************************************************
  * Get the registry property and return it
  ********************************************************************************************************************/
  public static function getStore() {
    $store = self::$registry;

    /*
    $store['static'] = get_defined_constants(true)['user'] ?? EMPTY_ARRAY;

    $store['request'] = array(
      'get'     => SAPI_IS_CLI ? $_ARGV : $_GET,
      'server'  => $_SERVER ?? EMPTY_ARRAY,
      'files'   => $_FILES ?? EMPTY_ARRAY,
      'post'    => $_POST ?? EMPTY_ARRAY,
      'cookie'  => $_COOKIE ?? EMPTY_ARRAY,
      'session' => $_SESSION ?? EMPTY_ARRAY,
    );
    */

    return $store;
  }

  /********************************************************************************************************************
  * Get the value of a dotted key from the registry property except for virtual regnodes
  ********************************************************************************************************************/
  public static function get(string $aKey, $aDefault = null) {
    if ($aKey == EMPTY_STRING) {
      return null;
    }

    $keys = gSplitStr(DOT, $aKey);

    if (in_array($keys[0] ?? EMPTY_STRING, self::$remap)) {
      switch ($keys[0] ?? EMPTY_STRING) {
        case 'static':
          if (count($keys) < 2) {
            return null;
          }

          $ucConst = strtoupper($keys[1]);
          $prefixConst = 'k' . ucfirst($keys[1]);

          switch (true) {
            case defined($prefixConst):
              $rv = constant($prefixConst);
              break;
            case defined($ucConst):
              $rv = constant($ucConst);
              break;
            case defined($keys[1]):
              $rv = constant($keys[1]);
              break;
            default:
              return null;
          }

          if (!\Illuminate\Support\Arr::accessible($rv)) {
            return $rv ?? $aDefault;
          }

          unset($keys[0], $keys[1]);
          $rv = \Illuminate\Support\Arr::get($rv, self::getGlobalVar('check', implode(DOT, $keys)), $aDefault);
          break;
        case 'request':
          if (count($keys) < 3) {
            return null;
          }

          $rv = self::getGlobalVar($keys[1], $keys[2]);

          if (!Illuminate\Support\Arr::accessible($rv)) {
            return $rv ?? $aDefault;
          }

          unset($keys[0], $keys[1]);
          $rv = \Illuminate\Support\Arr::get($rv, self::getGlobalVar('check', implode(DOT, $keys)), $aDefault);
          break;
        default:
          if (count($keys) < 2 || str_starts_with($keys[1], UNDERSCORE)) {
            return null;
          }

          unset($keys[0]);
          $rv = \Illuminate\Support\Arr::get($GLOBALS, self::getGlobalVar('check', implode(DOT, $keys)), $aDefault);
      }
    }
    else {
      $rv = \Illuminate\Support\Arr::get(self::$registry, $aKey, $aDefault);
    }
      
    return $rv;
  }

  /********************************************************************************************************************
  * Set the value of a dotted key from the registry property
  ********************************************************************************************************************/
  public static function set($aKey, $aValue) {
    if (in_array(gSplitStr(DOT, $aKey)[0] ?? EMPTY_STRING, self::$remap)) {
      gError('Setting values on remapped nodes is not supported.');
    }

    if ($aValue instanceof \Closure) {
      return false;
    }

    return \Illuminate\Support\Arr::set(self::$registry, $aKey, $aValue);
  }

  /********************************************************************************************************************
  * Access Super Globals
  ********************************************************************************************************************/
  public static function getGlobalVar($aNode, $aKey, $aDefault = null) {
    $rv = null;

    // Turn the variable type into all caps prefixed with an underscore
    $aNode = UNDERSCORE . strtoupper($aNode);

    // This handles the superglobals
    switch($aNode) {
      case '_CHECK':
        $rv = (empty($aKey) || $aKey === 'none' || $aKey === 0) ? null : $aKey;
        break;
      case '_GET':
        if (SAPI_IS_CLI && $GLOBALS['argc'] > 1) {
          $args = [];

          foreach (array_slice($GLOBALS['argv'], 1) as $_value) {
            $arg = @explode('=', $_value);

            if (count($arg) < 2) {
              continue;
            }

            $attr = str_replace('--', EMPTY_STRING, $arg[0]);
            $val = self::getGlobalVar('check', str_replace('"', EMPTY_STRING, $arg[1]));

            if (!$attr && !$val) {
              continue;
            }

            $args[$attr] = $val;
          }

          $rv = $args[$aKey] ?? $aDefault;
          break;
        }
      case '_SERVER':
      case '_ENV':
      case '_FILES':
      case '_POST':
      case '_COOKIE':
      case '_SESSION':
        $rv = $GLOBALS[$aNode][$aKey] ?? $aDefault;
        break;
      default:
        // We don't know WHAT was requested but it is obviously wrong...
        gError('Unknown system node.');
    }
    
    // We always pass $_GET values through a general regular expression
    // This allows only a-z A-Z 0-9 - / { } @ % whitespace and ,
    if ($rv && $aNode == "_GET") {
      $rv = preg_replace(REGEX_GET_FILTER, EMPTY_STRING, $rv);
    }

    // Files need special handling.. In principle we hard fail if it is anything other than
    // OK or NO FILE
    if ($rv && $aNode == "_FILES") {
      if (!in_array($rv['error'], [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE])) {
        gError('Upload of ' . $aKey . ' failed with error code: ' . $rv['error']);
      }

      // No file is handled as merely being null
      if ($rv['error'] == UPLOAD_ERR_NO_FILE) {
        return null;
      }

      // Cursory check the actual mime-type and replace whatever the web client sent
      $rv['type'] = mime_content_type($rv['tmp_name']);
    }
    
    return $rv;
  }
}

// ====================================================================================================================

// == | Static Error Class | ==========================================================================================



// ====================================================================================================================

// == | Static Output Class | =========================================================================================

class gConsole {
  const HTTP_HEADERS = array(
    404                       => 'HTTP/1.1 404 Not Found',
    501                       => 'HTTP/1.1 501 Not Implemented',
    'text'                    => 'Content-Type: text/plain',
    'html'                    => 'Content-Type: text/html',
    'xhtml'                   => 'Content-Type: application/xhtml+xml',
    'css'                     => 'Content-Type: text/css',
    'xml'                     => 'Content-Type: text/xml',
    'json'                    => 'Content-Type: application/json',
    'bin'                     => 'Content-Type: application/octet-stream',
    'xpi'                     => 'Content-Type: application/x-xpinstall',
    '7z'                      => 'Content-Type: application/x-7z-compressed',
    'xz'                      => 'Content-Type: application/x-xz',
  );

  /**********************************************************************************************************************
  * Sends HTTP Headers to client using a short name
  *
  * @dep HTTP_HEADERS
  * @dep kDebugMode
  * @dep gError()
  * @param $aHeader    Short name of header
  **********************************************************************************************************************/
  public static function header($aHeader, $aReplace = true) { 
    $debugMode = gRegistryUtils::debug();
    $isErrorPage = in_array($aHeader, [404, 501]);

    if (!array_key_exists($aHeader, self::HTTP_HEADERS)) {
      gError('Unknown' . SPACE . $aHeader . SPACE . 'header');
    }

    if ($debugMode && $isErrorPage) {
      gError(self::HTTP_HEADERS[$aHeader]);
    }

    if (!headers_sent()) { 
      header(self::HTTP_HEADERS[$aHeader], $aReplace);

      if ($isErrorPage) {
        exit();
      }
    }
  }

  /**********************************************************************************************************************
  * Sends HTTP Header to redirect the client to another URL
  *
  * @param $aURL   URL to redirect to
  **********************************************************************************************************************/
  public static function redirect($aURL) { header('Location: ' . $aURL, true, 302); exit(); }

  /**********************************************************************************************************************
  * Get a subdomain or base domain from a host
  *
  * @dep DOT
  * @dep gSplitString()
  * @param $aHost       Hostname
  * @param $aReturnSub  Should return subdmain
  * @returns            domain or subdomain
  ***********************************************************************************************************************/
  public static function getDomain(string $aHost, ?bool $aReturnSub = null) {
    $host = gSplitString(DOT, $aHost);
    return implode(DOT, $aReturnSub ? array_slice($host, 0, -2) : array_slice($host, -2, 2));
  }

  /******************************************************************************************************************
  * Simply prints output and sends header if not cli and exits
  ******************************************************************************************************************/
  public static function display($aContent, $aHeader = null) {
    $content = null;

    if (is_array($aContent)) {
      $title = $aContent['title'] ?? 'Output';
      $content = $aContent['content'] ?? EMPTY_STRING;

      if ($title == 'Output' && $content == EMPTY_STRING) {
        $content = $aContent ?? $content;
      }
    }
    else {
      $title = 'Output';
      $content = $aContent ?? EMPTY_STRING;
    }

    $content = (is_string($content) || is_int($content)) ? $content : json_encode($content, JSON_FLAGS['display']);

    // Send the header if not cli
    if (SAPI_IS_CLI) {
      if (!CLI_NO_LOGO) {
        $software = $title . DASH_SEPARATOR . kAppVendor . SPACE . kAppName . SPACE . kAppVersion;
        $titleLength = 120 - 8 - strlen($software);
        $titleLength = ($titleLength > 0) ? $titleLength : 2;
        $title = NEW_LINE . '==' . SPACE . PIPE . SPACE . $software . SPACE . PIPE . SPACE . str_repeat('=', $titleLength);
        $content = $title . NEW_LINE . NEW_LINE . $content . NEW_LINE . NEW_LINE . str_repeat('=', 120) . NEW_LINE;
      }
    }
    else {
      if (!headers_sent()) {
        self::header($aHeader ?? 'text');
      }
    }

    // Write out the content
    print($content);

    // We're done here...
    exit();
  }
}

// ====================================================================================================================

// == | Static Class Init and Global Wrappers | =======================================================================

function gVersionCompare(...$args) { return mozilla\vc\ToolkitVersionComparator::compare(...$args); }
function gGetArrVal(...$args) { return Illuminate\Support\Arr::get(...$args); }
function gSetArrVal(...$args) { return Illuminate\Support\Arr::set(...$args); }
function gUnsetArrVal(...$args) { return Illuminate\Support\Arr::forget(...$args); }

// --------------------------------------------------------------------------------------------------------------------

gRegistryUtils::init();
//gErrorUtils::init();

// --------------------------------------------------------------------------------------------------------------------

function gGetRegKey(...$args) { return gRegistryUtils::get(...$args); }
function gSetRegKey(...$args) { return gRegistryUtils::set(...$args); }
function gSuperGlobal(...$args) { return gRegistryUtils::getGlobalVar(...$args); }
function gMaybeNull(...$args) { return gRegistryUtils::getGlobalVar('check', ...$args); }

// --------------------------------------------------------------------------------------------------------------------

function gOutput(...$args) { return gConsole::display(...$args); }
function gHeader(...$args) { return gConsole::header(...$args); }
function gRedirect(...$args) { return gConsole::redirect(...$args); }

// ====================================================================================================================

// == | Global Functions | ============================================================================================

/**********************************************************************************************************************
* General Error Function
*
* @param $aMessage   Error message
**********************************************************************************************************************/
function gError($aMessage) {
  gOutput($aMessage);
}

/**********************************************************************************************************************
* Registers Files to be included such as components and modules
***********************************************************************************************************************/
function gRegisterIncludes($aConst, $aIncludes) {
  if (defined($aConst)) {
    return;
  }

  $includes = EMPTY_ARRAY;

  foreach($aIncludes as $_key => $_value) { 
    switch ($aConst) {
      case 'COMPONENTS':
        $includes[$_value] = gPath(ROOT_PATH, 'components', $_value, 'src', $_value . FILE_EXTS['php']);
        break;
      case 'MODULES':
        $includes[$_value] = gPath(ROOT_PATH, 'modules', $_value . FILE_EXTS['php']);
        break;
      case 'LIBRARIES':
        if (str_contains($_value, DOT . DOT)) {
          return;
        }

        $includes[$_key] = gPath(ROOT_PATH, 'third_party', $_value);
        break;
      default:
        gfError('Unknown include type');
    }
  }

  define($aConst, $includes);
}

/**********************************************************************************************************************
* Basic Filter Substitution of a string
*
* @dep EMPTY_STRING
* @dep SLASH
* @dep SPACE
* @dep gError()
* @param $aSubsts               multi-dimensional array of keys and values to be replaced
* @param $aString               string to operate on
* @param $aRegEx                set to true if pcre
* @returns                      bitwise int value representing applications
***********************************************************************************************************************/
function gSubst(string $aString, array $aSubsts, bool $aRegEx = false) {
  $rv = $aString;
  $replaceFunction = $aRegEx ? 'preg_replace' : 'str_replace';

  foreach ($aSubsts as $_key => $_value) {
    $rv = call_user_func($replaceFunction, ($aRegEx ? SLASH . $_key . SLASH . 'iU' : $_key), $_value, $rv);
  }

  return !$rv ? gError('Something has gone wrong...') : $rv;
}

/**********************************************************************************************************************
* Explodes a string to an array without empty elements if it starts or ends with the separator
*
* @dep DASH_SEPARATOR
* @dep gError()
* @param $aSeparator   Separator used to split the string
* @param $aString      String to be exploded
* @returns             Array of string parts
***********************************************************************************************************************/
function gSplitStr(string $aSeparator, string $aString) {
  return (!str_contains($aString, $aSeparator)) ? [$aString] :
          array_values(array_filter(explode($aSeparator, $aString), 'strlen'));
}
function gSplitString(...$args) { return gSplitStr(...$args); }

/**********************************************************************************************************************
* Splits a path into an indexed array of parts
*
* @dep SLASH
* @dep gSplitString()
* @param $aPath   URI Path
* @returns        array of uri parts in order
***********************************************************************************************************************/
function gSplitPath(string $aPath) {
  return ($aPath == SLASH) ? ['root'] : gSplitString(SLASH, $aPath);
}

/**********************************************************************************************************************
* Builds and Normalizes Paths
***********************************************************************************************************************/
function gPath(...$aParts) {
  $parts = EMPTY_ARRAY;
  $path = strtr(implode(SLASH, $aParts), '\\', SLASH);
  $prefix = EMPTY_STRING;
  $absolute = false;

  // extract a prefix being a protocol://, protocol:, protocol://drive: or simply drive:
  if (preg_match('{^( [0-9a-z]{2,}+: (?: // (?: [a-z]: )? )? | [a-z]: )}ix', $path, $match)) {
    $prefix = $match[1];
    $path = substr($path, strlen($prefix));
  }

  if (substr($path, 0, 1) === SLASH) {
    $absolute = true;
    $path = substr($path, 1);
  }

  $up = false;

  foreach (explode(SLASH, $path) as $chunk) {
    if (DOTDOT === $chunk && ($absolute || $up)) {
      array_pop($parts);
      $up = !(empty($parts) || DOTDOT === end($parts));
    }
    elseif (DOT !== $chunk && EMPTY_STRING !== $chunk) {
      $parts[] = $chunk;
      $up = DOTDOT !== $chunk;
    }
  }

  return $prefix . ($absolute ? SLASH : EMPTY_STRING) . implode(SLASH, $parts);
}

/**********************************************************************************************************************
* Strips the constant ROOT_PATH from a string
*
* @dep ROOT_PATH
* @dep EMPTY_STRING
* @param $aPath   Path to be stripped
* @returns        Stripped path
***********************************************************************************************************************/
function gStripSubstr(string $aStr, ?string $aStripStr = null) {
  return str_replace($aStripStr ?? ROOT_PATH, EMPTY_STRING, $aStr);
}

/**********************************************************************************************************************
* Hash a password
***********************************************************************************************************************/
function gPasswordHash(string $aPassword, mixed $aCrypt = PASSWORD_BCRYPT, ?string $aSalt = null) {
  switch ($aCrypt) {
    case PASSWORD_CLEARTEXT:
      // We can "hash" a cleartext password by prefixing it with the fake algo prefix $clear$
      if (str_contains($aPassword, DOLLAR)) {
        // Since the dollar sign is used as an identifier and/or separator for hashes we can't use passwords
        // that contain said dollar sign.
        gError('Cannot "hash" this Clear Text password because it contains a dollar sign.');
      }

      return DOLLAR . PASSWORD_CLEARTEXT . DOLLAR . time() . DOLLAR . $aPassword;
    case PASSWORD_HTACCESS:
      // We want to be able to generate Apache APR1-MD5 hashes for use in .htpasswd situations.
      $salt = $aSalt;

      if (!$salt) {
        $salt = EMPTY_STRING;

        for ($i = 0; $i < 8; $i++) {
          $offset = hexdec(bin2hex(openssl_random_pseudo_bytes(1))) % 64;
          $salt .= APRMD5_ALPHABET[$offset];
        }
      }

      $salt = substr($salt, 0, 8);
      $max = strlen($aPassword);
      $context = $aPassword . DOLLAR . PASSWORD_HTACCESS . DOLLAR . $salt;
      $binary = pack('H32', md5($aPassword . $salt . $aPassword));

      for ($i = $max; $i > 0; $i -= 16) {
        $context .= substr($binary, 0, min(16, $i));
      }

      for ($i = $max; $i > 0; $i >>= 1) {
        $context .= ($i & 1) ? chr(0) : $aPassword[0];
      }

      $binary = pack('H32', md5($context));

      for ($i = 0; $i < 1000; $i++) {
        $new = ($i & 1) ? $aPassword : $binary;

        if ($i % 3) {
          $new .= $salt;
        }
        if ($i % 7) {
          $new .= $aPassword;
        }

        $new .= ($i & 1) ? $binary : $aPassword;
        $binary = pack('H32', md5($new));
      }

      $hash = EMPTY_STRING;

      for ($i = 0; $i < 5; $i++) {
        $k = $i + 6;
        $j = $i + 12;
        if($j == 16) $j = 5;
        $hash = $binary[$i] . $binary[$k] . $binary[$j] . $hash;
      }

      $hash = chr(0) . chr(0) . $binary[11] . $hash;
      $hash = strtr(strrev(substr(base64_encode($hash), 2)), BASE64_ALPHABET, APRMD5_ALPHABET);

      return DOLLAR . PASSWORD_HTACCESS . DOLLAR . $salt . DOLLAR . $hash;
    default:
      // Else, our standard (and secure) default is PASSWORD_BCRYPT hashing.
      // We do not allow custom salts for anything using password_hash as PHP generates secure salts.
      // PHP Generated passwords are also self-verifiable via password_verify.
      return password_hash($aPassword, $aCrypt);
  }
}

/**********************************************************************************************************************
* Check a password
***********************************************************************************************************************/
function gPasswordVerify(string $aPassword, string $aHash) {
  // We can accept a pseudo-hash for clear text passwords in the format of $clrtxt$unix-epoch$clear-text-password
  if (str_starts_with($aHash, DOLLAR . PASSWORD_CLEARTEXT)) {
    $password = gSplitString(DOLLAR, $aHash) ?? null;

    if ($password == null || count($password) > 3) {
      gError('Unable to "verify" this Clear Text "hashed" password.');
    }

    return $aPassword === $password[2];
  }

  // We can also accept an Apache APR1-MD5 password that is commonly used in .htpasswd
  if (str_starts_with($aHash, DOLLAR . PASSWORD_HTACCESS)) {
    $salt = gSplitString(DOLLAR, $aHash)[1] ?? null;

    if(!$salt) {
      gError('Unable to verify this Apache APR1-MD5 hashed password.');
    }

    return gPasswordHash($aPassword, PASSWORD_HTACCESS, $salt) === $aHash;
  }

  // For everything else send to the native password_verify function.
  // It is almost certain to be a BCRYPT2 hash but hashed passwords generated BY PHP are self-verifiable.
  return password_verify($aPassword, $aHash);
}

/**********************************************************************************************************************
* Generates a v4 random guid or a "v4bis" guid with static vendor node
***********************************************************************************************************************/
function gGUID(?string $aVendor = null, $aXPCOM = null) {
  if ($aVendor) {
    if (strlen($aVendor) < 3) {
      gError('v4bis GUIDs require a defined vendor of more than three characters long.');
    }

    // Generate 8 pseudo-random bytes
    $bytes = random_bytes(8);

    // Knock the vendor down to lowercase so we can simply use a switch case
    $vendor = strtolower($aVendor);

    // We want "v4bis" GUIDs with the static vendor part to match the broken version of GUIDGenX for known nodes
    // as Moonchild half-assed his tool that he wrote for this and by the time it was discovered several were already
    // using the previous incorrect GUIDs.
    $knownVendorNodes = array(
      'binoc'           => hex2bin('8b97957ad5f8ea47'),
      'binoc-legacy'    => hex2bin('9aa0aa0e607640b9'),
      'mcp'             => hex2bin('bfc5fc555c87dbc4'),
      'lootyhoof'       => hex2bin('b98e98e62085837f'),
      'pseudo-static'   => hex2bin('93763763d1ad1978')
    );

    switch ($vendor) {
      case 'binoc':
      case 'binaryoutcast':
      case 'binary outcast':
        $bytes .= $knownVendorNodes['binoc'];
        break;
      case 'pseudo-static':
      case 'pseudostatic':
      case 'addons':
      case 'add-ons':
      case 'apmo':
        $bytes .= $knownVendorNodes['pseudo-static'];
        break;
      case 'mcp':
      case 'moonchildproductions':
      case 'moonchild productions':
        $bytes .= $knownVendorNodes['mcp'];
        break;
      case 'binoc-legacy':
      case 'lootyhoof':
        $bytes .= $knownVendorNodes[$vendor];
        break;
      default:
        // Since this isn't a known busted vendor node then we should generate it ourselves.
        // This matches the fixed version of GUIDGenX 1.1 which is to md5 hash the vendor string then
        // split it in half and XOR the two parts for the final value
        $vendor = hash('md5', $aVendor);
        $bytes .= hex2bin(substr($vendor, 0, 16)) ^ hex2bin(substr($vendor, 16, 32));
    }
  }
  else {
    // This is a pure v4 UUID spec which is 16 pseudo-random bytes.
    $bytes = random_bytes(16);
  }

  // Set the version and variant
  // NOTE: Like everything Moonzilla does, he did not set the variant value when he initially came up with "v4bis"
  // putting a busted generator into production use for the whole team. Sad!
  $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
  $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

  $hex = bin2hex($bytes);
  $guid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));

  // We want the GUID in XPIDL/C++ Header notation
  if ($aXPCOM) {
    $explode = gSplitString(DASH, $guid);
    $rv = "%{C++" . NEW_LINE . "//" . SPACE . "{" . $guid . "}" . NEW_LINE .
          "#define NS_CHANGE_ME_IID" . SPACE . 
          vsprintf("{ 0x%s, 0x%s, 0x%s, { 0x%s, 0x%s, 0x%s, 0x%s, 0x%s, 0x%s, 0x%s, 0x%s } }",
                   [$explode[0], $explode[1], $explode[2],
                    substr($explode[3], 0, 2), substr($explode[3], 2, 2),
                    substr($explode[4], 0, 2), substr($explode[4], 2, 2), substr($explode[4], 4, 2),
                    substr($explode[4], 6, 2), substr($explode[4], 8, 2), substr($explode[4], 10, 2)]) . NEW_LINE .
          "%}";
  }
  else {
    // We like Microsoft GUID notation not UUID which means Lisa needs braces.. I mean the GUID.
    $rv = '{' . $guid . '}';
  }

  return $rv;
}

// ====================================================================================================================
}