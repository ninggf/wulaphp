<?php

    class image_fx_ripple extends image_plugin_base implements image_plugin {

        public $type_id = "effect";
        public $sub_type_id = "ripple";
        public $version = 0.1;

//-----------------------------------------------------------------------------

        public function __construct($frequency=3, $amplitude=4, $wrap_around=true) {
        
            $this->frequency = $frequency;
            $this->amplitude = $amplitude;
            $this->wrap_around = $wrap_around;
        
        }

        public function generate() {
        
            $width = $this->_owner->imagesx();
            $height = $this->_owner->imagesy();

            for ($y=0;$y<$height;$y++) {
                for ($x=0;$x<$width;$x++) {

                    $dis_x = $x+(sin(deg2rad(($y/$height)*360)*$this->frequency)*$this->amplitude);
                    $dis_y = $y+(sin(deg2rad(($x/$width)*360)*$this->frequency)*$this->amplitude);

                    if ($this->wrap_around == true) {
                        $dis_x = ($dis_x < 0) ? $dis_x + $width : $dis_x;
                        $dis_x = ($dis_x >= $width) ? $dis_x - $width : $dis_x;
                        $dis_y = ($dis_y < 0) ? $dis_y + $height : $dis_y;
                        $dis_y = ($dis_y >= $height) ? $dis_y - $height : $dis_y;
                    } else {
                        $dis_x = ($dis_x < 0) ? 0 : $dis_x;
                        $dis_x = ($dis_x > $width) ? $width : $dis_x;
                        $dis_y = ($dis_y < 0) ? 0 : $dis_y;
                        $dis_y = ($dis_y > $height) ? $height : $dis_y;
                    }

                    $displacement['x'][$x][$y] = $dis_x;
                    $displacement['y'][$x][$y] = $dis_y;
                }
            }

            $this->_owner->displace($displacement);

            return true;
        
        }

    }
