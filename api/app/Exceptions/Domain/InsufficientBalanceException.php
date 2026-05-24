<?php

namespace App\Exceptions\Domain;

/**
 * Exception levée lorsqu'un portefeuille n'a pas assez de fonds.
 *
 * HTTP 422 Unprocessable Entity — la règle métier empêche l'opération.
 */
class InsufficientBalanceException extends DomainException
{
    public function getStatusCode(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'INSUFFICIENT_BALANCE';
    }

    /**
     * Construire l'exception pour un retrait refusé.
     */
    public static function forWithdrawal(float $available, float $requested): static
    {
        return new static(sprintf(
            'Solde insuffisant : %.0f FCFA disponible, %.0f FCFA demandé.',
            $available,
            $requested
        ));
    }

    /**
     * Construire l'exception pour un montant minimum non atteint.
     */
    public static function belowMinimum(float $amount, float $minimum): static
    {
        return new static(sprintf(
            'Montant %.0f FCFA inférieur au minimum de retrait de %.0f FCFA.',
            $amount,
            $minimum
        ));
    }
}
