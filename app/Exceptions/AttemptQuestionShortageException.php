<?php

namespace App\Exceptions;

use RuntimeException;

class AttemptQuestionShortageException extends RuntimeException
{
    /**
     * @param  list<array<string, mixed>>  $report
     */
    public function __construct(
        string $message,
        protected array $report = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function report(): array
    {
        return $this->report;
    }
}
