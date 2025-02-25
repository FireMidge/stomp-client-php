<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Transport;

/**
 * Basic text stomp message
 *
 * @package Stomp
 */
class Message extends Frame
{
    /**
     * Message constructor.
     */
    public function __construct(?string $body, array $headers = [])
    {
        parent::__construct('SEND', $headers, $body);
    }
}
