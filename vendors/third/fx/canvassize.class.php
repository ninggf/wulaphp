<?php

    class image_fx_canvassize extends image_plugin_base implements image_plugin {

        public $type_id = "effect";
        public $sub_type_id = "canvassize";
        public $version = 0.1;

//-----------------------------------------------------------------------------

        public function __construct($t=10, $r=10, $b=10, $l=10, $color="") {
        
            $this->t = $t;
            $this->r = $r;
            $this->b = $b;
            $this->l = $l;
            $this->color = $color;
        
        }

        public function generate() {

            $width = $this->_owner->imagesx();
            $height = $this->_owner->imagesy();

            $temp = new Image();

            if (!empty($this->color)) {
                $temp->createImageTrueColor($width+($this->r+$this->l),$height+($this->t+$this->b));
                $arrColor = image::hexColorToArrayColor($this->color);
                $tempcolor = imagecolorallocate($temp->image,$arrColor['red'],$arrColor['green'],$arrColor['blue']);
                imagefilledrectangle($temp->image, 0,0, $temp->imagesx(), $temp->imagesy(), $tempcolor);
            } else {
                $temp->createImageTrueColorTransparent($width+($this->r+$this->l),$height+($this->t+$this->b));
            }
            
            imagecopy($temp->image, $this->_owner->image, $this->l, $this->t, 0, 0, $width, $height);

            $this->_owner->image = $temp->image;
            
            unset($temp);

            return true;
        
        }

    }
