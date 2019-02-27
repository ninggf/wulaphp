<?php

interface image_plugin {
	public function attachToOwner($owner);

	public function getTypeId();

	public function getSubTypeId();

	public function getVersion();

	public function generate();
}

class image_plugin_base {
	protected $_owner;
	protected $type_id;
	protected $sub_type_id;
	protected $version;

	public function attachToOwner($owner) {
		$this->_owner = $owner;
	}

	public function getTypeId() {
		return $this->type_id;
	}

	public function getSubTypeId() {
		return $this->sub_type_id;
	}

	public function getVersion() {
		return $this->version;
	}
}

class image {
	public    $image;
	public    $imageType;
	protected $mid_handle;
	protected $gd_info;
	protected $gd_version        = '';
	protected $gd_support_gif    = false;
	protected $gd_support_png    = false;
	protected $gd_support_jpg    = false;
	protected $gd_support_ttf    = false;
	protected $settings          = [];
	protected $attachments       = [];
	protected $attachments_stack = [];

	protected $filepath;
	protected $filename;
	protected $filesize_bytes;

	protected $filesize_formatted;

	protected $original_width  = 0;
	protected $original_height = 0;

	// -----------------------------------------------------------------------------
	public function __construct() {
		$this->_detectGD();

		$this->mid_handle = true; // Set as false to use the top left corner as
		// the handle.

		$args = func_get_args();
		if (count($args) == 1) {
			if (!empty ($args [0])) {
				$this->openImage($args [0]);
			}
		} else if (count($args) == 2) {
			$this->createImageTrueColor($args [0], $args [1]);
		}
	}

	// -----------------------------------------------------------------------------
	public function attach($child) {
		if (!($child instanceof image_plugin)) {
			return false;
		}

		$type = $child->getTypeId();
		if (array_key_exists($type, $this->attachments)) {
			$this->attachments [ $type ]++;
		} else {
			$this->attachments [ $type ] = 1;
		}
		$id = "a_" . $type . "_" . $this->attachments [ $type ];

		$this->attachments_stack [ $id ] = $child;
		$this->attachments_stack [ $id ]->attachToOwner($this);

		return $id;
	}

	public function detach($id) {
		if (isset ($this->attachments_stack [ $id ])) {
			unset ($this->attachments_stack [ $id ]);
		}
	}

	public function evaluateFXStack() {
		if (is_array($this->attachments_stack)) {
			foreach ($this->attachments_stack as $id => $attachment) {
				switch ($attachment->getTypeId()) {
					case "effect" :
						$attachment->generate();
						break;
					case "draw" :
						$attachment->generate();
						break;
				}
			}
		}

		return true;
	}

	// -----------------------------------------------------------------------------
	public function createImage($x = 100, $y = 100, $color = "FFFFFF") {
		$this->image = imagecreate($x, $y);
		if (!empty ($color)) {
			$this->imagefill(0, 0, $color);
		}
	}

	public function createImageTrueColor($x = 100, $y = 100, $color = "FFFFFF") {
		$this->image = imagecreatetruecolor($x, $y);
		if (!empty ($color)) {
			$this->imagefill(0, 0, $color);
		}
	}

	public function createImageTrueColorTransparent($x = 100, $y = 100) {
		$this->image = imagecreatetruecolor($x, $y);
		$blank       = imagecreatefromstring(base64_decode($this->_blankpng()));
		imagesavealpha($this->image, true);
		imagealphablending($this->image, false);
		imagecopyresized($this->image, $blank, 0, 0, 0, 0, $x, $y, imagesx($blank), imagesy($blank));
		imagedestroy($blank);
	}

	public function openImage($filename = "") {
		if (file_exists($filename)) {
			$image_data = getimagesize($filename);
			if ($image_data) {
				$this->imageType = $image_data [2];
				switch ($image_data [2]) { // Element 2 refers to the image type

					case IMAGETYPE_GIF :
						if ($this->gd_support_gif) {
							$this->image = @imagecreatefromgif($filename);
							$this->_file_info($filename);

							return true;
						} else {
							return false;
						}
						break;
					case IMAGETYPE_PNG :
						if ($this->gd_support_png) {
							$this->image = @imagecreatefrompng($filename);
							$this->_file_info($filename);

							return true;
						} else {
							return false;
						}
						break;
					case IMAGETYPE_JPEG :
						if ($this->gd_support_jpg) {
							$this->image = @imagecreatefromjpeg($filename);
							$this->_file_info($filename);

							return true;
						} else {
							return false;
						}
						break;
					default :
						return false;
						break;
				}
			} else {

				// getimagesize failed
				return false;
			}
		} else {

			// file_exists failed
			return false;
		}
	}

	// -----------------------------------------------------------------------------
	public function sendHeader($image_format = IMAGE_TYPE_PNG) {
		switch ($image_format) {

			case IMAGE_TYPE_GIF :
				header("Content-type: image/gif");

				return true;
				break;
			case IMAGE_TYPE_PNG :
				header("Content-type: image/png");

				return true;
				break;
			case IMAGE_TYPE_JPEG :
				header("Content-type: image/jpeg");

				return true;
				break;
			default :
				return false;
		}
	}

