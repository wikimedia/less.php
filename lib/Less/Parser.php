<?php


require 'LessCache.php';

class Less_Parser extends Less_Cache{


	private $input;		// LeSS input string
	private $input_len;	// input string length
	private $pos;		// current index in `input`
	private $memo;		// temporarily holds `i`, when backtracking


	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var string
	 */
	private $filename;


	/**
	 *
	 */
	const version = '1.5.1b1';
	const less_version = '1.5.1';

	/**
	 * @var Less_Environment
	 */
	private $env;
	private $rules = array();

	private static $imports = array();

	public static $has_extends = false;


	/**
	 * @param Environment|null $env
	 */
	public function __construct( $env = null ){


		// Top parser on an import tree must be sure there is one "env"
		// which will then be passed around by reference.
		if( $env instanceof Less_Environment ){
			$this->env = $env;
		}else{
			$this->env = new Less_Environment( $env );
			self::$imports = array();
			self::$import_dirs = array();
			self::$has_extends = false;
		}

		$this->pos = 0;
	}



	/**
	 * Get the current css buffer
	 *
	 * @return string
	 */
	public function getCss(){

		$precision = ini_get('precision');
		@ini_set('precision',16);

 		$root = new Less_Tree_Ruleset(array(), $this->rules );
		$root->root = true;
		$root->firstRoot = true;


		//$importVisitor = new Less_importVisitor();
		//$importVisitor->run($root);

		//obj($root);

		$evaldRoot = $root->compile($this->env);

		$joinSelector = new Less_joinSelectorVisitor();
		$joinSelector->run($evaldRoot);


		if( self::$has_extends ){
			$extendsVisitor = new Less_processExtendsVisitor();
			$extendsVisitor->run($evaldRoot);
		}

		$toCSSVisitor = new Less_toCSSVisitor( $this->env );
		$toCSSVisitor->run($evaldRoot);

		$css = $evaldRoot->toCSS($this->env);

		if( Less_Environment::$compress ){
			$css = preg_replace('/(^(\s)+)|((\s)+$)/', '', $css);
		}

		@ini_set('precision',$precision);

		return $css;
	}


	/**
	 * Parse a Less string into css
	 *
	 * @param string $str The string to convert
	 * @param bool $returnRoot Indicates whether the return value should be a css string a root node
	 * @return Less_Tree_Ruleset|Less_Parser
	 */
	public function parse($str){
		$this->input = $str;
		$this->_parse();
	}


	/**
	 * Parse a Less string from a given file
	 *
	 * @throws Less_ParserException
	 * @param $filename The file to parse
	 * @param $uri_root The url of the file
	 * @param bool $returnRoot Indicates whether the return value should be a css string a root node
	 * @return Less_Tree_Ruleset|Less_Parser
	 */
	public function parseFile( $filename, $uri_root = '', $returnRoot = false){

		if( !file_exists($filename) ){
			throw new Less_ParserException(sprintf('File `%s` not found.', $filename));
		}

		$previousFileInfo = $this->env->currentFileInfo;
		$this->SetFileInfo($filename, $uri_root);

		$previousImportDirs = self::$import_dirs;
		self::AddParsedFile($filename);

		$return = null;
		if( $returnRoot ){
			$rules = $this->GetRules( $filename );
			$return = new Less_Tree_Ruleset(array(), $rules );
		}else{
			$this->_parse( $filename );
		}

		if( $previousFileInfo ){
			$this->env->currentFileInfo = $previousFileInfo;
		}
		self::$import_dirs = $previousImportDirs;

		return $return;
	}


	public function SetFileInfo( $filename, $uri_root = ''){

		$this->path = pathinfo($filename, PATHINFO_DIRNAME);
		$this->filename = Less_Environment::normalizePath($filename);

		$dirname = preg_replace('/[^\/\\\\]*$/','',$this->filename);

		$currentFileInfo = array();
		$currentFileInfo['currentDirectory'] = $dirname;
		$currentFileInfo['filename'] = $filename;
		$currentFileInfo['rootpath'] = $dirname;
		$currentFileInfo['entryPath'] = $dirname;

		if( empty($uri_root) ){
			$currentFileInfo['uri_root'] = $uri_root;
		}else{
			$currentFileInfo['uri_root'] = rtrim($uri_root,'/').'/';
		}


		//inherit reference
		if( isset($this->env->currentFileInfo['reference']) && $this->env->currentFileInfo['reference'] ){
			$currentFileInfo['reference'] = true;
		}

		$this->env->currentFileInfo = $currentFileInfo;

		self::$import_dirs = array_merge( array( $dirname => $currentFileInfo['uri_root'] ), self::$import_dirs );
	}

	public function SetCacheDir( $dir ){

		if( !is_dir($dir) ){
			throw new Less_ParserException('Less.php cache directory doesn\'t exist: '.$dir);
		}elseif( !is_writable($dir) ){
			throw new Less_ParserException('Less.php cache directory isn\'t writable: '.$dir);
		}else{
			$dir = str_replace('\\','/',$dir);
			self::$cache_dir = rtrim($dir,'/').'/';
			return true;
		}
	}

	public function SetImportDirs( $dirs ){
		foreach($dirs as $path => $uri_root){

			$path = str_replace('\\','/',$path);
			$uri_root = str_replace('\\','/',$uri_root);

			if( !empty($path) ){
				$path = rtrim($path,'/').'/';
			}
			if( !empty($uri_root) ){
				$uri_root = rtrim($uri_root,'/').'/';
			}
			self::$import_dirs[$path] = $uri_root;
		}
	}

	private function _parse( $file_path = false ){
		$this->rules = array_merge($this->rules, $this->GetRules( $file_path ));
	}


