<?php

    class image_draw_border extends image_plugin_base implements image_plugin {

        public $type_id = "draw";
        public $sub_type_id = "border";
        public $version = 0.1;

//-----------------------------------------------------------------------------

        public function __construct($padding=10, $color="000000") {
        
            $this->padding = $padding;
            $this->color = $color;
        
        }

        public function setBorder($padding=10, $color="000000") {

            $this->padding = $padding;
            $this->color = $color;

        }

        public function setPadding($padding=10) {

            $this->padding = $padding;

        }

        public function setColor($color="000000") {

            $this->color = $color;

        }

        public function generate() {
        
            $width = $this->_owner->imagesx();
            $height = $this->_owner->imagesy();
            
            $padding = $this->padding;

            $arrColor = image::hexColorToArrayColor($this->color);

            $temp = new Image();
            $temp->createImageTrueColor($width+($padding*2),$height+($padding*2));
            $tempcolor = imagecolorallocate($temp->image,$arrColor['red'],$arrColor['green'],$arrColor['blue']);
            imagefill($temp->image, 0,0, $tempcolor);
            
            imagecopy($temp->image,$this->_owner->image,$padding,$padding,0,0,$width, $height);

            $this->_owner->image = $temp->image;
            
            unset($temp);

            return true;
        
        }

    }
