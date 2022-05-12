<?php

/*
 * This file is part of the PHP Input package.
 *
 * (c) Francesco Bianco <bianco@javanile.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Javanile\Imap2;

use Javanile\Imap2\ImapClient;

class Connection
{
    protected $mailbox;
    protected $user;
    protected $password;
    protected $flags;
    protected $retries;
    protected $options;
    protected $client;
    protected $host;
    protected $port;
    protected $sslMode;

    /**
     *
     */
    public function __construct($mailbox, $user, $password, $flags = 0, $retries = 0, $options = [])
    {
        $this->mailbox = $mailbox;
        $this->user = $user;
        $this->password = $password;
        $this->flags = $flags;
        $this->retries = $retries;
        $this->options = $options;

        $mailboxParts = Functions::parseMailboxString($mailbox);

        $this->host = Functions::getHostFromMailbox($mailboxParts);
        $this->port = $mailboxParts['port'];
        $this->sslMode = Functions::getSslModeFromMailbox($mailboxParts);

        $this->client = new ImapClient();
    }

    /**
     * Extract input value using input name from the context otherwise get back a default value.
     *
     * @param $inputName
     * @param $defaultValue
     * @return void
     */
    public static function open($mailbox, $user, $password, $flags = 0, $retries = 0, $options = [])
    {
        if ($flags & OP_XOAUTH2 || !function_exists('imap_open')) {
            $connection = new Connection($mailbox, $user, $password, $flags, $retries, $options);

            return $connection->connect();
        }

        return imap_open($mailbox, $user, $password, $flags, $retries, $options);
    }

    /**
     *
     */
    protected function connect()
    {
        //$this->client->setDebug(true);

        $success = $this->client->connect($this->host, $this->user, $this->password, [
            'port' => $this->port,
            'ssl_mode' => $this->sslMode,
            'auth_type' => $this->flags & OP_XOAUTH2 ? 'XOAUTH2' : 'CHECK'
        ]);

        if ($success) {
            return $this;
        }

        return false;
    }

    /**
     *
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     *
     */
    public static function close($imap)
    {
        if (is_a($imap, Connection::class)) {
            return $imap->client->close();
        }

        imap_close($imap);
    }
}