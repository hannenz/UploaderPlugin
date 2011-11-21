<?php
/*
 * image.php
 *
 * Copyright 2011 Johannes Braun <me@hannenz.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * Image Manipulation component for CakePHP as used in UploaderPlugin.
 * Allows resizing, cropping and desaturating JPG, PNG and GIF images.
 *
 */
class ImageComponent extends Component {

	private $filename;
	private $image;
	private $type;
	private $width;
	private $height;
	private $ratio;

	//~ function __construct($filename = null){
		//~ $this->image = null;
		//~ if ($filename){
			//~ $this->load($filename);
		//~ }
		//~ parent::__construct();
	//~ }

/* Load the image that will be used for further processing
 *
 * name: load
 * @param string $filename
 * 		Full path to the image file to be processed
 *
 * @return boolean
 * 		success
 */
	function load($filename){
		if (!file_exists($filename)){
			return (false);
		}
		$this->filename = $filename;
		$info = getimagesize($filename);
		$this->type = $info[2];
		switch ($this->type){
			case IMAGETYPE_JPEG:
				$this->image = imagecreatefromjpeg($filename);
				break;
			case IMAGETYPE_PNG:
				$this->image = imagecreatefrompng($filename);
				break;
			case IMAGETYPE_GIF:
				$this->image = imagecreatefromgif($filename);
				break;
			default:
				die ("Unknown Image Type!");
		}
		$this->width = imagesx($this->image);
		$this->height = imagesy($this->image);
		$this->ratio = $this->width / $this->height;
		return (true);
	}

/* Save the (propably) modified image to disk
 *
 * name: save
 * @param string $filename
 * 		Output filename
 * @param int $type:
 * 		IMAGE_TYPE_[JPEG|GIF|PNG], the output type
 * @param int $compression
 * 		Compression rate (JPG only)
 * @param int $permissions
 * 		Access permissions of the created file as UNIX mask
 * 		(null = don't change permissions, keep system's default)
 *
 * @return boolean
 * 		success
 */
	function save($filename = null, $type = null, $compression = 75, $permissions = null){
		if ($this->image == null){
			return (false);
		}
		if ($type == null){
			$type = $this->type;
		}
		if ($filename == null){
			$filename = $this->filename;
		}
		switch ($type){
			case IMAGETYPE_JPEG:
				imagejpeg($this->image, $filename, $compression);
				break;
			case IMAGETYPE_PNG:
				imagepng($this->image, $filename);
				break;
			case IMAGETYPE_GIF:
				imagegif($this->image, $filename);
				break;
		}

		if ($permissions != null){
			chmod($filename, $permissions);
		}
		return (true);
	}

/* Outputs an image directly to the browser (not saving to disk)
 *
 * name: out
 * @param int $type
 * 		IMAGE_TYPE_[JPEG|GIF|PNG]
 * @param int $compression
 * 		JPEG compression rate
 *
 * @return boolean
 * 		success
 */
	function out($type = null, $compression = 75){
		if ($this->image == null){
			return (false);
		}
		if ($type == null){
			$type = $this->type;
		}
		switch ($type){
			case IMAGETYPE_JPEG:
				header('content-type: image/jpeg');
				imagejpeg($this->image);
				break;
			case IMAGETYPE_PNG:
				header('content-type: image/png');
				imagepng($this->image);
				break;
			case IMAGETYPE_GIF:
				header('content-type: image/gif');
				imagegif($this->image);
				break;
		}
		return (true);
	}


/* Resize the image to the given width and/or height
 *
 * name: resize
 * @param int $width
 * 		The resulting width or null
 * @param $height
 * 		The resulting height or null
 * @param boolean  $shrinkOnlyn
 * 		Only resize larger images
 * 		:TODO: ot implemented yet !!!
 *
 * @return boolean
 * 		sucess
 */
	function resize($width = null, $height = null, $shrinkonly = true){
		/* :TODO: Shrinkonly! */
		if ($this->image == null){
			return (false);
		}
		if ($width == null){
			$width = $height * $this->ratio;
		}
		if ($height == null){
			$height = $width / $this->ratio;
		}
		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);

		$this->set_image($new_image);
		return (true);
	}

/* Scale the image by factor
 *
 * name: scale
 * @param float $factor:
 * 		Scaling factor as float (1.0 = no scaling)
 *
 * @return boolean
 * 		success
 */
	function scale($factor){
		return ($this->resize($this->width * $factor, $this->height * $factor));
	}

/* Crop image to a rectangle with the smaller dimension of the originals
 * image used as edge length. If width is given, the rectangle will be
 * resized afterwards
 *
 * name: crop
 * @param int $width
 * 		Resulting width
 * @param boolean $center
 * 		Crop from the image's center
 *
 * @return boolean
 * 		success
 */
	function crop($width = null, $center = true){
		if ($this->image == null){
			return (false);
		}

		$offset = ($center) ? (abs($this->width - $this->height) / 2) : 0;
		$length = $this->width > $this->height ? $this->height : $this->width;
		$new_image = imagecreatetruecolor($length, $length);

		if ($this->width > $this->height){
			imagecopy($new_image, $this->image, 0, 0, $offset, 0, $this->width, $this->height);
		}
		else {
			imagecopy($new_image, $this->image, 0, 0, 0, $offset, $this->width, $this->height);
		}

		$this->set_image($new_image);

		if ($width){
			return ($this->resize($width, null));
		}
		return (true);
	}

/* Desaturate the image
 *
 * name: desaturate
 *
 * @return boolean
 * 		success
 */
	function desaturate(){
		if ($this->image == null){
			return (false);
		}

		if (function_exists('imagefilter')){
			imagefilter($this->image, IMG_FILTER_GRAYSCALE);
		}
		else {
			imagetruecolortopalette($this->image, false, 256);
			for ($c = 0; $c < imagecolorstotal($this->image); $c++){
				$col = imagecolorsforindex($this->image, $c);
				$gray = round(0.299 * $col['red'] + 0.587 * $col['green'] + 0.114 * $col['blue']);
				imagecolorset($this->image, $c, $gray, $gray, $gray);
			}
		}
		return (true);
	}

/* Re-apply image properties (width, height and ratio) after
 * modification
 *
 * name: set_image
 * @param $im
 * 		The image
 */
	private function set_image($im){
		$this->image = $im;
		$this->width = imagesx($im);
		$this->height = imagesy($im);
		$this->ratio = $this->width / $this->height;
	}
}
