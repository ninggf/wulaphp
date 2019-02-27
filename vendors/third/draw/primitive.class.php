<?php

    class image_draw_primitive extends image_plugin_base implements image_plugin {

        public $type_id = "draw";
        public $sub_type_id = "primitive";
        public $version = 0.1;
        
        private $arr_shapes = array();

//-----------------------------------------------------------------------------

        public function __construct($base_color="000000") {
        
            $this->base_color = $base_color;
        
        }

        public function addLine($x1, $y1, $x2, $y2, $color="") {
            
            if (empty($color)) { $color = $this->base_color; }
        
            $this->arr_shapes[] = array("LINE", $x1, $y1, $x2, $y2, $color);

        }

        public function addRectangle($x1, $y1, $x2, $y2, $color="", $filled=false) {

            if (empty($color)) { $color = $this->base_color; }

            if (!$filled) {
                $this->arr_shapes[] = array("RECTANGLE", $x1, $y1, $x2, $y2, $color);
            } else {
                $this->arr_shapes[] = array("FILLED_RECTANGLE", $x1, $y1, $x2, $y2, $color);
            }

        }

        public function addFilledRectangle($x1, $y1, $x2, $y2, $color="") {

            if (empty($color)) { $color = $this->base_color; }

            $this->arr_shapes[] = array("FILLED_RECTANGLE", $x1, $y1, $x2, $y2, $color);

        }


        public function addEllipse($x1, $y1, $x2, $y2, $color="", $filled=false) {

            if (empty($color)) { $color = $this->base_color; }
            
            $w = $x2-$x1;
            $h = $y2-$y1;

            if (!$filled) {
                $this->arr_shapes[] = array("ELLIPSE", $x1, $y1, $w, $h, $color);
            } else {
                $this->arr_shapes[] = array("FILLED_ELLIPSE", $x1, $y1, $w, $h, $color);
            }

        }

        public function addFilledEllipse($x1, $y1, $x2, $y2, $color="") {

            if (empty($color)) { $color = $this->base_color; }

            $w = $x2-$x1;
            $h = $y2-$y1;

            $this->arr_shapes[] = array("FILLED_ELLIPSE", $x1, $y1, $w, $h, $color);

        }

        public function addCircle($x, $y, $r, $color="") {

            if (empty($color)) { $color = $this->base_color; }

            $this->arr_shapes[] = array("ELLIPSE", $x, $y, $r, $r, $color);

        }

        public function generate() {
                
            foreach ($this->arr_shapes as $shape) {
            
                switch ($shape[0]) {
                
                    case "LINE":
                        $color = $this->_owner->imagecolorallocate($shape[5]);
                        imageline($this->_owner->image, $shape[1], $shape[2], $shape[3], $shape[4], $color);
                        break;
                    case "RECTANGLE":
                        $color = $this->_owner->imagecolorallocate($shape[5]);
                        imagerectangle($this->_owner->image, $shape[1], $shape[2], $shape[3], $shape[4], $color);
                        break;
                    case "FILLED_RECTANGLE":
                        $color = $this->_owner->imagecolorallocate($shape[5]);
                        imagefilledrectangle($this->_owner->image, $shape[1], $shape[2], $shape[3], $shape[4], $color);
                        break;
                    case "ELLIPSE":
                        $color = $this->_owner->imagecolorallocate($shape[5]);
                        imageellipse($this->_owner->image, $shape[1], $shape[2], $shape[3], $shape[4], $color);
                        break;
                    case "FILLED_ELLIPSE":
                        $color = $this->_owner->imagecolorallocate($shape[5]);
                        imagefilledellipse($this->_owner->image, $shape[1], $shape[2], $shape[3], $shape[4], $color);
                        break;
                }
            
            }
        
        }

    }
