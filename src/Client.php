<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp;

use Generator;
use Stomp\Exception\ConnectionException;
use Stomp\Exception\MissingReceiptException;
use Stomp\Exception\StompException;
use Stomp\Exception\UnexpectedResponseException;
use Stomp\Network\Connection;
use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;
use Stomp\Transport\Frame;

/**
 * Stomp Client
 *
 * This is the minimal implementation of a Stomp Client, it allows to send and receive Frames using the Stomp Protocol.
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Client
{
    /**
     * Perform request synchronously
     */
    private bool $sync = true;


    /**
     * Client id used for durable subscriptions
     */
    private string $clientId;

    /**
     * Connection session id
     */
    private ?string $sessionId = null;

    /**
     * Frames that have been read but not processed yet.
     *
     * @var Frame[]
     */
    private array $unprocessedFrames = [];

    private ?Connection $connection = null;

    private ?Protocol $protocol = null;

    /**
     * Seconds to wait for a receipt.
     */
    private float $receiptWait = 2;

    private string $login;

    private string $passcode;

    private array $versions = [Version::VERSION_1_0, Version::VERSION_1_1, Version::VERSION_1_2];

    private ?string $host = null;

    /**
     * @var int[]
     */
    private array $heartbeat = [0, 0];

    private bool $isConnecting = false;

    /**
     * Constructor
     *
     * @param string|Connection $broker Broker URL or a connection
     * @see Connection::__construct()
     */
    public function __construct(string|Connection $broker)
    {
        $this->connection = $broker instanceof Connection ? $broker : new Connection($broker);
    }

    /**
     * Configure versions to support.
     *
     * @param array $versions defaults to all client supported versions
     */
    public function setVersions(array $versions) : void
    {
        $this->versions = $versions;
    }

    /**
     * Configure the login to use.
     */
    public function setLogin(string $login, string $passcode) : void
    {
        $this->login = $login;
        $this->passcode = $passcode;
    }

    /**
     * Sets an fixed vhostname, which will be passed on connect as header['host'].
     *
     * (null = Default value is the hostname determined by connection.)
     */
    public function setVhostname(?string $host = null) : void
    {
        $this->host = $host;
    }

    /**
     * Set the desired heartbeat for the connection.
     *
     * A heartbeat is a specific message that will be send / received when no other data is send / received
     * within an interval - to indicate that the connection is still stable. If client and server agree on a beat and
     * the interval passes without any data activity / beats the connection will be considered as broken and closed.
     *
     * If you want to make sure that the server is still available, you should use the ServerAliveObserver
     * in combination with an requested server heartbeat interval.
     *
     * If you define a heartbeat for client side, you must assure that
     * your application will send data within the interval.
     * You can add \Stomp\Network\Observer\HeartbeatEmitter to your connection in order to send beats automatically.
     *
     * If you don't use HeartbeatEmitter you must either send messages within the interval
     * or make calls to Connection::sendAlive()
     *
     * @param int $send
     *   Number of milliseconds between expected sending of heartbeats. 0 means
     *   no heartbeats sent.
     * @param int $receive
     *   Number of milliseconds between expected receipt of heartbeats. 0 means
     *   no heartbeats expected. (not yet supported by this client)
     * @see \Stomp\Network\Observer\ServerAliveObserver
     * @see \Stomp\Network\Observer\HeartbeatEmitter
     * @see \Stomp\Network\Connection::sendAlive()
     */
    public function setHeartbeat(int $send = 0, int $receive = 0) : void
    {
        $this->heartbeat = [$send, $receive];
    }

    /**
     * Connect to server
     *
     * @throws StompException
     * @see setVhostname
     */
    public function connect() : bool
    {
        if ($this->isConnected()) {
            return true;
        }
        $this->isConnecting = true;
        $this->connection->connect();
        $this->connection->getParser()->legacyMode(true);
        $this->protocol = new Protocol($this->clientId);

        $this->host = $this->host ?: $this->connection->getHost();

        $connectFrame = $this->protocol->getConnectFrame(
            $this->login,
            $this->passcode,
            $this->versions,
            $this->host,
            $this->heartbeat
        );
        $this->sendFrame($connectFrame, false);

        if ($frame = $this->getConnectedFrame()) {
            $version = new Version($frame);

            if ($version->hasVersion(Version::VERSION_1_1)) {
                $this->connection->getParser()->legacyMode(false);
            }

            $this->sessionId = $frame['session'];
            $this->protocol = $version->getProtocol($this->clientId);
            $this->isConnecting = false;
            return true;
        }
        throw new ConnectionException('Connection not acknowledged');
    }

    /**
     * Returns the next available frame from the connection, respecting the connect timeout.
     * @throws ConnectionException
     * @throws Exception\ErrorFrameException
     */
    private function getConnectedFrame() : ?Frame
    {
        $deadline = microtime(true) + $this->getConnection()->getConnectTimeout();
        do {
            if ($frame = $this->connection->readFrame()) {
                return $frame;
            }
        } while (microtime(true) <= $deadline);

        return null;
    }

    /**
     * Send a message to a destination in the messaging system
     *
     * @param string $destination Destination queue
     * @param string|Frame $msg Message
     * @param array $header
     * @param boolean $sync Perform request synchronously
     */
    public function send(string $destination, string|Frame $msg, array $header = [], ?bool $sync = null) : bool
    {
        if (!$msg instanceof Frame) {
            return $this->send($destination, new Frame('SEND', $header, $msg), [], $sync);
        }

        $msg->addHeaders($header);
        $msg['destination'] = $destination;

        return $this->sendFrame($msg, $sync);
    }

    /**
     * Send a frame.
     */
    public function sendFrame(Frame $frame, ?bool $sync = null) : bool
    {
        if (!$this->isConnecting && !$this->isConnected()) {
            $this->connect();
        }
        // determine if client was configured to write sync or not
        $writeSync = $sync !== null ? $sync : $this->sync;
        if ($writeSync) {
            return $this->sendFrameExpectingReceipt($frame);
        } else {
            return $this->connection->writeFrame($frame);
        }
    }

    /**
     * Write frame to server and expect an matching receipt frame
     */
    protected function sendFrameExpectingReceipt(Frame $stompFrame) : bool
    {
        $receipt = md5(microtime());
        $stompFrame['receipt'] = $receipt;
        $this->connection->writeFrame($stompFrame);
        return $this->waitForReceipt($receipt);
    }

    /**
     * Wait for a receipt
     *
     * @throws UnexpectedResponseException If response has an invalid receipt.
     * @throws MissingReceiptException     If no receipt is received.
     */
    protected function waitForReceipt(string $receipt) : bool
    {
        $stopAfter = $this->calculateReceiptWaitEnd();
        while (true) {
            if ($frame = $this->connection->readFrame()) {
                if ($frame->getCommand() == 'RECEIPT') {
                    if ($frame['receipt-id'] == $receipt) {
                        return true;
                    } else {
                        throw new UnexpectedResponseException($frame, sprintf('Expected receipt id %s', $receipt));
                    }
                } else {
                    $this->unprocessedFrames[] = $frame;
                }
            }
            if (microtime(true) >= $stopAfter) {
                break;
            }
        }
        throw new MissingReceiptException($receipt);
    }

    /**
     * Returns the timestamp with micro time to stop wait for a receipt.
     */
    protected function calculateReceiptWaitEnd() : float
    {
        return microtime(true) + $this->receiptWait;
    }

    /**
     * Read response frame from server.
     */
    public function readFrame() : ?Frame
    {
        return array_shift($this->unprocessedFrames) ?: $this->connection->readFrame();
    }

    /**
     * Graceful disconnect from the server
     */
    public function disconnect(?bool $sync = false) : void
    {
        try {
            if ($this->connection && $this->connection->isConnected()) {
                if ($this->protocol) {
                    $this->sendFrame($this->protocol->getDisconnectFrame(), $sync);
                }
            }
        } catch (StompException $ex) {
            // nothing!
        }
        if ($this->connection) {
            $this->connection->disconnect();
        }

        $this->sessionId = null;
        $this->unprocessedFrames = [];
        $this->protocol = null;
        $this->isConnecting = false;
    }

    /**
     * Current stomp session ID
     */
    public function getSessionId() : ?string
    {
        return $this->sessionId;
    }

    /**
     * Graceful object destruction
     *
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Check if client session has been established
     */
    public function isConnected() : bool
    {
        return !empty($this->sessionId) && $this->connection->isConnected();
    }

    /**
     * Get the used connection.
     */
    public function getConnection() : Connection
    {
        return $this->connection;
    }

    /**
     * Get the currently used protocol.
     */
    public function getProtocol() : ?Protocol
    {
        if (!$this->isConnecting && !$this->isConnected()) {
            $this->connect();
        }
        return $this->protocol;
    }

    public function getClientId() : string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId) : static
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * Set seconds to wait for a receipt.
     */
    public function setReceiptWait(float $seconds) : void
    {
        $this->receiptWait = $seconds;
    }

    /**
     * Check if client runs in synchronized mode, which is the default operation mode.
     */
    public function isSync() : bool
    {
        return $this->sync;
    }

    /**
     * Toggle synchronized mode.
     */
    public function setSync(bool $sync) : void
    {
        $this->sync = $sync;
    }

    /**
     * Check if all buffers are empty.
     */
    public function isBufferEmpty() : bool
    {
        if (empty($this->unprocessedFrames) === false) {
            return false;
        }

        if ($this->getConnection()->getParser()->isBufferEmpty() === false) {
            return false;
        }

        try {
            return $this->getConnection()->isConnected() == false || $this->getConnection()->hasDataToRead() === false;
        } catch (ConnectionException $connectionException) {
            return true;
        }
    }

    /**
     * Generates a set of all frames that are received but not processed.
     *
     * @return Generator|Frame[]
     */
    public function flushBufferedFrames() : Generator
    {
        foreach ($this->unprocessedFrames as $unprocessedFrame) {
            yield $unprocessedFrame;
        }
        while ($frame = $this->getConnection()->getParser()->nextFrame()) {
            yield $frame;
        }
    }
}
