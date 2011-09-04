<?php

namespace Less\Node;

class Element
{
    public $combinator;
    public $value;
    public function __construct($combinator, $value = '')
    {
        if ( ! ($combinator instanceof Combinator)) {
            $combinator = new Combinator($combinator);
        }
        $this->value = trim($value);
    }
}

/*`
(function (tree) {

tree.Element = function (combinator, value) {
    this.combinator = combinator instanceof tree.Combinator ?
                      combinator : new(tree.Combinator)(combinator);
    this.value = value ? value.trim() : "";
};
tree.Element.prototype.toCSS = function (env) {
    return this.combinator.toCSS(env || {}) + this.value;
};
*/