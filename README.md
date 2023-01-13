[![Packagist](https://img.shields.io/packagist/v/wikimedia/less.php.svg?style=flat)](https://packagist.org/packages/wikimedia/less.php)
[![Build Status](https://github.com/wikimedia/less.php/actions/workflows/php.yml/badge.svg)](https://github.com/wikimedia/less.php/actions)

Less.php
========

This is a PHP port of the [official LESS processor](https://lesscss.org).

* [Installation](#installation)
* [Security](#security)
* [Basic use](#basic-use)
* [Caching](#caching)
* [Source maps](#source-maps)
* [Command line](#command-line)
* [Who uses Less.php?](#who-uses-lessphp)
* [Integration with other projects](#integrations)
* [Transitioning from Leafo/lessphp](#transitioning-from-leafolessphp)
* [Credits](#credits)

## About

The code structure of Less.php mirrors that of upstream Less.js to ensure compatibility and help reduce maintenance. The port is currently compatible with Less.js 2.5.3.

Please note that "inline JavaScript expressions" (via eval or backticks) are not supported.

## Installation

You can install the library with Composer or standalone.

#### Composer

If you have [Composer](https://getcomposer.org/download/) installed:

1. Run `composer require wikimedia/less.php`
2. Use `Less_Parser` in your code.

#### Standalone

1. [Download a release](https://github.com/wikimedia/less.php/tags) and upload the PHP files to your server.
2. Include the library:
   ```php
   require_once '[path to]/less.php/lib/Less/Autoloader.php';
   Less_Autoloader::register();
   ```
3. Use `Less_Parser` in your code.

## Security

The LESS processor language is powerful and includes features that may read or embed arbitrary files that the web server has access to, and features that may be computationally exensive if misused.

In general you should treat LESS files as being in the same trust domain as other server-side executables, such as PHP code. In particular, it is not recommended to allow people that use your web service to provide arbitrary LESS code for server-side processing.

_See also [SECURITY](./SECURITY.md)._

## Basic use

#### Parse strings

```php
$parser = new Less_Parser();
$parser->parse( '@color: #36c; .link { color: @color; } a { color: @color; }' );
$css = $parser->getCss();
```

#### Parse files

The `parseFile()` function takes two parameters:

* The absolute path to a `.less` file.
* The base URL for any relative image or CSS references in the `.less` file,
  typically the same directory that contains the `.less` file or a public equivalent.

```php
$parser = new Less_Parser();
$parser->parseFile( '/var/www/mysite/bootstrap.less', 'https://example.org/mysite/' );
$css = $parser->getCss();
```

#### Handle invalid syntax

An exception will be thrown if the compiler encounters invalid LESS.

```php
try{
	$parser = new Less_Parser();
	$parser->parseFile( '/var/www/mysite/bootstrap.less', 'https://example.org/mysite/' );
	$css = $parser->getCss();
} catch (Exception $e) {
	echo $e->getMessage();
}
```

#### Parse multiple inputs

Less.php can parse multiple input sources (e.g. files and/or strings) and generate a single CSS output.

```php
$parser = new Less_Parser();
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$parser->parse( '@color: #36c; .link { color: @color; } a { color: @color; }' );
$css = $parser->getCss();
```

#### Metadata

Less.php keeps track of which `.less` files have been parsed, i.e. the input
file(s) and any direct and indirect imports.

```php
$parser = new Less_Parser();
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
$files = $parser->AllParsedFiles();
```

#### Compress output

You can tell Less.php to remove comments and whitespace to generate minified CSS.

```php
$options = [ 'compress' => true ];
$parser = new Less_Parser( $options );
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
```

#### Get variables

You can use the `getVariables()` method to get an all variables defined and
their value in an associative array. Note that the input must be compiled first
by calling `getCss()`.

```php
$parser = new Less_Parser;
$parser->parseFile( '/var/www/mysite/bootstrap.less');
$css = $parser->getCss();
$variables = $parser->getVariables();

```

#### Set variables

Use the `ModifyVars()` method to inject additional variables, i.e. custom values
computed or accessed from your PHP code.

```php
$parser = new Less_Parser();
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$parser->ModifyVars( [ 'font-size-base' => '16px' ] );
$css = $parser->getCss();
```

#### Import directories

By default, Less.php will look for imported files in the directory of the file passed to `parseFile()`.

If you use `parse()`, or if need to enable additional import directories, you can specify these by
calling `SetImportDirs()`.

```php
$directories = [ '/var/www/mysite/bootstrap/' => '/mysite/bootstrap/' ];
$parser = new Less_Parser();
$parser->SetImportDirs( $directories );
$parser->parseFile( '/var/www/mysite/theme.less', '/mysite/' );
$css = $parser->getCss();
```

## Caching

Compiling LESS code into CSS can be a time-consuming process. It is recommended to cache your results.

#### Basic cache

Use the `Less_Cache` class to save and reuse the results of compiling LESS files.
This class will check the modified time and size of each LESS file (including imported files) and
either re-use or re-generate the CSS output accordingly.

The cache files are determinstically named, based on the full list of referenced LESS files and the metadata (file path, file mtime, file size) of each file. This means that each time a change is made, a different cache filename is used.

```php
$lessFiles = [ '/var/www/mysite/bootstrap.less' => '/mysite/' ];
$options = [ 'cache_dir' => '/var/www/writable_folder' ];
$cssOutputFile = Less_Cache::Get( $lessFiles, $options );
$css = file_get_contents( '/var/www/writable_folder/' . $cssOutputFile );
```

#### Caching with variables

Passing custom variables to `Less_Cache::Get()`:

```php
$lessFiles = [ '/var/www/mysite/bootstrap.less' => '/mysite/' ];
$options = [ 'cache_dir' => '/var/www/writable_folder' ];
$variables = [ 'width' => '100px' ];
$cssOutputFile = Less_Cache::Get( $lessFiles, $options, $variables );
$css = file_get_contents( '/var/www/writable_folder/' . $cssOutputFile );
```

#### Incremental caching

In addition to the whole-output caching described above, Less.php also has the ability to keep an internal cache which allows re-parses to be faster by effectively only re-compiling portions that have changed.

Use the Less_Parser `'cache_dir'` option, or call by `SetCacheDir()`, to enable the internal cache. Less.php will then save serialized metadata for each encountered `.less` file.

Note: This feature only caches intermediate results to internally speed up repeated CSS generation. This is not a substitute for whole-output caching. Your application should still cache the generated CSS files.

```php
$options = [ 'cache_dir'=>'/var/www/writable_folder' ];
$parser = new Less_Parser( $options );
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
```

Less.php supports multiple different cache formats via the `cache_method` option. Supported formats for the internal partial cache are:

* `php`: Store data as includable PHP file that returns a static array or class instantiation statement.
* `var_export`: Like "php", but generated by PHP's `var_export()` function without any optimizations.
  It's recommended to use "php" instead.
* `serialize`: Store data as text files containing PHP's serialized representation. This is faster, but more memory-intense.
* `callback`: Specify your own custom read and write callable functions via the `cache_callback_get` and
  `cache_callback_set` options.

  Less.php will pass these parameters to set:
  * `Less_Parser $parser`
  * `string $file_path` Inpyt `.less` file.
  * `string $cache_file` Contains identifier that changes every time input is modified.
  * `array $rules` Tree of internal Less_Tree objects.
  
  The `get` callback must return the `array $rules`, or if something went wrong,
  return `null` (cache doesn't exist) or `false`.

## Source maps

Less.php supports v3 sourcemaps.

#### Inline

The sourcemap will be appended to the generated CSS file.

```php
$options = [ 'sourceMap' => true ];
$parser = new Less_Parser($options);
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
```

#### Saving to map file

```php
$options = [
	'sourceMap' => true,
	'sourceMapWriteTo' => '/var/www/mysite/writable_folder/filename.map',
	'sourceMapURL' => '/mysite/writable_folder/filename.map',
];
$parser = new Less_Parser($options);
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
```

## Command line

An additional script has been included to use the Less.php compiler from the command line.
In its simplest invocation, you specify an input file and the compiled CSS is written to standard out:

```
$ lessc input.less > output.css
```

By using the `-w` flag you can watch a specified input file and have it compile as needed to the output file:

```
$ lessc -w input.less output.css
```

Errors from watch mode are written to standard out.

For more information, run `lessc --help`

## Who uses Less.php?

* **[Wikipedia](https://en.wikipedia.org/wiki/MediaWiki)** and the MediaWiki platform ([docs](https://www.mediawiki.org/wiki/ResourceLoader/Architecture#Resource:_Styles)).
* **[Matomo](https://en.wikipedia.org/wiki/Matomo_(software))** ([docs](https://devdocs.magento.com/guides/v2.4/frontend-dev-guide/css-topics/custom_preprocess.html)).
* **[Magento](https://en.wikipedia.org/wiki/Magento)** as part of Adobe Commerce ([docs](https://developer.matomo.org/guides/asset-pipeline#vanilla-javascript-css-and-less-files)).
* **[Icinga](https://en.wikipedia.org/wiki/Icinga)** in Icinga Web ([docs](https://github.com/Icinga/icingaweb2)).
* **[Shopware](https://de.wikipedia.org/wiki/Shopware)** ([docs](https://developers.shopware.com/designers-guide/less/)).

## Integrations

Less.php has been integrated with various other projects.

#### Transitioning from Leafo/lessphp

If you're looking to transition from the [Leafo/lessphp](https://github.com/leafo/lessphp) library, use the `lessc.inc.php` adapter file that comes with Less.php.

This allows Less.php to be a drop-in replacement for Leafo/lessphp.

[Download Less.php](https://github.com/wikimedia/less.php/archive/main.zip), unzip the files into your project, and include its `lessc.inc.php` instead.

Note: The `setPreserveComments` option is ignored. Less.php already preserves CSS block comments by default, and removes LESS inline comments.

#### Drupal

Less.php can be used with [Drupal's less module](https://drupal.org/project/less) via the `lessc.inc.php` adapter. [Download Less.php](https://github.com/wikimedia/less.php/archive/main.zip) and unzip it so that `lessc.inc.php` is located at `sites/all/libraries/lessphp/lessc.inc.php`, then install the Drupal less module as usual.

#### WordPress JBST theme

A copy of Less.php is built-in to the [JBST framework](https://github.com/bassjobsen/jamedo-bootstrap-start-theme).

#### WordPress plugin

The [Less PHP plugin for WordPress](https://wordpress.org/plugins/lessphp/) bundles a copy of Less.php for use in other plugins or themes. This dependency can also be combined with the [TGM Library](http://tgmpluginactivation.com/).

## Credits

Less.php was originally ported to PHP in 2011 by [Matt Agar](https://github.com/agar) and then updated by [Martin Jantošovič](https://github.com/Mordred) in 2012. From 2013 to 2017, [Josh Schmidt](https://github.com/oyejorge) lead development of the library. Since 2019, the library is maintained by Wikimedia Foundation.
