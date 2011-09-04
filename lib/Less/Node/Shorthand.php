<?php

namespace Less\Node;

class Shorthand
{
    public function __construct($a, $b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}

/*`


tree.Shorthand = function (a, b) {
    this.a = a;
    this.b = b;
};

tree.Shorthand.prototype = {
    toCSS: function (env) {
        return this.a.toCSS(env) + "/" + this.b.toCSS(env);
    },
    eval: function () { return this }
};

})(require('less/tree'));
*/