<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Basic Content Generation using the Special Component's Template
***********************************************************************************************************************/
function gfGenContent($aTitle, $aContent, $aTextBox = null, $aList = null, $aError = null) {
  $templateHead = @file_get_contents('./skin/special/template-header.xhtml');
  $templateFooter = @file_get_contents('./skin/special/template-footer.xhtml');

  // Make sure the template isn't busted, if it is send a text only error as an array
  if (!$templateHead || !$templateFooter) {
    gfError([__FUNCTION__ . ': Special Template is busted...', $aTitle, $aContent], -1);
  }

  // Can't use both the textbox and list arguments
  if ($aTextBox && $aList) {
    gfError(__FUNCTION__ . ': You cannot use both textbox and list');
  }

  // Anonymous function to determin if aContent is a string-ish or not
  $notString = function() use ($aContent) {
    return (!is_string($aContent) && !is_int($aContent)); 
  };

  // If not a string var_export it and enable the textbox
  if ($notString()) {
    $aContent = var_export($aContent, true);
    $aTextBox = true;
    $aList = false;
  }

  // Use either a textbox or an unordered list
  if ($aTextBox) {
    // We are using the textbox so put aContent in there
    $aContent = '<textarea style="width: 1195px; resize: none;" name="content" rows="36" readonly>' .
                $aContent .
                '</textarea>';
  }
  elseif ($aList) {
    // We are using an unordered list so put aContent in there
    $aContent = '<ul><li>' . $aContent . '</li><ul>';
  }

  // Set page title
  $templateHead = str_replace('<title></title>',
                  '<title>' . $aTitle . ' - ' . SOFTWARE_NAME . ' ' . SOFTWARE_VERSION . '</title>',
                  $templateHead);

  // If we are generating an error from gfError we want to clean the output buffer
  if ($aError) {
    ob_get_clean();
  }

  // Send an html header
  header('Content-Type: text/html', false);

  // write out the everything
  print($templateHead . '<h2>' . $aTitle . '</h2>' . $aContent . $templateFooter);

  // We're done here
  exit();
}

// --------------------------------------------------------------------------------------------------------------------

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

switch ($gaRuntime['explodedPath'][0]) {
  case 'phpinfo':
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_ENVIRONMENT | INFO_VARIABLES);
    break;
  case 'software-state':
    gfGenContent('Software State', $gaRuntime);
    break;
  case 'test':
    $gaRuntime['requestTestCase'] = gfSuperVar('get', 'case');
    $arrayTestsGlob = glob('./base/tests/*.php');
    $arrayFinalTests = [];

    foreach ($arrayTestsGlob as $_value) {
      $arrayFinalTests[] = str_replace('.php', '', str_replace('./base/tests/', '', $_value));
    }

    unset($arrayTestsGlob);

    if ($gaRuntime['requestTestCase']) {
      if (!in_array($gaRuntime['requestTestCase'], $arrayFinalTests)) {
        gfError('Unknown test case');
      }

      require_once('./base/tests/' . $gaRuntime['requestTestCase'] . '.php');
    }

    $testsHTML = '';

    foreach ($arrayFinalTests as $_value) {
      $testsHTML .= '<li><a href="/test/?case=' . $_value . '">' . $_value . '</a></li>';
    }

    $testsHTML = '<ul>' . $testsHTML . '</ul>';

    gfGenContent('Test Cases', $testsHTML);
    break;
  case 'root':
    $rootHTML = '<a href="/test/">Test Cases</a></li><li>' .
                '<a href="/phpinfo/">PHP Info</a></li><li>' .
                '<a href="/software-state/">Software State</a>';
    gfGenContent('Functions', $rootHTML, null, true);
  default:
    gfHeader(404);
}

// ====================================================================================================================
?>