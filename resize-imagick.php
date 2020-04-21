<?php
	error_reporting(E_WARNING|E_ERROR);
	ini_set('display_errors', 'On');

	IF($_REQUEST["debug"]) var_dump($_REQUEST);

	$file = $_REQUEST["file"];
	$height = $_REQUEST["height"];
	$width = $_REQUEST["width"];
	// $aspectratio

	$targetdirectory = "cache";

	$imagefile = str_replace(array("/photos/", "?".$_SERVER["QUERY_STRING"]), "", $_SERVER["REQUEST_URI"]);
	IF(0 && file_exists("{$targetdirectory}/{$imagefile}")) :
		$img = imagecreatefromjpeg("{$targetdirectory}/{$imagefile}");
		header("Content-Type: image/jpeg");
		imagejpeg($img);
		imagedestroy($img);

	ELSEIF(file_exists("{$file}.jpg")) :
/*
		$img = imagecreatefromjpeg("{$file}.jpg");

		//$newimg = imagescale($img, $width, $height ?: -1); # needs php version >= 5.5.0
		# for php version < 5.5.0
		list($originalwidth, $originalheight) = getimagesize("{$file}.jpg");
		$newimg = imagecreatetruecolor($width, $height);
		imagecopyresampled($newimg, $img, 0, 0, 0, 0, $width, $height, $originalwidth, $originalheight);

		imagejpeg($newimg, "cache/{$file}_{$width}x{$height}.jpg", 60);
		imagedestroy($img);

		header("Content-Type: image/jpeg");
		imagejpeg($newimg);
		imagedestroy($newimg);
*/

    $img = new Imagick("{$file}.jpg");
    $profiles = $img->getImageProfiles("icc", true);
    //$img->scaleImage($width, $height, false);
    //$img->thumbnailImage($width, $height);
    $img->resizeImage($width, $height, Imagick::FILTER_TRIANGLE, 1.1);
    //$img->optimizeImageLayers();
    $img->setImageColorspace(Imagick::COLORSPACE_SRGB);
    $img->setImageCompression(Imagick::COMPRESSION_JPEG);
    $img->setImageCompressionQuality(40);
    //$img->posterizeImage(136, false);
    $img->stripImage();
   // IF(!empty($profiles)) $img->profileImage("icc", $profiles['icc']);
    $img->writeImage("{$targetdirectory}/{$file}_{$width}x{$height}.ig.jpg");
    header("Content-Type: image/jpeg");
    ECHO($img);
    $img->destroy();

	ELSE :
		IF($_REQUEST["debug"]) printf("Requested file not found - (%s)", $_SERVER["REQUEST_URI"]);
		return false;
	ENDIF;
?>