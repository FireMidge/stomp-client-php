<?php


namespace Stomp\States;

use Stomp\States\Exception\DrainingMessageException;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

class DrainingConsumerState extends StateTemplate
{

    /**
     * Activates the current state, after it has been applied on base.
     */
    protected function init(array $options = []) : int|string|null
    {
        return null;
    }

    /**
     * Returns the options needed in current state.
     */
    protected function getOptions() : array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function ack(Frame $frame) : void
    {
        $this->getClient()->sendFrame($this->getProtocol()->getAckFrame($frame), false);
    }

    /**
     * @inheritdoc
     */
    public function nack(Frame $frame, bool $requeue = null) : void
    {
        $this->getClient()->sendFrame($this->getProtocol()->getNackFrame($frame, null, $requeue), false);
    }

    /**
     * @inheritdoc
     */
    public function send(string $destination, Message $message) : bool
    {
        return $this->getClient()->send($destination, $message);
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
            new ProducerState($this->getClient(), $this->getBase())
        );

        return null;
    }

    /**
     * @inheritdoc
     */
    public function begin() : void
    {
        throw new DrainingMessageException($this->getClient(), $this, __FUNCTION__);
    }

    /**
     * @inheritdoc
     */
    public function subscribe(string $destination, ?string $selector, string $ack, array $header = []) : int|string|null
    {
        throw new DrainingMessageException($this->getClient(), $this, __FUNCTION__);
    }
}
