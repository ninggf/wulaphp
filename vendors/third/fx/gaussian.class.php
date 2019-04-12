<?php

    class image_fx_gaussian extends image_plugin_base implements image_plugin {

        public $type_id = "effect";
        public $sub_type_id = "gaussian";
        public $version = 0.1;

//-----------------------------------------------------------------------------

        public function __construct($matrix=array(1,4,8,16,8,4,1)) {
        
            $this->matrix = $matrix;
        
        }

        public function generate() {
        
            $width = $this->_owner->imagesx();
            $height = $this->_owner->imagesy();
            
            $matrix = $this->matrix;
            $matrix_width = count($matrix);
            $matrix_sum = array_sum($matrix);
            $c = 0;
            $m_offset = floor($matrix_width/2);

            for ($y=0;$y<$height;$y++) {
                for ($x=0;$x<$width;$x++) {
                    $t = $this->_owner->imagecolorat($x,$y);
                    $t1 = image::intColorToArrayColor($t);
                    $p[$x][$y]['r'] = $t1['red'];
                    $p[$x][$y]['g'] = $t1['green'];
                    $p[$x][$y]['b'] = $t1['blue'];
                    $p[$x][$y]['a'] = $t1['alpha'];
                    $p1[$x][$y]['r'] = 255;
                    $p1[$x][$y]['g'] = 255;
                    $p1[$x][$y]['b'] = 255;
                    $p1[$x][$y]['a'] = 127;
                }
            }

            $temp = new Image();
            $temp->createImageTrueColorTransparent($width,$height);

            imagesavealpha($temp->image, true);
            imagealphablending($temp->image, true);

            for($i=$m_offset;$i<$width-$m_offset;$i++){
                for($j=$m_offset;$j<$height-$m_offset;$j++){
                    
                    $sumr=0; $sumg=0; $sumb=0; $suma=0;
                    
                    for($k=0;$k<$matrix_width;$k++){
                        $xx = $i-(($matrix_width)>>1)+$k;
                        $sumr+=$p[$xx][$j]['r']*$matrix[$k];
                        $sumg+=$p[$xx][$j]['g']*$matrix[$k];
                        $sumb+=$p[$xx][$j]['b']*$matrix[$k];
                        $suma+=$p[$xx][$j]['a']*$matrix[$k];
                    }

                    $p1[$i][$j]['r'] = $sumr/$matrix_sum;
                    $p1[$i][$j]['g'] = $sumg/$matrix_sum;
                    $p1[$i][$j]['b'] = $sumb/$matrix_sum;
                    $p1[$i][$j]['a'] = $suma/$matrix_sum;

                }
            }

            for($i=$m_offset;$i<$width-$m_offset;$i++){
                for($j=$m_offset;$j<$height-$m_offset;$j++){
                
                    $sumr=0; $sumg=0; $sumb=0; $suma=0;
                    
                    for($k=0;$k<$matrix_width;$k++){
                        $xy = $j-(($matrix_width)>>1)+$k;
                        $sumr+=$p1[$i][$xy]['r']*$matrix[$k];
                        $sumg+=$p1[$i][$xy]['g']*$matrix[$k];
                        $sumb+=$p1[$i][$xy]['b']*$matrix[$k];
                        $suma+=$p1[$i][$xy]['a']*$matrix[$k];
                    }
                    
                    $col = imagecolorallocatealpha($temp->image,($sumr/$matrix_sum),($sumg/$matrix_sum),($sumb/$matrix_sum),($suma/$matrix_sum));
                    imagesetpixel($temp->image,$i,$j,$col);

                }
            }

            $this->_owner->image = $temp->image;
            
            unset($temp);

            return true;
        
        }

    }
