<?php

namespace Tests\Unit\DTOs;

use App\DTOs\ApiResponse;
use Tests\TestCase;

/**
 * Tests pour ApiResponse DTO
 */
class ApiResponseTest extends TestCase
{
    // ==================== Success Response Tests ====================

    public function test_success_response_has_success_true(): void
    {
        $response = ApiResponse::success();
        
        $this->assertTrue($response->success);
    }

    public function test_success_response_default_message(): void
    {
        $response = ApiResponse::success();
        
        $this->assertEquals('Opération réussie.', $response->message);
    }

    public function test_success_response_with_custom_message(): void
    {
        $response = ApiResponse::success(message: 'Utilisateur créé.');
        
        $this->assertEquals('Utilisateur créé.', $response->message);
    }

    public function test_success_response_with_data(): void
    {
        $data = ['id' => 1, 'name' => 'John'];
        $response = ApiResponse::success(data: $data);
        
        $this->assertEquals($data, $response->data);
    }

    public function test_success_response_with_meta(): void
    {
        $meta = ['page' => 1, 'total' => 100];
        $response = ApiResponse::success(meta: $meta);
        
        $this->assertEquals($meta, $response->meta);
    }

    public function test_success_response_has_no_code(): void
    {
        $response = ApiResponse::success();
        
        $this->assertNull($response->code);
    }

    public function test_success_response_has_no_errors(): void
    {
        $response = ApiResponse::success();
        
        $this->assertNull($response->errors);
    }

    // ==================== Error Response Tests ====================

    public function test_error_response_has_success_false(): void
    {
        $response = ApiResponse::error('Une erreur est survenue');
        
        $this->assertFalse($response->success);
    }

    public function test_error_response_with_message(): void
    {
        $response = ApiResponse::error('Utilisateur non trouvé');
        
        $this->assertEquals('Utilisateur non trouvé', $response->message);
    }

    public function test_error_response_default_code(): void
    {
        $response = ApiResponse::error('Error');
        
        $this->assertEquals('ERROR', $response->code);
    }

    public function test_error_response_with_custom_code(): void
    {
        $response = ApiResponse::error('Not found', 'USER_NOT_FOUND');
        
        $this->assertEquals('USER_NOT_FOUND', $response->code);
    }

    public function test_error_response_with_errors(): void
    {
        $errors = ['email' => ['Invalid email format']];
        $response = ApiResponse::error('Validation failed', 'VALIDATION_ERROR', $errors);
        
        $this->assertEquals($errors, $response->errors);
    }

    public function test_error_response_has_no_data(): void
    {
        $response = ApiResponse::error('Error');
        
        $this->assertNull($response->data);
    }

    // ==================== Paginated Response Tests ====================

    public function test_paginated_response_has_success_true(): void
    {
        $response = ApiResponse::paginated([], []);
        
        $this->assertTrue($response->success);
    }

    public function test_paginated_response_default_message(): void
    {
        $response = ApiResponse::paginated([], []);
        
        $this->assertEquals('Données récupérées.', $response->message);
    }

    public function test_paginated_response_with_custom_message(): void
    {
        $response = ApiResponse::paginated([], [], 'Liste des commandes');
        
        $this->assertEquals('Liste des commandes', $response->message);
    }

    public function test_paginated_response_with_data(): void
    {
        $data = [['id' => 1], ['id' => 2]];
        $response = ApiResponse::paginated($data, []);
        
        $this->assertEquals($data, $response->data);
    }

    public function test_paginated_response_has_pagination_meta(): void
    {
        $pagination = [
            'current_page' => 1,
            'per_page' => 10,
            'total' => 100,
            'last_page' => 10,
        ];
        $response = ApiResponse::paginated([], $pagination);
        
        $this->assertArrayHasKey('pagination', $response->meta);
        $this->assertEquals($pagination, $response->meta['pagination']);
    }

    // ==================== toArray Tests ====================

    public function test_to_array_contains_success(): void
    {
        $response = ApiResponse::success();
        $array = $response->toArray();
        
        $this->assertArrayHasKey('success', $array);
        $this->assertTrue($array['success']);
    }

    public function test_to_array_contains_message(): void
    {
        $response = ApiResponse::success(message: 'Test');
        $array = $response->toArray();
        
        $this->assertArrayHasKey('message', $array);
        $this->assertEquals('Test', $array['message']);
    }

    public function test_to_array_includes_data_when_present(): void
    {
        $response = ApiResponse::success(data: ['key' => 'value']);
        $array = $response->toArray();
        
        $this->assertArrayHasKey('data', $array);
    }

    public function test_to_array_excludes_data_when_null(): void
    {
        $response = ApiResponse::success();
        $array = $response->toArray();
        
        $this->assertArrayNotHasKey('data', $array);
    }

    public function test_to_array_includes_code_when_present(): void
    {
        $response = ApiResponse::error('Error', 'ERROR_CODE');
        $array = $response->toArray();
        
        $this->assertArrayHasKey('code', $array);
    }

    public function test_to_array_excludes_code_when_null(): void
    {
        $response = ApiResponse::success();
        $array = $response->toArray();
        
        $this->assertArrayNotHasKey('code', $array);
    }

