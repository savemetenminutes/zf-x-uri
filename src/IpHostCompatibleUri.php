<?php

namespace Smtm\Zfx\Uri;

use Zend\Uri\Uri;
use Zend\Validator\Ip;

class IpHostCompatibleUri extends Uri
{
    const DEFAULT_SCHEME = 'https';

    public function __construct($uri = null)
    {
        parent::__construct($uri);

        $ipAddressValidator = new Ip();

        $normalizedUrl = clone $this;
        if(! $this->getHost()) {
            $normalizedUrl = new Uri(self::DEFAULT_SCHEME.'://'.$this->toString());
        }
        if($ipAddressValidator->isValid($normalizedUrl->getHost())) {
            $this->setHost();
        }
    }
}