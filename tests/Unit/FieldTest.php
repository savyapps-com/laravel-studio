<?php

namespace SavyApps\LaravelStudio\Tests\Unit;

use SavyApps\LaravelStudio\Tests\TestCase;
use SavyApps\LaravelStudio\Resources\Fields\Text;
use SavyApps\LaravelStudio\Resources\Fields\Email;
use SavyApps\LaravelStudio\Resources\Fields\Number;
use SavyApps\LaravelStudio\Resources\Fields\Boolean;
use SavyApps\LaravelStudio\Resources\Fields\Select;
use SavyApps\LaravelStudio\Resources\Fields\Date;

class FieldTest extends TestCase
{
    /** @test */
    public function text_field_can_be_created(): void
    {
        $field = Text::make('Name');

        $this->assertEquals('Name', $field->label);
        $this->assertEquals('name', $field->attribute);
        $this->assertEquals('text', $field->component);
    }

    /** @test */
    public function text_field_can_have_custom_attribute(): void
    {
        $field = Text::make('Full Name', 'full_name');

        $this->assertEquals('Full Name', $field->label);
        $this->assertEquals('full_name', $field->attribute);
    }

    /** @test */
    public function field_can_be_sortable(): void
    {
        $field = Text::make('Name')->sortable();

        $array = $field->toArray();
        $this->assertTrue($array['sortable']);
    }

    /** @test */
    public function field_can_be_searchable(): void
    {
        $field = Text::make('Name')->searchable();

        $array = $field->toArray();
        $this->assertTrue($array['searchable']);
    }

    /** @test */
    public function field_can_have_rules(): void
    {
        $field = Text::make('Name')->rules('required|string|max:255');

        $this->assertEquals('required|string|max:255', $field->rules);
    }

    /** @test */
    public function field_can_have_placeholder(): void
    {
        $field = Text::make('Name')->placeholder('Enter your name');

        $array = $field->toArray();
        $this->assertEquals('Enter your name', $array['placeholder']);
    }

    /** @test */
    public function field_can_have_help_text(): void
    {
        $field = Text::make('Name')->help('This is your display name');

        $array = $field->toArray();
        $this->assertEquals('This is your display name', $array['help']);
    }

    /** @test */
    public function field_can_have_default_value(): void
    {
        $field = Select::make('Status')->default('active');

        $array = $field->toArray();
        $this->assertEquals('active', $array['default']);
    }

    /** @test */
    public function email_field_has_correct_component(): void
    {
        $field = Email::make('Email');

        $this->assertEquals('email', $field->component);
    }

    /** @test */
    public function number_field_has_correct_component(): void
    {
        $field = Number::make('Age');

        $this->assertEquals('number', $field->component);
    }

    /** @test */
    public function boolean_field_has_correct_component(): void
    {
        $field = Boolean::make('Is Active');

        $this->assertEquals('boolean', $field->component);
    }

    /** @test */
    public function select_field_has_options(): void
    {
        $field = Select::make('Status')->options([
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]);

        $array = $field->toArray();
        $this->assertArrayHasKey('options', $array);
        $this->assertCount(2, $array['options']);
    }

    /** @test */
    public function date_field_has_correct_component(): void
    {
        $field = Date::make('Published At');

        $this->assertEquals('date', $field->component);
    }

    /** @test */
    public function field_can_be_hidden_from_index(): void
    {
        $field = Text::make('Secret')->hideFromIndex();

        $array = $field->toArray();
        $this->assertTrue($array['hideFromIndex'] ?? false);
    }

    /** @test */
    public function field_can_be_hidden_from_detail(): void
    {
        $field = Text::make('Secret')->hideFromDetail();

        $array = $field->toArray();
        $this->assertTrue($array['hideFromDetail'] ?? false);
    }

    /** @test */
    public function field_can_specify_cols(): void
    {
        $field = Text::make('Name')->cols('col-span-6');

        $array = $field->toArray();
        $this->assertEquals('col-span-6', $array['cols']);
    }

    /** @test */
    public function field_serializes_to_array_correctly(): void
    {
        $field = Text::make('Name')
            ->sortable()
            ->searchable()
            ->rules('required')
            ->placeholder('Enter name');

        $array = $field->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('attribute', $array);
        $this->assertArrayHasKey('component', $array);
        $this->assertArrayHasKey('sortable', $array);
        $this->assertArrayHasKey('searchable', $array);
    }
}
