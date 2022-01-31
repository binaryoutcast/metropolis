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
* Basic Content Generation using the Special Component's Template
***********************************************************************************************************************/
function gfGenContent($aMetadata, $aLegacyContent = null, $aTextBox = null, $aList = null, $aError = null) {
  $ePrefix = __FUNCTION__ . DASH_SEPARATOR;
  $skinPath = '/skin/default';

  // Anonymous functions
  $contentIsStringish = function($aContent) {
    return (!is_string($aContent) && !is_int($aContent)); 
  };

  $textboxContent = function($aContent) {
    return '<textarea class="special-textbox aligncenter" name="content" rows="36" readonly>' .
           $aContent . '</textarea>';
  };

  $template = gfReadFile(DOT . $skinPath . SLASH . 'template.xhtml');

  if (!$template) {
    gfError($ePrefix . 'Special Template is busted...', null, true);
  }

  $pageSubsts = array(
    '{$SKIN_PATH}'        => $skinPath,
    '{$SITE_NAME}'        => defined('SITE_NAME') ? SITE_NAME : SOFTWARE_NAME . SPACE . SOFTWARE_VERSION,
    '{$SITE_MENU}'        => EMPTY_STRING,
    '{$PAGE_TITLE}'       => null,
    '{$PAGE_CONTENT}'     => null,
    '{$SOFTWARE_NAME}'    => SOFTWARE_NAME,
    '{$SOFTWARE_VERSION}' => SOFTWARE_VERSION,
  );

  if ($aLegacyContent) {
    if (is_array($aMetadata)) {
      gfError($ePrefix . 'aMetadata may not be an array in legacy mode.');
    }

    if ($aTextBox && $aList) {
      gfError($ePrefix . 'You cannot use both textbox and list');
    }

    if ($contentIsStringish($aLegacyContent)) {
      $aLegacyContent = var_export($aLegacyContent, true);
      $aTextBox = true;
      $aList = false;
    }

    if ($aTextBox) {
      $aLegacyContent = $textboxContent($aLegacyContent);
    }
    elseif ($aList) {
      // We are using an unordered list so put aLegacyContent in there
      $aLegacyContent = '<ul><li>' . $aLegacyContent . '</li><ul>';
    }

    if (!$aError && ($GLOBALS['gaRuntime']['qTestCase'] ?? null)) {
      $pageSubsts['{$PAGE_TITLE}'] = 'Test Case' . DASH_SEPARATOR . $GLOBALS['gaRuntime']['qTestCase'];

      foreach ($GLOBALS['gaRuntime']['siteMenu'] ?? EMPTY_ARRAY as $_key => $_value) {
        $pageSubsts['{$SITE_MENU}'] .= '<li><a href="' . $_key . '">' . $_value . '</a></li>';
      }
    }
    else {
      $pageSubsts['{$PAGE_TITLE}'] = $aMetadata;
    }

    $pageSubsts['{$PAGE_CONTENT}'] = $aLegacyContent;
  }
  else {
    if ($aTextBox || $aList) {
      gfError($ePrefix . 'Mode attributes are deprecated.');
    }

    if (!array_key_exists('title', $aMetadata) && !array_key_exists('content', $aMetadata)) {
      gfError($ePrefix . 'You must specify a title and content');
    }

    $pageSubsts['{$PAGE_TITLE}'] = $aMetadata['title'];
    $pageSubsts['{$PAGE_CONTENT}'] = $contentIsStringish($aMetadata['content']) ?
                                     $textboxContent(var_export($aMetadata['content'], true)) :
                                     $aMetadata['content'];

    foreach ($aMetadata['menu'] ?? EMPTY_ARRAY as $_key => $_value) {
      $pageSubsts['{$SITE_MENU}'] .= '<li><a href="' . $_key . '">' . $_value . '</a></li>';
    }
  }

  if ($pageSubsts['{$SITE_MENU}'] == EMPTY_STRING) {
    $pageSubsts['{$SITE_MENU}'] = '<li><a href="/">Root</a></li>';
  }

  if (!str_starts_with($pageSubsts['{$PAGE_CONTENT}'], '<p') &&
      !str_starts_with($pageSubsts['{$PAGE_CONTENT}'], '<ul') &&
      !str_starts_with($pageSubsts['{$PAGE_CONTENT}'], '<h1') &&
      !str_starts_with($pageSubsts['{$PAGE_CONTENT}'], '<h2') &&
      !str_starts_with($pageSubsts['{$PAGE_CONTENT}'], '<table')) {
    $pageSubsts['{$PAGE_CONTENT}'] = '<p>' . $pageSubsts['{$PAGE_CONTENT}'] . '</p>';
  }

  $template = gfSubst('string', $pageSubsts, $template);

  // If we are generating an error from gfError we want to clean the output buffer
  if ($aError) {
    ob_get_clean();
  }

  // Send an html header
  header('Content-Type: text/html', false);

  // write out the everything
  print($template);

  // We're done here
  exit();
}

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
  'debugMode'         => null,
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