<?
    include("../includes/defines.php");
    
	if (!$_FILES['image'])
		exit('no file');
	
	$image;
	if (!$image = new Imagick($_FILES['image']['tmp_name']))
		exit('failed to load img');
	
	if ($image->getImageHeight() > 2000 || $image->getImageWidth() > 3200) {
		$aspect = $image->getImageWidth() / $image->getImageHeight();
		if ($aspect < 1)
			$image->resizeImage($aspect * 2000, 2000, Imagick::FILTER_LANCZOS, false);
		else
			$image->resizeImage(3200, 3200 / $aspect, Imagick::FILTER_LANCZOS, false);
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