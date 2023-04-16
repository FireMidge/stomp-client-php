<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Exception;

use Stomp\Transport\Frame;

/**
 * Exception that occurs, when a frame / response was received that was not expected at this moment.
 *
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class UnexpectedResponseException extends StompException
{
    private Frame $frame;

    public function __construct(Frame $frame, string $expectedInfo)
    {
        $this->frame = $frame;
        parent::__construct(sprintf('Unexpected response received. %s', $expectedInfo));
    }

    public function getFrame() : Frame
    {
        return $this->frame;
    }
}
