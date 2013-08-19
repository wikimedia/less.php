<?php

namespace Less\Node;

class Dimension{

    public function __construct($value, $unit = false){
        $this->value = floatval($value);

		if( $unit && ($unit instanceof \Less\Node\Unit) ){
			$this->unit = $unit;
		}elseif( $unit ){
			$this->unit = new \Less\Node\Unit( array($unit) );
		}else{
			$this->unit = new \Less\Node\Unit( );
		}
    }

    public function compile($env = null) {
        return $this;
    }

    public function toColor() {
        return new \Less\Node\Color(array($this->value, $this->value, $this->value));
    }

	public function toCSS( $env = null ){

		if( (!$env || $env->strictUnits !== false) && !$this->unit->isSingular() ){
			throw new \Less\Exception\CompilerException("Multiple units in dimension. Correct the units or use the unit function. Bad unit: ".$this->unit->toString());
		}

		$value = $this->value;
		$strValue = (string)$value;

		if( $env && $env->compress ){
			// Zero values doesn't need a unit
			if( $value === 0 && !$this->unit->isAngle() ){
				return $strValue;
			}

			// Float values doesn't need a leading zero
			if( $value > 0 && $value < 1 && $strValue[0] === '0' ){
				$strValue = substr($strValue,1);
			}
		}

		return $this->unit->isEmpty() ? $strValue : $strValue . $this->unit->toCSS();
	}

    public function __toString(){
        return $this->toCSS();
    }

    // In an operation between two Dimensions,
    // we default to the first Dimension's unit,
    // so `1px + 2em` will yield `3px`.
    public function operate($env, $op, $other){

		$value = \Less\Environment::operate($env, $op, $this->value, $other->value);
		$unit = clone $this->unit;

		if( $op === '+' || $op === '-' ){

			if( !count($unit->numerator) && !count($unit->denominator) ){
				$unit->numerator = array_slice($other->unit->numerator,0);
				$unit->denominator = array_slice($other->unit->denominator,0);
			}elseif( !count($other->unit->numerator) && !count($other->unit->denominator) ){
				// do nothing
			}else{
				$other = $other->convertTo( $this->unit->usedUnits());

				if( $env->strictUnits !== false && $other->unit->toString() !== $unit->toCSS() ){
					throw new \Less\Exception\CompilerException("Incompatible units. Change the units or use the unit function. Bad units: '".$unit->toString() . "' and ".$other->unit->toString()+"'.");
				}

				$value = \Less\Environment::operate($env, $op, $this->value, $other->value);
			}
		}elseif( $op === '*' ){
			$unit->numerator = array_merge($unit->numerator, $other->unit->numerator);
			$unit->denominator = array_merge($unit->denominator, $other->unit->denominator);
			sort($unit->numerator);
			sort($unit->denominator);
			$unit->cancel();
		}elseif( $op === '/' ){
			$unit->numerator = array_merge($unit->numerator, $other->unit->denominator);
			$unit->denominator = array_merge($unit->denominator, $other->unit->numerator);
			sort($unit->numerator);
			sort($unit->denominator);
			$unit->cancel();
		}
		return new \Less\Node\Dimension( $value, $unit);
    }

	public function compare($other) {
		if ($other instanceof Dimension) {

			$a = $this->unify();
			$b = $other->unify();
			$aValue = $a->value;
			$bValue = $b->value;

			if ($bValue > $aValue) {
				return -1;
			} elseif ($bValue < $aValue) {
				return 1;
			} else {
				if( !$b->unit->isEmpty() && $a->unit->compare($b) !== 0) {
					return -1;
				}
				return 0;
			}
		} else {
			return -1;
		}
	}

	function unify() {
		return $this->convertTo(array('length'=> 'm', 'duration'=> 's', 'angle' => 'rad' ));
	}

    function convertTo($conversions) {
		$value = $this->value;
		$unit = clone $this->unit;

		if( is_string($conversions) ){
			$derivedConversions = array();
			foreach( \Less\Node\UnitConversions::$groups as $i ){
				if( isset(\Less\Node\UnitConversions::${$i}[$conversions]) ){
					$derivedConversions = array( $i => $conversions);
				}
			}
			$conversions = $derivedConversions;
		}


		foreach($conversions as $groupName => $targetUnit){
			$group = \Less\Node\UnitConversions::${$groupName};

			//numerator
			for($i=0; $i < count($unit->numerator); $i++ ){
				$atomicUnit = $unit->numerator[$i];
				if( !isset($group[$atomicUnit]) ){
					continue;
				}

				$value = $value * ($group[$atomicUnit] / $group[$targetUnit]);

				$unit->numerator[$i] = $targetUnit;
			}

			//denominator
			for($i=0; $i < count($unit->denominator); $i++ ){
				$atomicUnit = $unit->denominator[$i];
				if( !isset($group[$atomicUnit]) ){
					continue;
				}

				$value = $value / ($group[$atomicUnit] / $group[$targetUnit]);

				$unit->denominator[$i] = $targetUnit;
			}
		}

		$unit->cancel();

		return new \Less\Node\Dimension( $value, $unit);
    }
}
