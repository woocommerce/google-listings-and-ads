<?php

/**
 * Generate the measurement.php file using ClassPreloader.
 *
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */


define('CLASS_PRELOADER', __DIR__.'/../vendor/classpreloader/classpreloader');

require CLASS_PRELOADER.'/src/ClassLoader/Config.php';
require CLASS_PRELOADER.'/src/ClassLoader/ClassNode.php';
require CLASS_PRELOADER.'/src/ClassLoader/ClassList.php';
require CLASS_PRELOADER.'/src/ClassLoader.php';

use ClassPreloader\ClassLoader;


$config = ClassLoader::getIncludes(function (ClassLoader $loader) {
    require __DIR__.'/../vendor/autoload.php';
    $loader->register();

    // Craft a fake request so that ClassPreloader can run through the proxy
    // script and pick up all the necessary files and classes.
    $_SERVER = ['SCRIPT_NAME' => '/src/measurement.php'];
    $_GET = ['id' => 'G-12345', 's' => '/healthy'];

    Google\GoogleTagGatewayLibrary\Proxy\Runner::create()->run();
});

// Add the main entrypoint file that executes code.
$config->addFile('src/Proxy/main.php');

return $config;
