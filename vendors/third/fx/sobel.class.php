<?php

    class image_fx_sobel extends image_plugin_base implements image_plugin {

        public $type_id = "effect";
        public $sub_type_id = "sobel";
        public $version = 0.1;

//-----------------------------------------------------------------------------

        public function __construct() {
                
        }

        public function generate() {
        
            $width = $this->_owner->imagesx();
            $height = $this->_owner->imagesy();

            $mx = array(array(-0,0,0),
                        array(-1,0,1),
                        array(-0,0,0)
                      );

            $my = array(array(0,1,0),
                        array(0,0,0),
                        array(-0,-1,-0)
                      );

            $m_elements = count($mx);
            $m_offset = floor($m_elements/2);
            
            $p = array();

            for ($y=0;$y<$height;$y++) {
                for ($x=0;$x<$width;$x++) {
                    $rgb = $this->_owner->imagecolorat($x,$y);
                    $arrRgb = image::intColorToArrayColor($rgb);
                    $p[$x][$y]['r'] = $arrRgb['red'];
                    $p[$x][$y]['g'] = $arrRgb['green'];
                    $p[$x][$y]['b'] = $arrRgb['blue'];
                }
            }

            for($y=1; $y<$height-1; $y++)  {
                for($x=1; $x<$width-1; $x++)  {

                    $sumXr = 0;
                    $sumXg = 0;
                    $sumXb = 0;

                    $sumYr = 0;
                    $sumYg = 0;
                    $sumYb = 0;

                    for($i=-1; $i<=1; $i++)  {
                        for($j=-1; $j<=1; $j++)  {
                            $sumXr = $sumXr + (($p[$x+$i][$y+$j]['r']) * $mx[$i+1][$j+1]);
                            $sumXg = $sumXg + (($p[$x+$i][$y+$j]['g']) * $mx[$i+1][$j+1]);
                            $sumXb = $sumXb + (($p[$x+$i][$y+$j]['b']) * $mx[$i+1][$j+1]);

                            $sumYr = $sumYr + (($p[$x+$i][$y+$j]['r']) * $my[$i+1][$j+1]);
                            $sumYg = $sumYg + (($p[$x+$i][$y+$j]['g']) * $my[$i+1][$j+1]);
                            $sumYb = $sumYb + (($p[$x+$i][$y+$j]['b']) * $my[$i+1][$j+1]);
                        }
                    }

                    $sumr = abs($sumXr) + abs($sumYr);
                    $sumg = abs($sumXg) + abs($sumYg);
                    $sumb = abs($sumXb) + abs($sumYb);

                    if($sumr>255) $sumr=255;
                    if($sumg>255) $sumg=255;
                    if($sumb>255) $sumb=255;

                    if($sumr<0) $sumr=0;
                    if($sumg<0) $sumg=0;
                    if($sumb<0) $sumb=0;

                    $color = imagecolorallocate($this->_owner->image,255-$sumr,255-$sumg,255-$sumb);

                    imagesetpixel($this->_owner->image,$x,$y,$color);

                }
            }

            return true;
        
        }

    }
