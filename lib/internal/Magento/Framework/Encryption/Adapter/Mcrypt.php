<?php

namespace Magento\Framework\Encryption\Adapter;

class Mcrypt implements EncryptionAdapterInterface
{
    /**
     * @var string
     */
    private $cipher;

    /**
     * @var string
     */
    private $mode;

    /**
     * @var string
     */
    private $initVector;

    /**
     * @var resource
     */
    private $handle;

    /**
     * Mcrypt constructor.
     * @param $key
     * @param string $cipher
     * @param string $mode
     * @param bool $initVector
     * @throws \Exception
     */
    public function __construct(
        $key,
        $cipher = MCRYPT_BLOWFISH,
        $mode = MCRYPT_MODE_ECB,
        $initVector = false
    ) {
        $this->cipher = $cipher;
        $this->mode = $mode;
        // @codingStandardsIgnoreStart
        $this->handle = @mcrypt_module_open($cipher, '', $mode, '');
        // @codingStandardsIgnoreEnd
        try {
            // @codingStandardsIgnoreStart
            $maxKeySize = @mcrypt_enc_get_key_size($this->handle);
            // @codingStandardsIgnoreEnd
            if (strlen($key) > $maxKeySize) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    new \Magento\Framework\Phrase('Key must not exceed %1 bytes.', [$maxKeySize])
                );
            }
            // @codingStandardsIgnoreStart
            $initVectorSize = @mcrypt_enc_get_iv_size($this->handle);
            // @codingStandardsIgnoreEnd
            if (true === $initVector) {
                /* Generate a random vector from human-readable characters */
                $abc = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $initVector = '';
                for ($i = 0; $i < $initVectorSize; $i++) {
                    $initVector .= $abc[rand(0, strlen($abc) - 1)];
                }
            } elseif (false === $initVector) {
                /* Set vector to zero bytes to not use it */
                $initVector = str_repeat("\0", $initVectorSize);
            } elseif (!is_string($initVector) || strlen($initVector) != $initVectorSize) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    new \Magento\Framework\Phrase(
                        'Init vector must be a string of %1 bytes.',
                        [$initVectorSize]
                    )
                );
            }
            $this->_initVector = $initVector;
        } catch (\Exception $e) {
            // @codingStandardsIgnoreStart
            @mcrypt_module_close($this->handle);
            // @codingStandardsIgnoreEnd
            throw $e;
        }
        // @codingStandardsIgnoreStart
        @mcrypt_generic_init($this->handle, $key, $initVector);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Destructor frees allocated resources
     */
    public function __destruct()
    {
        // @codingStandardsIgnoreStart
        @mcrypt_generic_deinit($this->handle);
        // @codingStandardsIgnoreEnd
        // @codingStandardsIgnoreStart
        @mcrypt_module_close($this->handle);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Retrieve a name of currently used cryptographic algorithm
     *
     * @return string
     */
    public function getCipher()
    {
        return $this->cipher;
    }

    /**
     * Mode in which cryptographic algorithm is running
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Retrieve an actual value of initial vector that has been used to initialize a cipher
     *
     * @return string
     */
    public function getInitVector()
    {
        return $this->initVector;
    }

    /**
     * Encrypt a data
     *
     * @param  string $data String to encrypt
     * @return string
     * @throws \Exception
     */
    public function encrypt($data)
    {
        throw new \Exception((string)__('Mcrypt cannot be used for encryption. Use Sodium instead'));
    }

    /**
     * @param string $data
     * @return string
     */
    public function decrypt($data)
    {
        if (strlen($data) == 0) {
            return $data;
        }
        // @codingStandardsIgnoreStart
        $data = @mdecrypt_generic($this->handle, $data);
        // @codingStandardsIgnoreEnd
        /*
         * Returned string can in fact be longer than the unencrypted string due to the padding of the data
         * @link http://www.php.net/manual/en/function.mdecrypt-generic.php
         */
        $data = rtrim($data, "\0");
        return $data;
    }
}
