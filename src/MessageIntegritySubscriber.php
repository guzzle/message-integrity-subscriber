<?php

namespace GuzzleHttp\Subscriber\MessageIntegrity;

use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Event\BeforeEvent;

/**
 * Verifies the message integrity of a response after all of the data has been
 * received.
 */
class MessageIntegritySubscriber implements SubscriberInterface
{
    private $full;
    private $streaming;

    /**
     * Creates a new plugin that validates the Content-MD5 of responses
     *
     * @return MessageIntegritySubscriber
     */
    public static function createForContentMd5()
    {
        return new self([
            'header' => 'Content-MD5',
            'hash'   => new PhpHash('md5')
        ]);
    }

    /**
     * Validates the options provided to an integrity plugin.
     *
     * @param array $config Associative array of configuration options.
     * @throws \InvalidArgumentException
     */
    public static function validateOptions(array &$config)
    {
        if (!isset($config['header'])) {
            throw new \InvalidArgumentException('header is required');
        }

        if (!isset($config['hash'])) {
            throw new \InvalidArgumentException('hash is required');
        }

        if (!($config['hash']) instanceof HashInterface) {
            throw new \InvalidArgumentException('hash must be an instance of HashInterface');
        }
    }

    /**
     * @param array $config Associative array of configuration options.
     *     - header: (string) The name of the header to validate against.
     *     - hash: (HashInterface) used to validate the header value
     *     - size_cutoff: (int) Don't validate when size is greater than this
     *       number.
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config)
    {
        $this->full = new OnCompleteIntegritySubscriber($config);
        $this->streaming = new StreamIntegritySubscriber($config);
    }

    public static function getSubscribedEvents()
    {
        return ['before' => ['onBefore']];
    }

    public function onBefore(BeforeEvent $event)
    {
        if ($event->getRequest()->getConfig()['stream']) {
            $event->getRequest()->getEmitter()->addSubscriber($this->streaming);
        } else {
            $event->getRequest()->getEmitter()->addSubscriber($this->full);
        }
    }
}
