<?php

// Used by phpunit/FixturesTest.php and test/compare.php

$fixtureDir = __DIR__ . '/Fixtures';

return [
	'less.php' => [
		'lessDir' => "$fixtureDir/less.php/less",
		'cssDir' => "$fixtureDir/less.php/css",
	],
	'bug-reports' => [
		'lessDir' => "$fixtureDir/bug-reports/less",
		'cssDir' => "$fixtureDir/bug-reports/css",
	],
	'bootstrap-3.0.3' => [
		'lessDir' => "$fixtureDir/bootstrap-3.0.3/less",
		'cssDir' => "$fixtureDir/bootstrap-3.0.3/css",
	],
	'bootstrap-3.1' => [
		'lessDir' => "$fixtureDir/bootstrap-3.1/less",
		'cssDir' => "$fixtureDir/bootstrap-3.1/css",
	],
	'bootstrap-3.2' => [
		'lessDir' => "$fixtureDir/bootstrap-3.2/less",
		'cssDir' => "$fixtureDir/bootstrap-3.2/css",
	],

	// Upstream fixtures and parser options are declared
	// at https://github.com/less/less.js/blob/v2.5.3/test/index.js#L17

	'lessjs-2.5.3' => [
		'lessDir' => "$fixtureDir/lessjs-2.5.3/less",
		'cssDir' => "$fixtureDir/lessjs-2.5.3/css",
		'overrideDir' => "$fixtureDir/lessjs-2.5.3/override",
	],
	'lessjs-2.5.3/compression' => [
		'lessDir' => "$fixtureDir/lessjs-2.5.3/less/compression",
		'cssDir' => "$fixtureDir/lessjs-2.5.3/css/compression",
		'overrideDir' => "$fixtureDir/lessjs-2.5.3/override/compression",
		'options' => [
			'compress' => true,
		],
	],
	'lessjs-2.5.3/legacy' => [
		'lessDir' => "$fixtureDir/lessjs-2.5.3/less/legacy",
		'cssDir' => "$fixtureDir/lessjs-2.5.3/css/legacy",
		'options' => [
		],
	],
	'lessjs-2.5.3/strict-units' => [
		'lessDir' => "$fixtureDir/lessjs-2.5.3/less/strict-units",
		'cssDir' => "$fixtureDir/lessjs-2.5.3/css/strict-units",
		'options' => [
			'strictUnits' => true,
		],
	],
	'lessjs-2.5.3/include-path' => [
		'lessDir' => "$fixtureDir/lessjs-2.5.3/less/include-path",
		'cssDir' => "$fixtureDir/lessjs-2.5.3/css/include-path",
		'overrideDir' => "$fixtureDir/lessjs-2.5.3/override/include-path",
		'options' => [
			'import_dirs' => [
				"$fixtureDir/lessjs-2.5.3/data" => '',
				"$fixtureDir/lessjs-2.5.3/less/import" => '',
			],
		],
	],
	'lessjs-3.13.1' => [
		'lessDir' => "$fixtureDir/lessjs-3.13.1/less/_main",
		'cssDir' => "$fixtureDir/lessjs-3.13.1/css/_main",
		// 'overrideDir' => "$fixtureDir/lessjs-3.13.1/override",
	],
	'lessjs-3.13.1/compression' => [
		'lessDir' => "$fixtureDir/lessjs-2.5.3/less/compression",
		'cssDir' => "$fixtureDir/lessjs-2.5.3/css/compression",
		// 'overrideDir' => "$fixtureDir/lessjs-3.13.1/override/compression",
		'options' => [
			'compress' => true,
		],
	],
	'lessjs-3.13.1/strict-units' => [
		'lessDir' => "$fixtureDir/lessjs-3.13.1/less/strict-units",
		'cssDir' => "$fixtureDir/lessjs-3.13.1/css/strict-units",
		'options' => [
			'strictUnits' => true,
		],
	],
	'lessjs-3.13.1/include-path' => [
		'lessDir' => "$fixtureDir/lessjs-3.13.1/less/include-path",
		'cssDir' => "$fixtureDir/lessjs-3.13.1/css/include-path",
		// 'overrideDir' => "$fixtureDir/lessjs-3.13.1/override/include-path",
		'options' => [
			'import_dirs' => [
				"$fixtureDir/lessjs-3.13.1/data" => '',
				"$fixtureDir/lessjs-3.13.1/less/import" => '',
			],
		],
	],
];
