<?php
	error_reporting(E_WARNING|E_ERROR);
	ini_set('display_errors', 'On');

	IF($_REQUEST["debug"]) var_dump($_REQUEST);

	$targetdirectory = "cache";
	$imagefile = str_replace(array("/photos/", "?".$_SERVER["QUERY_STRING"]), "", $_SERVER["REQUEST_URI"]);


  $file = $_REQUEST["file"];
  $type = $_REQUEST['ext'];
	$height = $_REQUEST["height"];
	$width = $_REQUEST["width"];

	IF(!($originalage = @filemtime("{$file}.jpg"))) {
    // File not found
    IF(($originalage = @filemtime("{$file}1.jpg"))) {
      // Assume forgot to create thumb version, use first photo
      $file .= "1";
    } ELSE {
      IF($_REQUEST["debug"]) printf("Requested file not found - (%s)", $_SERVER["REQUEST_URI"]);
      return false;
    }
  }

	$cached = file_exists("{$targetdirectory}/{$imagefile}");
	$cachedage = $cached ? filemtime("{$targetdirectory}/{$imagefile}") : 0;
	$force = $_REQUEST["redraw"] ?: false;
	$replaced = $cached && $cachedage >= $originalage ? false : true;

	IF($cached && !$force && !$replaced) :
		$img = new Imagick("{$targetdirectory}/{$imagefile}");
    header("Content-Type: image/jpeg");
    ECHO($img);
    $img->destroy();

  ELSEIF($type == "webp" && file_exists("{$file}.jpg")) :
    // WEBP
    $dest = "{$targetdirectory}/{$file}".($width > 0 ? "_{$width}x{$height}" : "").".webp";
    $img = imagecreatefromjpeg("{$file}.jpg");
    $webp = imagewebp($img, $dest, 80);
    header("Content-Type: image/webp");
    ECHO($img);
    imagedestroy($img);

	ELSEIF(file_exists("{$file}.jpg")) :
    $img = new Imagick("{$file}.jpg");
    // IF($width == 0 || $height == 0) {
    //   $size = $img->getImageGeometry();
    //   $width = $size['width'];
    //   $height = $size['height'];
    // }
    // $profiles = $img->getImageProfiles("icc", true);
    //$img->scaleImage($width, $height, false);
    //$img->thumbnailImage($width, $height);
    $img->resizeImage($width, $height, Imagick::FILTER_TRIANGLE, 0.8);
    $img->optimizeImageLayers();
    $img->setImageColorspace(Imagick::COLORSPACE_SRGB);
    $img->setImageProperty('jpeg:sampling-factor', '4:2:0');
    $img->setInterlaceScheme(Imagick::INTERLACE_PLANE);
    $img->setImageCompression(Imagick::COMPRESSION_JPEG);
    //$img->setImageCompressionQuality(100);
    //$img->posterizeImage(136, false);
    $img->stripImage();
   // IF(!empty($profiles)) $img->profileImage("icc", $profiles['icc']);
    $img->writeImage("{$targetdirectory}/{$file}_{$width}x{$height}.jpg");
    header("Content-Type: image/jpeg");
    ECHO($img);
    $img->destroy();

	ELSE :
    header("HTTP/1.1 404 Not Found");
    header("Content-Type: application/json");
    IF(1 || $_REQUEST["debug"]) printf("Requested file not found - (%s)", $_SERVER["REQUEST_URI"]);
		return false;
	ENDIF;
?>