	/**
	 * Return the results of parsePrimary for $file_path
	 * Use cache and save cached results if possible
	 *
	 */
	var $cache_method = 'serialize';
	private function GetRules( $file_path ){

		$cache_file = false;
		if( $file_path ){
			$cache_file = $this->CacheFile( $file_path );

			if( $cache_file && file_exists($cache_file) ){
				switch($this->cache_method){

					// Using serialize
					// Faster but uses more memory
					case 'serialize':
						$cache = unserialize(file_get_contents($cache_file));
						if( $cache ){
							touch($cache_file);
							return $cache;
						}
					break;


					// Using generated php code
					case 'php':
					return include($cache_file);
				}
			}

			$this->input = file_get_contents( $file_path );
		}

		$this->pos = 0;
		$this->input = preg_replace('/\r\n/', "\n", $this->input);

		// Remove potential UTF Byte Order Mark
		$this->input = preg_replace('/\\G\xEF\xBB\xBF/', '', $this->input);
		$this->input_len = strlen($this->input);

		$rules = $this->parsePrimary();


		// free up a little memory
		unset($this->input, $this->pos);


		//save the cache
		if( $cache_file ){

			switch($this->cache_method){
				case 'serialize':
					file_put_contents( $cache_file, serialize($rules) );
				break;
				case 'php':
					file_put_contents( $cache_file, '<?php return '.var_export($rules,true).'; ?>' );
				break;
				default:
					throw new Less_ParserException('Unknown caching option: "'.$this->cache_method.'"');
				break;
			}

			if( self::$clean_cache ){
				self::CleanCache();
			}

		}

		return $rules;
	}


	public static function ReleaseMemory(){
		if( function_exists('gc_collect_cycles') ){
			gc_collect_cycles();
		}
	}

	public function CacheFile( $file_path ){

		if( $file_path && self::$cache_dir ){

			$env = get_object_vars($this->env);
			unset($env['frames']);

			$parts = array();
			$parts[] = $file_path;
			$parts[] = filesize( $file_path );
			$parts[] = filemtime( $file_path );
			$parts[] = $env;
			$parts[] = self::cache_version;
			$parts[] = $this->cache_method;
			return self::$cache_dir.'lessphp_'.base_convert( sha1(json_encode($parts) ), 16, 36).'.lesscache';
		}
	}


	static function AddParsedFile($file){
		self::$imports[] = $file;
	}

	static function AllParsedFiles(){
		return self::$imports;
	}

	static function FileParsed($file){
		return in_array($file,self::$imports);
	}


	function save() {
		$this->memo = $this->pos;
	}

	private function restore() {
		$this->pos = $this->memo;
	}


	private function isWhitespace($offset = 0) {
		return ctype_space($this->input[ $this->pos + $offset]);
	}

	/**
	 * Parse from a token, regexp or string, and move forward if match
	 *
	 * @param string $tok
	 * @return null|bool|object
	 */
	private function match($toks){

		// The match is confirmed, add the match length to `this::pos`,
		// and consume any extra white-space characters (' ' || '\n')
		// which come after that. The reason for this is that LeSS's
		// grammar is mostly white-space insensitive.
		//

		foreach($toks as $tok){

			if( $tok[0] === '/' ){
				$match = $this->MatchReg($tok);

			}elseif( strlen($tok) == 1 ){
				$match = $this->MatchChar($tok);

			}else{
				// Non-terminal, match using a function call
				$match = $this->$tok();

			}

			if( $match ){
				return $match;
			}
		}
	}

	private function MatchFuncs($toks){

		foreach($toks as $tok){
			$match = $this->$tok();
			if( $match ){
				return $match;
			}
		}

	}

	// Match a single character in the input,
	private function MatchChar($tok){
		if( ($this->pos < $this->input_len) && ($this->input[$this->pos] === $tok) ){
			$this->skipWhitespace(1);
			return $tok;
		}
	}

	// Match a regexp from the current start point
	private function MatchReg($tok){

		if( preg_match($tok, $this->input, $match, 0, $this->pos) ){
			$this->skipWhitespace(strlen($match[0]));
			return count($match) === 1 ? $match[0] : $match;
		}
	}

	//match a string
	private function MatchString($string){
		$len = strlen($string);

		if( ($this->input_len >= ($this->pos+$len)) && substr_compare( $this->input, $string, $this->pos, $len, true ) === 0 ){
			$this->skipWhitespace($len);
			return $string;
		}

	}


	/**
	 * Same as match(), but don't change the state of the parser,
	 * just return the match.
	 *
	 * @param $tok
	 * @param int $offset
	 * @return bool
	 */
	public function PeekReg($tok){
		return preg_match($tok, $this->input, $match, 0, $this->pos);
	}

	public function PeekChar($tok){
		return ($this->input[$this->pos] === $tok );
		//return ($this->pos < $this->input_len) && ($this->input[$this->pos] === $tok );
	}


	public function skipWhitespace($length){

		$this->pos += $length;

		for(; $this->pos < $this->input_len; $this->pos++ ){
			$c = $this->input[$this->pos];

			if( ($c !== "\n") && ($c !== "\r") && ($c !== "\t") && ($c !== ' ') ){
				break;
			}
		}
	}


	public function expect($tok, $msg = NULL) {
		$result = $this->match( array($tok) );
		if (!$result) {
			throw new Less_ParserException(
				$msg === NULL
					? "Expected '" . $tok . "' got '" . $this->input[$this->pos] . "'"
					: $msg
			);
		} else {
			return $result;
		}
	}

	//
	// Here in, the parsing rules/functions
	//
	// The basic structure of the syntax tree generated is as follows:
	//
	//   Ruleset ->  Rule -> Value -> Expression -> Entity
	//
	// Here's some LESS code:
	//
	//	.class {
	//	  color: #fff;
	//	  border: 1px solid #000;
	//	  width: @w + 4px;
	//	  > .child {...}
	//	}
	//
	// And here's what the parse tree might look like:
	//
	//	 Ruleset (Selector '.class', [
	//		 Rule ("color",  Value ([Expression [Color #fff]]))
	//		 Rule ("border", Value ([Expression [Dimension 1px][Keyword "solid"][Color #000]]))
	//		 Rule ("width",  Value ([Expression [Operation "+" [Variable "@w"][Dimension 4px]]]))
	//		 Ruleset (Selector [Element '>', '.child'], [...])
	//	 ])
	//
	//  In general, most rules will try to parse a token with the `$()` function, and if the return
	//  value is truly, will return a new node, of the relevant type. Sometimes, we need to check
	//  first, before parsing, that's when we use `peek()`.
	//

