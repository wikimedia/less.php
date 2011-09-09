<?php

namespace Less\Node;

class Color
{
    public function __construct($rgb, $a = 1)
    {
        if (is_array($rgb)) {
            $this->rgb = $rgb;
        } else if (strlen($rgb) == 6) {
            $this->rgb = array_map(function($c) { return hexdec($c); }, str_split($rgb, 2));
        } else {
            $this->rgb = array_map(function($c) { return hexdec($c.$c); }, str_split($rgb, 1));
        }
        $this->alpha = is_numeric($a) ? $a : 1;
    }

    public function compile($env = null)
    {
        return $this;
    }

    //
    // If we have some transparency, the only way to represent it
    // is via `rgba`. Otherwise, we use the hex representation,
    // which has better compatibility with older browsers.
    // Values are capped between `0` and `255`, rounded and zero-padded.
    //
    public function toCSS()
    {
        if ($this->alpha < 1.0) {
            $values = array_map(function ($c) {
                return round($c);
            }, $this->rgb);
            $values[] = $this->alpha;

            return "rgba(" . implode(', ', $values) . ")";
        } else {
            return '#' . implode('', array_map(function ($i) {
                $i = round($i);
                $i = ($i > 255 ? 255 : ($i < 0 ? 0 : $i));
                $i = dechex($i);
                return str_pad($i, 2, '0', STR_PAD_LEFT);
            }, $this->rgb));
        }
    }

    //
    // Operations have to be done per-channel, if not,
    // channels will spill onto each other. Once we have
    // our result, in the form of an integer triplet,
    // we create a new Color node to hold the result.
    //
    public function operate($op, $other) {
        $result = array();

        if (! ($other instanceof \Less\Node\Color)) {
            $other = $other->toColor();
        }

        for ($c = 0; $c < 3; $c++) {
            $result[$c] = \Less\Environment::operate($op, $this->rgb[$c], $other->rgb[$c]);
        }
        return new \Less\Node\Color($result, $this->alpha + $other->alpha);
    }

    public function toHSL()
    {
        $r = $this->rgb[0] / 255;
        $g = $this->rgb[1] / 255;
        $b = $this->rgb[2] / 255;
        $a = $this->alpha;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2;                 break;
                case $b: $h = ($r - $g) / $d + 4;                 break;
            }
            $h /= 6;
        }
        return array('h' => $h * 360, 's' => $s, 'l' => $l, 'a' => $a );
    }

    public function toARGB()
    {
        $argb = array_merge( (array) round($this->alpha * 255), $this->rgb);
        return '#' . implode('', array_map(function ($i) {
            $i = round($i);
            $i = dechex($i > 255 ? 255 : ($i < 0 ? 0 : $i));
            return str_pad($i, 2, '0', STR_PAD_LEFT);
        }, $argb));
    }
}
