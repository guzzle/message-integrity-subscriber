<?php

namespace GuzzleHttp\Tests\MessageIntegrity;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\MessageIntegrity\OnCompleteIntegritySubscriber;
use GuzzleHttp\Subscriber\MessageIntegrity\PhpHash;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class OnCompleteIntegritySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \GuzzleHttp\Subscriber\MessageIntegrity\MessageIntegrityException
     * @expectedExceptionMessage Content-MD5 message integrity check failure. Expected "fud" but got "rL0Y20zC+Fzt72VPzMSk2A==
     */
    public function testThrowsSpecificException()
    {
        $sub = new OnCompleteIntegritySubscriber([
            'header' => 'Content-MD5',
            'hash'   => new PhpHash('md5')
        ]);
        $client = new Client();
        $client->getEmitter()->addSubscriber($sub);
        $client->getEmitter()->addSubscriber(new Mock([
            new Response(200, ['Content-MD5' => 'fud'], Stream::factory('foo'))
        ]));
        $request = $client->createRequest('GET', 'http://httpbin.org');
        $client->send($request);
    }
}
