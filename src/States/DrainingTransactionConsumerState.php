<?php


namespace Stomp\States;

use Stomp\States\Exception\DrainingMessageException;
use Stomp\Transport\Frame;

class DrainingTransactionConsumerState extends ConsumerState
{
    use TransactionsTrait;

    protected function init(array $options = []) : int|string|null
    {
        $this->initTransaction($options);
        return parent::init($options);
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
    public function read() : ?Frame
    {
        if ($frame = $this->getClient()->readFrame()) {
            return $frame;
        }
        $this->setState(
            new ProducerTransactionState($this->getClient(), $this->getBase()),
            ['transactionId' => $this->transactionId]
        );
        return null;
    }

    /**
     * @inheritdoc
     */
    public function commit() : void
    {
        throw new DrainingMessageException($this->getClient(), $this, __FUNCTION__);
    }

    /**
     * @inheritdoc
     */
    public function abort() : void
    {
        throw new DrainingMessageException($this->getClient(), $this, __FUNCTION__);
    }
}
