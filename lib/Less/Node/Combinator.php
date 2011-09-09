<?php

namespace Less\Node;

class Combinator
{
    public $value;
    public function __construct($value = '')
    {
        if ($value == ' ') {
            $this->value = ' ';
        } else if ($value == '& ') {
            $this->value = '& ';
        } else {
            $this->value = trim($value);
        }
    }

    public function toCSS ($env)
    {
        $v = array(
            ''   => '',
            ' '  => ' ',
            '&'  => '',
            '& ' => ' ',
            ':'  => ' :',
            '::' => '::',
            '+'  => $env->compress ? '+' : ' + ',
            '~'  => $env->compress ? '~' : ' ~ ',
            '>'  => $env->compress ? '>' : ' > '
        );

        return $v[$this->value];
    }
}
