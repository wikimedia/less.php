# Maintainers guide

## Internal overview

This is an overview of the high-level steps during the transformation
from Less to CSS, and how they compare between Less.js and Less.php.

Less.js:

* `less.render(input, { paths: … })`
  * `Parser.parse` normalizes input
  * `Parser.parse` parses input into rules via `parsers.primary`
  * `Parser.parse` creates the "root" ruleset object
  * `Parser.parse` applies ImportVisitor
    * `ImportVisitor` applies these steps to each `Import` node:
      * `ImportVisitor#processImportNode`
      * `Import#evalForImport`
    * `ImportVisitor` ends with `ImporVisitor#tryRun` loop (async, after last call to `ImportVisitor#onImported`.
* `less.render` callback
  * `ParseTree.prototype.toCSS`
    * `transformTree` applies pre-visitors, compiles all rules, and applies post-visitors.
  * `ParseTree.prototype.toCSS` runs toCSS transform on the "root" ruleset.
* CSS result ready!

Less.php

* `Less_Parser->parseFile`
  * `Less_Parser->_parse`
  * `Less_Parser->GetRules` normalizes input (via `Less_Parser->SetInput`)
  * `Less_Parser->GetRules` parses input into rules via `Less_Parser->parsePrimary`
* `Less_Parser->getCss`
  * `Less_Parser->getCss` creates the "root" ruleset object
  * `Less_Parser->getCss` applies Less_ImportVisitor
    * `Less_ImportVisitor` applies these steps to each `Import` node:
      * `ImportVisitor->processImportNode`
      * `Less_Tree_Import->compileForImport`
    * `ImportVisitor` ends with `ImporVisitor#tryRun` loop (all sync, no async needed).
  * `Less_Parser->getCss` applies pre-visitors, compiles all rules, and applies post-visitors.
  * `Less_Parser->getCss` runs toCSS transform on the "root" ruleset.
* CSS result ready!

## Compatibility

The `wikimedia/less.php` package inherits a long history of loosely compatible
and interchangable Less compilers written in PHP.

Starting with less.php v3.2.1 (released in 2023), the public API is more clearly
documented, and internal code is now consistently marked `@private`.

The public API includes the `Less_Parser` class and several of its public methods.
For legacy reasons, some of its internal methods remain public. Maintainers must
take care to search the following downstream applications when changing or
removing public methods. If a method has one or more references in the below
codebases, treat it as a breaking change and document a migration path in the
commit message (and later in CHANGES.md), even if the method was undocumented
or feels like it is for internal use only.

* [MediaWiki (source code)](https://codesearch.wmcloud.org/core/?q=Less_Parser&files=php%24)
* [Matomo (source code)](https://github.com/matomo-org/matomo/blob/5.0.2/core/AssetManager/UIAssetMerger/StylesheetUIAssetMerger.php)
* [Adobe Magento (source code)](https://github.com/magento/magento2/blob/2.4.6/lib/internal/Magento/Framework/Css/PreProcessor/Adapter/Less/Processor.php)
* [Shopware 5 (source code)](https://github.com/shopware5/shopware/blob/5.7/engine/Shopware/Components/Theme/LessCompiler/Oyejorge.php)