    public function test_to_array_includes_errors_when_present(): void
    {
        $response = ApiResponse::error('Error', 'CODE', ['field' => ['error']]);
        $array = $response->toArray();
        
        $this->assertArrayHasKey('errors', $array);
    }

    public function test_to_array_excludes_errors_when_null(): void
    {
        $response = ApiResponse::success();
        $array = $response->toArray();
        
        $this->assertArrayNotHasKey('errors', $array);
    }

    public function test_to_array_includes_meta_when_present(): void
    {
        $response = ApiResponse::success(meta: ['key' => 'value']);
        $array = $response->toArray();
        
        $this->assertArrayHasKey('meta', $array);
    }

    public function test_to_array_excludes_meta_when_null(): void
    {
        $response = ApiResponse::success();
        $array = $response->toArray();
        
        $this->assertArrayNotHasKey('meta', $array);
    }

    // ==================== HTTP Response Tests ====================

    public function test_to_response_returns_json_response(): void
    {
        $response = ApiResponse::success();
        $jsonResponse = $response->toResponse();
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $jsonResponse);
    }

    public function test_to_response_default_status_200(): void
    {
        $response = ApiResponse::success();
        $jsonResponse = $response->toResponse();
        
        $this->assertEquals(200, $jsonResponse->getStatusCode());
    }

    public function test_to_response_custom_status(): void
    {
        $response = ApiResponse::success();
        $jsonResponse = $response->toResponse(201);
        
        $this->assertEquals(201, $jsonResponse->getStatusCode());
    }

    public function test_created_returns_201(): void
    {
        $response = ApiResponse::success();
        $jsonResponse = $response->created();
        
        $this->assertEquals(201, $jsonResponse->getStatusCode());
    }

    public function test_bad_request_returns_400(): void
    {
        $response = ApiResponse::error('Bad request');
        $jsonResponse = $response->badRequest();
        
        $this->assertEquals(400, $jsonResponse->getStatusCode());
    }

    public function test_unauthorized_returns_401(): void
    {
        $response = ApiResponse::error('Unauthorized');
        $jsonResponse = $response->unauthorized();
        
        $this->assertEquals(401, $jsonResponse->getStatusCode());
    }

    public function test_forbidden_returns_403(): void
    {
        $response = ApiResponse::error('Forbidden');
        $jsonResponse = $response->forbidden();
        
        $this->assertEquals(403, $jsonResponse->getStatusCode());
    }

    public function test_not_found_returns_404(): void
    {
        $response = ApiResponse::error('Not found');
        $jsonResponse = $response->notFound();
        
        $this->assertEquals(404, $jsonResponse->getStatusCode());
    }

    public function test_unprocessable_returns_422(): void
    {
        $response = ApiResponse::error('Validation error');
        $jsonResponse = $response->unprocessable();
        
        $this->assertEquals(422, $jsonResponse->getStatusCode());
    }

    public function test_server_error_returns_500(): void
    {
        $response = ApiResponse::error('Server error');
        $jsonResponse = $response->serverError();
        
        $this->assertEquals(500, $jsonResponse->getStatusCode());
    }

    // ==================== Arrayable Interface Tests ====================

    public function test_implements_arrayable(): void
    {
        $response = ApiResponse::success();
        
        $this->assertInstanceOf(\Illuminate\Contracts\Support\Arrayable::class, $response);
    }

    // ==================== Constructor Tests ====================

    public function test_constructor_with_all_parameters(): void
    {
        $response = new ApiResponse(
            success: true,
            message: 'Test message',
            data: ['key' => 'value'],
            code: 'TEST_CODE',
            errors: ['field' => ['error']],
            meta: ['page' => 1]
        );
        
        $this->assertTrue($response->success);
        $this->assertEquals('Test message', $response->message);
        $this->assertEquals(['key' => 'value'], $response->data);
        $this->assertEquals('TEST_CODE', $response->code);
        $this->assertEquals(['field' => ['error']], $response->errors);
        $this->assertEquals(['page' => 1], $response->meta);
    }

    // ==================== Edge Cases ====================

    public function test_success_with_empty_array_data(): void
    {
        $response = ApiResponse::success(data: []);
        
        $this->assertEquals([], $response->data);
        $this->assertArrayHasKey('data', $response->toArray());
    }

    public function test_success_with_null_data(): void
    {
        $response = ApiResponse::success(data: null);
        
        $this->assertNull($response->data);
        $this->assertArrayNotHasKey('data', $response->toArray());
    }

    public function test_error_with_empty_errors_array(): void
    {
        $response = ApiResponse::error('Error', 'CODE', []);
        
        $this->assertEquals([], $response->errors);
        $this->assertArrayHasKey('errors', $response->toArray());
    }

    public function test_json_response_content_matches_to_array(): void
    {
        $response = ApiResponse::success(data: ['id' => 1], message: 'Created');
        $jsonResponse = $response->toResponse();
        
        $content = json_decode($jsonResponse->getContent(), true);
        
        $this->assertEquals($response->toArray(), $content);
    }
}
