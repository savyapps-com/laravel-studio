<?php

namespace SavyApps\LaravelStudio\Tests\Unit;

use SavyApps\LaravelStudio\Exceptions\StudioException;
use SavyApps\LaravelStudio\Tests\TestCase;

class StudioExceptionTest extends TestCase
{
    /** @test */
    public function it_creates_validation_exception(): void
    {
        $errors = ['email' => ['The email is required.']];
        $exception = StudioException::validation($errors);

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals('VALIDATION_ERROR', $exception->getErrorCode());
        $this->assertEquals($errors, $exception->getErrors());
    }

    /** @test */
    public function it_creates_not_found_exception(): void
    {
        $exception = StudioException::notFound('User', '123');

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals("User with identifier '123' not found", $exception->getMessage());
        $this->assertEquals('NOT_FOUND', $exception->getErrorCode());
    }

    /** @test */
    public function it_creates_not_found_exception_without_identifier(): void
    {
        $exception = StudioException::notFound('User');

        $this->assertEquals('User not found', $exception->getMessage());
    }

    /** @test */
    public function it_creates_unauthorized_exception(): void
    {
        $exception = StudioException::unauthorized();

        $this->assertEquals(401, $exception->getStatusCode());
        $this->assertEquals('Unauthorized', $exception->getMessage());
        $this->assertEquals('UNAUTHORIZED', $exception->getErrorCode());
    }

    /** @test */
    public function it_creates_forbidden_exception(): void
    {
        $exception = StudioException::forbidden('Custom forbidden message');

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertEquals('Custom forbidden message', $exception->getMessage());
        $this->assertEquals('FORBIDDEN', $exception->getErrorCode());
    }

    /** @test */
    public function it_creates_conflict_exception(): void
    {
        $exception = StudioException::conflict();

        $this->assertEquals(409, $exception->getStatusCode());
        $this->assertEquals('Resource conflict', $exception->getMessage());
        $this->assertEquals('CONFLICT', $exception->getErrorCode());
    }

    /** @test */
    public function it_creates_bad_request_exception(): void
    {
        $exception = StudioException::badRequest();

        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEquals('Bad request', $exception->getMessage());
        $this->assertEquals('BAD_REQUEST', $exception->getErrorCode());
    }

    /** @test */
    public function it_creates_server_error_exception(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = StudioException::serverError('Server error', $previousException);

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertEquals('Server error', $exception->getMessage());
        $this->assertEquals('SERVER_ERROR', $exception->getErrorCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    /** @test */
    public function it_renders_json_response(): void
    {
        $exception = StudioException::validation(['email' => ['Required']]);

        $response = $exception->render();

        $this->assertEquals(422, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertEquals('VALIDATION_ERROR', $data['error_code']);
        $this->assertEquals(['email' => ['Required']], $data['errors']);
    }

    /** @test */
    public function it_reports_only_server_errors(): void
    {
        $validationException = StudioException::validation([]);
        $notFoundException = StudioException::notFound();
        $serverException = StudioException::serverError();

        $this->assertFalse($validationException->report());
        $this->assertFalse($notFoundException->report());
        $this->assertTrue($serverException->report());
    }

    /** @test */
    public function it_renders_without_error_code_when_null(): void
    {
        $exception = new StudioException('Simple error', 400);

        $response = $exception->render();
        $data = $response->getData(true);

        $this->assertArrayNotHasKey('error_code', $data);
    }

    /** @test */
    public function it_renders_without_errors_when_empty(): void
    {
        $exception = StudioException::notFound();

        $response = $exception->render();
        $data = $response->getData(true);

        $this->assertArrayNotHasKey('errors', $data);
    }
}
