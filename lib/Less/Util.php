<?php

/**
 * Utility class
 *
 * @package Less
 * @subpackage Util
 */
class Less_Util {


	/**
	 * Converts all line endings to Unix format
	 *
	 * @param string $string The string
	 * @return string The normalized string
	 */
	public static function normalizeLineFeeds($string){
		return preg_replace("/\r\n/", "\n", $string);
	}

	/**
	 * Removes potential UTF Byte Order Mark
	 *
	 * @param string $string The string to fix
	 * @return string Fixed string
	 */
	public static function removeUtf8ByteOrderMark($string){
		return preg_replace('/\G\xEF\xBB\xBF/', '', $string);
	}

	/**
	 * Php version of javascript's `encodeURIComponent` function
	 *
	 * @param string $string The string to encode
	 * @return string The encoded string
	 */
	public static function encodeURIComponent($string){
		$revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
		return strtr(rawurlencode($string), $revert);
	}

	/**
	 * Returns the line number from the $string for a character at specified $index
	 *
	 * @param string $string
	 * @param integer $index
	 * @return integer
	 */
	public static function getLineNumber($string, $index){
		// FIXME: use mb_substr?
		// we have a part from the beginning to the current index
		$part = substr($string, 0, strlen($string) - strlen(substr($string, $index)));
		// lets count the linebreaks in the part
		$line = substr_count($part, "\n") + 1;
		return $line;
	}

	/**
	 * Generates unique cache key for given $filename
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function generateCacheKey($filename){
		return md5('key of the bottomless pit' . $filename);
	}

	/**
	 * Is the path absolute?
	 *
	 * @param string $path
	 * @return boolean
	 */
	public static function isPathAbsolute($path){
		if(empty($path)){
			return false;
		}

		if($path[0] == '/' || $path[0] == '\\' ||
			(strlen($path) > 3 && ctype_alpha($path[0]) &&
			$path[1] == ':' && ($path[2] == '\\' || $path[2] == '/'))){
			return true;
		}

		return false;
	}

	/**
	 * Is the path relative?
	 *
	 * @param string $path The path
	 * @return boolean
	 */
	public static function isPathRelative($path){
		return !preg_match('/^(?:[a-z-]+:|\/)/', $path);
	}

	/**
	 * Normalizes the path
	 *
	 * @param string $path The path or url
	 * @return string The normalized path
	 */
	public static function normalizePath($path){
		// leave http(s) paths:
		if(strpos($path, 'http://') === 0
				|| strpos($path, 'https://') === 0){
			return $path;
		}

		// WINDOWS path separator is \\
		$segments = array_reverse(explode('/', str_replace('\\', '/', $path)));
		$path = array();
		$path_len = 0;
		while($segments){
			$segment = array_pop($segments);
			switch($segment){
				case '.':
					break;
				case '..':
					if(!$path_len || ( $path[$path_len - 1] === '..')){
						$path[] = $segment;
						$path_len++;
					}
					else
					{
						array_pop($path);
						$path_len--;
					}
					break;

				default:
					$path[] = $segment;
					$path_len++;
					break;
			}
		}

		return implode('/', $path);
	}

	/**
	 * Normalizes the string to be used
	 *
	 * @param string $string
	 * @return string
	 */
	public static function normalizeString($string){
		return self::removeUtf8ByteOrderMark(self::normalizeLineFeeds($string));
	}

	/**
	 * Returns an camelCased version of the `under_scored` string.
	 *
	 * @param string $underscored String to camelize
	 * @return string Underscored string
	 */
	public static function camelize($underscored){
		$replacePairs = array(
			'#/(.?)#e' => "'::'.strtoupper('\\1')",
			'/(^|_)(.)/e' => "strtoupper('\\2')"
		);
		// FIXME: php 5.5 is /e modifier deprecated! do a workaround
		$camelized = @preg_replace(array_keys($replacePairs), array_values($replacePairs), $underscored);
		$camelized[0] = strtolower($camelized[0]);
		return $camelized;
	}

}