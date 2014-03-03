<?php

namespace GuzzleHttp\Subscriber\MessageIntegrity;

use GuzzleHttp\Stream\StreamDecoratorTrait;
use GuzzleHttp\Stream\StreamInterface;

/**
 * Stream decorator that validates a rolling hash of the entity body as it is
 * read.
 *
 * @todo Allow the file pointer to skip around and read bytes randomly
 */
class ReadIntegrityStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /** @var HashInterface */
    private $hash;

    /** @var callable|null */
    private $validationCallback;

    /** @var int Last position that the hash was updated at */
    private $lastHashPos = 0;

    /** @var bool */
    private $expected;

    /**
     * @param StreamInterface $stream   Stream that is validated
     * @param HashInterface   $hash     Hash used to calculate the rolling hash
     * @param string          $expected The expected hash result.
     * @param callable        $onFail   Optional function to invoke when there
     *     is a mismatch between the calculated hash and the expected hash.
     *     The callback is called with the resulting hash and the expected hash.
     *     This callback can be used to throw specific exceptions.
     */
    public function __construct(
        StreamInterface $stream,
        HashInterface $hash,
        $expected,
        callable $onFail = null
    ) {
        $this->stream = $stream;
        $this->hash = $hash;
        $this->validationCallback = $onFail;
        $this->expected = $expected;
    }

    public function read($length)
    {
        $data = $this->stream->read($length);
        // Only update the hash if this data has not already been read
        if ($this->tell() >= $this->lastHashPos) {
            $this->hash->update($data);
            $this->lastHashPos += $length;
            if ($this->eof()) {
                $result = base64_encode($this->hash->complete());
                if ($this->expected !== $result) {
                    $this->mismatch($result);
                }
            }
        }
    }

    private function mismatch($result)
    {
        if ($this->validationCallback) {
            call_user_func(
                $this->validationCallback,
                $result,
                $this->expected
            );
        }

        throw new \UnexpectedValueException(
            sprintf('Message integrity check failure. Expected %s '
                . 'but got %s', $this->expected, $result)
        );
    }
}
