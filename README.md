less.php
========

The **dynamic** stylesheet language.

<http://lesscss.org>

about
-----

This is a PHP port of the official LESS processor <http://lesscss.org> and should produce the same results as LESS 1.4.2.

Most of the code structure remains the same, which should allow for fairly easy updates in the future.
Namespaces, anonymous functions and shorthand ternary operators - `?:` have been removed to make this package compatible with php 5.2+.

There are still a few unsupported LESS features:

- Evaluation of JavaScript expressions within back-ticks (for obvious reasons).
- Definition of custom functions - will be added to the `\Less\Environment` class.


use
---

### The parser

```php
<?php

$parser = new \Less\Parser();
$parser->getEnvironment()->setCompress(true);

// parse css from a less source file or directly from a string
$css = $parser
            ->parseFile($path)
            ->parse("@color: #4D926F; #header { color: @color; } h2 { color: @color; }")
            ->getCss();
```

### The command line tool

The `bin/lessc` command line tool will accept an input (and optionally an output) file name to process.

```bash
$ ./bin/lessc input.less output.css
```

### In your website

The `bin/less.php` file can be moved to the directory containing your less source files. Including a links as follows
will compile and cache the css.

```html
<link rel="stylesheet" type="text/css" href="/static/less/css.php?bootstrap.less" />
```

NB: You'll need to update this file to point to the `lib` directory, and also make sure the `./cache` directory is
writable by the web server.

license
-------

See `LICENSE` file.