	//
	// The `primary` rule is the *entry* and *exit* point of the parser.
	// The rules here can appear at any level of the parse tree.
	//
	// The recursive nature of the grammar is an interplay between the `block`
	// rule, which represents `{ ... }`, the `ruleset` rule, and this `primary` rule,
	// as represented by this simplified grammar:
	//
	//	 primary  →  (ruleset | rule)+
	//	 ruleset  →  selector+ block
	//	 block	→  '{' primary '}'
	//
	// Only at one point is the primary rule not called from the
	// block rule: at the root level.
	//
	private function parsePrimary(){
		$root = array();

		while( true ){

			if( $this->pos >= $this->input_len ){
				break;
			}

			$node = $this->MatchFuncs( array('parseExtendRule', 'parseMixinDefinition', 'parseRule', 'parseRuleset', 'parseMixinCall', 'parseComment', 'parseDirective'));


			if( is_array($node) ){
				$root = array_merge($root,$node);
			}elseif( $node ){
				$root[] = $node;
			}elseif( !$this->MatchReg('/\\G[\s\n;]+/') ){
				break;
			}

		}

		return $root;
	}



	// We create a Comment node for CSS comments `/* */`,
	// but keep the LeSS comments `//` silent, by just skipping
	// over them.
	private function parseComment(){

		if( $this->input[$this->pos] !== '/' ){
			return;
		}

		if( $this->input[$this->pos+1] === '/' ){
			return new Less_Tree_Comment($this->MatchReg('/\\G\/\/.*/'), true, $this->pos, $this->env->currentFileInfo);
		//}elseif( $comment = $this->MatchReg('/\\G\/\*(?:[^*]|\*+[^\/*])*\*+\/\n?/')) {
		}elseif( $comment = $this->MatchReg('/\\G\/\*(?s).*?\*+\/\n?/') ) { //not the same as less.js to prevent fatal errors
			return new Less_Tree_Comment($comment, false, $this->pos, $this->env->currentFileInfo);
		}
	}

	private function parseComments(){
		$comments = array();

		while($comment = $this->parseComment() ){
			$comments[] = $comment;
		}

		return $comments;
	}



	//
	// A string, which supports escaping " and '
	//
	//	 "milky way" 'he\'s the one!'
	//
	private function parseEntitiesQuoted() {
		$j = 0;
		$e = false;
		$index = $this->pos;

		if ($this->PeekChar('~')) {
			$j++;
			$e = true; // Escaped strings
		}

		$char = $this->input[$this->pos+$j];
		if( $char != '"' && $char !== "'" ){
			return;
		}

		if ($e) {
			$this->MatchChar('~');
		}

		if ($str = $this->MatchReg('/\\G"((?:[^"\\\\\r\n]|\\\\.)*)"|\'((?:[^\'\\\\\r\n]|\\\\.)*)\'/')) {
			$result = $str[0][0] == '"' ? $str[1] : $str[2];
			return new Less_Tree_Quoted($str[0], $result, $e, $index, $this->env->currentFileInfo );
		}
		return;
	}

	//
	// A catch-all word, such as:
	//
	//	 black border-collapse
	//
	private function parseEntitiesKeyword(){

		if( $k = $this->MatchReg('/\\G[_A-Za-z-][_A-Za-z0-9-]*/') ){
			$color = Less_Tree_Color::fromKeyword($k);
			if( $color ){
				return $color;
			}
			return new Less_Tree_Keyword($k);
		}
	}

	//
	// A function call
	//
	//	 rgb(255, 0, 255)
	//
	// We also try to catch IE's `alpha()`, but let the `alpha` parser
	// deal with the details.
	//
	// The arguments are parsed with the `entities.arguments` parser.
	//
	private function parseEntitiesCall(){
		$index = $this->pos;

		if( !preg_match('/\\G([\w-]+|%|progid:[\w\.]+)\(/', $this->input, $name,0,$this->pos) ){
			return;
		}
		$name = $name[1];
		$nameLC = strtolower($name);

		if ($nameLC === 'url') {
			return null;
		} else {
			$this->pos += strlen($name);
		}

		if( $nameLC === 'alpha' ){
			$alpha_ret = $this->parseAlpha();
			if( $alpha_ret ){
				return $alpha_ret;
			}
		}

		$this->MatchChar('('); // Parse the '(' and consume whitespace.

		$args = $this->parseEntitiesArguments();

		if( !$this->MatchChar(')') ){
			return;
		}

		if ($name) {
			return new Less_Tree_Call($name, $args, $index, $this->env->currentFileInfo );
		}
	}

	/**
	 * Parse a list of arguments
	 *
	 * @return array
	 */
	private function parseEntitiesArguments(){
		$args = array();
		while( $arg = $this->MatchFuncs( array('parseEntitiesAssignment','parseExpression') ) ){
			$args[] = $arg;
			if (! $this->MatchChar(',')) {
				break;
			}
		}
		return $args;
	}

	private function parseEntitiesLiteral(){
		return $this->MatchFuncs( array('parseEntitiesDimension','parseEntitiesColor','parseEntitiesQuoted','parseUnicodeDescriptor') );
	}

	// Assignments are argument entities for calls.
	// They are present in ie filter properties as shown below.
	//
	//	 filter: progid:DXImageTransform.Microsoft.Alpha( *opacity=50* )
	//
	private function parseEntitiesAssignment() {
		if (($key = $this->MatchReg('/\\G\w+(?=\s?=)/')) && $this->MatchChar('=') && ($value = $this->parseEntity())) {
			return new Less_Tree_Assignment($key, $value);
		}
	}

	//
	// Parse url() tokens
	//
	// We use a specific rule for urls, because they don't really behave like
	// standard function calls. The difference is that the argument doesn't have
	// to be enclosed within a string, so it can't be parsed as an Expression.
	//
	private function parseEntitiesUrl(){


		if( !$this->MatchString('url(') ){
			return;
		}

		$value = $this->match( array('parseEntitiesQuoted','parseEntitiesVariable','/\\G(?:(?:\\\\[\(\)\'"])|[^\(\)\'"])+/') );
		if( !$value ){
			$value = '';
		}


		$this->expect(')');


		return new Less_Tree_Url((isset($value->value) || $value instanceof Less_Tree_Variable)
							? $value : new Less_Tree_Anonymous($value), $this->env->currentFileInfo );
	}


