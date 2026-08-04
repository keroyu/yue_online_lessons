<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Somebody else took the slot between the visitor loading the list and pressing
 * submit (011 FR-032). Surfaced to the caller as a 409 so the wizard can refetch
 * rather than showing a generic failure.
 */
class SlotUnavailableException extends RuntimeException
{
    public function __construct(string $message = '該時段剛被預約，請重新選擇')
    {
        parent::__construct($message);
    }
}
