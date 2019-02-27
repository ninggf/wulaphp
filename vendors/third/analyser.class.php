<?php

    class image_analyser extends image_plugin_base implements image_plugin {
    
        public $type_id = "analysis";
        public $sub_type_id = "analyser";
        public $version = 0.1;

        private $count_colors = array();
        private $count_a = array();
        private $count_r = array();
        private $count_g = array();
        private $count_b = array();
        private $count_hue = array();
        private $count_saturation = array();
        private $count_brightness = array();
        private $_analyse_complete = false;
        private $_analyseHSB_complete = false;

//-----------------------------------------------------------------------------

        public function __construct() {

            for ($x = 0; $x < 255; $x++) {
                $this->count_a[$x] = 0;
                $this->count_r[$x] = 0;
                $this->count_g[$x] = 0;
                $this->count_b[$x] = 0;        
            }

        }

        public function generate() {
        
        }

//-----------------------------------------------------------------------------

        public function countColors($channel="all") {

            if (!isset($this->_owner->image)) { return false; }
            if (!$this->_analyse_complete) { $this->_analyse(); }
            return count($this->count_colors);

        }

//-----------------------------------------------------------------------------

        public function averageChannel($channel="all") {

            if (!isset($this->_owner->image)) { return false; }

            $this->_analyse();

            switch ($channel) {
                case "r":
                case "red":
                    return $this->_array_avg($this->count_r);
                    break;
                case "g":
                case "green":
                    return $this->_array_avg($this->count_g);
                    break;
                case "b":
                case "blue":
                    return $this->_array_avg($this->count_b);
                    break;
                case "a":
                case "alpha":
                    return $this->_array_avg($this->count_a);
                    break;
                default:
                    return $this->_array_avg($this->count_colors);
                    break;
            }

        }

        public function minChannel($channel="all") {

            if (!isset($this->_owner->image)) { return false; }
            if (!$this->_analyse_complete) { $this->_analyse(); }

            switch ($channel) {
                case "r":
                case "red":
                    return $this->_array_min($this->count_r);
                    break;
                case "g":
                case "green":
                    return $this->_array_min($this->count_g);
                    break;
                case "b":
                case "blue":
                    return $this->_array_min($this->count_b);
                    break;
                case "a":
                case "alpha":
                    return $this->_array_min($this->count_a);
                    break;
                default:
                    return $this->_array_min($this->count_colors);
                    break;
            }
        }

        public function maxChannel($channel="all") {

            if (!isset($this->_owner->image)) { return false; }
            if (!$this->_analyse_complete) { $this->_analyse(); }

            switch ($channel) {
                case "r":
                case "red":
                    return $this->_array_max($this->count_r);
                    break;
                case "g":
                case "green":
                    return $this->_array_max($this->count_g);
                    break;
                case "b":
                case "blue":
                    return $this->_array_max($this->count_b);
                    break;
                case "a":
                case "alpha":
                    return $this->_array_max($this->count_a);
                    break;
                default:
                    return $this->_array_max($this->count_colors);
                    break;
            }
        }

//-----------------------------------------------------------------------------

        public function hue($x, $y) {

            if (!isset($this->_owner->image)) { return false; }
            $color = $this->_owner->imageColorAt($x, $y);
            $arrColor = $this->_owner->intColorToArrayColor($color);
            list($h,$s,$b) = $this->_hsb($arrColor['red'],$arrColor['green'],$arrColor['blue']);
            return $h;

        }

        public function saturation($x, $y) {

            if (!isset($this->_owner->image)) { return false; }
            $color = $this->_owner->imageColorAt($x, $y);
            $arrColor = $this->_owner->intColorToArrayColor($color);
            list($h,$s,$b) = $this->_hsb($arrColor['red'],$arrColor['green'],$arrColor['blue']);
            return $s;

        }

        public function brightness($x, $y) {

            if (!isset($this->_owner->image)) { return false; }
            $color = $this->_owner->imageColorAt($x, $y);
            $arrColor = $this->_owner->intColorToArrayColor($color);
            list($h,$s,$b) = $this->_hsb($arrColor['red'],$arrColor['green'],$arrColor['blue']);
            return $b;

        }

//-----------------------------------------------------------------------------

        public function imageHue() {
        
            if (!$this->_analyse_complete) { $this->_analyse(); }
            if (!$this->_analyseHSB_complete) { $this->_analyseHSB(); }
        
            return $this->_array_max($this->count_hue);
        
        }

        public function imageSaturation() {
        
            if (!$this->_analyse_complete) { $this->_analyse(); }
            if (!$this->_analyseHSB_complete) { $this->_analyseHSB(); }
            
            return $this->_array_max($this->count_saturation);
        
        }

        public function imageBrightness() {
        
            if (!$this->_analyse_complete) { $this->_analyse(); }
            if (!$this->_analyseHSB_complete) { $this->_analyseHSB(); }
            
            return $this->_array_max($this->count_brightness);
        
        }
        
//-----------------------------------------------------------------------------

        private function _analyse() {
        
            if (!isset($this->_owner->image)) { return false; }
            
            $width = $this->_owner->imagesx();
            $height = $this->_owner->imagesy();
            
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {

                    $color = $this->_owner->imageColorAt($x, $y);
                    $arrColor = $this->_owner->intColorToArrayColor($color);                                        
                    $this->count_colors[$color]++;
                    $this->count_a[$arrColor['alpha']]++;
                    $this->count_r[$arrColor['red']]++;
                    $this->count_g[$arrColor['green']]++;
                    $this->count_b[$arrColor['blue']]++;
                    
                }
            }

            $this->_analyse_complete = true;

        }
        
        private function _analyseHSB() {
                
            foreach ($this->count_colors as $color => $count) {

                $arrColor = $this->_owner->intColorToArrayColor($color);
                list($h,$s,$b) = $this->_hsb($arrColor['red'],$arrColor['green'],$arrColor['blue']);
                $this->count_hue[$h]++;
                $this->count_saturation[$s]++;
                $this->count_brightness[$b]++;

            }
            
            $this->_analyseHSB_complete = true;
        
        }

        private function _array_avg($array) {

            foreach ($array as $k => $v) {
            
                $t += $k*$v;
                $s += $v;
            
            }

            return round($t/$s);

        }

        private function _array_min($array) {
        
            $mv = 256;

            foreach ($array as $k => $v) {
            
                if ($v < $mv) {
                
                    $mk = $k;
                    $mv = $v;
                
                }
            
            }

            return $mk;

        }

        private function _array_max($array) {

            $mv = 0;

            foreach ($array as $k => $v) {
            
                if ($v > $mv) {
                
                    $mk = $k;
                    $mv = $v;
                
                }
            
            }

            return $mk;

        }

        private function _hsb($r,$g,$b) {

            $hue = 0.0;
            $saturation = 0.0;
            $brightness = 0.0;
            $min = min($r,$g,$b);
            $max = max($r,$g,$b);

            $delta = ($max - $min);
            
            $brightness = $max;
            
            if ($max != 0.0) {
                $saturation = $delta / $max;
            } else {
                $saturation = 0.0;
                $hue = -1;
            }
            if ($saturation != 0.0){
                if ($r == $max) {
                    $hue = ($g - $b) / $delta;
                } else {
                    if ($g == $max) {
                        $hue = 2.0 + ($b - $r) / $delta;
                    } else {
                        if ($b == $max) {
                            $hue = 4.0 + ($r - $g) / $delta;
                        }
                    }     
                }
            } else {
                $hue = -1.0;
            } 

            $hue = $hue * 60.0 ;  
            if ($hue < 0.0) { $hue = $hue + 360.0; }
            $saturation = round($saturation * 100);
            $brightness = round(($brightness/255) * 100);

            return array($hue,$saturation,$brightness);

        }

    }

    