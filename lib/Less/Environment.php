<?php

namespace Less;

class Environment
{
    /**
     * @var array
     */
    public $frames;

    /**
     * @var bool
     */
    public $compress;

    /**
     * @var bool
     */
    public $debug;

    public function __construct()
    {
        $this->frames = array();
        $this->compress = false;
        $this->debug = false;
    }

    /**
     * @return bool
     */
    public function getCompress()
    {
        return $this->compress;
    }

    /**
     * @param bool $compress
     * @return void
     */
    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    /**
     * @return bool
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @param $debug
     * @return void
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function unshiftFrame($frame)
    {
        array_unshift($this->frames, $frame);
    }

    public function shiftFrame()
    {
        return array_shift($this->frames);
    }

    public function addFrame($frame)
    {
        $this->frames[] = $frame;
    }

    public function addFrames(array $frames)
    {
        $this->frames = array_merge($this->frames, $frames);
    }

    static public function operate ($op, $a, $b)
    {
        switch ($op) {
            case '+': return $a + $b;
            case '-': return $a - $b;
            case '*': return $a * $b;
            case '/': return $a / $b;
        }
    }

    static public function find ($obj, $fun)
    {
        foreach($obj as $i => $o) {

            if ($r = call_user_func($fun, $o)) {

                return $r;
            }
        }
        return null;
    }

    static public function clamp($val)
    {
        return min(1, max(0, $val));
    }

    static public function number($n)
    {
        if ($n instanceof \Less\Node\Dimension) {
            return floatval($n->unit == '%' ? $n->value / 100 : $n->value);
        } else if (is_numeric($n)) {
            return $n;
        } else {
            throw new \Less\Exception\CompilerException("color functions take numbers as parameters");
        }
    }

    public function rgb ($r, $g, $b)
    {
        return $this->rgba($r, $g, $b, 1.0);
    }

    public function rgba($r, $g, $b, $a)
    {
        $rgb = array_map(function ($c) { return \Less\Environment::number($c); }, array($r, $g, $b));
        $a = self::number($a);
        return new \Less\Node\Color($rgb, $a);
    }

    public function hsl($h, $s, $l)
    {
        return $this->hsla($h, $s, $l, 1.0);
    }

    public function hsla($h, $s, $l, $a)
    {
        $h = fmod(self::number($h), 360) / 360; // Classic % operator will change float to int
        $s = self::number($s);
        $l = self::number($l);
        $a = self::number($a);

        $m2 = $l <= 0.5 ? $l * ($s + 1) : $l + $s - $l * $s;
        $m1 = $l * 2 - $m2;

        $hue = function ($h) use ($m1, $m2) {
            $h = $h < 0 ? $h + 1 : ($h > 1 ? $h - 1 : $h);
            if      ($h * 6 < 1) return $m1 + ($m2 - $m1) * $h * 6;
            else if ($h * 2 < 1) return $m2;
            else if ($h * 3 < 2) return $m1 + ($m2 - $m1) * (2/3 - $h) * 6;
            else                 return $m1;
        };

        return $this->rgba($hue($h + 1/3) * 255,
                           $hue($h)       * 255,
                           $hue($h - 1/3) * 255,
                           $a);
    }

    public function hue($color)
    {
        $c = $color->toHSL();
        return new \Less\Node\Dimension(round($c['h']));
    }

    public function saturation($color)
    {
        $c = $color->toHSL();
        return new \Less\Node\Dimension(round($c['s'] * 100), '%');
    }

    public function lightness($color)
    {
        $c = $color->toHSL();
        return new \Less\Node\Dimension(round($c['l'] * 100), '%');
    }

    public function alpha($color)
    {
        $c = $color->toHSL();
        return new \Less\Node\Dimension(round($c['a']));
    }

    public function saturate($color, $amount)
    {
        $hsl = $color->toHSL();

        $hsl['s'] += $amount->value / 100;
        $hsl['s'] = self::clamp($hsl['s']);

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    public function desaturate($color, $amount)
    {
        $hsl = $color->toHSL();

        $hsl['s'] -= $amount->value / 100;
        $hsl['s'] = self::clamp($hsl['s']);

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    public function lighten($color, $amount)
    {
        $hsl = $color->toHSL();

        $hsl['l'] += $amount->value / 100;
        $hsl['l'] = self::clamp($hsl['l']);

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    public function darken($color, $amount)
    {
        $hsl = $color->toHSL();

        $hsl['l'] -= $amount->value / 100;
        $hsl['l'] = self::clamp($hsl['l']);

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    public function fadein($color, $amount)
    {
        $hsl = $color->toHSL();

        if ($amount->unit == '%') {
            $hsl['a'] += $amount->value / 100;
        } else {
            $hsl['a'] += $amount->value;
        }
        $hsl['a'] = self::clamp($hsl['a']);

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    public function fadeout($color, $amount)
    {
        $hsl = $color->toHSL();

        if ($amount->unit == '%') {
            $hsl['a'] -= $amount->value / 100;
        } else {
            $hsl['a'] -= $amount->value;
        }
        $hsl['a'] = self::clamp($hsl['a']);

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    public function fade($color, $amount)
    {
        $hsl = $color->toHSL();

        if ($amount->unit == '%') {
            $hsl['a'] = $amount->value / 100;
        } else {
            $hsl['a'] = $amount->value;
        }
        $hsl['a'] = self::clamp($hsl['a']);

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    public function spin($color, $amount)
    {
        $hsl = $color->toHSL();
        $hue = ($hsl['h'] + $amount->value) % 360;

        $hsl['h'] = $hue < 0 ? 360 + $hue : $hue;

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    //
    // Copyright (c) 2006-2009 Hampton Catlin, Nathan Weizenbaum, and Chris Eppstein
    // http://sass-lang.com
    //
    public function mix($color1, $color2, $weight = null)
    {
        if (!$weight) {
            $weight = new \Less\Node\Dimension('50', '%');
        }

        $p = $weight->value / 100.0;
        $w = $p * 2 - 1;
        $hsl1 = $color1->toHSL();
        $hsl2 = $color2->toHSL();
        $a = $hsl1['a'] - $hsl2['a'];

        $w1 = (((($w * $a) == -1) ? $w : ($w + $a) / (1 + $w * $a)) + 1) / 2;
        $w2 = 1 - $w1;

        $rgb = array($color1->rgb[0] * $w1 + $color2->rgb[0] * $w2,
                     $color1->rgb[1] * $w1 + $color2->rgb[1] * $w2,
                     $color1->rgb[2] * $w1 + $color2->rgb[2] * $w2);

        $alpha = $color1->alpha * $p + $color2->alpha * (1 - $p);

        return new \Less\Node\Color($rgb, $alpha);
    }

    public function greyscale($color)
    {
        return $this->desaturate($color, new \Less\Node\Dimension(100));
    }

    public function e ($str)
    {
        return new \Less\Node\Anonymous($str instanceof \Less\Node\JavaScript ? $str->evaluated : $str);
    }

    public function escape ($str)
    {
        return new \Less\Node\Anonymous(urlencode($str->value));
    }

    public function _percent()
    {
        $numargs = func_num_args();
        $quoted = func_get_arg(0);

        $args = func_get_args();
        array_shift($args);
        $str = $quoted->value;

        foreach($args as $arg) {
            $str = preg_replace_callback('/%[sda]/i', function($token) use ($arg) {
                $token = $token[0];
                $value = stristr($token, 's') ? $arg->value : $arg->toCSS();
                return preg_match('/[A-Z]$/', $token) ? urlencode($value) : $value;
            }, $str, 1);
        }
        $str = str_replace('%%', '%', $str);

        return new \Less\Node\Quoted('"' . $str . '"', $str);
    }

    public function round($n)
    {
        if ($n instanceof \Less\Node\Dimension) {
            return new \Less\Node\Dimension(round(self::number($n)), $n->unit);
        } else if (is_numeric($n)) {
            return round($n);
        } else {
            throw new \Less\Exception\CompilerException("math functions take numbers as parameters");
        }
    }

    public function argb($color)
    {
        return new \Less\Node\Anonymous($color->toARGB());
    }
}