	//
	// A Variable entity, such as `@fink`, in
	//
	//	 width: @fink + 2px
	//
	// We use a different parser for variable definitions,
	// see `parsers.variable`.
	//
	private function parseEntitiesVariable(){
		$index = $this->pos;
		if ($this->PeekChar('@') && ($name = $this->MatchReg('/\\G@@?[\w-]+/'))) {
			return new Less_Tree_Variable($name, $index, $this->env->currentFileInfo);
		}
	}


	// A variable entity useing the protective {} e.g. @{var}
	private function parseEntitiesVariableCurly() {
		$index = $this->pos;

		if( $this->input_len > ($this->pos+1) && $this->input[$this->pos] === '@' && ($curly = $this->MatchReg('/\\G@\{([\w-]+)\}/')) ){
			return new Less_Tree_Variable('@'.$curly[1], $index, $this->env->currentFileInfo);
		}
	}

	//
	// A Hexadecimal color
	//
	//	 #4F3C2F
	//
	// `rgb` and `hsl` colors are parsed through the `entities.call` parser.
	//
	private function parseEntitiesColor()
	{
		if ($this->PeekChar('#') && ($rgb = $this->MatchReg('/\\G#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})/'))) {
			return new Less_Tree_Color($rgb[1]);
		}
	}

	//
	// A Dimension, that is, a number and a unit
	//
	//	 0.5em 95%
	//
	private function parseEntitiesDimension(){

		$c = @ord($this->input[$this->pos]);

		//Is the first char of the dimension 0-9, '.', '+' or '-'
		if (($c > 57 || $c < 43) || $c === 47 || $c == 44){
			return;
		}

		if ($value = $this->MatchReg('/\\G([+-]?\d*\.?\d+)(%|[a-z]+)?/')) {
			return new Less_Tree_Dimension($value[1], isset($value[2]) ? $value[2] : null);
		}
	}


	//
	// A unicode descriptor, as is used in unicode-range
	//
	// U+0?? or U+00A1-00A9
	//
	function parseUnicodeDescriptor() {

		if ($ud = $this->MatchReg('/\\G(U\+[0-9a-fA-F?]+)(\-[0-9a-fA-F?]+)?/')) {
			return new Less_Tree_UnicodeDescriptor($ud[0]);
		}
	}


	//
	// JavaScript code to be evaluated
	//
	//	 `window.location.href`
	//
	private function parseEntitiesJavascript(){
		$e = false;
		$j = $this->pos;
		if( $this->input[$j] === '~' ){
			$j++;
			$e = true;
		}
		if( $this->input[$j] !== '`' ){
			return;
		}
		if( $e ){
			$this->MatchChar('~');
		}
		if ($str = $this->MatchReg('/\\G`([^`]*)`/')) {
			return new Less_Tree_Javascript($str[1], $this->pos, $e);
		}
	}


	//
	// The variable part of a variable definition. Used in the `rule` parser
	//
	//	 @fink:
	//
	private function parseVariable(){
		if ($this->PeekChar('@') && ($name = $this->MatchReg('/\\G(@[\w-]+)\s*:/'))) {
			return $name[1];
		}
	}

	//
	// extend syntax - used to extend selectors
	//
	function parseExtend($isRule = false){

		$index = $this->pos;
		$extendList = array();


		//if( !$this->MatchReg( $isRule ? '/\\G&:extend\(/' : '/\\G:extend\(/' ) ){ return; }
		if( !$this->MatchString( $isRule ? '&:extend(' : ':extend(' ) ){ return; }

		do{
			$option = null;
			$elements = array();
			while( true ){
				$option = $this->MatchReg('/\\G(all)(?=\s*(\)|,))/');
				if( $option ){ break; }
				$e = $this->parseElement();
				if( !$e ){ break; }
				$elements[] = $e;
			}

			if( $option ){
				$option = $option[1];
			}

			$extendList[] = new Less_Tree_Extend( new Less_Tree_Selector($elements), $option, $index );

		}while( $this->MatchChar(",") );

		$this->expect('/\\G\)/');

		if( $isRule ){
			$this->expect('/\\G;/');
		}

		if( $extendList ){
			self::$has_extends = true;
		}

		return $extendList;
	}

	function parseExtendRule(){
		return $this->parseExtend(true);
	}


	//
	// A Mixin call, with an optional argument list
	//
	//	 #mixins > .square(#fff);
	//	 .rounded(4px, black);
	//	 .button;
	//
	// The `while` loop is there because mixins can be
	// namespaced, but we only support the child and descendant
	// selector for now.
	//
	private function parseMixinCall(){
		$elements = array();
		$index = $this->pos;
		$important = false;
		$args = null;
		$c = null;

		$char = $this->input[$this->pos];
		if( $char !== '.' && $char !== '#' ){
			return;
		}

		$this->save(); // stop us absorbing part of an invalid selector

		while( $e = $this->MatchReg('/\\G[#.](?:[\w-]|\\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+/') ){
			$elements[] = new Less_Tree_Element($c, $e, $this->pos, $this->env->currentFileInfo);
			$c = $this->MatchChar('>');
		}

		if( $this->MatchChar('(') ){
			$returned = $this->parseMixinArgs(true);
			$args = $returned['args'];
			$this->expect(')');
		}

		if( !$args ){
			$args = array();
		}

		if( $this->parseImportant() ){
			$important = true;
		}

		if( $elements && ($this->MatchChar(';') || $this->PeekChar('}')) ){
			return new Less_Tree_MixinCall($elements, $args, $index, $this->env->currentFileInfo, $important);
		}

		$this->restore();
	}


