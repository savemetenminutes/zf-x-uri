<?php

namespace Smtm\Zfx\Uri;

use Smtm\Zfx\Uri\Exception;
use Zend\Uri\Uri;
use Zend\Validator\Hostname;
use Zend\Validator\Ip;
use Zend\Validator\ValidatorInterface;

class ReparsedUri extends Uri
{
    const ALLOW_ALL = '*';
    /*
    const IP_ADDRESS_OCTET_REGEX =
        '(?:(?:[1-2]{1}[0-5]{1}[0-5]{1})|(?:[0]{0,1}[0-9]{1}[0-9]{1})|(?:[0]{0,1}[0]{0,1}[0-9]{1}))';
    const IP_ADDRESS_REGEX =
        self::IP_ADDRESS_OCTET_REGEX .
        '\.' .
        self::IP_ADDRESS_OCTET_REGEX .
        '\.' .
        self::IP_ADDRESS_OCTET_REGEX .
        '\.' .
        self::IP_ADDRESS_OCTET_REGEX;
    const DEFAULT_SCHEME = 'https';
    */

    protected $hostValidator;
    protected $ipValidator;
    protected $uriSyntaxValidator; // TODO: Add custom validator
    protected $isMimeContent;
    protected $isJavaScript;
    protected $isMailTo;
    protected $content;
    protected $isFragment;
    protected $isSchemeRelative;
    protected $isPathRelative;
    protected $isHostIpAddress;
    protected $subdomains;
    protected $domain;
    protected $tld;
    protected $allowedSchemes = [self::ALLOW_ALL];
    protected $disallowedSchemes = [];

    public function __construct($uri = null)
    {
        parent::__construct($uri);
    }

    public function parse($uri)
    {
        $this->reset();

        $scheme = null;
        $isMimeContent = 0;
        $isJavaScript = 0;
        $isMailTo = 0;
        $content = null;
        $isFragment = 0;
        $userInfo = null;
        $host = null;
        $port = null;
        $path = null;
        $query = null;
        $fragment = null;

        $tld = null;
        $domain = null;
        $subdomains = null;

        $isSchemeRelative = null;
        $isPathRelative = null;
        $isHostIpAddress = null;

        if(strstr($uri, 'javascript:') === 0) {
            $isMimeContent = 1;
            $isJavaScript = 1;
            $content = substr($uri, 11);
        } else {
            if(strstr($uri, 'mailto:') === 0) {
                $isMimeContent = 1;
                $isMailTo = 1;
                $content = substr($uri, 7);
            } else {
                if (
                    (substr_count($uri, '@') > 1)
                    ||
                    (substr_count($uri, '#') > 1)
                    ||
                    (substr_count($uri, ':') > 3)
                ) {
                    throw new Exception\UriFormatException(Exception\UriFormatException::MESSAGE_INVALID_URI_FORMAT,
                        Exception\UriFormatException::CODE_INVALID_URI_FORMAT);
                }

                if(strstr($uri, '#') === 0) {
                    $isFragment = 1;
                    $fragment = substr($uri, 1);
                } else {
                    $schemeUri        = explode('://', $uri);
                    $isSchemeRelative = ((count($schemeUri) > 1) && (reset($schemeUri) === '')) ? true : false;
                    $noSchemeUri      = array_pop($schemeUri);
                    $scheme           = $isSchemeRelative ? null : array_shift($schemeUri);

                    $userInfoUri   = explode('@', $noSchemeUri);
                    $noUserInfoUri = array_pop($userInfoUri);
                    $userInfo      = array_shift($userInfoUri);

                    $hostUri         = preg_split('#[\?/\#]#', $noUserInfoUri);
                    $hostPort        = array_shift($hostUri);
                    $hostPortUri     = explode(':', $hostPort);
                    $port            = (count($hostPortUri) > 1) ? array_pop($hostPortUri) : null;
                    $port            = ($port !== '') ? $port : null;
                    $host            = implode($hostPortUri);
                    $validHostPort   = ($this->hostValidator !== null) ? $this->hostValidator->isValid($hostPort) : true;
                    $validHost       = ($this->hostValidator !== null) ? $this->hostValidator->isValid($host) : true;
                    $validHost       = ($host === 'localhost') ? true : $validHost;
                    $host            = ($validHost || ($port !== null)) ? $host : null;
                    $isHostIpAddress = ($this->ipValidator !== null) ? $this->ipValidator->isValid($host) : null;
                    $subdomains      = null;
                    $domain          = null;
                    $tld             = null;
                    if (!$isHostIpAddress) {
                        $domains    = explode('.', $host);
                        $tld        = array_pop($domains);
                        $domain     = array_pop($domains);
                        $subdomains = implode('.', $domains);
                    }

                    $pathQueryFragmentUri = ($host !== null) ? substr($noUserInfoUri,
                        strlen($hostPort)) : $noUserInfoUri;
                    $fragmentUri          = explode('#', $pathQueryFragmentUri);
                    $fragment             = (count($fragmentUri) > 1) ? array_pop($fragmentUri) : null;

                    $pathQueryUri = implode($fragmentUri);
                    $queryUri     = explode('?', $pathQueryUri);
                    $query        = (count($queryUri) > 1) ? array_pop($queryUri) : null;

                    $isPathRelative = (strpos($pathQueryUri, '/') !== 0) ? true : false;
                    $path           = implode($queryUri);
                    $path           = ($path !== '') ? $path : null;
                }
            }
        }

        $this->setIsMimeContent($isMimeContent);
        $this->setIsJavaScript($isJavaScript);
        $this->setIsMailTo($isMailTo);
        $this->setContent($content);
        $this->setIsFragment($isFragment);
        $this->setScheme($scheme);
        $this->setUserInfo($userInfo);
        $this->setHost($host);
        $this->setPort($port);
        $this->setPath($path);
        $this->setQuery($query);
        $this->setFragment($fragment);

        $this->setTld($tld);
        $this->setDomain($domain);
        $this->setSubdomains($subdomains);

        $this->setIsSchemeRelative($isSchemeRelative);
        $this->setIsPathRelative($isPathRelative);
        $this->setIsHostIpAddress($isHostIpAddress);
    }

