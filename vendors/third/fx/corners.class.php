<?php

    class image_fx_corners extends image_plugin_base implements image_plugin {

        public $type_id = "effect";
        public $sub_type_id = "corners";
        public $version = 0.1;

//-----------------------------------------------------------------------------

        public function __construct($radius_x=0, $radius_y=0) {
        
            $this->radius_x = $radius_x;
            $this->radius_y = $radius_y;
        
        }

        public function setRadius($x=10, $y=10) {

            $this->radius_x = $x;
            $this->radius_y = $y;

        }
        
        function generate() {
        
            imagesavealpha($this->_owner->image, true);
            imagealphablending($this->_owner->image,false);
            
            $image_x = $this->_owner->imagesx();
            $image_y = $this->_owner->imagesy();

            $gdCorner = imagecreatefromstring(base64_decode($this->_cornerpng()));

            $corner = new Image();
            $corner->createImageTrueColorTransparent($this->radius_x, $this->radius_y);
            imagecopyresampled($corner->image, $gdCorner, 0,0, 0,0, $this->radius_x,$this->radius_y, imagesx($gdCorner),imagesy($gdCorner));

            $corner_x = $this->radius_x-1;
            $corner_y = $this->radius_y-1;

            for ($y=0;$y<$corner_y;$y++) {

                for ($x=0;$x<$corner_x;$x++) {

                    for ($c=0; $c<4; $c++) {

                        switch ($c) {
                            case 0:
                                $xo = 0; $yo = 0;
                                $cxo = $x; $cyo = $y;
                            break;
                            case 1:
                                $xo = ($image_x-$corner_x); $yo = 0;
                                $cxo = $corner_x-$x; $cyo = $y;
                            break;
                            case 2:
                                $xo = ($image_x-$corner_x); $yo = ($image_y-$corner_y);
                                $cxo = $corner_x-$x; $cyo = $corner_y-$y;
                            break;
                            case 3:
                                $xo = 0; $yo = ($image_y-$corner_y);
                                $cxo = $x; $cyo = $corner_y-$y;
                            break;
                        }

                        $irgb = imagecolorat($this->_owner->image, $xo+$x, $yo+$y);
                        $r   = ($irgb >> 16) & 0xFF;
                        $g   = ($irgb >> 8) & 0xFF;
                        $b   =  $irgb & 0xFF;

                        $crgb = imagecolorat($corner->image,$cxo,$cyo);
                        $a = ($crgb >> 24) & 0xFF;

                        $colour = imagecolorallocatealpha($this->_owner->image, $r, $g, $b, $a);

                        switch ($c) {

                            case 0: imagesetpixel($this->_owner->image,$x,$y,$colour);         break;
                            case 1: imagesetpixel($this->_owner->image,$xo+$x,$y,$colour);     break;
                            case 2: imagesetpixel($this->_owner->image,$xo+$x,$yo+$y,$colour); break;
                            case 3: imagesetpixel($this->_owner->image,$x,$yo+$y,$colour);     break;

                        }

                    }

                }

            }

        }

//-----------------------------------------------------------------------------

        private function _cornerpng() {

            $c  = "iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAYAAACM/rhtAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29m";
            $c .= "dHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAH2SURBVHjaYvz//z/DQAFGRkZNIAXCakCsBMSyQCwJ";
            $c .= "xCJAzA/EnAABxEJnB8kDKWsgtgBiMyA2AmJWfHoAAoiFDo4ChYQ7ELsBsTMQK5CiHyCAWGjoMBUgFQDE";
            $c .= "fkBsS645AAHEQgOHKQKpCCAOA2IDSs0DCCAGUCahBgYCDiBOB+KjQPyfWhgggKjlODsgXkxVh0ExQABR";
            $c .= "6jBGIM4B4ms0cRwQAwQQJY4D5cYJNHMYFAMEELmOA5Vhq2nuOCAGCCByHOcCxPvo4jggBgggUh3nAcTH";
            $c .= "6eY4IAYIIFJDjr6OA2KAACIlze2ju+OAGCCAiM2tqwfEcUAMEEDElHMTBsxxQAwQQIQcmDOgjgNigAAi";
            $c .= "VH1dG2gHAgQQvop/8YA7DogBAgiXA9MHheOAGCCAmHC05+IYBgkACCAmLGKgxqbVYHEgQAAxYWmmhzEM";
            $c .= "IgAQQOghGECVZjoVAUAAMaH1vvwYBhkACCDknBs2WHIuMgYIIOQodmMYhAAggJiQevzOg9GBAAEEC0Fr";
            $c .= "Unv89AIAAQRzoAXDIAUAAQRzoNlgdSBAADFAh79+DcYcDMIAAcQEdSDrYA09gABigg4eDloAEEBM0JHN";
            $c .= "QQsAAogJOuw6aAFAADFBx4QHLQAIICbogPWgBQABxAQdTR+0ACCAQP3eP0DMPFgdCBBgAJ273bQUqcwV";
            $c .= "AAAAAElFTkSuQmCC";

            return $c;

        }

    }

