<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Protocol;

use Stomp\Exception\StompException;
use Stomp\Transport\Frame;

/**
 * Stomp base protocol
 *
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Protocol
{
    /**
     * Client id used for durable subscriptions
     */
    private ?string $clientId;

    private string $version;

    /**
     * Server Version
     */
    private ?string $server;

    /**
     * Setup stomp protocol with configuration.
     */
    public function __construct(?string $clientId = null, string $version = Version::VERSION_1_0, ?string $server = null)
    {
        $this->clientId = $clientId;
        $this->server   = $server;
        $this->version  = $version;
    }

    /**
     * Get the connect frame
     *
     * @param int[] $heartbeat
     */
    final public function getConnectFrame(
        string $login = '',
        string $passcode = '',
        array $versions = [],
        ?string $host = null,
        array $heartbeat = [0, 0]
    ) : Frame {
        $frame = $this->createFrame('CONNECT');
        $frame->legacyMode(true);

        if ($login || $passcode) {
            $frame->addHeaders(['login' => $login, 'passcode' => $passcode]);
        }

        if ($this->hasClientId()) {
            $frame['client-id'] = $this->getClientId();
        }

        if (!empty($versions)) {
            $frame['accept-version'] = implode(',', $versions);
        }

        $frame['host'] = $host;

        $frame['heart-beat'] = $heartbeat[0] . ',' . $heartbeat[1];

        return $frame;
    }

    /**
     * Get subscribe frame.
     *
     * @throws StompException;
     */
    public function getSubscribeFrame(
        string $destination,
        ?string $subscriptionId = null,
        string $ack = 'auto',
        ?string $selector = null
    ) : Frame {
        // validate ACK types per spec
        // https://stomp.github.io/stomp-specification-1.0.html#frame-ACK
        // https://stomp.github.io/stomp-specification-1.1.html#ACK
        // https://stomp.github.io/stomp-specification-1.2.html#ACK
        if ($this->hasVersion(Version::VERSION_1_1)) {
            $validAcks = ['auto', 'client', 'client-individual'];
        } else {
            $validAcks = ['auto', 'client'];
        }

        if (! in_array($ack, $validAcks)) {
            throw new StompException(
                sprintf(
                    '"%s" is not a valid ack value for STOMP %s. A valid value is one of %s',
                    $ack,
                    $this->version,
                    implode(',', $validAcks)
                )
            );
        }
        
        $frame = $this->createFrame('SUBSCRIBE');

        $frame['destination'] = $destination;
        $frame['ack']         = $ack;
        $frame['id']          = $subscriptionId;
        $frame['selector']    = $selector;

        return $frame;
    }

    /**
     * Get unsubscribe frame.
     */
    public function getUnsubscribeFrame(string $destination, ?string $subscriptionId = null) : Frame
    {
        $frame = $this->createFrame('UNSUBSCRIBE');
        $frame['destination'] = $destination;
        $frame['id'] = $subscriptionId;

        return $frame;
    }

    /**
     * Get transaction begin frame.
     */
    public function getBeginFrame(?string $transactionId = null) : Frame
    {
        $frame = $this->createFrame('BEGIN');
        $frame['transaction'] = $transactionId;
        return $frame;
    }

    /**
     * Get transaction commit frame.
     */
    public function getCommitFrame(?string $transactionId = null) : Frame
    {
        $frame = $this->createFrame('COMMIT');
        $frame['transaction'] = $transactionId;
        return $frame;
    }

    /**
     * Get transaction abort frame.
     */
    public function getAbortFrame(?string $transactionId = null) : Frame
    {
        $frame = $this->createFrame('ABORT');
        $frame['transaction'] = $transactionId;
        return $frame;
    }

    /**
     * Get message acknowledge frame.
     */
    public function getAckFrame(Frame $frame, ?string $transactionId = null) : Frame
    {
        $ack = $this->createFrame('ACK');
        $ack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            if (isset($frame['ack'])) {
                $ack['id'] = $frame['ack'];
            } else {
                $ack['id'] = $frame->getMessageId();
            }
        } else {
            $ack['message-id'] = $frame->getMessageId();
            if ($this->hasVersion(Version::VERSION_1_1)) {
                $ack['subscription'] = $frame['subscription'];
            }
        }
        return $ack;
    }

    /**
     * Get message not acknowledge frame.
     *
     * @throws StompException
     * @throws \LogicException
     */
    public function getNackFrame(Frame $frame, ?string $transactionId = null, ?bool $requeue = null) : Frame
    {
        if ($requeue !== null) {
            throw new \LogicException('requeue header not supported');
        }
        if ($this->version === Version::VERSION_1_0) {
            throw new StompException('Stomp Version 1.0 has no support for NACK Frames.');
        }
        $nack = $this->createFrame('NACK');
        $nack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $nack['id'] = $frame->getMessageId();
        } else if ($this->hasVersion(Version::VERSION_1_1)) {
            $nack['subscription'] = $frame['subscription'];
        }

        $nack['message-id'] = $frame->getMessageId();
        return $nack;
    }

    /**
     * Get the disconnect frame.
     */
    public function getDisconnectFrame() : Frame
    {
        $frame = $this->createFrame('DISCONNECT');
        if ($this->hasClientId()) {
            $frame['client-id'] = $this->getClientId();
        }
        return $frame;
    }

    /**
     * Client Id is set
     */
    public function hasClientId() : bool
    {
        // It may be possible that clientId is an empty string
        return (bool) $this->clientId;
    }

    public function getClientId() : ?string
    {
        return $this->clientId;
    }

    /**
     * Stomp Version
     */
    public function getVersion() : string
    {
        return $this->version;
    }

    /**
     * Server Version Info
     */
    public function getServer() : ?string
    {
        return $this->server;
    }

    /**
     * Checks if given version is included (equal or lower) in active protocol version.
     */
    public function hasVersion(string $version) : bool
    {
        return version_compare($this->version, $version, '>=');
    }

    /**
     * Creates a Frame according to the detected STOMP version.
     */
    protected function createFrame(string $command) : Frame
    {
        $frame = new Frame($command);

        if ($this->version === Version::VERSION_1_0) {
            $frame->legacyMode(true);
        }

        return $frame;
    }
}
