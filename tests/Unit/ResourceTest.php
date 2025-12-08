<?php

namespace SavyApps\LaravelStudio\Tests\Unit;

use SavyApps\LaravelStudio\Tests\TestCase;
use SavyApps\LaravelStudio\Tests\Fixtures\TestResource;
use SavyApps\LaravelStudio\Tests\Fixtures\TestModel;

class ResourceTest extends TestCase
{
    /** @test */
    public function it_has_correct_model_binding(): void
    {
        $resource = new TestResource();

        $this->assertEquals(TestModel::class, TestResource::$model);
    }

    /** @test */
    public function it_has_correct_labels(): void
    {
        $this->assertEquals('Test Resources', TestResource::$label);
        $this->assertEquals('Test Resource', TestResource::$singularLabel);
    }

    /** @test */
    public function it_has_correct_title_attribute(): void
    {
        $this->assertEquals('name', TestResource::$title);
    }

    /** @test */
    public function it_has_search_columns(): void
    {
        $this->assertEquals(['name', 'email'], TestResource::$search);
    }

    /** @test */
    public function it_has_per_page_setting(): void
    {
        $this->assertEquals(15, TestResource::$perPage);
    }

    /** @test */
    public function it_returns_index_fields(): void
    {
        $resource = new TestResource();
        $fields = $resource->getIndexFields();

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        $this->assertCount(6, $fields);
    }

    /** @test */
    public function it_returns_show_fields(): void
    {
        $resource = new TestResource();
        $fields = $resource->getShowFields();

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        $this->assertCount(9, $fields);
    }

    /** @test */
    public function it_returns_form_fields(): void
    {
        $resource = new TestResource();
        $fields = $resource->getFormFields();

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        $this->assertCount(6, $fields);
    }

    /** @test */
    public function it_returns_filters(): void
    {
        $resource = new TestResource();
        $filters = $resource->filters();

        $this->assertIsArray($filters);
        $this->assertCount(2, $filters);
    }

    /** @test */
    public function it_returns_actions(): void
    {
        $resource = new TestResource();
        $actions = $resource->actions();

        $this->assertIsArray($actions);
        $this->assertCount(1, $actions);
    }

    /** @test */
    public function it_generates_validation_rules_for_create(): void
    {
        $resource = new TestResource();
        $rules = $resource->rules('create');

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('status', $rules);
    }

    /** @test */
    public function it_generates_validation_rules_for_update(): void
    {
        $resource = new TestResource();
        $rules = $resource->rules('update');

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
    }

    /** @test */
    public function it_flattens_fields_correctly(): void
    {
        $resource = new TestResource();
        $fields = $resource->flattenFields($resource->getFormFields());

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
    }
}