	public function imageGif($filename = "") {
		if (!isset ($this->image)) {
			return false;
		}

		$this->evaluateFXStack();

		if ($this->gd_support_gif) {
			if (!empty ($filename)) {
				return imagegif($this->image, $filename);
			} else {
				if ($this->sendHeader(IMAGE_TYPE_GIF)) {
					return imagegif($this->image);
				}
			}
		} else {

			return false;
		}
	}

	public function imagePng($filename = "") {
		if (!isset ($this->image)) {
			return false;
		}

		$this->evaluateFXStack();

		if ($this->gd_support_png) {
			if (!empty ($filename)) {
				return imagepng($this->image, $filename);
			} else {
				if ($this->sendHeader(IMAGE_TYPE_PNG)) {
					return imagepng($this->image);
				}
			}
		}

		return false;
	}

	public function imageJpeg($filename = "", $quality = 80) {
		if (!isset ($this->image)) {
			return false;
		}

		$this->evaluateFXStack();
		if ($this->gd_support_jpg) {
			if (!empty ($filename)) {
				return imagejpeg($this->image, $filename, $quality);
			} else {
				if ($this->sendHeader(IMAGE_TYPE_JPEG)) {
					return imagejpeg($this->image, "", $quality);
				}
			}
		}

		return false;
	}

	public function save($filename, $quality = 80) {
		if ($this->image) {
			error_get_last();
			switch ($this->imageType) {
				case IMAGETYPE_GIF :
					return $this->imageGif($filename);
				case IMAGETYPE_PNG :
					return $this->imagePng($filename);
				case IMAGETYPE_JPEG :
					return $this->imageJpeg($filename, $quality);
				default :
					return false;
			}
		}

		return false;
	}

	public function destroyImage() {
		if (!isset ($this->image)) {
			return false;
		}

		imagedestroy($this->image);
		unset ($this->image);

		return true;
	}

	// -----------------------------------------------------------------------------
	public function imagesx() {
		if (!isset ($this->image)) {
			return false;
		}

		return imagesx($this->image);
	}

	public function imagesy() {
		if (!isset ($this->image)) {
			return false;
		}

		return imagesy($this->image);
	}

	public function imageIsTrueColor() {
		if (!isset ($this->image)) {
			return false;
		}

		return imageistruecolor($this->image);
	}

	public function imageColorAt($x = 0, $y = 0) {
		if (!isset ($this->image)) {
			return false;
		}

		$color = imagecolorat($this->image, $x, $y);

		if (!$this->imageIsTrueColor()) {
			$arrColor = imagecolorsforindex($this->image, $color);

			return $this->arrayColorToIntColor($arrColor);
		} else {
			return $color;
		}
	}

	// -----------------------------------------------------------------------------
	public function imagefill($x = 0, $y = 0, $color = "FFFFFF") {
		if (!isset ($this->image)) {
			return false;
		}

		$arrColor = image::hexColorToArrayColor($color);
		$bgcolor  = imagecolorallocate($this->image, $arrColor ['red'], $arrColor ['green'], $arrColor ['blue']);
		imagefill($this->image, 0, 0, $bgcolor);
	}

	public function imagecolorallocate($color = "FFFFFF") {
		$arrColor = image::hexColorToArrayColor($color);

		return imagecolorallocate($this->image, $arrColor ['red'], $arrColor ['green'], $arrColor ['blue']);
	}

