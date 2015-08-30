<?php
/**
* @author SignpostMarv
*/


namespace SignpostMarv\Ico;


use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;

class Ico
{


  protected $images = array();


  public function __construct($file=null, array $sizes=array()){
    if(!is_null($file)){
      $this->addImage($file, array_map(function($e){
        return is_integer($e) ? array($e, $e) : $e;
      }, $sizes));
    }
  }


  public function addImage($file, array $sizes=array()){
    $im = ImageManagerStatic::make($file);

    foreach($sizes as $size){
      list($width, $height) = $size;
      $im->backup();
      $new_im = $im->resize($width, $height);
      $this->addImageData($new_im);
      $im->reset();
    }

    return true;
  }


  protected function addImageData(Image $im){
    $width = $im->width();
    $height = $im->height();

    $pixelData = array();
    $opacityData = array();
    $currentOpacityVal = 0;

    for($y=($height - 1);$y>=0;$y--){
      for($x=0;$x<$width;$x++){
        $color = $im->pickColor($x, $y, 'integer');

        list($r, $g, $b, $alpha) = $im->pickColor($x, $y, 'array');

        $opacity = ($alpha <= 0.5) ? 1 : 0;

        $alpha *= 255;
        $alpha |= 0;


        $color &= 0xffffff;
        $color |= 0xff000000 & ($alpha << 24);

        $pixelData[] = $color;

        $currentOpacityVal = ($currentOpacityVal << 1) | $opacity;

        if((($x + 1) % 32) == 0){
          $opacityData[] = $currentOpacityVal;
          $currentOpacityVal = 0;
        }
      }
      if(($x % 32) > 0){
        while(($x++ % 32) > 0){
          $currentOpacityVal = $currentOpacityVal << 1;
        }

        $opacityData[] = $currentOpacityVal;
        $currentOpacityVal = 0;
      }
    }

    $imageHeaderSize = 40;
    $colorMaskSize = $width * $height * 4;
    $opacityMaskSize = (ceil($width / 32) * 4) * $height;

    $data =
      pack('VVVvvVVVVVV', 40, $width, ($height * 2), 1, 32, 0, 0, 0, 0, 0, 0)
    ;
    foreach($pixelData as $color){
      $data .= pack('V', $color);
    }
    foreach($opacityData as $opacity){
      $data .= pack('N', $opacity);
    }

    $this->images[] = array(
      'width' => $width,
      'height' => $height,
      'color_palette_colors' => 0,
      'bits_per_pixel' => 32,
      'size' => ($imageHeaderSize + $colorMaskSize + $opacityMaskSize),
      'data' => $data,
    );
  }


  protected function getIcoData(){
    if(!is_array($this->images) || empty($this->images)){
      return false;
    }

    $data = pack('vvv', 0, 1, count($this->images));
    $pixelData = '';
    $iconDirEntrySize = 16;
    $offset = 6 + ($iconDirEntrySize * (count($this->images)));

    foreach($this->images as $image){
      $data .= pack(
        'CCCCvvVV',
        $image['width'],
        $image['height'],
        $image['color_palette_colors'],
        0,
        1,
        $image['bits_per_pixel'],
        $image['size'],
        $offset
      );
      $pixelData .= $image['data'];
      $offset += $image['size'];
    }

    $data .= $pixelData;
    unset($pixelData);

    return $data;
  }
}
