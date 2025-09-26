<?php declare(strict_types=1);

namespace Careminate\Exceptions;

use RuntimeException;

class AuthException extends RuntimeException
{
    /**
     * Optional: Store additional context for authentication errors.
     */
    protected array $context = [];

    /**
     * Constructor
     *
     * @param string $message
     * @param int $code
     * @param array $context
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = "Authentication error",
        int $code = 0,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get additional context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}

