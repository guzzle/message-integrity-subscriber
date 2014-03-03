<?php

namespace GuzzleHttp\Subscriber\MessageIntegrity;

use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Stream\StreamInterface;

/**
 * Verifies the message integrity of a response after all of the data has been
 * received.
 */
class OnCompleteIntegritySubscriber implements SubscriberInterface
{
    /** @var HashInterface */
    private $hash;
    private $header;
    private $sizeCutoff;

    /**
     * @param array $config Associative array of configuration options.
     * @see GuzzleHttp\Subscriber\MessageIntegritySubscriber::__construct for a
     *     list of available configuration options.
     */
    public function __construct(array $config)
    {
        MessageIntegritySubscriber::validateOptions($config);
        $this->header = $config['header'];
        $this->hash = $config['hash'];
        $this->sizeCutoff = isset($config['size_cutoff'])
            ? $config['size_cutoff']
            : null;
    }

    public static function getSubscribedEvents()
    {
        return ['complete' => ['onComplete', -1]];
    }

    public function onComplete(CompleteEvent $event)
    {
        if ($this->canValidate($event->getResponse())) {
            $response = $event->getResponse();
            $this->matchesHash(
                $event,
                (string) $response->getHeader($this->header),
                $response->getBody()
            );
        }
    }

    private function canValidate(ResponseInterface $response)
    {
        if (!($body = $response->getBody())) {
            return false;
        } elseif (!$response->hasHeader($this->header)) {
            return false;
        } elseif ($response->hasHeader('Transfer-Encoding') ||
            $response->hasHeader('Content-Encoding')
        ) {
            // Currently does not support un-gzipping or inflating responses
            return false;
        } elseif (!$body->isSeekable()) {
            return false;
        } elseif ($this->sizeCutoff !== null &&
            $body->getSize() > $this->sizeCutoff
        ) {
            return false;
        }

        return true;
    }

    private function matchesHash(
        CompleteEvent $event,
        $hash,
        StreamInterface $body
    ) {
        $body->seek(0);
        while (!$body->eof()) {
            $this->hash->update($body->read(16384));
        }

        $result = base64_encode($this->hash->complete());

        if ($hash !== $result) {
            throw new MessageIntegrityException(
                sprintf('%s message integrity check failure. Expected "%s" but'
                    . ' got "%s"', $this->header, $hash, $result),
                $event->getRequest(),
                $event->getResponse()
            );
        }
    }
}
