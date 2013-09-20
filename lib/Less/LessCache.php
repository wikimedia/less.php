<?php


class Less_Cache{

	public static $cache_dir = false;		// directory less.php can use for storing data
	public static $import_dirs = array();
	public static $error;

    const cache_version = '142b2';


	function Parse( $less_files, $parser_options = array() ){


		//check $cache_dir
		if( empty(self::$cache_dir) ){
			throw new Exception('cache_dir not set');
			return false;
		}

		self::$cache_dir = str_replace('\\','/',self::$cache_dir);
		self::$cache_dir = rtrim(self::$cache_dir,'/');

		if( !is_dir(self::$cache_dir) ){
			throw new Exception('cache_dir does not exist');
			return false;
		}


		// generate name for compiled css file
		$less_files = (array)$less_files;
		$less_files = array_filter($less_files);
		$hash = self::ArrayHash($less_files);
 		$list_file = self::$cache_dir.'/lessphp_'.$hash.'.list';


 		// check cached content
		$compiled_file = false;
		$less_cache = false;
 		if( file_exists($list_file) ){

			//get info about the list file
			$compiled_name = 'lessphp_'.$hash.'_'.self::GenEtag( filesize($list_file) ).'.css';
			$compiled_file = self::$cache_dir.'/'.$compiled_name;


			//check modified time of all included files
			if( file_exists($compiled_file) ){

				$list = explode("\n",file_get_contents($list_file));
				$list_updated = filemtime($list_file);

				foreach($list as $file ){
					if( !file_exists($file) || filemtime($file) > $list_updated ){
						$compiled_file = false;
						break;
					}
				}


				// return relative path if we don't need to regenerate
				if( $compiled_file ){

					//touch the files to extend the cache
					touch($list_file);
					touch($compiled_file);

					return $compiled_name;
				}
			}

		}


		$compiled = self::ParseFiles( $less_files );
		if( !$compiled ){
			return false;
		}


		//save the cache
		$cache = implode("\n",$less_files);
		file_put_contents( $list_file, $cache );


		//save the css
		$compiled_name = 'less_'.$hash.'_'.self::GenEtag( filesize($list_file) ).'.css';
		$compiled_file = '/data/_cache/'.$compiled_name;
		file_put_contents( $dataDir.$compiled_file, $compiled );

		return $compiled_file;

	}

	function ParseFiles( &$less_files ){

		//prepare the processor
		include_once('Less.php');
		$parser = new Less_Parser(); //array('compress'=>true)
		$parser->SetCacheDir( self::$cache_dir );
		$parser->SetImportDirs( self::$import_dirs );


		// combine files
 		try{
			foreach($less_files as $file_path => $uri_or_less ){

				//treat as less markup if there are newline characters
				if( strpos($uri_or_less,"\n") !== false ){
					$parser->Parse( $uri_or_less );
					continue;
				}

				$parser->ParseFile( $file_path, $uri_root );
			}

			$compiled = $parser->getCss();

		}catch(Exception $e){
			self::$error = $e;
			return false;
		}

		$less_files = $parser->allParsedFiles();

		return $compiled;
	}


	static function GenEtag(){
		$etag = '';
		$args = func_get_args();
		$args[] = self::cache_version;
		foreach($args as $arg){
			if( !ctype_digit($arg) ){
				$arg = crc32( $arg );
				$arg = sprintf("%u\n", $arg );
			}
			$etag .= base_convert( $arg, 10, 36);
		}
		return $etag;
	}


	/**
	 * Generate a checksum for the $array
	 *
	 */
	static function ArrayHash($array){
		return md5(json_encode($array) );
	}


}