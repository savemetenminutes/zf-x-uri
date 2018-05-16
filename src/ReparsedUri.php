<?php

namespace Smtm\Zfx\Uri;

use Zend\Uri\Uri;
use Zend\Validator\Ip;

class ReparsedUri extends Uri
{
    const IP_ADDRESS_OCTET_REGEX =
        '(?:(?:[1-2]{1}[0-5]{1}[0-5]{1})|(?:[0]{0,1}[0-9]{1}[0-9]{1})|(?:[0]{0,1}[0]{0,1}[0-9]{1}))';
    const IP_ADDRESS_REGEX =
        self::IP_ADDRESS_OCTET_REGEX.
        '\.'.
        self::IP_ADDRESS_OCTET_REGEX.
        '\.'.
        self::IP_ADDRESS_OCTET_REGEX.
        '\.'.
        self::IP_ADDRESS_OCTET_REGEX
    ;
    const DEFAULT_SCHEME = 'https';

    public function __construct($uri = null)
    {
        parent::__construct();
        $schemeUri = explode('://', $uri);
        $noSchemeUri = array_pop($schemeUri);
        $scheme = array_shift($schemeUri);
        $userInfoUri = explode('@', $noSchemeUri);
        $noUserInfoUri = array_pop($userInfoUri);
        $userInfo = array_shift($userInfoUri);
        $hostUri = preg_split('[?/#]', $noUserInfoUri);
        $host = array_shift($hostUri);
        $pathQueryFragmentUri = implode($hostUri);
        $fragmentUri = explode('#', $pathQueryFragmentUri);
        $fragment = (count($fragmentUri) > 1) ? array_pop($fragmentUri) : null;
        $pathQueryUri = implode($fragmentUri);
        $queryUri = explode('?', $pathQueryUri);
        $query = (count($queryUri) > 1) ? array_pop($queryUri) : null;
        $path = implode($queryUri);
        $this->setScheme($scheme);
        $this->setUserInfo($userInfo);
        $this->setHost($host);
        $this->setPath($path);
        $this->setQuery($query);
        $this->setFragment($fragment);
    }
}