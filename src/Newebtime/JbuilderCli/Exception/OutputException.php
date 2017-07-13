<?php
/**
 * @package    JBuilderCli
 * @copyright  Copyright (c) 2003-2017 Frédéric Vandebeuque / Newebtime
 * @license    Mozilla Public License, version 2.0
 */

namespace Newebtime\JbuilderCli\Exception;

use Exception;

class OutputException extends Exception
{
    private $types = [
        'error'   => 3,
        'warning' => 2,
        'caution' => 1
    ];

    protected $messages;

    protected $type;

    public function __construct($message, $type = 'warning', Exception $previous = null)
    {
        if (!array_key_exists($type, $this->types)) {
            $type = 'warning';
        }

        $this->type = $type;

        if (is_array($message)) {
            $this->messages = $message;
            $message = current($message);
        } else {
            $this->messages = [$message];
        }

        $code = $this->types[$type];

        parent::__construct($message, $code, $previous);
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getType()
    {
        return $this->type;
    }
}
