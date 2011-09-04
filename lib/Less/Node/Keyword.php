<?php

namespace Less\Node;

class Keyword
{
    public function __construct($value)
    {
        $this->value = $value;
    }
}

/*`
(function (tree) {

tree.Keyword = function (value) { this.value = value };
tree.Keyword.prototype = {
    eval: function () { return this },
    toCSS: function () { return this.value }
};

})(require('less/tree'));
*/