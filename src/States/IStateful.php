<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

use Stomp\States\Meta\SubscriptionList;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

/**
 * Interface IStateful methods that must be treated in every stomp state.
 *
 * @package Stomp\States
 */
interface IStateful
{
    /**
     * Acknowledge consumption of a message from a subscription
     */
    public function ack(Frame $frame) : void;

    /**
     * Not acknowledge consumption of a message from a subscription
     *
     * @param bool $requeue Requeue header not supported on all brokers
     */
    public function nack(Frame $frame, ?bool $requeue = null) : void;

    /**
     * Send a message.
     */
    public function send(string $destination, Message $message) : bool;

    /**
     * Begins a transaction.
     */
    public function begin() : void;

    /**
     * Commit current transaction.
     */
    public function commit() : void;

    /**
     * Abort current transaction.
     */
    public function abort() : void;

    /**
     * Subscribe to given destination.
     *
     * Returns the subscriptionId used for this, which can be null.
     */
    public function subscribe(string $destination, ?string $selector, string $ack, array $header = []) : int|string|null;

    /**
     * Unsubscribe from current or given destination.
     */
    public function unsubscribe(int|string|null $subscriptionId = null) : void;

    /**
     * Read a frame
     */
    public function read() : ?Frame;

    /**
     * Returns as list of all active subscriptions.
     */
    public function getSubscriptions() : SubscriptionList;
}
