<?
    include("../includes/defines.php");
    
	if (!$_FILES['image'])
		exit('no file');
	
	$image;
	if (!$image = new Imagick($_FILES['image']['tmp_name']))
		exit('failed to load img');
	
	if ($image->getImageHeight() > 1000 || $image->getImageWidth() > 1600) {
		$aspect = $image->getImageWidth() / $image->getImageHeight();
		if ($aspect < 1)
			$image->resizeImage($aspect * 1000, 1000, Imagick::FILTER_LANCZOS, false);
		else
			$image->resizeImage(1600, 1600 / $aspect, Imagick::FILTER_LANCZOS, false);
	}
	
	$filehash = '';
	do {
		$filehash = md5(uniqid());
	} while (file_exists('images/' . $filehash . '.jpg'));
	
    $image->setCompression(Imagick::COMPRESSION_JPEG);
    $image->setCompressionQuality(STATIC_IMG_QUALITY); 
    $image->writeImage('images/' . $filehash . '.jpg');
	
	$response = array();
	$response['hash'] = $filehash;
	
	print json_encode($response);
?>