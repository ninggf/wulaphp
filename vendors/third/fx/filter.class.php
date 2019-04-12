<?php

    class image_fx_filter extends image_plugin_base implements image_plugin {

        public $type_id = "effect";
        public $sub_type_id = "filter";
        public $version = 0.1;
        
//-----------------------------------------------------------------------------

        public function __construct($filter=IMG_FILTER_NEGATE, $arg1=0, $arg2=0, $arg3=0, $arg4=0) {
        
            $this->filter = $filter;
            $this->arg1 = $arg1;
            $this->arg2 = $arg2;
            $this->arg3 = $arg3;
        
        }

        public function generate() {

            switch ($this->filter) {
            
                case IMG_FILTER_NEGATE:
                    imagefilter($this->_owner->image, IMG_FILTER_NEGATE);
                    break;
                case IMG_FILTER_GRAYSCALE:
                    imagefilter($this->_owner->image, IMG_FILTER_GRAYSCALE);
                    break;
                case IMG_FILTER_BRIGHTNESS:
                    imagefilter($this->_owner->image, IMG_FILTER_BRIGHTNESS, $this->arg1);
                    break;
                case IMG_FILTER_CONTRAST:
                    imagefilter($this->_owner->image, IMG_FILTER_CONTRAST, $this->arg1);
                    break;
                case IMG_FILTER_COLORIZE:
                    imagefilter($this->_owner->image, IMG_FILTER_COLORIZE, $this->arg1, $this->arg2, $this->arg3, $this->arg3);
                    break;
                case IMG_FILTER_EDGEDETECT:
                    imagefilter($this->_owner->image, IMG_FILTER_EDGEDETECT);
                    break;
                case IMG_FILTER_EMBOSS:
                    imagefilter($this->_owner->image, IMG_FILTER_EMBOSS);
                    break;
                case IMG_FILTER_GAUSSIAN_BLUR:
                    imagefilter($this->_owner->image, IMG_FILTER_GAUSSIAN_BLUR);
                    break;
                case IMG_FILTER_SELECTIVE_BLUR:
                    imagefilter($this->_owner->image, IMG_FILTER_SELECTIVE_BLUR);
                    break;
                case IMG_FILTER_MEAN_REMOVAL:
                    imagefilter($this->_owner->image, IMG_FILTER_MEAN_REMOVAL);
                    break;
                case IMG_FILTER_SMOOTH:
                    imagefilter($this->_owner->image, IMG_FILTER_SMOOTH, $this->arg1);
                    break;    
                default:
                    return false;
                    break;

            }
            
        }

    }