    public function reset()
    {
        parent::reset(); // TODO: Change the autogenerated stub
        $this->setIsFragment(null);
        $this->setIsMimeContent(null);
        $this->setIsJavaScript(null);
        $this->setIsMailTo(null);
        $this->setContent(null);
        $this->setIsSchemeRelative(null);
        $this->setIsPathRelative(null);
        $this->setIsHostIpAddress(null);
    }

    /**
     * @return mixed
     */
    public function getHostValidator()
    {
        return $this->hostValidator;
    }

    /**
     * @param ValidatorInterface | null $hostValidator
     * @return ReparsedUri
     */
    public function setHostValidator($hostValidator = null)
    {
        $this->hostValidator = $hostValidator;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIpValidator()
    {
        return $this->ipValidator;
    }

    /**
     * @param ValidatorInterface | null $ipValidator
     * @return ReparsedUri
     */
    public function setIpValidator(ValidatorInterface $ipValidator = null)
    {
        $this->ipValidator = $ipValidator;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUriSyntaxValidator()
    {
        return $this->uriSyntaxValidator;
    }

    /**
     * @param mixed $uriSyntaxValidator
     * @return ReparsedUri
     */
    public function setUriSyntaxValidator($uriSyntaxValidator)
    {
        $this->uriSyntaxValidator = $uriSyntaxValidator;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsMimeContent()
    {
        return $this->isMimeContent;
    }

    /**
     * @param mixed $isMimeContent
     * @return ReparsedUri
     */
    public function setIsMimeContent($isMimeContent)
    {
        $this->isMimeContent = $isMimeContent;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsJavaScript()
    {
        return $this->isJavaScript;
    }

    /**
     * @param mixed $isJavaScript
     * @return ReparsedUri
     */
    public function setIsJavaScript($isJavaScript)
    {
        $this->isJavaScript = $isJavaScript;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsMailTo()
    {
        return $this->isMailTo;
    }

    /**
     * @param mixed $isMailTo
     * @return ReparsedUri
     */
    public function setIsMailTo($isMailTo)
    {
        $this->isMailTo = $isMailTo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     * @return ReparsedUri
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsFragment()
    {
        return $this->isFragment;
    }

    /**
     * @param mixed $isFragment
     * @return ReparsedUri
     */
    public function setIsFragment($isFragment)
    {
        $this->isFragment = $isFragment;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsSchemeRelative()
    {
        return $this->isSchemeRelative;
    }

    /**
     * @param mixed $isSchemeRelative
     * @return ReparsedUri
     */
    public function setIsSchemeRelative($isSchemeRelative)
    {
        $this->isSchemeRelative = $isSchemeRelative;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsPathRelative()
    {
        return $this->isPathRelative;
    }

    /**
     * @param mixed $isPathRelative
     * @return ReparsedUri
     */
    public function setIsPathRelative($isPathRelative)
    {
        $this->isPathRelative = $isPathRelative;
        return $this;
    }

    /**
     * @return bool | null
     */
    public function getIsHostIpAddress()
    {
        return $this->isHostIpAddress;
    }

    /**
     * @param bool | null $isHostIpAddress
     * @return ReparsedUri
     */
    public function setIsHostIpAddress(bool $isHostIpAddress = null)
    {
        $this->isHostIpAddress = $isHostIpAddress;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubdomains()
    {
        return $this->subdomains;
    }

    /**
     * @param mixed $subdomains
     * @return ReparsedUri
     */
    public function setSubdomains($subdomains)
    {
        $this->subdomains = $subdomains;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param mixed $domain
     * @return ReparsedUri
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTld()
    {
        return $this->tld;
    }

    /**
     * @param mixed $tld
     * @return ReparsedUri
     */
    public function setTld($tld)
    {
        $this->tld = $tld;
        return $this;
    }

    /**
     * @return array
     */
    public function getAllowedSchemes(): array
    {
        return $this->allowedSchemes;
    }

    /**
     * @param array $allowedSchemes
     * @return ReparsedUri
     */
    public function setAllowedSchemes(array $allowedSchemes): ReparsedUri
    {
        $this->allowedSchemes = $allowedSchemes;
        return $this;
    }

    /**
     * @return array
     */
    public function getDisallowedSchemes(): array
    {
        return $this->disallowedSchemes;
    }

    /**
     * @param array $disallowedSchemes
     * @return ReparsedUri
     */
    public function setDisallowedSchemes(array $disallowedSchemes): ReparsedUri
    {
        $this->disallowedSchemes = $disallowedSchemes;
        return $this;
    }
}
