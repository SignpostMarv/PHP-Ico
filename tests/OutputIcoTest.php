<?php
declare(strict_types=1);

namespace PHP_ICO\Tests;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use PHP_ICO;
use PHPUnit_Framework_TestCase;
use TypeError;

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
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.jpg',
                array(),
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.png',
                array(),
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.gif',
                array(16,16),
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.jpg',
                array(16, 16),
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.png',
                array(16, 16),
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.gif',
                array(
                    array(16,16),
                ),
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.jpg',
                array(
                    array(16,16),
                ),
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.png',
                array(
                    array(16,16),
                ),
            ),
        );
    }

    public function badAddImageSingleProvider_invalidFile()
    {
        return array(
            array(
                '',
                array(),
                InvalidArgumentException::class,
                'File not specified!',
            ),
            array(
                __DIR__ . DIRECTORY_SEPARATOR . 'test-ico-1.xcf',
                array(),
                InvalidArgumentException::class,
                'Could not determine image size!',
            ),
        );
    }

    public function badAddImageSingleProvider_invalidFileTypeError()
    {
        return array(
            array(
                null,
                array(),
            ),
            array(
                false,
                array(),
            ),
            array(
                true,
                array(),
            ),
            array(
                1,
                array(),
            ),
            array(
                1.2,
                array(),
            ),
            array(
                array(),
                array()
            ),
            array(
                '',
                1,
            ),
            array(
                '',
                1.2,
            ),
            array(
                '',
                null,
            ),
            array(
                '',
                false,
            ),
        );
    }

    public function badSaveIcoProvider_MultipleSizes()
    {
        return array(
            array(
                array(),
                BadMethodCallException::class,
                'Cannot call PHP_ICO::PHP_ICO::_get_ico_data() with no images!',
            ),
        );
    }

    /**
    * @dataProvider goodAddImageSingleProvider
    */
    public function testConstructorOnly($file, $sizes)
    {
        $this->assertTrue(is_file($file));
        $this->assertTrue(is_readable($file));
        $this->assertTrue(is_file($file . '.ico'));
        $this->assertTrue(is_readable($file . '.ico'));

        $ico = new PHP_ICO($file, $sizes);
        $outputToHere = tempnam(sys_get_temp_dir(), 'PHP_ICO_tests');
        $this->assertTrue($ico->save_ico($outputToHere));
        $this->assertSame(sha1_file($file . '.ico'), sha1_file($outputToHere));
        unlink($outputToHere);
    }

    /**
    * @dataProvider badAddImageSingleProvider_invalidFile
    */
    public function testAddImageBadFiles($file, $sizes, $expectException, $expectExceptionMessage)
    {
        $ico = new PHP_ICO();
        $this->expectException($expectException);
        $this->expectExceptionMessage($expectExceptionMessage);
        $ico->add_image($file, $sizes);
    }

    /**
    * @dataProvider badAddImageSingleProvider_invalidFileTypeError
    */
    public function testAddImageTypeError($file, $sizes)
    {
        $ico = new PHP_ICO();
        $this->expectException(TypeError::class);
        $ico->add_image($file, $sizes);
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
        $this->expectException($expectException);
        $this->expectExceptionMessage($expectExceptionMessage);
        try {
            $ico->save_ico($outputToHere);
        } catch (Exception $e) {
        }
        unlink($outputToHere);
        if ($e instanceof Exception) {
            throw $e;
        }
    }
}
