<?php

namespace App\Exceptions\Domain;

/**
 * Exception levée quand aucun coursier disponible ne peut être affecté.
 *
 * HTTP 409 Conflict — état métier bloquant.
 */
class CourierNotAvailableException extends DomainException
{
    public function getStatusCode(): int
    {
        return 409;
    }

    public function getErrorCode(): string
    {
        return 'COURIER_NOT_AVAILABLE';
    }

    public static function noActiveCouriers(): static
    {
        return new static('Aucun coursier disponible dans votre zone pour le moment.');
    }

    public static function tooFarFromPickup(float $distanceKm): static
    {
        return new static(sprintf(
            'Aucun coursier disponible à moins de %d km du point de collecte.',
            (int) floor($distanceKm)
        ));
    }
}
