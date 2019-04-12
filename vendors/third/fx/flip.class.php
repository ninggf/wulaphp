<?php

    class image_fx_flip extends image_plugin_base implements image_plugin {

        public $type_id = "effect";
        public $sub_type_id = "flip";
        public $version = 0.1;

//-----------------------------------------------------------------------------

        public function __construct($flip_x=true, $flip_y=false) {
        
            $this->flip_x = $flip_x;
            $this->flip_y = $flip_y;
        
        }

        public function setFlip($flip_x=true, $flip_y=false) {

            $this->flip_x = $flip_x;
            $this->flip_y = $flip_y;

        }

        public function generate() {
        
            $src_x = $this->_owner->imagesx();
            $src_y = $this->_owner->imagesy();
            
            $flip_x = $this->flip_x;
            $flip_y = $this->flip_y;

            $flip = new image($src_x, $src_y);
 
            if ($flip_x==true) {
                imagecopy($flip->image,$this->_owner->image,0,0,0,0,$src_x,$src_y);
                for ($x=0; $x<$src_x; $x++) {
                    imagecopy(
                        $this->_owner->image,
                        $flip->image,
                        $src_x-$x-1,
                        0,
                        $x,
                        0,
                        1,
                        $src_y
                    );
                }
            }

            if ($flip_y==true) {
                imagecopy($flip->image,$this->_owner->image,0,0,0,0,$src_x,$src_y);
                for ($y=0; $y<$src_y; $y++) {
                    imagecopy(
                        $this->_owner->image,
                        $flip->image,
                        0,
                        $src_y-$y-1,
                        0,
                        $y,
                        $src_x,
                        1
                    );
                }
            }

            $this->_owner->image = $flip->image;
            
            unset($flip);

            return true;
        
        }

    }
