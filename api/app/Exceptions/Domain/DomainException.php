<?php

namespace App\Exceptions\Domain;

use RuntimeException;

/**
 * Exception de domaine métier — base de toutes les exceptions métier typées.
 *
 * Ces exceptions représentent des violations des règles métier (ex: solde insuffisant,
 * transition d'état invalide). Elles sont rendues en JSON par le handler global.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * Code HTTP à retourner dans la réponse JSON.
     */
    public function getStatusCode(): int
    {
        return 422;
    }

    /**
     * Code machine (pour les clients API).
     */
    public function getErrorCode(): string
    {
        return 'DOMAIN_ERROR';
    }
}
