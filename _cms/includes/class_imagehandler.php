<?
    // #########################
    // Image Management Class
    // #########################
    
    define("IMAGEHANDLER_GD",       0);
    define("IMAGEHANDLER_IMAGICK",  1);
    define("IMAGEHANDLER_DEFAULT",  IMAGEHANDLER_IMAGICK);
    
    class ImageHandler {
        public $ready = false;
        public $handler_type;
        public $handler;

        public function __construct($image, $handler_type = IMAGEHANDLER_DEFAULT) {
            if ($handler_type == IMAGEHANDLER_IMAGICK && !extension_loaded('imagick'))
                $handler_type = IMAGEHANDLER_GD;
            
            $this->handler_type = $handler_type;
            
            if (!file_exists($image))
                return null;
            
            switch($this->handler_type) {
                case IMAGEHANDLER_GD:
                    $this->handler = new GDImageHandler($image);
                    break;
                case IMAGEHANDLER_IMAGICK:
                    $this->handler = new IMagickImageHandler($image);
                    break;
                default:
                    die("ImageHandler(): Unknow handler type '" . $this->handler_type . "'");
            }
            
            $this->ready = $this->handler->ready;
        }
        
        public function Resize($w, $h, $crop = false) {
            $this->handler->Resize($w, $h, $crop);
        }
        
        public function Save($path) {
            $this->handler->Save($path);
        }
        
        public function GetHeight() {
            return $this->handler->GetHeight();
        }
        
        public function GetWidth() {
            return $this->handler->GetWidth();
        }
    }
    
    class GDImageHandler {
        public $ready = false;
        public $gdimg;
        
        public function __construct($image) {
            $this->gdimg = $this->imageCreateFromAny($image);
            if ($this->gdimg)
                $this->ready = true;
        }
        
        public function Resize($w, $h, $crop = false) {
            $width = imagesx($this->gdimg);
            $height = imagesy($this->gdimg);
            $r = $width / $height;
            if ($crop) {
                if ($width > $height) {
                    $width = ceil($width-($width*($r-$w/$h)));
                } else {
                    $height = ceil($height-($height*($r-$w/$h)));
                }
                $newwidth = $w;
                $newheight = $h;
            } else {
                if ($w/$h > $r) {
                    $newwidth = $h*$r;
                    $newheight = $h;
                } else {
                    $newheight = $w/$r;
                    $newwidth = $w;
                }
            }
                
            $dst = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($dst, $this->gdimg, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            $this->gdimg = $dst;
        }
        
        public function Save($path) {
            imagejpeg($this->gdimg, $path, STATIC_IMG_QUALITY);
        }
        
        public function GetHeight() {
            return imagesy($this->gdimg);
        }
        
        public function GetWidth() {
            return imagesx($this->gdimg);
        }
        
        public function imageCreateFromAny($filepath) {
            $type = exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize()
            $allowedTypes = array(
                1,  // [] gif
                2,  // [] jpg
                3,  // [] png
                6   // [] bmp
            );
            if (!in_array($type, $allowedTypes)) {
                return false;
            }
            switch ($type) {
                case 1 :
                    $im = imageCreateFromGif($filepath);
                break;
                case 2 :
                    $im = imageCreateFromJpeg($filepath);
                break;
                case 3 :
                    $im = imageCreateFromPng($filepath);
                break;
                case 6 :
                    $im = imageCreateFromBmp($filepath);
                break;
            }   
            return $im; 
        }
    }
    
    class IMagickImageHandler {
        public $ready;
        public $img;
        
        public function __construct($image) {
            $this->img = new Imagick($image);
            if ($this->img)
                $this->ready = true;
        }
        
        public function Resize($w, $h, $crop = false) {
            if ($crop) {
                $this->img->cropThumbnailImage($w, $h);
            } else {
                $this->img->thumbnailImage($w, $h, TRUE);
            }
        }
        
        public function Save($path) {
            $this->img->setCompression(Imagick::COMPRESSION_JPEG);
            $this->img->setCompressionQuality(STATIC_IMG_QUALITY); 
            $this->img->writeImage($path);
        }
        
        public function GetHeight() {
            return $this->img->getImageHeight();
        }
        
        public function GetWidth() {
            return $this->img->getImageWidth();
        }
    }
    
?>