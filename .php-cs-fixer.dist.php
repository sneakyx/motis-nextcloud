<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Nextcloud\CodingStandard\Config;

$config = new Config();
$config
	->getFinder()
	->ignoreVCSIgnored(true)
	->notPath('vendor')
	->in(__DIR__ . '/lib')
	->in(__DIR__ . '/tests');
return $config;
