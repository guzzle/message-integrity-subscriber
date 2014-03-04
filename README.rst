===================================
Guzzle Message Integrity Subscriber
===================================

Verifies the integrity of HTTP responses using customizable validators.

This plugin can be used, for example, to validate the message integrity of
responses based on the ``Content-MD5`` header. The plugin offers a convenience
method for validating a ``Content-MD5`` header.

.. code-block:: php

    use GuzzleHttp\Client();
    use GuzzleHttp\Subscriber\MessageIntegrity\ResponseSubscriber;

    $subscriber = ResponseSubscriber::createForContentMd5();
    $client = new Client();
    $client->getEmitter()->addSubscriber($subscriber);

Constructor Options
-------------------

The ``GuzzleHttp\Subscriber\MessageIntegrity\ResponseSubscriber`` class
accepts an associative array of options:

expected
    (callable) A function that returns the hash that is expected for a
    response. The function accepts a ResponseInterface objects and returns a
    string that is compared against the calculated rolling hash.

hash
    (``GuzzleHttp\Subscriber\MessageIntegrity\HashInterface``) A hash object
    used to compute a hash of the response body. The result created by the
    has is then compared against the extracted header value.

size_cutoff
    (integer) If specified, the message integrity will only be validated if the
    response size is less than the ``size_cutoff`` value (in bytes).

.. code-block:: php

    use GuzzleHttp\Client();
    use GuzzleHttp\Message\ResponseInterface;
    use GuzzleHttp\Subscriber\MessageIntegrity\ResponseSubscriber;

    $subscriber = new ResponseSubscriber([
        'hash' => new PhpHash('md5', ['base64' => true])
        'expected' => function (ResponseInterface $response) {
            return $response->getHeader('Content-MD5');
        }
    ]);

    $client = new Client();
    $client->getEmitter()->addSubscriber($subscriber);

Handling Errors
---------------

If the calculated hash of the response body does not match the extracted
responses header, then a ``GuzzleHttp\Subscriber\MessageIntegrity\MessageIntegrityException``
is thrown. This exception extends from ``GuzzleHttp\Exception\RequestException``
so it contains a request accessed via ``getRequest()`` and a response via
``getResponse()``.

.. code-block:: php

    use GuzzleHttp\Client();
    use GuzzleHttp\Subscriber\MessageIntegrity\ResponseSubscriber;
    use GuzzleHttp\Subscriber\MessageIntegrity\MessageIntegrityException;

    $subscriber = ResponseSubscriber::createForContentMd5();
    $client = new Client();
    $client->getEmitter()->addSubscriber($subscriber);

    try {
        $client->get('http://httpbin.org/get');
    } catch (MessageIntegrityException $e) {
        echo $e->getRequest() . "\n";
        echo $e->getResponse() . "\n";
    }

Limitations
-----------

- Only works with seekable responses or streaming responses.
- Does not currently work with responses that use a ``Transfer-Encoding``
  header.
- Does not currently work with responses that use a ``Content-Encoding`` header.
