<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\RabbitMq;

use Stomp\Exception\StompException;
use Stomp\Protocol\Protocol;
use Stomp\Transport\Frame;
use Stomp\Protocol\Version;

/**
 * RabbitMq Stomp dialect.
 *
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class RabbitMq extends Protocol
{

    /**
     * Prefetch Size for subscriptions.
     */
    private int $prefetchCount = 1;

    /**
     * RabbitMq subscribe frame.
     *
     * @param bool $durable durable subscription
     */
    public function getSubscribeFrame(
        string $destination,
        ?string $subscriptionId = null,
        string $ack = 'auto',
        ?string $selector = null,
        bool $durable = false
    ) : Frame {
        $frame = parent::getSubscribeFrame($destination, $subscriptionId, $ack, $selector);
        $frame['prefetch-count'] = $this->prefetchCount;
        if ($durable) {
            $frame['persistent'] = 'true';
        }
        return $frame;
    }

    /**
     * RabbitMq unsubscribe frame.
     */
    public function getUnsubscribeFrame(
        string $destination,
        ?string $subscriptionId = null,
        bool $durable = false
    ) : Frame
    {
        $frame = parent::getUnsubscribeFrame($destination, $subscriptionId);
        if ($durable) {
            $frame['persistent'] = 'true';
        }
        return $frame;
    }


    /**
     * Prefetch Count for subscriptions
     */
    public function getPrefetchCount() : int
    {
        return $this->prefetchCount;
    }

    /**
     * Prefetch Count for subscriptions
     */
    public function setPrefetchCount(int $prefetchCount) : void
    {
        $this->prefetchCount = $prefetchCount;
    }


    /**
     * Get message not acknowledge frame.
     *
     * @param bool $requeue Requeue header supported on RabbitMQ >= 3.4, ignored in prior versions
     *
     * @throws StompException
     */
    public function getNackFrame(Frame $frame, ?string $transactionId = null, ?bool $requeue = null) : Frame
    {
        if ($this->getVersion() === Version::VERSION_1_0) {
            throw new StompException('Stomp Version 1.0 has no support for NACK Frames.');
        }
        $nack = $this->createFrame('NACK');
        if ($requeue !== null) {
            $nack->addHeaders(['requeue' => $requeue ? 'true' : 'false']);
        }
        $nack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $nack['id'] = $frame->getMessageId();
        } else if ($this->hasVersion(Version::VERSION_1_1)) {
            $nack['subscription'] = $frame['subscription'];
        }

        $nack['message-id'] = $frame->getMessageId();
        return $nack;
    }
}
