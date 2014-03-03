<?php

namespace GuzzleHttp\Tests\MessageIntegrity;

use GuzzleHttp\Subscriber\MessageIntegrity\PhpHash;

class PhpHashTest extends \PHPUnit_Framework_TestCase
{
    public function testHashesData()
    {
        $hash = new PhpHash('md5');
        $hash->update('foo');
        $hash->update('bar');
        $result = $hash->complete();
        $this->assertEquals(md5('foobar', true), $result);
    }
}
