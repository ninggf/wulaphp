<?php
class image_fx_mosaic extends image_plugin_base implements image_plugin {
	public $type_id = "effect";
	public $sub_type_id = "mosaic";
	public $version = 0.1;
	public $pos = 'br';
	public $size;
	public function __construct($pos = 'br', $size = '200x200') {
		$this->pos = $pos;
		$size = explode ( 'x', $size );
		$this->size [0] = intval ( $size [0] );
		if (count ( $size ) == 1) {
			$this->size [1] = $this->size [0];
		} else {
			$this->size [1] = intval ( $size [1] );
		}
	}
	public function generate() {
		if ($this->size [0] == 0 || $this->size [1] == 0) {
			return true;
		}
		$width = $this->_owner->imagesx ();
		$height = $this->_owner->imagesy ();
		$watermark_width = $this->size [0];
		$watermark_height = $this->size [1];
		
		switch ($this->pos) {
			case "tl" :
				$x = 0;
				$y = 0;
				break;
			case "tm" :
				$x = ($width - $watermark_width) / 2;
				$y = 0;
				break;
			case "tr" :
				$x = $width - $watermark_width;
				$y = 0;
				break;
			case "ml" :
				$x = 0;
				$y = ($height - $watermark_height) / 2;
				break;
			case "mm" :
				$x = ($width - $watermark_width) / 2;
				$y = ($height - $watermark_height) / 2;
				break;
			case "mr" :
				$x = $width - $watermark_width;
				$y = ($height - $watermark_height) / 2;
				break;
			case "bl" :
				$x = 0;
				$y = $height - $watermark_height;
				break;
			case "bm" :
				$x = ($width - $watermark_width) / 2;
				$y = $height - $watermark_height;
				break;
			case "br" :
				$x = $width - $watermark_width;
				$y = $height - $watermark_height;
				break;
			default :
				$x = 0;
				$y = 0;
				break;
		}
		$crop = new image ();
		$crop->createImageTrueColorTransparent ( $watermark_width, $watermark_height );
		imagecopy ( $crop->image, $this->_owner->image, 0, 0, $x, $y, $watermark_width, $watermark_height );
		
		$crop->attach ( new image_fx_jitter ( 6 ) );
		$crop->evaluateFXStack ();
		imagecopy ( $this->_owner->image, $crop->image, $x, $y, 0, 0, $watermark_width, $watermark_height );
		$crop->destroyImage ();
		return true;
	}
}