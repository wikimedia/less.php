<?php

namespace Less\Node;

class Quoted
{
    public $escaped;
    public $content;
    public $str;
    public $i;

    public function __construct($str, $content, $escaped, $i)
    {
        $this->escaped = $escaped;
        $this->value = $content ?: '';
        $this->quote = $str[0];
        $this->index = $i;
    }
}

/*`
(function (tree) {

tree.Quoted = function (str, content, escaped, i) {
    this.escaped = escaped;
    this.value = content || '';
    this.quote = str.charAt(0);
    this.index = i;
};
tree.Quoted.prototype = {
    toCSS: function () {
        if (this.escaped) {
            return this.value;
        } else {
            return this.quote + this.value + this.quote;
        }
    },
    eval: function (env) {
        var that = this;
        var value = this.value.replace(/`([^`]+)`/g, function (_, exp) {
            return new(tree.JavaScript)(exp, that.index, true).eval(env).value;
        }).replace(/@\{([\w-]+)\}/g, function (_, name) {
            var v = new(tree.Variable)('@' + name, that.index).eval(env);
            return v.value || v.toCSS();
        });
        return new(tree.Quoted)(this.quote + value + this.quote, value, this.escaped, this.index);
    }
};

})(require('less/tree'));
*/