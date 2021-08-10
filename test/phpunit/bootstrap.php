<?php

if ( !class_exists( PHPUnit_Framework_TestCase::class ) ) {
	class_alias( \PHPUnit\Framework\TestCase::class, PHPUnit_Framework_TestCase::class );
}

class phpunit_bootstrap extends PHPUnit_Framework_TestCase {
	public $fixtures_dir;
	public $cache_dir;

	public static function getFixtureDir() {
		$rootDir = dirname( dirname( __DIR__ ) );
		return $rootDir . '/test/Fixtures';
	}

	public function setUp() : void {
		$rootDir = dirname( dirname( __DIR__ ) );
		require_once $rootDir . '/lib/Less/Autoloader.php';
		Less_Autoloader::register();

		$this->fixtures_dir = self::getFixtureDir();
		$this->cache_dir = $rootDir . '/test/phpunit/_cache/';
		$this->checkCacheDirectory();
	}

	private function checkCacheDirectory() {
		if ( !file_exists( $this->cache_dir ) && !mkdir( $this->cache_dir ) ) {
			$this->fail( "Could not be create cache dir at " . $this->cache_dir );
		}

		if ( !is_writable( $this->cache_dir ) ) {
			$this->fail( "Cache dir not writable at " . $this->cache_dir );
		}
	}
}
