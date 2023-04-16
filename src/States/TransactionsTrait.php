<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

use Stomp\Client;
use Stomp\Protocol\Protocol;
use Stomp\Transport\Message;
use Stomp\Util\IdGenerator;

/**
 * TransactionsTrait provides base logic for all transaction based states.
 *
 * @package Stomp\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
trait TransactionsTrait
{
    abstract public function getProtocol() : Protocol;

    abstract public function getClient() : Client;

    /**
     * Id used for current transaction.
     */
    protected int|string $transactionId;

    /**
     * Init the transaction state.
     */
    protected function initTransaction(array $options = [])
    {
        if (!isset($options['transactionId'])) {
            $this->transactionId = IdGenerator::generateId();
            $this->getClient()->sendFrame(
                $this->getProtocol()->getBeginFrame($this->transactionId)
            );
        } else {
            $this->transactionId = $options['transactionId'];
        }
    }

    /**
     * Options for this transaction state.
     */
    protected function getOptions() : array
    {
        return ['transactionId' => $this->transactionId];
    }

    /**
     * Send a message within this transaction.
     */
    public function send(string $destination, Message $message) : bool
    {
        return $this->getClient()->send($destination, $message, ['transaction' => $this->transactionId], false);
    }

    /**
     * Commit current transaction.
     */
    protected function transactionCommit() : void
    {
        $this->getClient()->sendFrame($this->getProtocol()->getCommitFrame($this->transactionId));
        IdGenerator::releaseId($this->transactionId);
    }

    /**
     * Abort the current transaction.
     */
    protected function transactionAbort() : void
    {
        $this->getClient()->sendFrame($this->getProtocol()->getAbortFrame($this->transactionId));
        IdGenerator::releaseId($this->transactionId);
    }
}
