<?php

namespace GuzzleHttp\Tests\MessageIntegrity;

use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Subscriber\MessageIntegrity\CompleteResponse;
use GuzzleHttp\Subscriber\MessageIntegrity\PhpHash;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class CompleteResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \GuzzleHttp\Subscriber\MessageIntegrity\MessageIntegrityException
     * @expectedExceptionMessage Message integrity check failure. Expected "fud" but got "rL0Y20zC+Fzt72VPzMSk2A==
     */
    public function testThrowsSpecificException()
    {
        $sub = new CompleteResponse([
            'hash' => new PhpHash('md5', ['base64' => true]),
            'expected' => function (ResponseInterface $response) {
                return $response->getHeader('Content-MD5');
            }
        ]);
        $client = new Client();
        $client->getEmitter()->attach($sub);
        $client->getEmitter()->attach(new Mock([
            new Response(200, ['Content-MD5' => 'fud'], Stream::factory('foo'))
        ]));
        $request = $client->createRequest('GET', 'http://httpbin.org');
        $client->send($request);
    }
}
