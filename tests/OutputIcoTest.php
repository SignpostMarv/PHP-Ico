<?php
declare(strict_types=1);

namespace PHP_ICO\Tests;

use BadMethodCallException;
use Exception;
use Intervention\Image\Exception\NotReadableException;
use InvalidArgumentException;
use PHP_ICO;
use PHPUnit_Framework_TestCase;

/**
 * Class EasyDBTest
 * @package ParagonIE\EasyDB\Tests
 */
class OutputIcoTest extends PHPUnit_Framework_TestCase
{
    public function goodAddImageSingleProvider()
    {
        return array(
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.gif',
                array(),
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.gif.ico',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.jpg',
                array(),
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.jpg.ico',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.png',
                array(),
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.png.ico',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.gif',
                array(16,16),
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.gif.ico',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.jpg',
                array(16, 16),
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.jpg.ico',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.png',
                array(16, 16),
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.png.ico',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.gif',
                array(
                    array(16,16),
                ),
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.gif.ico',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.jpg',
                array(
                    array(16,16),
                ),
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.jpg.ico',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.png',
                array(
                    array(16,16),
                ),
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.png.ico',
            ),
        );
    }

    public function badAddImageSingleProvider_invalidFile()
    {
        return array(
            array(
                null,
                array(),
                'Intervention\\Image\\Exception\\NotReadableException',
                'Image source not readable',
            ),
            array(
                false,
                array(),
                'Intervention\\Image\\Exception\\NotReadableException',
                'Image source not readable',
            ),
            array(
                true,
                array(),
                'Intervention\\Image\\Exception\\NotReadableException',
                'Image source not readable',
            ),
            array(
                1,
                array(),
                'Intervention\\Image\\Exception\\NotReadableException',
                'Image source not readable',
            ),
            array(
                '',
                array(),
                'Intervention\\Image\\Exception\\NotReadableException',
                'Unable to init from given binary data.',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.xcf',
                array(),
                'Intervention\\Image\\Exception\\NotReadableException',
                'Unable to read image from file (' . __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.xcf).',
            ),
        );
    }

    public function badSaveIcoProvider_MultipleSizes()
    {
        return array(
            array(
                array(),
                'BadMethodCallException',
                'Cannot call PHP_ICO::PHP_ICO::_get_ico_data() with no images!',
            ),
        );
    }

    /**
    * @dataProvider goodAddImageSingleProvider
    */
    public function testConstructorOnly($file, $sizes, $expectedIco)
    {
        $this->assertTrue(is_file($file));
        $this->assertTrue(is_readable($file));
        $this->assertTrue(is_file($file . '.ico'));
        $this->assertTrue(is_readable($file . '.ico'));

        $ico = new PHP_ICO($file, $sizes);
        $outputToHere = tempnam(sys_get_temp_dir(), 'PHP_ICO_tests');
        $this->assertTrue($ico->save_ico($outputToHere));
        $this->assertSame(sha1_file($expectedIco), sha1_file($outputToHere));
        unlink($outputToHere);
    }

    /**
    * @dataProvider badAddImageSingleProvider_invalidFile
    */
    public function testAddImageBadFiles($file, $sizes, $expectException, $expectExceptionMessage)
    {
        $ico = new PHP_ICO();
        if (method_exists($this, 'expectException')) {
            $this->expectException($expectException);
            $this->expectExceptionMessage($expectExceptionMessage);
            $ico->add_image($file, $sizes);
        } else {
            try {
            } catch (Exception $e) {
                $this->assertSame(get_class($e), $expectException);
                $this->assertSame($e->getMessage(), $expectExceptionMessage);
            }
        }
    }

    /**
    * @dataProvider badSaveIcoProvider_MultipleSizes
    */
    public function testSaveIcoBadData($arrayOfFilesAndSizes, $expectException, $expectExceptionMessage)
    {
        $ico = new PHP_ICO();
        foreach ($arrayOfFilesAndSizes as $file => $sizes) {
            $ico->add_image($file, $sizes);
        }
        $outputToHere = tempnam(sys_get_temp_dir(), 'PHP_ICO_tests');
        $e = null;
        if (method_exists($this, 'expectException')) {
            $this->expectException($expectException);
            $this->expectExceptionMessage($expectExceptionMessage);
        }
        try {
            $ico->save_ico($outputToHere);
        } catch (Exception $e) {
        }
        unlink($outputToHere);
        if ($e instanceof Exception) {
            if (!method_exists($this, 'expectException')) {
                $this->assertSame(get_class($e), $expectException);
                $this->assertSame($e->getMessage(), $expectExceptionMessage);
            } else {
                throw $e;
            }
        }
    }
}
