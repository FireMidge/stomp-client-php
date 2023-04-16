<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

use Stomp\States\Exception\InvalidStateException;
use Stomp\Transport\Frame;

/**
 * ConsumerTransactionState client is a consumer within an transaction.
 *
 * @package Stomp\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConsumerTransactionState extends ConsumerState
{
    use TransactionsTrait;

    /**
     * @inheritdoc
     */
    protected function init(array $options = []) : int|string|null
    {
        $this->initTransaction($options);
        return parent::init($options);
    }

    /**
     * @inheritdoc
     */
    public function commit() : void
    {
        $this->getClient()->sendFrame(
            $this->getProtocol()->getCommitFrame($this->transactionId)
        );
        $this->setState(new ConsumerState($this->getClient(), $this->getBase()), parent::getOptions());
    }

    /**
     * @inheritdoc
     */
    public function abort() : void
    {
        $this->transactionAbort();
        $this->setState(new ConsumerState($this->getClient(), $this->getBase()), parent::getOptions());
    }

    /**
     * @inheritdoc
     */
    public function ack(Frame $frame) : void
    {
        $this->getClient()->sendFrame($this->getProtocol()->getAckFrame($frame, $this->transactionId), false);
    }

    /**
     * @inheritdoc
     */
    public function nack(Frame $frame, ?bool $requeue = null) : void
    {
        $this->getClient()->sendFrame(
            $this->getProtocol()->getNackFrame($frame, $this->transactionId, $requeue),
            false
        );
    }

    /**
     * @inheritdoc
     */
    public function unsubscribe(int|string|null $subscriptionId = null) : void
    {
        if ($this->endSubscription($subscriptionId)) {
            if ($this->getClient()->isBufferEmpty()) {
                $this->setState(
                    new ProducerTransactionState($this->getClient(), $this->getBase()),
                    ['transactionId' => $this->transactionId]
                );
            } else {
                $this->setState(
                    new DrainingTransactionConsumerState($this->getClient(), $this->getBase()),
                    ['transactionId' => $this->transactionId]
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function begin() : void
    {
        throw new InvalidStateException($this, __FUNCTION__);
    }
}
