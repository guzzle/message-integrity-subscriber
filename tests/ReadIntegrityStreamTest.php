<?php

namespace GuzzleHttp\Tests\MessageIntegrity;

use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\MessageIntegrity\PhpHash;
use GuzzleHttp\Subscriber\MessageIntegrity\ReadIntegrityStream;

class ReadIntegrityStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \UnexpectedValueException
     */
    public function testValidatesRollingMd5()
    {
        $real = Stream::factory('foobar');
        $hash = new PhpHash('md5');
        $stream = new ReadIntegrityStream($real, $hash, 'foo');
        $stream->getContents();
    }

    /**
     * @expectedException \UnderflowException
     */
    public function testValidatesRollingMd5WithCallback()
    {
        $real = Stream::factory('foobar');
        $hash = new PhpHash('md5');
        $stream = new ReadIntegrityStream($real, $hash, 'foo', function ($a, $b) {
            $this->assertEquals(base64_encode(md5('foobar', true)), $a);
            $this->assertEquals('foo', $b);
            throw new \UnderflowException('test');
        });
        $stream->getContents();
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testValidatesRollingMd5WithCallbackThatDoesNotThrow()
    {
        $real = Stream::factory('foobar');
        $hash = new PhpHash('md5');
        $stream = new ReadIntegrityStream($real, $hash, 'foo', function () {});
        $stream->getContents();
    }

    public function testValidatesSuccessfully()
    {
        $real = Stream::factory('foobar');
        $hash = new PhpHash('md5');
        $expected = base64_encode(md5('foobar', true));
        $stream = new ReadIntegrityStream($real, $hash, $expected);
        $stream->getContents();
    }
}
