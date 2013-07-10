<?php

use Sms16\Sms\sms16;
use Sms16\Sms\messageSms16;

/*
 * Sms16 gateway bundle
 *
 */
class SmsProxy
{
    /**
     * Login to sms16 server
     * @var string
     */
    protected $login;

    /**
     * Password to sms16 server
     * @var string
     */
    protected $password;

    /**
     * Sender value. Limited by length of 10 chars.
     * @var string
     */
    protected $sender;

    /**
     * Instance of original api class
     * @var sms16
     */
    protected $provider;

    /**
     * Constructor
     * @param string $login
     * @param string $password
     * @param string $sender
     */
    public function __construct($login, $password, $sender) {
        $this->login = $login;
        $this->password = $password;
        $this->sender = $sender;
    }

    /**
     * Get api class instance
     * @return sms16
     */
    public function getProvider() {
        return $this->provider ?: $this->provider = new sms16($this->login, $this->password);
    }

    /**
     * Send sms
     * @param string $phone
     * @param string $text
     * @param string|null $sender
     * @return object - decoded simplexml
     */
    public function send($phone, $text, $sender=null) {
        if (is_null($sender)) $sender = $this->sender;

        $message = new messageSms16(array('abonent' => $phone, 'text' => $text, 'sender' => $sender));
        return $this->getProvider()->send(array($message));
    }

}