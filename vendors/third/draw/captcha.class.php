<?php

    class image_draw_captcha extends image_plugin_base implements image_plugin {

        public $type_id = "draw";
        public $sub_type_id = "captcha";
        public $version = 0.1;
        
        private $arr_ttf_font = array();

//-----------------------------------------------------------------------------

        public function __construct($password="") {
        
            $this->password = $password;
            $this->text_size = 15;
            $this->text_size_random = 0;
            $this->text_angle_random = 0;
            $this->text_spacing = 5;

        }

        public function addTTFFont($font="") {
        
            if (file_exists($font)) {            
                $this->arr_ttf_font[] = $font;
                return true;
            } else {
                return false;
            }
                
        }

        public function generate() {

            imagesavealpha($this->_owner->image, true);
            imagealphablending($this->_owner->image, true);

            $width = $this->_owner->imagesx();
            $height = $this->_owner->imagesy();
            
            $white = imagecolorallocate($this->_owner->image, 0,0,0);

            $l = array();
            $total_width = 0;

            for ($x=0; $x<strlen($this->password); $x++) {
                
                $l[$x]['text'] = $this->password[$x];
                $l[$x]['font'] = $this->arr_ttf_font[rand(0, count($this->arr_ttf_font)-1)];
                $l[$x]['size'] = rand($this->text_size,$this->text_size+$this->text_size_random);
                $l[$x]['angle'] = ($this->text_angle_random/2)-rand(0,$this->text_angle_random);

                $captcha_dimensions = imagettfbbox($l[$x]['size'], $l[$x]['angle'], $l[$x]['font'], $l[$x]['text']);
                
                $l[$x]['width'] = abs($captcha_dimensions[2])+$this->text_spacing;
                $l[$x]['height'] = abs($captcha_dimensions[5]);
                
                $total_width += $l[$x]['width'];

            }

            $x_offset = ($width-$total_width)/2;
            $x_pos = 0;
            $y_pos = 0;

            foreach ($l as $p => $ld) {            
            
                $y_pos = ($height+$ld['height'])/2;
            
                imagettftext($this->_owner->image, $ld['size'], $ld['angle'], $x_offset+$x_pos, $y_pos, $white, $ld['font'], $ld['text']);
                $x_pos += $ld['width'];
            
            }

        }

    }
