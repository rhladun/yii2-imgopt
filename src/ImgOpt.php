<?php
/**
 * @copyright Copyright (c) 2024 Intelligent System Design
 * @license Apache-2.0
 */
namespace rhladun\imgopt;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Image optimization widget for Yii2 Framework with auto WebP & AVIF image format generation from PNG/JPG files.
 *
 * What it does? Instead of static images like this:
 *
 * ```html
 * <img src="/images/product/extra.png" alt="Extra product">
 * ```
 *
 * It will generate an extra WebP & AVIF image files (in the same directory the provided
 * image is located) and serve it to your browser in HTML code, with a default
 * fallback to the original image for browsers that doesn't support WebP/AVIF images.
 *
 * Replace your IMG tag within your templates with a call to:
 *
 * ```php
 * <?= \rhladun\imgopt\ImgOpt::widget(["src" => "/images/product/extra.png", "alt" => "Extra product" ]) ?>
 * ```
 *
 *  And it will generate a WebP & AVIF image files (original image is left untouched) and
 *  the following HTML code gets generated:
 *
 * ```output html 
 * <picture>
  *     <source type="image/webp" srcset="/images/product/webp/extra.webp">
 *     <img src="/images/product/extra.png" alt="Extra product">
 * </picture>
 * ```
 * Example for using for img tag with rezizes 
 * ```php
 *   <?= \rhladun\ImgOpt\ImgOpt::widget(["src" => "/images/product/extra.png", "alt" => "Image 2", 'type_src'=>'srcset_webp', 'sizes' =>[576, 768, 992]]) ?> 
 * ```
 * 
 * ```output html
 *  <img src="/images/product/extra.png" srcset="/images/product/webp/4@576x414.webp 576w,/images/product/webp/extra@768x552.webp 768w,/images/product/webp/extra@992x713.webp 992w,/images/product/webp/extra.webp 10000w" alt="Image 2">  
 * ``` 
 * 
 * Example for using for replace background image
 * ```php
 *   <div style="<?= \rhladun\ImgOpt\ImgOpt::widget(["src" => "/images/product/extra.png", 'type_src'=>'background_webp']) ?>"></div> 
 * ```
 * 
 * ```output html
 *  <div class="image_block image_bg" style="background-image: url('/images/product/webp/extra.webp')"></div>  
  * ```
 *
 * @property string $src image source relative to the @webroot Yii2 alias (required)
 * @property string $type_src output format tags picture|srcset_webp|srcset_avif|background_webp|background_avif (required)
 * @property string $alt image alternative description used as alt="description" property (optional)
 * @property string $css image class list as a string (can contain multiple classes) used as class="one two three..." (optional)
 * @property string $style image custom CSS styles used as style="one; two; three;..." (optional)
 * @property string $loading lazy loading option (auto|lazy|eager) (https://web.dev/browser-level-image-lazy-loading/) (optional)
 * @property string $itemprop use schema itemprop="image" value (optional)
 * @property string $height  height used as height="value" (optional)
 * @property string $width width used as width="value" (optional)
 * @property bool $recreate set to TRUE to recreate the WebP file again (optional)
 * @property bool $disable set to TRUE to disable WebP images serving (optional)
 * @property string $_webp_path path to the stored WebP file format, (short path) or null (like "/webp") (optional)
 * @property string $_avif_path path to the stored AVIF file format, (short path) or null (like "/avif") (optional)
 * @property array auto resize images for specific  dimensions (array or null) (like [576, 768, 992, 1200]) (optional)
 *
 */
class ImgOpt extends Widget
{
	/**
	 * @var string image source relative to the @webroot Yii2 alias (required)
	 */
	public $src;

	/**
	 * @var array images list (required)
	 */	
	private $_items = null;

	/**
	 * @var string path to the stored WebP file format, (short path) or null (like "/webp")
	 */
	public $_webp_path = "/webp";

	/**
	 * @var string path to the stored AVIF file format, (short path) or null (like "/avif")
	 */
	public $_avif_path = "/avif";

	/**
	 * @var array resize images for specific  dimensions (array or []) (like [576, 768, 992, 1200])
	 */
	public $sizes = [];

	/**
	 * @var string image alternative description used as alt="description" property (optional)
	 */
	public $alt;

	/**
	 * @var string image class list as a string (can contain multiple classes) used as class="one two three..." (optional)
	 */
	public $css;

	/**
	 * @var string image custom CSS styles used as style="one; two; three;..." (optional)
	 */
	public $style;

	/**
	 * @var string lazy loading option (auto|lazy|eager) (https://web.dev/browser-level-image-lazy-loading/) (optional)
	 */
	public $loading;

	/**
	 * @var string use schema itemprop="image" value (optional)
	 */
	public $itemprop;

	/**
	 * @var string image height used as height="value" (optional)
	 */
	public $height;

	/**
	 * @var string image width used as width="value" (optional)
	 */
	public $width;

	/**
	 * @var bool set to TRUE to recreate the WebP and AVIF files again (optional)
	 */
	public $recreate = false;

	/**
	 * @var bool set to TRUE to disable WebP/AVIF images serving (optional)
	 */
	public $disable = false;

	/**
	 * @var string picture_webp|picture_avif|srcset_webp|srcset_avif|background_webp|background_avif 
	 */
	public $type_src = 'picture_webp';	

	/**
	 * Generates optimized WebP/AVIF file from the provided image, relative to the
	 * Yii2 @webroot file alias.
	 *
	 * @param string $img Relative path to the image in @webroot Yii2 directory
	 * @param bool $recreate Recreate the output file again
	 * @return string|null Path to the output image file (relative to @webroot) or null (marks usage of the original image only)
	 */
	private function get_or_convert_to_dest_format($img, $recreate = false, $resize_width = null)
	{

		$pre_path = '';

		if (in_array($this->type_src, array('picture_avif', 'srcset_avif', 'background_avif'))) {
			$file_extension = ".avif";
			$convertion_function = "imageavif";
			if ($this->_avif_path) $pre_path = $this->_avif_path;
		} else {
			$file_extension = ".webp";
			$convertion_function = "imagewebp";
			if ($this->_webp_path) $pre_path = $this->_webp_path;
		}


		if (($this->disable == true) || (function_exists($convertion_function) == false) )
		{
			return null;
		}

		// build full path to the image (relative to the webroot)
		$web_root = Yii::getAlias('@webroot');

		$img_full_path = $web_root . $img;

		// check if the source image exist
		if (file_exists($img_full_path) === false)
		{
			return null;
		}

		// modification time of the original image
		$img_modification_time = filemtime($img_full_path);


		$original_file_size = filesize($img_full_path);

		if ($original_file_size === 0)
		{
			return null;
		}

		// get path details (full path & short path details)
		$short_file_info = pathinfo($img);
		$file_info = pathinfo($img_full_path);


		if (!is_dir($file_info["dirname"].$pre_path)) {
			mkdir($file_info["dirname"].$pre_path, 0755);
		}

		$ext = strtolower($file_info["extension"]);
		$output_filename_with_extension = $short_file_info["filename"] . $file_extension;

		if (!in_array($ext, array('png', 'jpg', 'jpeg'))) return null;


		if ($resize_width) {
			list($orig_width, $orig_height) = getimagesize($file_info["dirname"]. "/" .$file_info['basename']);
			if ($resize_width>=$orig_width) return null;
		 	$resize_height = ceil($orig_height*($resize_width/$orig_width));
	 		$output_filename_with_extension = $short_file_info["filename"].'@'.$resize_width.'x'.$resize_height.$file_extension;
		}

	  	$output_short_path = $short_file_info["dirname"]. $pre_path . "/" . $output_filename_with_extension;
	  	$output_full_path = $file_info["dirname"]. $pre_path. "/" . $output_filename_with_extension;

		// if the WEBP file already exists check if we want to re-create it
		if ($recreate === false && file_exists($output_full_path))
		{
			// if the output file is bigger than the original image
			// use the original image
			if (filesize($output_full_path) >= $original_file_size)
			{
				return null;
			}

			$output_modification_time = filemtime($output_full_path);

			// if the modification dates on the original image
			// and WEBP image are the same = use the WEBP image
			// in any other case - recreate the file
			if ($img_modification_time !== false && $output_modification_time !== false)
			{
				if ($img_modification_time === $output_modification_time)
				{
					return $output_short_path;
				}
			}
		}

		if ($ext === "png")
		{
			$img = imagecreatefrompng($img_full_path);
			imagepalettetotruecolor($img);
			imagealphablending($img, true);
			imagesavealpha($img, true);
			if ($resize_width) {
				$tmp = imagecreatetruecolor($resize_width, $resize_height);
				imagecopyresampled($tmp, $img, 0, 0, 0, 0, $resize_width, $resize_height, $orig_width, $orig_height);
				$img = $tmp;
			}
			

		}
		else if ($ext === "jpg" || $ext === "jpeg")
		{
			$img = imagecreatefromjpeg($img_full_path);
			imagepalettetotruecolor($img);
			if ($resize_width) {
				$tmp = imagecreatetruecolor($resize_width, $resize_height);
				imagecopyresampled($tmp, $img, 0, 0, 0, 0, $resize_width, $resize_height, $orig_width, $orig_height);
				$img = $tmp;
			}

		}

		// start with 100 quality
		$quality = 100;

		// generate WEBP in the best possible quality
		// and file size less than the original
		do
		{
			// generate output WEBP file
			try
			{
				call_user_func($convertion_function, $img, $output_full_path, $quality);
			}
			catch(yii\base\ErrorException $exception)
			{
				imagedestroy($img);
				return null;
			}


			// decrease quality
			$quality -= 5;

			// no point in going below 70% quality
			if ($quality < 70) break;
		}
		while (filesize($output_full_path) >= $original_file_size);

		// release input image
		imagedestroy($img);

		// set modification time on the WEBP file to match the
		// modification time of the original image
		if ($img_modification_time !== false)
		{
			touch($output_full_path, $img_modification_time);
		}

		// if the final WEBP image is bigger than the original file
		// don't use it (use the original only)
		if (filesize($output_full_path) >= $original_file_size)
		{
			return null;
		}

		return $output_short_path;
	}

	public function init()
	{
		parent::init();
		asort($this->sizes);

		if (is_array($this->sizes)) foreach ($this->sizes as $val) {
			if ($tmp=$this->get_or_convert_to_dest_format($this->src, $this->recreate == true, $val)) $this->_items[$val] = $tmp;
		}

		if ($tmp = $this->get_or_convert_to_dest_format($this->src, $this->recreate == true)) $this->_items[0] = $tmp;
	}

	/**
	 * Generates image with srcset atrribute and  resize options
	 */

	private function srcset() {

		if (is_null($this->_items)) return null;

		if (sizeof($this->_items)>=1) foreach ($this->_items as $key=>$item) if (!empty($item))  {
			$srcset[] =  ($key>0) ? $item." ".$key."w" : $item." 10000w";
		}

		return $this->img(array("srcset" => implode(",", $srcset)));
	}

	/**
	 * Generates Image inside of <picture> elements support complex adaptive source sets combining rules for width
	 */

	private function picture() {

		if (is_null($this->_items)) return null;

		// include it within <picture> tag
		$html = "<picture>";
		foreach ($this->_items as $key=>$item) if (!empty($item))  {
			$options =  ["srcset" => $item, "type" => ($this->type_src=='picture_webp') ? "image/webp" : "image/avif"];
			if ($key>0) $options['media'] = "(max-width:".$key."px)";
			$html .= Html::tag("source", [], $options);
		}

		// fallback image (unoptimized)
		$html .= $this->img();
		$html .= "</picture>";

		return $html;

	}


	private function background() {

		if (is_null($this->_items)) return null;

		$background_image[] =  "background-image: url('".$this->_items[0]."')";


		if (sizeof($this->_items)>1) {

			$webkit_image_set = 'background-image: -webkit-image-set(';
			$image_set = 'background-image: image-set(';

			foreach ($this->_items as $key=>$item) if (!empty($item) && $key>0)  {
				$image_set_arr[] = "url('".$item."') ".$key."w";
			}
			$webkit_image_set .= implode(', ', $image_set_arr).')';
			$image_set .= implode(',', $image_set_arr).')';

			$background_image[] = $webkit_image_set;
			$background_image[] = $image_set;
		}
		return implode('; ', $background_image);
	}

	private function img($attr_add=array()) {

		$attr = [
			"class" => $this->css,
			"style" => $this->style,
			"alt" => $this->alt,
			"height" => $this->height,
			"width" => $this->width,
			"loading" => $this->loading,
			"itemprop" => $this->itemprop
		];

		$attr = array_merge($attr, $attr_add);

		return $img = Html::img($this->src, $attr);
	}



	public function run()
	{
		// our unoptimized image (include all the possible attributes)
		$img = $this->img();
		switch ($this->type_src) {
			case 'srcset_webp':
			case 'srcset_avif':				
				$img = $this->srcset();
				break;	
			case 'picture_webp':
			case 'picture_avif':				
				$img = $this->picture();
				break;
			case 'background_webp':
			case 'background_avif':				
				$img = $this->background();
				break;	
		}
		return $img;
	}
}
