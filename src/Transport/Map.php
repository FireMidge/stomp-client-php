<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Transport;

/**
 * Message that contains a set of name-value pairs
 *
 * @package Stomp
 */
class Map extends Message
{
    public mixed $map;

    /**
     * Constructor
     *
     * @param array|object|string $body string will get decoded (receive), otherwise the body will be encoded (send)
     * @param array $headers
     * @param string $command
     */
    public function __construct(array|object|string $body, array $headers = [], string $command = 'SEND')
    {
        if (is_string($body)) {
            parent::__construct($body, $headers);
            $this->map = json_decode($body, true);
        } else {
            parent::__construct(json_encode($body), $headers + ['transformation' => 'jms-map-json']);
            $this->map = $body;
        }

        $this->command = $command;
    }

    /**
     * Returns the received decoded json.
     *
     * @return mixed
     */
    public function getMap() : mixed
    {
        return $this->map;
    }
}
