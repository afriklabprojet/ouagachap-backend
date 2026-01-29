<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

/**
 * DTO standardisé pour les réponses API
 * Assure une structure cohérente pour toutes les réponses
 */
class ApiResponse implements Arrayable
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly mixed $data = null,
        public readonly ?string $code = null,
        public readonly ?array $errors = null,
        public readonly ?array $meta = null,
    ) {}

    /**
     * Créer une réponse de succès
     */
    public static function success(
        mixed $data = null,
        string $message = 'Opération réussie.',
        ?array $meta = null
    ): self {
        return new self(
            success: true,
            message: $message,
            data: $data,
            meta: $meta,
        );
    }

    /**
     * Créer une réponse d'erreur
     */
    public static function error(
        string $message,
        string $code = 'ERROR',
        ?array $errors = null
    ): self {
        return new self(
            success: false,
            message: $message,
            code: $code,
            errors: $errors,
        );
    }

    /**
     * Créer une réponse avec pagination
     */
    public static function paginated(
        mixed $data,
        array $pagination,
        string $message = 'Données récupérées.'
    ): self {
        return new self(
            success: true,
            message: $message,
            data: $data,
            meta: ['pagination' => $pagination],
        );
    }

    /**
     * Convertir en array
     */
    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->code !== null) {
            $response['code'] = $this->code;
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        if ($this->meta !== null) {
            $response['meta'] = $this->meta;
        }

        return $response;
    }

    /**
     * Convertir en JsonResponse
     */
    public function toResponse(int $status = 200): JsonResponse
    {
        return response()->json($this->toArray(), $status);
    }

    /**
     * Réponse 201 Created
     */
    public function created(): JsonResponse
    {
        return $this->toResponse(201);
    }

    /**
     * Réponse 400 Bad Request
     */
    public function badRequest(): JsonResponse
    {
        return $this->toResponse(400);
    }

    /**
     * Réponse 401 Unauthorized
     */
    public function unauthorized(): JsonResponse
    {
        return $this->toResponse(401);
    }

    /**
     * Réponse 403 Forbidden
     */
    public function forbidden(): JsonResponse
    {
        return $this->toResponse(403);
    }

    /**
     * Réponse 404 Not Found
     */
    public function notFound(): JsonResponse
    {
        return $this->toResponse(404);
    }

    /**
     * Réponse 422 Unprocessable Entity
     */
    public function unprocessable(): JsonResponse
    {
        return $this->toResponse(422);
    }

    /**
     * Réponse 500 Server Error
     */
    public function serverError(): JsonResponse
    {
        return $this->toResponse(500);
    }
}