	private function parseMixinArgs( $isCall ){
		$expressions = array();
		$argsSemiColon = array();
		$isSemiColonSeperated = null;
		$argsComma = array();
		$expressionContainsNamed = null;
		$name = null;
		$nameLoop = null;
		$returner = array('args'=>null, 'variadic'=> false);

		while( true ){
			if( $isCall ){
				$arg = $this->parseExpression();
			} else {
				$this->parseComments();
				if( $this->input[ $this->pos ] === '.' && $this->MatchReg('/\\G\.{3}/') ){
					$returner['variadic'] = true;
					if( $this->MatchChar(";") && !$isSemiColonSeperated ){
						$isSemiColonSeperated = true;
					}

					if( $isSemiColonSeperated ){
						$argsSemiColon[] = array('variadic'=>true);
					}else{
						$argsComma[] = array('variadic'=>true);
					}
					break;
				}
				$arg = $this->MatchFuncs( array('parseEntitiesVariable','parseEntitiesLiteral','parseEntitiesKeyword') );
			}


			if( !$arg ){
				break;
			}


			$nameLoop = null;
			if( $arg instanceof Less_Tree_Expression ){
				$arg->throwAwayComments();
			}
			$value = $arg;
			$val = null;

			if( $isCall ){
				// Variable
				if( count($arg->value) == 1 ){
					$val = $arg->value[0];
				}
			} else {
				$val = $arg;
			}


			if( $val && $val instanceof Less_Tree_Variable ){

				if( $this->MatchChar(':') ){
					if( $expressions ){
						if( $isSemiColonSeperated ){
							throw new Less_ParserException('Cannot mix ; and , as delimiter types');
						}
						$expressionContainsNamed = true;
					}
					$value = $this->expect('parseExpression');
					$nameLoop = ($name = $val->name);
				}elseif( !$isCall && $this->MatchReg('/\\G\.{3}/') ){
					$returner['variadic'] = true;
					if( $this->MatchChar(";") && !$isSemiColonSeperated ){
						$isSemiColonSeperated = true;
					}
					if( $isSemiColonSeperated ){
						$argsSemiColon[] = array('name'=> $arg->name, 'variadic' => true);
					}else{
						$argsComma[] = array('name'=> $arg->name, 'variadic' => true);
					}
					break;
				}elseif( !$isCall ){
					$name = $nameLoop = $val->name;
					$value = null;
				}
			}

			if( $value ){
				$expressions[] = $value;
			}

			$argsComma[] = array('name'=>$nameLoop, 'value'=>$value );

			if( $this->MatchChar(',') ){
				continue;
			}

			if( $this->MatchChar(';') || $isSemiColonSeperated ){

				if( $expressionContainsNamed ){
					throw new Less_ParserException('Cannot mix ; and , as delimiter types');
				}

				$isSemiColonSeperated = true;

				if( count($expressions) > 1 ){
					$value = new Less_Tree_Value($expressions);
				}
				$argsSemiColon[] = array('name'=>$name, 'value'=>$value );

				$name = null;
				$expressions = array();
				$expressionContainsNamed = false;
			}
		}

		$returner['args'] = ($isSemiColonSeperated ? $argsSemiColon : $argsComma);
		return $returner;
	}


	//
	// A Mixin definition, with a list of parameters
	//
	//	 .rounded (@radius: 2px, @color) {
	//		...
	//	 }
	//
	// Until we have a finer grained state-machine, we have to
	// do a look-ahead, to make sure we don't have a mixin call.
	// See the `rule` function for more information.
	//
	// We start by matching `.rounded (`, and then proceed on to
	// the argument list, which has optional default values.
	// We store the parameters in `params`, with a `value` key,
	// if there is a value, such as in the case of `@radius`.
	//
	// Once we've got our params list, and a closing `)`, we parse
	// the `{...}` block.
	//
	private function parseMixinDefinition(){
		$params = array();
		$variadic = false;
		$cond = null;

		$char = $this->input[$this->pos];
		if( ($char !== '.' && $char !== '#') || ($char === '{' && $this->Peek('/\\G[^{]*\}/')) ){
			return;
		}

		$this->save();

		if ($match = $this->MatchReg('/\\G([#.](?:[\w-]|\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+)\s*\(/')) {
			$name = $match[1];

			$argInfo = $this->parseMixinArgs( false );
			$params = $argInfo['args'];
			$variadic = $argInfo['variadic'];


			// .mixincall("@{a}");
			// looks a bit like a mixin definition.. so we have to be nice and restore
			if( !$this->MatchChar(')') ){
				//furthest = i;
				$this->restore();
			}

			$this->parseComments();

			if( $this->MatchString('when') ){ // Guard
			//if ($this->MatchReg('/\\Gwhen/')) { // Guard
				$cond = $this->expect('parseConditions', 'Expected conditions');
			}

			$ruleset = $this->parseBlock();

			if( is_array($ruleset) ){
				return new Less_Tree_MixinDefinition($name, $params, $ruleset, $cond, $variadic);
			} else {
				$this->restore();
			}
		}
	}

	//
	// Entities are the smallest recognized token,
	// and can be found inside a rule's value.
	//
	private function parseEntity(){

		return $this->MatchFuncs( array('parseEntitiesLiteral','parseEntitiesVariable','parseEntitiesUrl','parseEntitiesCall','parseEntitiesKeyword','parseEntitiesJavascript','parseComment') );
	}

	//
	// A Rule terminator. Note that we use `peek()` to check for '}',
	// because the `block` rule will be expecting it, but we still need to make sure
	// it's there, if ';' was ommitted.
	//
	private function parseEnd()
	{
		return ($end = $this->MatchChar(';') ) ? $end : $this->PeekChar('}');
	}

	//
	// IE's alpha function
	//
	//	 alpha(opacity=88)
	//
	private function parseAlpha(){

		if( !$this->MatchString('(opacity=') ){
		//if ( ! $this->MatchReg('/\\G\(opacity=/i')) {
			return;
		}

		$value = $this->MatchReg('/\\G[0-9]+/');
		if ($value === null) {
			$value = $this->parseEntitiesVariable();
		}

		if ($value !== null) {
			$this->expect(')');
			return new Less_Tree_Alpha($value);
		}
	}


