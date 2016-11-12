<?php

/*
Copyright 2011-2016 Chris Jean & iThemes
Licensed under GPLv2 or above

Version 1.0.4
*/

use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;

class PHP_ICO
{
    /**
     * Images in the BMP format.
     *
     * @var array
     */
    private $_images = array();

    /**
     * Constructor - Create a new ICO generator.
     *
     * If the constructor is not passed a file, a file will need to be supplied using the {@link PHP_ICO::add_image}
     * function in order to generate an ICO file.
     *
     * @param string $file Optional. Path to the source image file.
     * @param array $sizes Optional. An array of sizes (each size is an array with a width and height) that the source image should be rendered at in the generated ICO file. If sizes are not supplied, the size of the source image will be used.
     */
    public function __construct($file = false, $sizes = array())
    {
        if (false != $file) {
            $this->add_image($file, $sizes);
        }
    }

    /**
     * Add an image to the generator.
     *
     * This function adds a source image to the generator. It serves two main purposes: add a source image if one was
     * not supplied to the constructor and to add additional source images so that different images can be supplied for
     * different sized images in the resulting ICO file. For instance, a small source image can be used for the small
     * resolutions while a larger source image can be used for large resolutions.
     *
     * @param string $file Path to the source image file.
     * @param array $sizes Optional. An array of sizes (each size is an array with a width and height) that the source image should be rendered at in the generated ICO file. If sizes are not supplied, the size of the source image will be used.
     * @return boolean true on success and false on failure.
     */
    public function add_image($file, $sizes = array())
    {
        $im = ImageManagerStatic::make($file);


        if (empty($sizes)) {
            $sizes = array( $im->width(), $im->height() );
        }

        // If just a single size was passed, put it in array.
        $sizes = array_values(
            array_reduce(
                array_map(
                    function ($e) {
                        if (!is_array($e)) {
                            $e = array($e);
                        }
                        if (count($e) === 1) {
                            $e = array(current($e), current($e));
                        }
                        $e = array_values($e);
                        return array($e[0], $e[1]);
                    },
                    (array)$sizes
                ),
                function ($was, $size) {
                    list($width, $height) = $size;
                    $was[$width . ':' . $height] = $size;
                    return $was;
                }
            )
        );

        foreach ($sizes as $size) {
            list($width, $height) = $size;
            $im->backup();
            $new_im = $im->resize($width, $height);
            $this->_add_image_data($new_im);
            $im->reset();
        }

        return true;
    }

    /**
     * Write the ICO file data to a file path.
     *
     * @param string $file Path to save the ICO file data into.
     * @return boolean true on success and false on failure.
     */
    public function save_ico($file)
    {
        if (false === ($fh = fopen($file, 'w'))) {
            throw new \RuntimeException(
                'Could not open file for writing!'
            );
        }

        if (false === (fwrite($fh, $this->_get_ico_data()))) {
            fclose($fh);
            throw new \RuntimeException(
                'Could not write to opened file!'
            );
        }

        fclose($fh);

        return true;
    }

    /**
     * Generate the final ICO data by creating a file header and adding the image data.
     */
    private function _get_ico_data()
    {
        if (! is_array($this->_images) || empty($this->_images)) {
            throw new \BadMethodCallException(
                'Cannot call ' .
                get_class($this) .
                '::' .
                __METHOD__ .
                '() with no images!'
            );
        }
        $data = pack('vvv', 0, 1, count($this->_images));
        $pixelData = '';
        $iconDirEntrySize = 16;
        $offset = 6 + ($iconDirEntrySize * (count($this->_images)));

        foreach ($this->_images as $image) {
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

    /**
     * Take a image resource and change it into a raw BMP format.
     */
    private function _add_image_data(Image $im)
    {
        $width = $im->width();
        $height = $im->height();

        $pixelData = array();
        $opacityData = array();
        $currentOpacityVal = 0;

        for ($y=($height - 1);$y>=0;$y--) {
            for ($x=0;$x<$width;$x++) {
                $color = $im->pickColor($x, $y, 'integer');

                list($r, $g, $b, $alpha) = $im->pickColor($x, $y, 'array');

                $opacity = ($alpha <= 0.5) ? 1 : 0;

                $alpha *= 255;
                $alpha |= 0;


                $color &= 0xffffff;
                $color |= 0xff000000 & ($alpha << 24);

                $pixelData[] = $color;

                $currentOpacityVal = ($currentOpacityVal << 1) | $opacity;

                if ((($x + 1) % 32) == 0) {
                    $opacityData[] = $currentOpacityVal;
                    $currentOpacityVal = 0;
                }
            }
            if (($x % 32) > 0) {
                while (($x++ % 32) > 0) {
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
        foreach ($pixelData as $color) {
            $data .= pack('V', $color);
        }
        foreach ($opacityData as $opacity) {
            $data .= pack('N', $opacity);
        }

        $this->_images[] = array(
            'width' => $width,
            'height' => $height,
            'color_palette_colors' => 0,
            'bits_per_pixel' => 32,
            'size' => ($imageHeaderSize + $colorMaskSize + $opacityMaskSize),
            'data' => $data,
        );
    }
}
