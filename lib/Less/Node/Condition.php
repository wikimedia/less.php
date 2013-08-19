<?php

namespace Less\Node;

class Condition {

	public $type = 'Condition';
	private $op;
	private $lvalue;
	private $rvalue;
	private $index;
	private $negate;

	public function __construct($op, $l, $r, $i = 0, $negate = false) {
		$this->op = trim($op);
		$this->lvalue = $l;
		$this->rvalue = $r;
		$this->index = $i;
		$this->negate = $negate;
	}

	public function accept($visitor){
		$this->lvalue = $visitor->visit( $this->lvalue );
		$this->rvalue = $visitor->visit( $this->rvalue );
	}

    public function compile($env) {
		$a = $this->lvalue->compile($env);
		$b = $this->rvalue->compile($env);

		$i = $this->index;

		$result = function($op) use ($a, $b) {
			switch ($op) {
				case 'and':
					return $a && $b;
				case 'or':
					return $a || $b;
				default:
					$aReflection = new \ReflectionClass($a);
					$bReflection = new \ReflectionClass($b);
					if ($aReflection->hasMethod('compare')) {
						$result = $a->compare($b);
					} elseif ($bReflection->hasMethod('compare')) {
						$result = $b->compare($a);
					} else {
						throw new \Less\Exception\CompilerException('Unable to perform comparison', $this->index);
					}
					switch ($result) {
						case -1: return $op === '<' || $op === '=<';
						case  0: return $op === '=' || $op === '>=' || $op === '=<';
						case  1: return $op === '>' || $op === '>=';
					}
			}
		};
		$result = $result($this->op);
		return $this->negate ? !$result : $result;
    }

}
