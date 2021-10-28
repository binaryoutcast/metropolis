<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Checks the exploded count against the number of path parts in an exploded path and 404s it if it is greater
***********************************************************************************************************************/
function gfCheckPathCount($aExpectedCount) {
  global $gaRuntime;
  if (count($gaRuntime['explodedPath']) > $aExpectedCount) {
    gfHeader(404);
  }
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// This is protected...
gfLocalAuth();

// The Special Component doesn't intend on having more than one level on metropolis
gfCheckPathCount(1);
$gvSpecialFunction = $gaRuntime['explodedPath'][0];

$gaRuntime['siteMenu'] = array(
  '/'                 => 'Root',
  '/test/'            => 'Test Cases',
  '/software-state/'  => 'Software State',
  '/phpinfo/'         => 'PHP Info',
);

switch ($gvSpecialFunction) {
  case 'root':
    gfGenContent(['title' => 'Special Component',
                  'content' => '<h2>Welcome to the Special Component!</h2><p>Please select a function from the command bar above.</p>',
                  'menu' => $gaRuntime['siteMenu']]);
  case 'test':
    $gaRuntime['qTestCase'] = gfSuperVar('get', 'case');
    $arrayTestsGlob = glob('./base/tests/*.php');
    $arrayFinalTests = [];

    foreach ($arrayTestsGlob as $_value) {
      $arrayFinalTests[] = str_replace('.php', '', str_replace('./base/tests/', '', $_value));
    }

    unset($arrayTestsGlob);

    if ($gaRuntime['qTestCase']) {
      if (!in_array($gaRuntime['qTestCase'], $arrayFinalTests)) {
        gfError('Unknown test case');
      }

      require_once('./base/tests/' . $gaRuntime['qTestCase'] . '.php');
    }

    $testsHTML = EMPTY_STRING;

    foreach ($arrayFinalTests as $_value) {
      $testsHTML .= '<li><a href="/test/?case=' . $_value . '">' . $_value . '</a></li>';
    }

    $testsHTML = '<h2>Please select a test case&hellip;</h2><ul>' . $testsHTML . '</ul>';

    gfGenContent(['title' => 'Test Cases',
                  'content' => $testsHTML,
                  'menu' => $gaRuntime['siteMenu']]);
    break;
  case 'phpinfo':
    gfHeader('html');
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_ENVIRONMENT | INFO_VARIABLES);
    break;
  case 'software-state':
    gfGenContent(['title' => 'Software State',
                  'content' => $gaRuntime,
                  'menu' => $gaRuntime['siteMenu']]);
    break;
  default:
    gfHeader(404);
}

// ====================================================================================================================
?>