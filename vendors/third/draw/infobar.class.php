<?php

    class image_draw_infobar extends image_plugin_base implements image_plugin {

        public $type_id = "draw";
        public $sub_type_id = "info";
        public $version = 0.1;

//-----------------------------------------------------------------------------

        public function __construct($info="[Filename]", $position="b", $justify="c", $barcolor="000000", $textcolor="FFFFFF") {
        
            $this->info = $info;
            $this->position = $position;
            $this->justify = $justify;
            
            $this->barcolor = $barcolor;
            $this->textcolor = $textcolor;

            $this->font = 2;
        
        }
        
        public function generate() {
        
            $src_x = $this->_owner->imagesx();
            $src_y = $this->_owner->imagesy();

            $temp = new image();
            $temp->createImageTrueColorTransparent($src_x, $src_y+20);
            
            $text = str_replace("[Filename]", $this->_owner->filename, $this->info);
            
            switch ($this->position) {

                case "t": $x = 0; $y = 20; $bar_y = 0; $text_y = 3; break;
                case "b": $x = 0; $y = 0; $bar_y = $src_y+20; $text_y = $bar_y-20+3; break;
                default:  return false; break;
                
            }
            
            switch ($this->justify) {
            
                case "l": $text_x = 3; break;
                case "c": $text_x = ($src_x - (imagefontwidth($this->font)*strlen($text)))/2; break;
                case "r": $text_x = $src_x-3-(imagefontwidth($this->font)*strlen($text)); break;
            
            }

            //Draw the bar background
            $arrColor = $this->_owner->hexColorToArrayColor($this->barcolor);
            $bar_color = imagecolorallocate($temp->image,$arrColor['red'],$arrColor['green'],$arrColor['blue']);
            imagefilledrectangle($temp->image, 0, $bar_y, $src_x, 20, $bar_color);

            //Copy the image
            imagecopy($temp->image,$this->_owner->image,$x,$y,0,0,$src_x, $src_y);

            //Draw the text (to be replaced with image_draw_text one day
            $arrColor = $this->_owner->hexColorToArrayColor($this->textcolor);
            $text_color = imagecolorallocate($temp->image,$arrColor['red'],$arrColor['green'],$arrColor['blue']);
            imagestring($temp->image, $this->font, $text_x, $text_y, $text, $text_color);

            $this->_owner->image = $temp->image;

            unset($temp);

            return true;
        
        }

    }
