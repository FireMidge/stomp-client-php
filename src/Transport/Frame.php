<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Transport;

use ArrayAccess;

/**
 * Stomp Frames are messages that are sent and received on a stomp connection.
 *
 * @package Stomp
 */
class Frame implements ArrayAccess
{
    /**
     * Stomp Command
     */
    protected string $command;

    /**
     * Frame Headers
     */
    protected array $headers;

    /**
     * Frame Content
     */
    public mixed $body;

    /**
     * Frame should set a content-length header on transmission
     */
    private bool $addLengthHeader = false;

    /**
     * Whether frame is in stomp 1.0 mode
     */
    private bool $legacyMode = false;

    /**
     * Constructor
     */
    public function __construct(string $command, array $headers = [], ?string $body = null)
    {
        $this->command = $command;
        $this->headers = $headers ?: [];
        $this->body    = $body;
    }

    /**
     * Add given headers to currently set headers.
     *
     * Will override existing keys.
     *
     * @param array $header
     */
    public function addHeaders(array $header) : static
    {
        $this->headers += $header;
        return $this;
    }

    /**
     * Stomp message Id
     */
    public function getMessageId() : string
    {
        return $this['message-id'];
    }

    public function isErrorFrame() : bool
    {
        return ($this->command === 'ERROR');
    }

    /**
     * Tell the frame that we expect a length header.
     */
    public function expectLengthHeader(bool $expected = false) : void
    {
        $this->addLengthHeader = $expected;
    }

    /**
     * Enable legacy mode for this frame.
     */
    public function legacyMode(bool $legacy = false) : void
    {
        $this->legacyMode = $legacy;
    }

    /**
     * Frame is in legacy mode.
     */
    public function isLegacyMode() : bool
    {
        return $this->legacyMode;
    }

    public function getCommand() : string
    {
        return $this->command;
    }

    public function getBody() : ?string
    {
        return $this->body;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * Convert frame to transportable string
     */
    public function __toString() : string
    {
        $data = $this->command . "\n";

        if (!$this->legacyMode) {
            if ($this->body && ($this->addLengthHeader || stripos($this->body, "\x00") !== false)) {
                $this['content-length'] = $this->getBodySize();
            }
        }

        foreach ($this->headers as $name => $value) {
            $data .= $this->encodeHeaderValue($name) . ':' . $this->encodeHeaderValue($value) . "\n";
        }

        $data .= "\n";
        $data .= $this->body;
        return $data . "\x00";
    }

    /**
     * Size of Frame body.
     */
    protected function getBodySize() : int
    {
        return strlen($this->body);
    }

    /**
     * Encodes header values.
     */
    protected function encodeHeaderValue(string $value) : string
    {
        if ($this->legacyMode) {
            return str_replace(["\n"], ['\n'], $value);
        }
        return str_replace(["\\", "\r", "\n", ':'], ["\\\\", '\r', '\n', '\c'], $value);
    }

    public function offsetExists(mixed $offset) : bool
    {
        return isset($this->headers[$offset]);
    }

    public function offsetGet($offset) : mixed
    {
        if (isset($this->headers[$offset])) {
            return $this->headers[$offset];
        }

        return null;
    }

    public function offsetSet(mixed $offset, mixed $value) : void
    {
        if ($value !== null) {
            $this->headers[$offset] = $value;
        }
    }

    public function offsetUnset($offset) : void
    {
        unset($this->headers[$offset]);
    }
}
