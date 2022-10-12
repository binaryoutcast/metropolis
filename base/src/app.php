<?php
// == | Setup | =======================================================================================================

gRegisterIncludes('COMPONENTS', EMPTY_ARRAY);
gRegisterIncludes('MODULES', EMPTY_ARRAY);
gRegisterIncludes('LIBRARIES', array(
  'rdf'             => 'rdf_parser.php',
  'safeMySQL'       => 'safemysql.class.php',
  'smarty'          => 'smarty/Smarty.class.php',
));

const kPrettyComps = ['special'];

gRegistrySet('app.offline', (file_exists(ROOT_PATH . '/.offline') && !gSuperGlobal('get', 'overrideOffline')));

// ====================================================================================================================



// ====================================================================================================================

?>