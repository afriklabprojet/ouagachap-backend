<?php

namespace App\Exceptions\Domain;

/**
 * Exception levée lors d'une transition d'état de commande invalide.
 *
 * HTTP 409 Conflict — l'état courant ne permet pas cette transition.
 */
class InvalidOrderStatusTransitionException extends DomainException
{
    public function getStatusCode(): int
    {
        return 409;
    }

    public function getErrorCode(): string
    {
        return 'INVALID_STATUS_TRANSITION';
    }

    /**
     * Construire l'exception pour une transition interdite.
     */
    public static function fromTo(string $from, string $to): static
    {
        return new static(sprintf(
            "Transition invalide : une commande à l'état '%s' ne peut pas passer à '%s'.",
            $from,
            $to
        ));
    }
}
