<?
	$hash = $_GET['hash'];
	
	// check for bad symbols
	if (preg_match("/[^a-zA-Z0-9\s]/", $hash))
		die('bad hash');
	
	$width = (isset($_GET['w']) ? $_GET['w'] : 0);
	$height = (isset($_GET['h']) ? $_GET['h'] : 0);
    
    $width = ($width > 3200 ? 3200 : $width);
    $height = ($height > 2000 ? 2000 : $height);
    
    $file = 'images/' . $hash . '.jpg';
    $file_resized = 'images/' . $hash . '-' . $width . 'x' . $height . '.jpg';
	
    if (file_exists($file_resized)) {
        $img = new Imagick($file_resized);
        // print
        header("Content-Type: image/jpeg");
        print $img->getImageBlob();
    }
    
	// check if image exists
	if (!file_exists($file))
		die('not found');
	
	$img = new Imagick($file);
    
	if ($width || $height) {
        $img->cropThumbnailImage($width, $height);
        $img->writeImage($file_resized);
	}

	// print
	header("Content-Type: image/jpeg");
	print $img->getImageBlob();
	
	$img->destroy();
?>