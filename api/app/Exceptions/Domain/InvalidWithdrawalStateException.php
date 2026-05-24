<?php

namespace App\Exceptions\Domain;

class InvalidWithdrawalStateException extends DomainException
{
    public function getErrorCode(): string
    {
        return 'INVALID_WITHDRAWAL_STATE';
    }

    public static function cannotApprove(): static
    {
        return new static('Ce retrait ne peut pas être approuvé');
    }

    public static function cannotReject(): static
    {
        return new static('Ce retrait ne peut pas être rejeté');
    }

    public static function mustBeApprovedBeforeCompletion(): static
    {
        return new static('Ce retrait doit être approuvé avant d\'être complété');
    }
}
