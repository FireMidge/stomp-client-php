<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\ActiveMq;

use ArrayAccess;

/**
 * Options for ActiveMq Stomp
 *
 * For more details visit http://activemq.apache.org/stomp.html
 *
 * @package Stomp\Broker\ActiveMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Options implements ArrayAccess
{
    private array $extensions = [
        'activemq.dispatchAsync',
        'activemq.exclusive',
        'activemq.maximumPendingMessageLimit',
        'activemq.noLocal',
        'activemq.prefetchSize',
        'activemq.priority',
        'activemq.retroactive',
    ];

    private array $options = [];

    /**
     * Options constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
    }

    public function offsetExists(mixed $offset) : bool
    {
        return isset($this->options[$offset]);
    }

    public function offsetGet(mixed $offset) : mixed
    {
        return $this->options[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value) : void
    {
        if (in_array($offset, $this->extensions, true)) {
            $this->options[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset) : void
    {
        unset($this->options[$offset]);
    }

    public function getOptions() : array
    {
        return $this->options;
    }


    public function activateRetroactive() : static
    {
        $this->options['activemq.retroactive'] = 'true';
        return $this;
    }
    public function activateExclusive() : static
    {
        $this->options['activemq.exclusive'] = 'true';
        return $this;
    }

    public function activateDispatchAsync() : static
    {
        $this->options['activemq.dispatchAsync'] = 'true';
        return $this;
    }

    /**
     * Set the priority of the consumer.
     *
     * The broker orders a queue’s consumers according to their priorities,
     * dispatching messages to the highest priority consumers first.
     * Once a particular consumer’s prefetch buffer is full the broker will start dispatching messages to the consumer
     * with the next lowest priority whose prefetch buffer is not full.
     *
     * https://activemq.apache.org/consumer-priority
     *
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority(int $priority) : static
    {
        if ($priority > 127 || $priority < 0) {
            // https://activemq.apache.org/consumer-priority
            throw new \LogicException('Priority value must be within the range of 0 to 127');
        }

        $this->options['activemq.priority'] = $priority;
        return $this;
    }

    /**
     * The prefetch size limits the maximum number of messages that
     * can be dispatched to an individual consumer at once.
     * The consumer in turn uses the prefetch limit to size its prefetch message buffer.
     *
     * https://activemq.apache.org/what-is-the-prefetch-limit-for.html
     *
     * @param int $size  The maximum number of messages dispatched to an individual consumer at once.
     */
    public function setPrefetchSize(int $size) : static
    {
        $this->options['activemq.prefetchSize'] = max($size, 1);
        return $this;
    }


    public function activateNoLocal() : static
    {
        $this->options['activemq.noLocal'] = 'true';
        return $this;
    }

    public function setMaximumPendingLimit($limit) : static
    {
        $this->options['activemq.maximumPendingMessageLimit'] = $limit;
        return $this;
    }
}
