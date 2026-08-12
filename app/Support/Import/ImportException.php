<?php

namespace App\Support\Import;

use RuntimeException;

/**
 * A file-level problem that stops the import before any rows are considered.
 *
 * The message is written for the shop owner, not the developer — it is shown
 * verbatim on the import screen.
 */
class ImportException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
