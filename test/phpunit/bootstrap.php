<?php

class LessTestCase extends PHPUnit\Framework\TestCase {
	/** @var string */
	protected static $fixturesDir;
	/** @var string */
	protected static $cacheDir;

	public static function setUpBeforeClass(): void {
		$rootDir = dirname( dirname( __DIR__ ) );
		require_once $rootDir . '/lib/Less/Autoloader.php';
		Less_Autoloader::register();

		$rootDir = dirname( dirname( __DIR__ ) );
		self::$fixturesDir = $rootDir . '/test/Fixtures';

		self::$cacheDir = $rootDir . '/test/phpunit/_cache/';
		self::checkCacheDirectory();
		// Cleaning the cache dir is only needed once per class
		self::cleanCacheDirectory();
	}

	private static function checkCacheDirectory() {
		if ( !file_exists( self::$cacheDir ) && !mkdir( self::$cacheDir ) ) {
			self::fail( "Could not create cache dir at " . self::$cacheDir );
		}

		if ( !is_writable( self::$cacheDir ) ) {
			self::fail( "Cache dir not writable at " . self::$cacheDir );
		}
	}

	private static function cleanCacheDirectory() {
		// Clean directory of cache from previous test runs
		// on different code versions (incl e.g. any bugs they might contain).
		foreach ( scandir( self::$cacheDir ) as $entry ) {
			if ( $entry !== '.' && $entry !== '..' ) {
				unlink( self::$cacheDir . '/' . $entry );
			}
		}
	}
}
