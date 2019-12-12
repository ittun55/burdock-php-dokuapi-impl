<?php
require_once __DIR__ . '/vendor/autoload.php';

use Api\Cli\DumpSchema;
use Symfony\Component\Console\Application;

define('DOKU_INC',dirname(__FILE__).'/../');
$application = new Application();
$application->add(new DumpSchema());
$application->run();