<?php

namespace Less\Node;

class Combinator
{
    public $value;
    public function __construct($value = '')
    {
        if ($value === ' ') {
            $this->value = ' ';
        } else if ($value === '& ') {
            $this->value = '& ';
        } else {
            $this->value = trim($value);
        }
    }
}

/*
tree.Combinator = function (value) {
    if (value === ' ') {
        this.value = ' ';
    } else if (value === '& ') {
        this.value = '& ';
    } else {
        this.value = value ? value.trim() : "";
    }
};
tree.Combinator.prototype.toCSS = function (env) {
    return {
        ''  : '',
        ' ' : ' ',
        '&' : '',
        '& ' : ' ',
        ':' : ' :',
        '::': '::',
        '+' : env.compress ? '+' : ' + ',
        '~' : env.compress ? '~' : ' ~ ',
        '>' : env.compress ? '>' : ' > '
    }[this.value];
};

})(require('less/tree'));

*/