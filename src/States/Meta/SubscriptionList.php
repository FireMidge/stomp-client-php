<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States\Meta;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Stomp\Transport\Frame;
use Traversable;

/**
 * SubscriptionList meta info for active subscriptions.
 *
 * @package Stomp\States\Meta
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class SubscriptionList implements IteratorAggregate, ArrayAccess, Countable
{

    /**
     * @var Subscription[]
     */
    private array $subscriptions = [];

    /**
     * Returns the last added active Subscription.
     *
     * @return Subscription
     */
    public function getLast() : Subscription
    {
        return end($this->subscriptions);
    }

    /**
     * Returns the subscription the frame belongs to or null if no matching subscription was found.
     */
    public function getSubscription(Frame $frame) : ?Subscription
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->belongsTo($frame)) {
                return $subscription;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     *
     * @return \Iterator|Subscription[]
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->subscriptions);
    }

    public function offsetExists(mixed $offset) : bool
    {
        return isset($this->subscriptions[$offset]);
    }

    /**
     * @inheritdoc
     *
     * @return Subscription
     */
    public function offsetGet(mixed $offset) : mixed
    {
        return $this->subscriptions[$offset];
    }

    /**
     * @inheritdoc
     *
     * @param Subscription $value
     */
    public function offsetSet(mixed $offset, mixed $value) : void
    {
        $this->subscriptions[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset(mixed $offset) : void
    {
        unset($this->subscriptions[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function count() : int
    {
        return count($this->subscriptions);
    }
}
