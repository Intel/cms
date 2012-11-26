<?
    // #########################
    // Image Management Class
    // #########################
    
    include('php_image_magician.php');
    
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
        
        public function Resize($w, $h, $option = 'auto') {
            if ($option == 'auto') {
                if ($w && $h)
                    $option = 'crop';
                else if ($w)
                    $option = 'landscape';
                else
                    $option = 'portrait';
            }
            
            $this->handler->Resize($w, $h, $option);
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
    
    // Using image magician
    class GDImageHandler {
        public $ready = false;
        public $gdimg;
        
        public function __construct($image) {
            $this->gdimg = new imageLib($image); 
            if ($this->gdimg)
                $this->ready = true;
        }
        
        public function Resize($w, $h, $option) {
            $this->gdimg->resizeImage($w, $h, $option);
        }
        
        public function Save($path) {
            $this->gdimg->saveImage($path);
        }
        
        public function GetHeight() {
            return imagesy($this->gdimg);
        }
        
        public function GetWidth() {
            return imagesx($this->gdimg);
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
        
        public function Resize($w, $h, $option) {
            switch($option) {
                case 'crop':
                    $this->img->cropThumbnailImage($w, $h);
                    break;
                case 'landscape':
                case 'portrait':
                    $this->img->scaleImage($w, $h, false);
                    break;
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