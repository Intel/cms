<?
    include("../includes/defines.php");
    include("../includes/class_imagehandler.php");
    
    if (!$_FILES['image'])
        exit('no file');
    
    $image = new ImageHandler($_FILES['image']['tmp_name']);
    if (!$image->ready)
        exit('failed to load img');
    
    if ($image->GetHeight() > 2000 || $image->GetWidth() > 3200) {
        $image->Resize(3200, 2000);
    }
    
    $filehash = '';
    do {
        $filehash = md5(uniqid());
    } while (file_exists('images/' . $filehash . '.jpg'));
    
    $image->Save('images/' . $filehash . '.jpg');
    
    $response = array();
    $response['hash'] = $filehash;
    
    print json_encode($response);
?>