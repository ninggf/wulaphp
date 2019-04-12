<?php
class image_fx_crop extends image_plugin_base implements image_plugin {
	public $type_id = "effect";
	public $sub_type_id = "crop";
	public $version = 0.1;
	
	// -----------------------------------------------------------------------------
	public function __construct($left = 0, $top = 0, $crop_x = 0, $crop_y = 0) {
		$this->src_x = $left;
		$this->src_y = $top;
		$this->crop_x = $crop_x;
		$this->crop_y = $crop_y;
	}
	public function setCrop($crop_x = 0, $crop_y = 0) {
		$this->crop_x = $crop_x;
		$this->crop_y = $crop_y;
	}
	public function calculate() {
		$old_x = $this->_owner->imagesx ();
		$old_y = $this->_owner->imagesy ();
		
		$this->canvas_x = $old_x;
		$this->canvas_y = $old_y;
		
		// Calculate the cropping area
		if ($this->crop_x > 0) {
			if ($this->canvas_x > $this->crop_x) {
				$this->canvas_x = $this->crop_x;
			}
		}
		
		if ($this->crop_y > 0) {
			if ($this->canvas_y > $this->crop_y) {
				$this->canvas_y = $this->crop_y;
			}
		}
		
		return true;
	}
	public function generate() {
		$this->calculate ();
		
		$crop = new image ();
		$crop->createImageTrueColorTransparent ( $this->canvas_x, $this->canvas_y );
		
		$src_x = $this->src_x;
		$src_y = $this->src_y;
		
		imagecopy ( $crop->image, $this->_owner->image, 0, 0, $src_x, $src_y, $this->canvas_x, $this->canvas_y );
		
		$this->_owner->image = $crop->image;
		
		unset ( $crop );
		
		return true;
	}
}
