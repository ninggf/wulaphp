<?php

    class image_fx_offset extends image_plugin_base implements image_plugin {

        public $type_id = "effect";
        public $sub_type_id = "offset";
        public $version = 0.1;

//-----------------------------------------------------------------------------

        public function __construct($offset_x=0, $offset_y=0) {
        
            $this->offset_x = $offset_x;
            $this->offset_y = $offset_y;
        
        }

        public function setOffset($offset_x=0, $offset_y=0) {

            $this->offset_x = $offset_x;
            $this->offset_y = $offset_y;

        }

        public function generate() {

            $width = $this->_owner->imagesx();
            $height = $this->_owner->imagesy();

            $temp = new Image();
            $temp->createImageTrueColor($width,$height);

            imagecopy($temp->image,$this->_owner->image,$this->offset_x,$this->offset_y,0,0,$width-$this->offset_x,$height-$this->offset_y);
            imagecopy($temp->image,$this->_owner->image,0,0,$width-$this->offset_x,$height-$this->offset_y,$this->offset_x,$this->offset_y);
            imagecopy($temp->image,$this->_owner->image,0,$this->offset_y,$width-$this->offset_x,0,$this->offset_x,$height-$this->offset_y);
            imagecopy($temp->image,$this->_owner->image,$this->offset_x,0,0,$height-$this->offset_y,$width-$this->offset_x,$this->offset_y);

            $this->_owner->image = $temp->image;
            
            unset($temp);

            return true;
        
        }

    }