	//
	// A Selector Element
	//
	//	 div
	//	 + h1
	//	 #socks
	//	 input[type="text"]
	//
	// Elements are the building blocks for Selectors,
	// they are made out of a `Combinator` (see combinator rule),
	// and an element name, such as a tag a class, or `*`.
	//
	private function parseElement(){
		$c = $this->parseCombinator();

		$e = $this->match( array('/\\G(?:\d+\.\d+|\d+)%/', '/\\G(?:[.#]?|:*)(?:[\w-]|[^\x00-\x9f]|\\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+/',
			'*', '&', 'parseAttribute', '/\\G\([^()@]+\)/', '/\\G[\.#](?=@)/', 'parseEntitiesVariableCurly') );

		if( !$e ){
			if( $this->MatchChar('(') ){
				if( ($v = $this->parseSelector()) && $this->MatchChar(')') ){
					$e = new Less_Tree_Paren($v);
				}
			}
		}

		if ($e) {
			return new Less_Tree_Element($c, $e, $this->pos, $this->env->currentFileInfo);
		}
	}

	//
	// Combinators combine elements together, in a Selector.
	//
	// Because our parser isn't white-space sensitive, special care
	// has to be taken, when parsing the descendant combinator, ` `,
	// as it's an empty space. We have to check the previous character
	// in the input, to see if it's a ` ` character.
	//
	private function parseCombinator()
	{
		$c = isset($this->input[$this->pos]) ? $this->input[$this->pos] : '';
		if ($c === '>' || $c === '+' || $c === '~' || $c === '|') {

			$this->pos++;
			while( $this->isWhitespace() ){
				$this->pos++;
			}
			return new Less_Tree_Combinator($c);
		} elseif ($this->pos > 0 && (preg_match('/\s/', $this->input[$this->pos - 1]))) {
			return new Less_Tree_Combinator(' ');
		} else {
			return new Less_Tree_Combinator();
		}
	}

	//
	// A CSS selector (see selector below)
	// with less extensions e.g. the ability to extend and guard
	//
	private function parseLessSelector(){
		return $this->parseSelector(true);
	}

	//
	// A CSS Selector
	//
	//	 .class > div + h1
	//	 li a:hover
	//
	// Selectors are made out of one or more Elements, see above.
	//
	private function parseSelector( $isLess = false ){
		$elements = array();
		$extendList = array();
		$condition = null;
		$when = false;
		$extend = false;

		while( ($isLess && ($extend = $this->parseExtend())) || ($isLess && ($when = $this->MatchString('when') )) || ($e = $this->parseElement()) ){
			if( $when ){
				$condition = $this->expect('parseConditions', 'expected condition');
			}elseif( $condition ){
				//error("CSS guard can only be used at the end of selector");
			}elseif( $extend ){
				$extendList = array_merge($extendList,$extend);
			}else{
				//if( count($extendList) ){
					//error("Extend can only be used at the end of selector");
				//}
				$c = $this->input[ $this->pos ];
				$elements[] = $e;
				$e = null;
			}

			if( $c === '{' || $c === '}' || $c === ';' || $c === ',' || $c === ')') { break; }
		}

		if( $elements ){
			return new Less_Tree_Selector( $elements, $extendList, $condition, $this->pos, $this->env->currentFileInfo);
		}
		if( $extendList ) { throw new Less_ParserException('Extend must be used to extend a selector, it cannot be used on its own'); }
	}

	private function parseTag(){
		return ( $tag = $this->MatchReg('/\\G[A-Za-z][A-Za-z-]*[0-9]?/') ) ? $tag : $this->MatchChar('*');
	}

	private function parseAttribute(){

		$val = null;
		$op = null;

		if( !$this->MatchChar('[') ){
			return;
		}

		if( !($key = $this->parseEntitiesVariableCurly()) ){
			$key = $this->expect('/\\G(?:[_A-Za-z0-9-\*]*\|)?(?:[_A-Za-z0-9-]|\\\\.)+/');
		}

		if( ($op = $this->MatchReg('/\\G[|~*$^]?=/')) ){
			$val = $this->match( array('parseEntitiesQuoted','/\\G[0-9]+%/','/\\G[\w-]+/','parseEntitiesVariableCurly') );
		}

		$this->expect(']');

		return new Less_Tree_Attribute($key, $op, $val);
	}

	//
	// The `block` rule is used by `ruleset` and `mixin.definition`.
	// It's a wrapper around the `primary` rule, with added `{}`.
	//
	private function parseBlock(){
		if ($this->MatchChar('{') && (is_array($content = $this->parsePrimary())) && $this->MatchChar('}')) {
			return $content;
		}
	}

	//
	// div, .class, body > p {...}
	//
	private function parseRuleset(){
		$selectors = array();
		$start = $this->pos;

		while( $s = $this->parseLessSelector() ){
			$selectors[] = $s;
			$this->parseComments();
			if( !$this->MatchChar(',') ){
				break;
			}
			if( $s->condition ){
				//error("Guards are only currently allowed on a single selector.");
			}
			$this->parseComments();
		}

		if( $selectors && (is_array($rules = $this->parseBlock())) ){
			return new Less_Tree_Ruleset($selectors, $rules, $this->env->strictImports);
		} else {
			// Backtrack
			$this->pos = $start;
		}
	}


	private function parseRule( $tryAnonymous = null ){
		$merge = false;
		$start = $this->pos;
		$this->save();

		if( isset($this->input[$this->pos]) ){
			$c = $this->input[$this->pos];

			if( $c === '.' || $c === '#' || $c === '&' ){
				return;
			}
		}

		if( $name = $this->MatchFuncs( array('parseVariable','parseRuleProperty')) ){


			// prefer to try to parse first if its a variable or we are compressing
			// but always fallback on the other one
			if( !$tryAnonymous && (Less_Environment::$compress || ( $name[0] === '@')) ){
				$value = $this->MatchFuncs( array('parseValue','parseAnonymousValue'));
			}else{
				$value = $this->MatchFuncs( array('parseAnonymousValue','parseValue'));
			}

			$important = $this->parseImportant();

			if( substr($name,-1) === '+' ){
				$merge = true;
				$name = substr($name, 0, -1 );
			}

			if( $value && $this->parseEnd() ){
				return new Less_Tree_Rule($name, $value, $important, $merge, $start, $this->env->currentFileInfo);
			}else{
				$this->restore();
				if( $value && !$tryAnonymous ){
					return $this->parseRule(true);
				}
			}
		}
	}

