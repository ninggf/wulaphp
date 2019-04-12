<?php
class image_draw_watermark extends image_plugin_base implements image_plugin {
	public $type_id = "draw";
	public $sub_type_id = "watermark";
	public $version = 0.1;
	public $trans;
	// -----------------------------------------------------------------------------
	public function __construct(image $watermark = NULL, $position = "br", $trans = '0x0') {
		$this->watermark = $watermark;
		$this->position = $position;
		$this->trans = explode ( 'x', $trans );
		if (count ( $this->trans ) == 1) {
			$this->trans [1] = $this->trans [0];
		}
	}
	public function setPosition() {
		$args = func_get_args ();
		
		if (count ( $args ) == 1) {
			$this->position = $args [0];
		} elseif (count ( $args ) == 2) {
			$this->position = "user";
			$this->position_x = $args [0];
			$this->position_y = $args [1];
		}
	}
	public function generate() {
		imagesavealpha ( $this->_owner->image, true );
		imagealphablending ( $this->_owner->image, true );
		
		imagesavealpha ( $this->watermark->image, false );
		imagealphablending ( $this->watermark->image, false );
		
		$width = $this->_owner->imagesx ();
		$height = $this->_owner->imagesy ();
		
		$watermark_width = $this->watermark->imagesx ();
		$watermark_height = $this->watermark->imagesy ();
		$transX = 0;
		$transY = 0;
		if ($this->trans [0] > 0) {
			$transX = rand ( 0, $this->trans [0] );
		}
		if ($this->trans [1] > 0) {
			$transY = rand ( 0, $this->trans [1] );
		}
		switch ($this->position) {
			
			case "tl" :
				$x = 0 + $transX;
				$y = 0 + $transY;
				break;
			case "tm" :
				$x = ($width - $watermark_width) / 2+$this->getRandom($transX);
				$y = 0 + $transY;
				break;
			case "tr" :
				$x = $width - $watermark_width-$transX;
				$y = 0 + $transY;
				break;
			case "ml" :
				$x = 0 + $transX;
				$y = ($height - $watermark_height) / 2+$this->getRandom($transY);
				break;
			case "mm" :
				$x = ($width - $watermark_width) / 2+$this->getRandom($transX);
				$y = ($height - $watermark_height) / 2+$this->getRandom($transY);
				break;
			case "mr" :
				$x = $width - $watermark_width-$transX;
				$y = ($height - $watermark_height) / 2+$this->getRandom($transY);
				break;
			case "bl" :
				$x = 0 + $transX;
				$y = $height - $watermark_height - $transY;
				break;
			case "bm" :
				$x = ($width - $watermark_width) / 2+$this->getRandom($transX);
				$y = $height - $watermark_height - $transY;
				break;
			case "br" :
				$x = $width - $watermark_width - $transX;
				$y = $height - $watermark_height - $transY;
				break;
			case "user" :
				$x = $this->position_x - ($this->watermark->handle_x / 2);
				$y = $this->position_y - ($this->watermark->handle_y / 2);
				break;
			default :
				$x = 0;
				$y = 0;
				break;
		}
		
		if ($this->position != "tile") {
			imagecopy ( $this->_owner->image, $this->watermark->image, $x, $y, 0, 0, $watermark_width, $watermark_height );
		} else {
			imagesettile ( $this->_owner->image, $this->watermark->image );
			imagefilledrectangle ( $this->_owner->image, 0, 0, $width, $height, IMG_COLOR_TILED );
		}
		
		return true;
	}
	private function getRandom($w) {
		return rand ( 0, 1 ) == 1 ? $w : - $w;
	}
}
