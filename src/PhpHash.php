<?php

namespace GuzzleHttp\Subscriber\MessageIntegrity;

/**
 * Incremental hashing using PHP's hash functions.
 */
class PhpHash implements HashInterface
{
    private $context;
    private $algo;
    private $options;

    /**
     * @param string $algo Hashing algorithm. One of PHP's hash_algos()
     *     return values (e.g. md5, sha1, etc...).
     * @param array  $options Associative array of hashing options:
     *     - key: Secret key used with the hashing algorithm.
     *     - base64: Set to true to base64 encode the value when complete.
     */
    public function __construct($algo, array $options = [])
    {
        $this->algo = $algo;
        $this->options = $options;
    }

    public function update($data)
    {
        hash_update($this->getContext(), $data);
    }

    public function complete()
    {
        $hash = hash_final($this->getContext(), true);

        if (isset($this->options['base64']) && $this->options['base64']) {
            $hash = base64_encode($hash);
        }

        return $hash;
    }

    /**
     * Get a hash context or create one if needed
     *
     * @return resource
     */
    private function getContext()
    {
        if (!$this->context) {
            $key = isset($this->options['key']) ? $this->options['key'] : null;
            $this->context = hash_init(
                $this->algo,
                $key ? HASH_HMAC : 0,
                $key
            );
        }

        return $this->context;
    }
}
