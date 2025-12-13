<?php

namespace SavyApps\LaravelStudio\Tests\Unit;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use SavyApps\LaravelStudio\Tests\TestCase;
use SavyApps\LaravelStudio\Traits\ApiResponse;

class ApiResponseTraitTest extends TestCase
{
    use ApiResponse;

    /** @test */
    public function it_returns_success_response(): void
    {
        $response = $this->successResponse(['key' => 'value'], 'Success message');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Success message', $data['message']);
        $this->assertEquals(['key' => 'value'], $data['data']);
    }

    /** @test */
    public function it_returns_success_response_without_data(): void
    {
        $response = $this->successResponse(null, 'Success');

        $data = $response->getData(true);
        $this->assertEquals('Success', $data['message']);
        $this->assertArrayNotHasKey('data', $data);
    }

    /** @test */
    public function it_returns_created_response(): void
    {
        $response = $this->createdResponse(['id' => 1], 'Item created');

        $this->assertEquals(201, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Item created', $data['message']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    /** @test */
    public function it_returns_error_response(): void
    {
        $response = $this->errorResponse('Something went wrong', 400, ['field' => 'error'], 'CUSTOM_ERROR');

        $this->assertEquals(400, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Something went wrong', $data['message']);
        $this->assertEquals('CUSTOM_ERROR', $data['error_code']);
        $this->assertEquals(['field' => 'error'], $data['errors']);
    }

    /** @test */
    public function it_returns_validation_error_response(): void
    {
        $errors = ['email' => ['The email is required.']];
        $response = $this->validationErrorResponse($errors);

        $this->assertEquals(422, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertEquals('VALIDATION_ERROR', $data['error_code']);
        $this->assertEquals($errors, $data['errors']);
    }

    /** @test */
    public function it_returns_not_found_response(): void
    {
        $response = $this->notFoundResponse('User', '123');

        $this->assertEquals(404, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals("User with identifier '123' not found", $data['message']);
        $this->assertEquals('NOT_FOUND', $data['error_code']);
    }

    /** @test */
    public function it_returns_not_found_response_without_identifier(): void
    {
        $response = $this->notFoundResponse('User');

        $data = $response->getData(true);
        $this->assertEquals('User not found', $data['message']);
    }

    /** @test */
    public function it_returns_unauthorized_response(): void
    {
        $response = $this->unauthorizedResponse();

        $this->assertEquals(401, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Unauthenticated', $data['message']);
        $this->assertEquals('UNAUTHORIZED', $data['error_code']);
    }

    /** @test */
    public function it_returns_forbidden_response(): void
    {
        $response = $this->forbiddenResponse('Custom forbidden message');

        $this->assertEquals(403, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Custom forbidden message', $data['message']);
        $this->assertEquals('FORBIDDEN', $data['error_code']);
    }

    /** @test */
    public function it_returns_conflict_response(): void
    {
        $response = $this->conflictResponse('Resource already exists');

        $this->assertEquals(409, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Resource already exists', $data['message']);
        $this->assertEquals('CONFLICT', $data['error_code']);
    }

    /** @test */
    public function it_returns_server_error_response(): void
    {
        $response = $this->serverErrorResponse();

        $this->assertEquals(500, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('An unexpected error occurred', $data['message']);
        $this->assertEquals('SERVER_ERROR', $data['error_code']);
    }

    /** @test */
    public function it_returns_collection_response(): void
    {
        $response = $this->collectionResponse(
            [['id' => 1], ['id' => 2]],
            ['total' => 2, 'type' => 'users']
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Success', $data['message']);
        $this->assertCount(2, $data['data']);
        $this->assertEquals(2, $data['meta']['total']);
        $this->assertEquals('users', $data['meta']['type']);
    }

    /** @test */
    public function it_returns_paginated_response(): void
    {
        $items = [['id' => 1], ['id' => 2]];
        $paginator = new LengthAwarePaginator($items, 10, 2, 1);

        $response = $this->paginatedResponse($paginator);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Success', $data['message']);
        $this->assertCount(2, $data['data']);
        $this->assertEquals(1, $data['meta']['current_page']);
        $this->assertEquals(5, $data['meta']['last_page']);
        $this->assertEquals(2, $data['meta']['per_page']);
        $this->assertEquals(10, $data['meta']['total']);
    }
}