	function parseAnonymousValue(){

		if( preg_match('/\\G([^@+\/\'"*`(;{}-]*);/',$this->input, $match, 0, $this->pos) ){
			$this->pos += strlen($match[0]) - 1;
			return new Less_Tree_Anonymous($match[1]);
		}
	}

	//
	// An @import directive
	//
	//	 @import "lib";
	//
	// Depending on our environment, importing is done differently:
	// In the browser, it's an XHR request, in Node, it would be a
	// file-system operation. The function used for importing is
	// stored in `import`, which we pass to the Import constructor.
	//
	private function parseImport(){
		$index = $this->pos;

		$this->save();

		$dir = $this->MatchString('@import');
		//$dir = $this->MatchReg('/\\G@import?\s+/');

		$options = array();
		if( $dir ){
			$options = $this->parseImportOptions();
			if( !$options ){
				$options = array();
			}
		}

		if( $dir && ($path = $this->MatchFuncs( array('parseEntitiesQuoted','parseEntitiesUrl'))) ){
			$features = $this->parseMediaFeatures();
			if( $this->MatchChar(';') ){
				if( $features ){
					$features = new Less_Tree_Value($features);
				}

				return new Less_Tree_Import($path, $features, $options, $this->pos, $this->env->currentFileInfo );
			}
		}

		$this->restore();
	}

	private function parseImportOptions(){

		$options = array();

		// list of options, surrounded by parens
		if( !$this->MatchChar('(') ){ return null; }
		do{
			if( $o = $this->parseImportOption() ){
				$optionName = $o;
				$value = true;
				switch( $optionName ){
					case "css":
						$optionName = "less";
						$value = false;
					break;
					case "once":
						$optionName = "multiple";
						$value = false;
					break;
				}
				$options[$optionName] = $value;
				if( !$this->MatchChar(',') ){ break; }
			}
		}while($o);
		$this->expect(')');
		return $options;
	}

	private function parseImportOption(){
		$opt = $this->MatchReg('/\\G(less|css|multiple|once|inline|reference)/');
		if( $opt ){
			return $opt[1];
		}
	}

	private function parseMediaFeature() {
		$nodes = array();

		do {

			if( $e = $this->MatchFuncs(array('parseEntitiesKeyword','parseEntitiesVariable')) ){
				$nodes[] = $e;
			} elseif ($this->MatchChar('(')) {
				$p = $this->parseProperty();
				$e = $this->parseValue();
				if ($this->MatchChar(')')) {
					if ($p && $e) {
						$nodes[] = new Less_Tree_Paren(new Less_Tree_Rule($p, $e, null, null, $this->pos, $this->env->currentFileInfo, true));
					} elseif ($e) {
						$nodes[] = new Less_Tree_Paren($e);
					} else {
						return null;
					}
				} else
					return null;
			}
		} while ($e);

		if ($nodes) {
			return new Less_Tree_Expression($nodes);
		}
	}

	private function parseMediaFeatures() {
		$features = array();

		do {
			if ($e = $this->parseMediaFeature()) {
				$features[] = $e;
				if (!$this->MatchChar(',')) break;
			} elseif ($e = $this->parseEntitiesVariable()) {
				$features[] = $e;
				if (!$this->MatchChar(',')) break;
			}
		} while ($e);

		return $features ? $features : null;
	}

	private function parseMedia() {
		if( $this->MatchString('@media') ){
		//if ($this->MatchReg('/\\G@media/')) {
			$features = $this->parseMediaFeatures();

			if ($rules = $this->parseBlock()) {
				return new Less_Tree_Media($rules, $features, $this->pos, $this->env->currentFileInfo);
			}
		}
	}

	//
	// A CSS Directive
	//
	//	 @charset "utf-8";
	//
	private function parseDirective(){
		$hasBlock = false;
		$hasIdentifier = false;
		$hasExpression = false;

		if (! $this->PeekChar('@')) {
			return;
		}

		$value = $this->MatchFuncs(array('parseImport','parseMedia'));
		if( $value ){
			return $value;
		}

		$this->save();

		$name = $this->MatchReg('/\\G@[a-z-]+/');

		if( !$name ) return;

		$nonVendorSpecificName = $name;
		$pos = strpos($name,'-', 2);
		if( $name[1] == '-' && $pos > 0 ){
			$nonVendorSpecificName = "@" . substr($name, $pos + 1);
		}

		switch($nonVendorSpecificName) {
			case "@font-face":
				$hasBlock = true;
				break;
			case "@viewport":
			case "@top-left":
			case "@top-left-corner":
			case "@top-center":
			case "@top-right":
			case "@top-right-corner":
			case "@bottom-left":
			case "@bottom-left-corner":
			case "@bottom-center":
			case "@bottom-right":
			case "@bottom-right-corner":
			case "@left-top":
			case "@left-middle":
			case "@left-bottom":
			case "@right-top":
			case "@right-middle":
			case "@right-bottom":
				$hasBlock = true;
				break;
			case "@host":
			case "@page":
			case "@document":
			case "@supports":
			case "@keyframes":
				$hasBlock = true;
				$hasIdentifier = true;
				break;
			case "@namespace":
				$hasExpression = true;
				break;
		}

		if( $hasIdentifier ){
			$identifier = $this->MatchReg('/\\G[^{]+/');
			if( $identifier ){
				$name .= " " .trim($identifier);
			}
		}


		if( $hasBlock ){

			if ($rules = $this->parseBlock()) {
				return new Less_Tree_Directive($name, $rules, $this->pos, $this->env->currentFileInfo);
			}
		}else{
			if( ($value = $hasExpression ? $this->parseExpression() : $this->parseEntity()) && $this->MatchChar(';') ){
				return new Less_Tree_Directive($name, $value, $this->pos, $this->env->currentFileInfo);
			}
		}

		$this->restore();
	}


