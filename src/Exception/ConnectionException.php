<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Exception;

/**
 * Any kind of connection problems.
 *
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConnectionException extends StompException
{
    private array $connectionInfo = [];

    public function __construct(string $info, array $connection = [], ?ConnectionException $previous = null)
    {
        $this->connectionInfo = $connection;

        $host = ($previous ? $previous->getHostname() : null) ?: $this->getHostname();
        
        if ($host) {
            $info = sprintf('%s (Host: %s)', $info, $host);
        }

        parent::__construct($info, 0, $previous);
    }


    /**
     * Active used connection.
     */
    public function getConnectionInfo() : array
    {
        return $this->connectionInfo;
    }


    protected function getHostname() : ?string
    {
        return isset($this->connectionInfo['host']) ? $this->connectionInfo['host'] : null;
    }
}