	// -----------------------------------------------------------------------------
	public function displace($map) {
		$width  = $this->imagesx();
		$height = $this->imagesy();

		$temp = new image ($width, $height);

		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$rgb    = $this->imageColorAt($map ['x'] [ $x ] [ $y ], $map ['y'] [ $x ] [ $y ]);
				$arrRgb = image::intColorToArrayColor($rgb);
				$col    = imagecolorallocatealpha($temp->image, $arrRgb ['red'], $arrRgb ['green'], $arrRgb ['blue'], $arrRgb ['alpha']);
				imagesetpixel($temp->image, $x, $y, $col);
			}
		}

		$this->image = $temp->image;

		return true;
	}

	public function testImageHandle() {
		if (isset ($this->image)) {
			if (get_resource_type($this->image) == "gd") {
				return true;
			}
		}

		return false;
	}

	// -----------------------------------------------------------------------------
	public static function arrayColorToIntColor($arrColor = [0, 0, 0]) {
		$intColor = (($arrColor ['alpha'] & 0xFF) << 24) | (($arrColor ['red'] & 0xFF) << 16) | (($arrColor ['green'] & 0xFF) << 8) | (($arrColor ['blue'] & 0xFF) << 0);

		return $intColor;
	}

	public static function arrayColorToHexColor($arrColor = [0, 0, 0]) {
		$intColor = image::arrayColorToIntColor($arrColor);
		$hexColor = image::intColorToHexColor($intColor);

		return $hexColor;
	}

	public static function intColorToArrayColor($intColor = 0) {
		$arrColor ['alpha'] = ($intColor >> 24) & 0xFF;
		$arrColor ['red']   = ($intColor >> 16) & 0xFF;
		$arrColor ['green'] = ($intColor >> 8) & 0xFF;
		$arrColor ['blue']  = ($intColor) & 0xFF;

		return $arrColor;
	}

	public static function intColorToHexColor($intColor = 0) {
		$arrColor = image::intColorToArrayColor($intColor);
		$hexColor = image::arrayColorToHexColor($arrColor);

		return $hexColor;
	}

	public static function hexColorToArrayColor($hexColor = "000000") {
		$arrColor ['red']   = hexdec(substr($hexColor, 0, 2));
		$arrColor ['green'] = hexdec(substr($hexColor, 2, 2));
		$arrColor ['blue']  = hexdec(substr($hexColor, 4, 2));

		return $arrColor;
	}

	public static function hexColorToIntColor($intColor = "000000") {
		$arrColor = image::hexColorToArrayColor($intColor);
		$intColor = image::arrayColorToIntColor($arrColor);

		return $intColor;
	}

	// -----------------------------------------------------------------------------
	public function __get($name) {
		if ($name == "image") {
			return $this->image;
		}

		if ($name == "handle_x") {
			return ($this->mid_handle == true) ? floor($this->imagesx() / 2) : 0;
		}
		if ($name == "handle_y") {
			return ($this->mid_handle == true) ? floor($this->imagesy() / 2) : 0;
		}

		if (substr($name, 0, 2) == "a_") {
			return $this->attachments_stack [ $name ];
		} else if (array_key_exists($name, $this->settings)) {
			return $this->settings [ $name ];
		} else {
			return false;
		}
	}

	public function __set($name, $value) {
		if ($name == "image") {
			$this->image = $value;
		} else if (substr($name, 0, 2) == "a_") {
			$this->attachments_stack [ $name ] = $value;
		} else {
			$this->settings [ $name ] = $value;
		}
	}

	// -----------------------------------------------------------------------------
	private function _detectGD() {
		$this->gd_info = gd_info();
		preg_match('/\d+/', $this->gd_info ['GD Version'], $match);
		$this->gd_version     = $match [0];
		$this->gd_support_gif = $this->gd_info ['GIF Create Support'];
		$this->gd_support_png = $this->gd_info ['PNG Support'];
		$this->gd_support_jpg = isset ($this->gd_info ['JPG Support']) ? $this->gd_info ['JPG Support'] : $this->gd_info ['JPEG Support'];
		$this->gd_support_ttf = $this->gd_info ['FreeType Support'];
	}

	private function _file_info($filename) {
		$ext   = ['B', 'KB', 'MB', 'GB'];
		$round = 2;

		$this->filepath       = $filename;
		$this->filename       = basename($filename);
		$this->filesize_bytes = filesize($filename);

		$size = $this->filesize_bytes;
		for ($i = 0; $size > 1024 && $i < count($ext) - 1; $i++) {
			$size /= 1024;
		}
		$this->filesize_formatted = round($size, $round) . $ext [ $i ];

		$this->original_width  = $this->imagesx();
		$this->original_height = $this->imagesy();
	}

	private function _blankpng() {
		$c = "iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAYAAACM/rhtAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29m";
		$c .= "dHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAADqSURBVHjaYvz//z/DYAYAAcTEMMgBQAANegcCBNCg";
		$c .= "dyBAAA16BwIE0KB3IEAADXoHAgTQoHcgQAANegcCBNCgdyBAAA16BwIE0KB3IEAADXoHAgTQoHcgQAAN";
		$c .= "egcCBNCgdyBAAA16BwIE0KB3IEAADXoHAgTQoHcgQAANegcCBNCgdyBAAA16BwIE0KB3IEAADXoHAgTQ";
		$c .= "oHcgQAANegcCBNCgdyBAAA16BwIE0KB3IEAADXoHAgTQoHcgQAANegcCBNCgdyBAAA16BwIE0KB3IEAA";
		$c .= "DXoHAgTQoHcgQAANegcCBNCgdyBAgAEAMpcDTTQWJVEAAAAASUVORK5CYII=";

		return $c;
	}
}

function __image_class_load($file, $clz) {
	static $path = false;
	if (!$path) {
		$path = dirname(__FILE__) . DS;
	}
	$m = [];
	if (preg_match('/^image_fx_(.+)/', $clz, $m)) {
		return $path . 'fx' . DS . $m [1] . '.class.php';
	} else if (preg_match('/^image_draw_(.+)/', $clz, $m)) {
		return $path . 'draw' . DS . $m [1] . '.class.php';
	}

	return $file;
}

bind('loader\loadClass', '__image_class_load', 1000, 2);
