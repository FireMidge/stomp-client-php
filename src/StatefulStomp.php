<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp;

use Stomp\States\IStateful;
use Stomp\States\Meta\SubscriptionList;
use Stomp\States\ProducerState;
use Stomp\States\StateSetter;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

/**
 * Stateful Stomp Client
 *
 * This is a stateful implementation of a stomp client.
 * This client will help you using stomp in a safe way by using the state machine pattern.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class StatefulStomp extends StateSetter implements IStateful
{
    private IStateful $state;
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->state = new ProducerState($client, $this);
    }

    /**
     * Acknowledge consumption of a message from a subscription
     */
    public function ack(Frame $frame) : void
    {
        $this->state->ack($frame);
    }

    /**
     * Not acknowledge consumption of a message from a subscription
     *
     * @param bool $requeue requeue header not supported in all brokers
     */
    public function nack(Frame $frame, ?bool $requeue = null) : void
    {
        $this->state->nack($frame, $requeue);
    }

    /**
     * Send a message.
     *
     * @return bool
     */
    public function send(string $destination, Message $message) : bool
    {
        return $this->state->send($destination, $message);
    }

    /**
     * Begins a transaction.
     */
    public function begin() : void
    {
        $this->state->begin();
    }

    /**
     * Commit current transaction.
     */
    public function commit() : void
    {
        $this->state->commit();
    }

    /**
     * Abort current transaction.
     */
    public function abort() : void
    {
        $this->state->abort();
    }

    /**
     * Subscribe to given destination.
     *
     * Returns the subscriptionId used for this.
     */
    public function subscribe(
        string $destination,
        ?string $selector = null,
        string $ack = 'auto',
        array $header = []
    ) : int|string|null {
        return $this->state->subscribe($destination, $selector, $ack, $header);
    }

    /**
     * Unsubscribe from current or given destination.
     */
    public function unsubscribe(string|int|null $subscriptionId = null) : void
    {
        $this->state->unsubscribe($subscriptionId);
    }

    /**
     * Returns as list of all active subscriptions.
     */
    public function getSubscriptions() : SubscriptionList
    {
        return $this->state->getSubscriptions();
    }


    /**
     * Read a frame
     */
    public function read() : ?Frame
    {
        return $this->state->read();
    }

    /**
     * Current State
     */
    public function getState() : IStateful
    {
        return $this->state;
    }

    /**
     * Changes the current state.
     *
     * @param IStateful $state
     */
    protected function setState(IStateful $state) : int|string|null
    {
        $this->state = $state;
        return null;
    }

    /**
     * Returns the used client.
     */
    public function getClient() : Client
    {
        return $this->client;
    }
}
