<?php

namespace Smtm\Zfx\Uri\Exception;

class UriFormatException extends \InvalidArgumentException
{
    const MESSAGE_INVALID_URI_FORMAT = 'invalid_uri_format';
    const CODE_INVALID_URI_FORMAT = 0x01;
}