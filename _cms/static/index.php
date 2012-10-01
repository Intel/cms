<?
	$hash = $_GET['hash'];
	
	// check for bad symbols
	if (preg_match("/[^a-zA-Z0-9\s]/", $hash))
		die('bad hash');
	
	$file = 'images/' . $hash . '.jpg';
	
	// check if image exists
	if (!file_exists($file))
		die('not found');
	
	$img = new Imagick($file);
	
	$width = (isset($_GET['w']) ? $_GET['w'] : 0);
	$height = (isset($_GET['h']) ? $_GET['h'] : 0);
	if ($width || $height) {
        $img->cropThumbnailImage($width,$height);
		/*$aspect = $img->getImageWidth() / $img->getImageHeight();
		if (($aspect * $height) > $width)
			$img->resizeImage($aspect * $height, $height, Imagick::FILTER_LANCZOS, false);
		else
			$img->resizeImage($width, $width / $aspect, Imagick::FILTER_LANCZOS, false);*/
	}

	// print
	header("Content-Type: image/jpeg");
	print $img->getImageBlob();
	
	$img->destroy();
?>