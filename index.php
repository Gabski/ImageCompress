<?php

class Compress
{
    protected $file_url;
    protected $new_name_image;
    protected $quality;
    protected $pngQuality;
    protected $destination;
    protected $image_size;
    protected $image_data;
    protected $image_mime;
    protected $array_img_types;
    protected $maxSize;

    public function __construct($file_url, $new_name_image, $quality, $pngQuality, $destination = null, $maxsize = 5245330)
    {
        $this->set_file_url($file_url);
        $this->set_new_name_image($new_name_image);
        $this->set_quality($quality);
        $this->set_destination($destination);
        $this->set_maxSize($maxsize);
    }
    public function get_file_url()
    {
        return $this->file_url;
    }
    public function get_new_name_image()
    {
        return $this->new_name_image;
    }
    public function get_quality()
    {
        return $this->quality;
    }
    public function get_pngQuality()
    {
        return $this->pngQuality;
    }

    public function get_maxsize()
    {
        return $this->maxSize;
    }
    public function set_file_url($file_url)
    {
        $this->file_url = $file_url;
    }
    public function set_new_name_image($new_name_image)
    {
        $this->new_name_image = $new_name_image;
    }
    public function set_quality($quality)
    {
        $this->quality = $quality;
    }
    public function set_pngQuality($pngQuality)
    {
        $this->pngQuality = $pngQuality;
    }

    public function get_destination()
    {
        return $this->destination;
    }
    public function set_destination($destination)
    {
        $this->destination = $destination;
    }

    public function set_maxSize($maxsize)
    {
        $this->maxSize = $maxsize;
    }

    public function compress_image()
    {

        $array_img_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
        $new_image = null;
        $last_char = null;
        $image_extension = null;
        $destination_extension = null;
        $png_compression = null;

        try {
            if (empty($this->file_url) && !file_exists($this->file_url)) {
                throw new Exception('Podaj obrazek!');
                return false;
            }

            $image_data = getimagesize($this->file_url);
            $image_mime = $image_data['mime'];

            if (!in_array($image_mime, $array_img_types)) {
                throw new Exception('Podaj obrazek!');
                return false;
            }

            $image_size = filesize($this->file_url);

            if ($image_size >= $this->maxSize) {
                throw new \Exception("Wyślij mniejszy plik niż {$this->maxSize}bajtów!");
                return false;
            }

            if (empty($this->new_name_image)) {
                throw new Exception('Dodaj ścieżkę końcową');
                return false;
            }

            if (empty($this->quality)) {
                throw new Exception('Podaj jakość');
                return false;
            }
            $png_compression = (!empty($this->pngQuality)) ? $this->pngQuality : 9;

            $image_extension = pathinfo($this->file_url, PATHINFO_EXTENSION);
            $destination_extension = pathinfo($this->new_name_image, PATHINFO_EXTENSION);
            if (empty($destination_extension)) {
                $this->new_name_image = $this->new_name_image . '.' . $image_extension;
            }

            if (!empty($this->destination)) {

                $last_char = substr($this->destination, -1);

                if ($last_char !== '/') {
                    $this->destination = $this->destination . '/';
                }
            }

            switch ($image_mime) {
                case 'image/jpeg':
                case 'image/pjpeg':
                    $new_image = imagecreatefromjpeg($this->file_url);
                    imagejpeg($new_image, $this->destination . $this->new_name_image, $this->quality);
                    break;
                case 'image/png':
                case 'image/x-png':
                    $maxImgWidth = 900;
                    $src = imagecreatefrompng($this->file_url);
                    list($width, $height) = getimagesize($this->file_url);
                    if ($width > $maxImgWidth) {
                        $newwidth = $maxImgWidth;
                        $newheight = ($height / $width) * $newwidth;
                        $newImage = imagecreate($newwidth, $newheight);
                        imagecopyresampled($newImage, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
                        imagepng($newImage, $this->destination . $this->new_name_image, $png_compression);
                        imagedestroy($src);
                        imagedestroy($newImage);
                        $resizedFlag = true;
                    }
                    break;
                case 'image/gif':
                    $new_image = imagecreatefromgif($this->file_url);
                    imagealphablending($new_image, false);
                    imagesavealpha($new_image, true);
                    imagegif($new_image, $this->destination . $this->new_name_image);
            }

        } catch (Exception $ex) {
            return $ex->getMessage();
        }

        return $this->new_name_image;

    }
}

function getAllDirs($directory, $directory_seperator)
{

    $dirs = array_map(function ($item) use ($directory_seperator) {
        return $item . $directory_seperator;
    }, array_filter(glob($directory . '*'), 'is_dir'));

    foreach ($dirs as $dir) {
        $dirs = array_merge($dirs, getAllDirs($dir, $directory_seperator));
    }

    array_push($dirs, $directory);

    return $dirs;
}

function getAllImgs($directory)
{
    $resizedFilePath = array();
    foreach ($directory as $dir) {

        foreach (glob($dir . '*.{jpg,JPG,jpeg,JPEG}', GLOB_BRACE) as $filename) {

            array_push($resizedFilePath, ['file' => $filename, 'dir' => $dir]);

        }

    }
    return $resizedFilePath;
}

$directory = $argv[1] ?? 't/';
$directory_seperator = "/";

$allimages = getAllImgs(getAllDirs($directory, $directory_seperator));

foreach ($allimages as $filename) {
    $file = basename($filename['file']);

    // if (unlink($filename['dir'] . "n_" . $file)) {
    //     echo "Usunięto " . $filename['dir'] . "/"  . $file;
    // }

    $new_name_image = $file;
    $quality = 80;
    $pngQuality = 9;
    $destination = $filename['dir'];
    $maxsize = 5245330;

    $image_compress = new Compress($filename['file'], $new_name_image, $quality, $pngQuality, $destination, $maxsize);
    $done = $image_compress->compress_image();

    echo $filename['dir'] . " | " . $file . " | " . ($done ? "Kompresja zakończona" : "Kompresja nieudana") . "\n";
}