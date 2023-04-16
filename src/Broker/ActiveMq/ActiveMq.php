<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\ActiveMq;

use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;
use Stomp\Transport\Frame;

/**
 * ActiveMq Stomp dialect.
 *
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ActiveMq extends Protocol
{
    /**
     * Prefetch Size for subscriptions.
     */
    private int $prefetchSize = 1;

    /**
     * ActiveMq subscribe frame.
     *
     * @param boolean|false $durable durable subscription
     */
    public function getSubscribeFrame(
        string $destination,
        string|int|null $subscriptionId = null,
        string $ack = 'auto',
        ?string $selector = null,
        ?bool $durable = false
    ) : Frame {
        $frame = parent::getSubscribeFrame($destination, $subscriptionId, $ack, $selector);
        $frame['activemq.prefetchSize'] = $this->prefetchSize;
        if ($durable) {
            $frame['activemq.subscriptionName'] = $this->getClientId();
            $frame['durable-subscriber-name'] = $subscriptionId;
        }
        return $frame;
    }

    /**
     * ActiveMq unsubscribe frame.
     */
    public function getUnsubscribeFrame(
        string $destination,
        string|int|null $subscriptionId = null,
        ?bool $durable = false
    ) : Frame {
        $frame = parent::getUnsubscribeFrame($destination, $subscriptionId);
        if ($durable) {
            $frame['activemq.subscriptionName'] = $this->getClientId();
            $frame['durable-subscriber-name'] = $subscriptionId;
        }
        return $frame;
    }

    /**
     * @inheritdoc
     */
    public function getAckFrame(Frame $frame, ?string $transactionId = null) : Frame
    {
        $ack = $this->createFrame('ACK');
        $ack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $ack['id'] = $frame['ack'] ?: $frame->getMessageId();
        } else {
            $ack['message-id'] = $frame['ack'] ?: $frame->getMessageId();
            if ($this->hasVersion(Version::VERSION_1_1)) {
                $ack['subscription'] = $frame['subscription'];
            }
        }
        return $ack;
    }

    /**
     * @inheritdoc
     */
    public function getNackFrame(Frame $frame, ?string $transactionId = null, ?bool $requeue = null) : Frame
    {
        if ($requeue !== null) {
            throw new \LogicException(
                'requeue header not supported by ActiveMQ. Please read ActiveMQ DLQ documentation.'
            );
        }
        $nack = $this->createFrame('NACK');
        $nack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $nack['id'] = $frame['ack'] ?: $frame->getMessageId();
        } else {
            $nack['message-id'] = $frame['ack'] ?: $frame->getMessageId();
            if ($this->hasVersion(Version::VERSION_1_1)) {
                $nack['subscription'] = $frame['subscription'];
            }
        }
        return $nack;
    }


    /**
     * Prefetch Size for subscriptions
     */
    public function getPrefetchSize() : int
    {
        return $this->prefetchSize;
    }

    /**
     * Prefetch Size for subscriptions
     */
    public function setPrefetchSize(int $prefetchSize) : static
    {
        $this->prefetchSize = $prefetchSize;
        return $this;
    }
}
