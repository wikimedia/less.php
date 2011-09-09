<?php

namespace Less\Node;

class Operation
{
    public function __construct($op, $operands)
    {
        $this->op = trim($op);
        $this->operands = $operands;
    }

    public function compile($env)
    {
        $a = $this->operands[0]->compile($env);
        $b = $this->operands[1]->compile($env);

        if ($a instanceof \Less\Node\Dimension && $b instanceof \Less\Node\Color) {
            if ($this->op === '*' || $this->op === '+') {
                $temp = $b;
                $b = $a;
                $a = $temp;
            } else {
                throw new \Less\CompilerError("Can't subtract or divide a color from a number");
            }
        }

        return $a->operate($this->op, $b);
    }
}
