<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States\Meta;

use Stomp\Transport\Frame;

/**
 * Subscription Meta info
 *
 * @package Stomp\States\Meta
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Subscription
{
    private int|string $subscriptionId;

    private string $selector;

    private string $destination;

    private string $ack;

    private array $header;

    /**
     * @param array $header additionally passed to create this subscription
     */
    public function __construct(
        string $destination,
        string $selector,
        string $ack,
        int|string $subscriptionId,
        array $header = []
    ) {
        $this->subscriptionId = $subscriptionId;
        $this->selector       = $selector;
        $this->destination    = $destination;
        $this->ack            = $ack;
        $this->header         = $header;
    }

    public function getSubscriptionId() : int|string
    {
        return $this->subscriptionId;
    }

    public function getSelector() : string
    {
        return $this->selector;
    }

    public function getDestination() : string
    {
        return $this->destination;
    }

    public function getAck() : string
    {
        return $this->ack;
    }

    public function getHeader() : array
    {
        return $this->header;
    }

    /**
     * Checks if the given frame belongs to current Subscription.
     */
    public function belongsTo(Frame $frame) : bool
    {
        return ($frame['subscription'] === $this->subscriptionId);
    }
}
