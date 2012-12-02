<?
    include("../includes/defines.php");
    include("../includes/class_imagehandler.php");
    
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
        header("Content-Type: image/jpeg");
        readfile($file_resized);
        exit();
    }
    
	// check if image exists
	if (!file_exists($file))
		die('not found');
    
	if ($width || $height) {
        $img = new ImageHandler($file);
        
        $img->Resize($width, $height);
        
        $img->Save($file_resized);
        
        header("Content-Type: image/jpeg");
        readfile($file_resized);
	} else {
        header("Content-Type: image/jpeg");
        readfile($file);
    }
?>