	//
	// A Value is a comma-delimited list of Expressions
	//
	//	 font-family: Baskerville, Georgia, serif;
	//
	// In a Rule, a Value represents everything after the `:`,
	// and before the `;`.
	//
	private function parseValue(){
		$expressions = array();

		while ($e = $this->parseExpression()) {
			$expressions[] = $e;
			if (! $this->MatchChar(',')) {
				break;
			}
		}

		if( $expressions ){
			return new Less_Tree_Value($expressions);
		}
	}

	private function parseImportant (){
		if ($this->PeekChar('!')) {
			return $this->MatchReg('/\\G! *important/');
		}
	}

	private function parseSub (){

		if( $this->MatchChar('(') ){
			if( $a = $this->parseAddition() ){
				$e = new Less_Tree_Expression( array($a) );
				$this->expect(')');
				$e->parens = true;
				return $e;
			}
		}
	}

	function parseMultiplication(){
		$operation = false;
		$m = $this->parseOperand();
		if( $m ){
			$isSpaced = $this->isWhitespace( -1 );
			while( true ){

				if( $this->PeekReg('/\\G\/[*\/]/') ){
					break;
				}

				$op = $this->MatchChar('/');
				if( !$op ){
					$op = $this->MatchChar('*');
					if( !$op ){
						break;
					}
				}

				$a = $this->parseOperand();

				if(!$a) { break; }

				$m->parensInOp = true;
				$a->parensInOp = true;
				$operation = new Less_Tree_Operation( $op, array( $operation ? $operation : $m, $a ), $isSpaced );
				$isSpaced = $this->isWhitespace( -1 );
			}
			return ($operation ? $operation : $m);
		}
	}

	private function parseAddition (){
		$operation = false;
		if ($m = $this->parseMultiplication()) {
			$isSpaced = $this->isWhitespace( -1 );

			while( ($op = ($op = $this->MatchReg('/\\G[-+]\s+/')) ? $op : ( !$isSpaced ? ($this->match(array('+','-'))) : false )) && ($a = $this->parseMultiplication()) ){
				$m->parensInOp = true;
				$a->parensInOp = true;
				$operation = new Less_Tree_Operation($op, array($operation ? $operation : $m, $a), $isSpaced);
				$isSpaced = $this->isWhitespace( -1 );
			}
			return $operation ? $operation : $m;
		}
	}

	private function parseConditions() {
		$index = $this->pos;
		$condition = null;
		if( $a = $this->parseCondition() ){
			while( $this->PeekReg('/\\G,\s*(not\s*)?\(/') && $this->MatchChar(',') && ($b = $this->parseCondition()) ){
				$condition = new Less_Tree_Condition('or', $condition ? $condition : $a, $b, $index);
			}
			return $condition ? $condition : $a;
		}
	}

	private function parseCondition() {
		$index = $this->pos;
		$negate = false;


		if ($this->MatchString('not')) $negate = true;
		//if ($this->MatchReg('/\\Gnot/')) $negate = true;
		$this->expect('(');
		if ($a = ($this->MatchFuncs(array('parseAddition','parseEntitiesKeyword','parseEntitiesQuoted'))) ) {

			if( $op = $this->MatchReg('/\\G(?:>=|<=|=<|[<=>])/') ){
				if ($b = ($this->MatchFuncs(array('parseAddition','parseEntitiesKeyword','parseEntitiesQuoted')))) {
					$c = new Less_Tree_Condition($op, $a, $b, $index, $negate);
				} else {
					throw new Less_ParserException('Unexpected expression');
				}
			} else {
				$c = new Less_Tree_Condition('=', $a, new Less_Tree_Keyword('true'), $index, $negate);
			}
			$this->expect(')');
			return $this->MatchString('and') ? new Less_Tree_Condition('and', $c, $this->parseCondition()) : $c;
			//return $this->MatchReg('/\\Gand/') ? new Less_Tree_Condition('and', $c, $this->parseCondition()) : $c;
		}
	}

	//
	// An operand is anything that can be part of an operation,
	// such as a Color, or a Variable
	//
	private function parseOperand (){

		$negate = false;
		$offset = $this->pos+1;
		if( $offset >= $this->input_len ){
			return;
		}
		$char = $this->input[$offset];
		if( $char === '@' || $char === '(' ){
			$negate = $this->MatchChar('-');
		}

		$o = $this->MatchFuncs(array('parseSub','parseEntitiesDimension','parseEntitiesColor','parseEntitiesVariable','parseEntitiesCall'));

		if( $negate ){
			$o->parensInOp = true;
			$o = new Less_Tree_Negative($o);
		}

		return $o;
	}

	//
	// Expressions either represent mathematical operations,
	// or white-space delimited Entities.
	//
	//	 1px solid black
	//	 @var * 2
	//
	private function parseExpression (){
		$entities = array();

		while( $e = $this->MatchFuncs(array('parseAddition','parseEntity')) ){
			$entities[] = $e;
			// operations do not allow keyword "/" dimension (e.g. small/20px) so we support that here
			if( !$this->PeekReg('/\\G\/[\/*]/') && ($delim = $this->MatchChar('/')) ){
				$entities[] = new Less_Tree_Anonymous($delim);
			}

		}
		if( $entities ){
			return new Less_Tree_Expression($entities);
		}
	}

	private function parseProperty (){
		if( $name = $this->MatchReg('/\\G(\*?-?[_a-zA-Z0-9-]+)\s*:/') ){
			return $name[1];
		}
	}

	private function parseRuleProperty(){
		if( $name = $this->MatchReg('/\\G(\*?-?[_a-zA-Z0-9-]+)\s*(\+?)\s*:/') ){
			return $name[1] . (isset($name[2]) ? $name[2] : '');
		}
	}

	/**
	 * Some versions of php have trouble with method_exists($a,$b) if $a is not an object
	 *
	 */
	public static function is_method($a,$b){
		return is_object($a) && method_exists($a,$b);
	}

	/**
	 *
	 * Round 1.499999 to 1 instead of 2
	 *
	 */
	public static function round($i, $precision = 0){

		$precision = pow(10,$precision);
		$i = $i*$precision;

		$ceil = ceil($i);
		$floor = floor($i);
		if( ($ceil - $i) <= ($i - $floor) ){
			return $ceil/$precision;
		}else{
			return $floor/$precision;
		}
	}

}


