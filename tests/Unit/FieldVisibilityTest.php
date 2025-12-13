<?php

namespace SavyApps\LaravelStudio\Tests\Unit;

use SavyApps\LaravelStudio\Resources\Fields\Text;
use SavyApps\LaravelStudio\Resources\Fields\Select;
use SavyApps\LaravelStudio\Tests\TestCase;

class FieldVisibilityTest extends TestCase
{
    /** @test */
    public function it_shows_field_by_default(): void
    {
        $field = Text::make('Name');

        $this->assertTrue($field->isVisible([]));
        $this->assertTrue($field->isVisible(['name' => 'John']));
    }

    /** @test */
    public function it_hides_field_when_depends_on_condition_not_met(): void
    {
        $field = Text::make('Company Name')
            ->dependsOn('type', 'business');

        // Hidden when type is not 'business'
        $this->assertFalse($field->isVisible(['type' => 'personal']));

        // Shown when type is 'business'
        $this->assertTrue($field->isVisible(['type' => 'business']));
    }

    /** @test */
    public function it_hides_field_when_dependency_missing(): void
    {
        $field = Text::make('Company Name')
            ->dependsOn('type', 'business');

        // Hidden when dependency field doesn't exist
        $this->assertFalse($field->isVisible([]));
    }

    /** @test */
    public function it_supports_not_equal_operator(): void
    {
        $field = Text::make('Reason')
            ->dependsOn('status', 'approved', '!=');

        // Shown when status is NOT approved
        $this->assertTrue($field->isVisible(['status' => 'pending']));
        $this->assertTrue($field->isVisible(['status' => 'rejected']));

        // Hidden when status IS approved
        $this->assertFalse($field->isVisible(['status' => 'approved']));
    }

    /** @test */
    public function it_supports_greater_than_operator(): void
    {
        $field = Text::make('Discount Reason')
            ->dependsOn('quantity', 10, '>');

        $this->assertFalse($field->isVisible(['quantity' => 5]));
        $this->assertFalse($field->isVisible(['quantity' => 10]));
        $this->assertTrue($field->isVisible(['quantity' => 15]));
    }

    /** @test */
    public function it_supports_in_operator(): void
    {
        $field = Text::make('Tax ID')
            ->dependsOn('country', ['US', 'CA', 'UK'], 'in');

        $this->assertTrue($field->isVisible(['country' => 'US']));
        $this->assertTrue($field->isVisible(['country' => 'CA']));
        $this->assertFalse($field->isVisible(['country' => 'DE']));
    }

    /** @test */
    public function it_supports_empty_operator(): void
    {
        $field = Text::make('Other Description')
            ->dependsOn('category', null, 'empty');

        $this->assertTrue($field->isVisible(['category' => '']));
        $this->assertTrue($field->isVisible(['category' => null]));
        $this->assertFalse($field->isVisible(['category' => 'electronics']));
    }

    /** @test */
    public function it_supports_not_empty_operator(): void
    {
        $field = Text::make('Category Details')
            ->dependsOn('category', null, 'not_empty');

        $this->assertFalse($field->isVisible(['category' => '']));
        $this->assertFalse($field->isVisible(['category' => null]));
        $this->assertTrue($field->isVisible(['category' => 'electronics']));
    }

    /** @test */
    public function it_supports_depends_on_all_and_logic(): void
    {
        $field = Text::make('VIP Discount')
            ->dependsOnAll([
                ['is_vip', true],
                ['total', 100, '>='],
            ]);

        // Both conditions must be true
        $this->assertTrue($field->isVisible(['is_vip' => true, 'total' => 150]));

        // One condition false
        $this->assertFalse($field->isVisible(['is_vip' => false, 'total' => 150]));
        $this->assertFalse($field->isVisible(['is_vip' => true, 'total' => 50]));

        // Both conditions false
        $this->assertFalse($field->isVisible(['is_vip' => false, 'total' => 50]));
    }

    /** @test */
    public function it_supports_depends_on_any_or_logic(): void
    {
        $field = Text::make('Special Note')
            ->dependsOnAny([
                ['is_urgent', true],
                ['is_priority', true],
            ]);

        // Any condition true
        $this->assertTrue($field->isVisible(['is_urgent' => true, 'is_priority' => false]));
        $this->assertTrue($field->isVisible(['is_urgent' => false, 'is_priority' => true]));
        $this->assertTrue($field->isVisible(['is_urgent' => true, 'is_priority' => true]));

        // All conditions false
        $this->assertFalse($field->isVisible(['is_urgent' => false, 'is_priority' => false]));
    }

    /** @test */
    public function it_supports_show_when_callback(): void
    {
        $field = Text::make('Admin Note')
            ->showWhen(fn ($data) => ($data['role'] ?? '') === 'admin');

        $this->assertTrue($field->isVisible(['role' => 'admin']));
        $this->assertFalse($field->isVisible(['role' => 'user']));
        $this->assertFalse($field->isVisible([]));
    }

    /** @test */
    public function it_supports_hide_when_callback(): void
    {
        $field = Text::make('Public Note')
            ->hideWhen(fn ($data) => ($data['is_private'] ?? false) === true);

        $this->assertTrue($field->isVisible(['is_private' => false]));
        $this->assertFalse($field->isVisible(['is_private' => true]));
    }

    /** @test */
    public function it_supports_show_when_structured_condition(): void
    {
        $field = Text::make('Billing Address')
            ->showWhen([
                'type' => 'comparison',
                'field' => 'needs_billing',
                'operator' => '=',
                'value' => true,
            ]);

        $this->assertTrue($field->isVisible(['needs_billing' => true]));
        $this->assertFalse($field->isVisible(['needs_billing' => false]));
    }

    /** @test */
    public function it_supports_hide_when_structured_condition(): void
    {
        $field = Text::make('Details')
            ->hideWhen([
                'type' => 'comparison',
                'field' => 'type',
                'operator' => '=',
                'value' => 'simple',
            ]);

        $this->assertFalse($field->isVisible(['type' => 'simple']));
        $this->assertTrue($field->isVisible(['type' => 'advanced']));
    }

    /** @test */
    public function it_supports_nested_and_conditions(): void
    {
        $field = Text::make('Complex Field')
            ->showWhen([
                'type' => 'and',
                'conditions' => [
                    [
                        'type' => 'comparison',
                        'field' => 'enabled',
                        'operator' => '=',
                        'value' => true,
                    ],
                    [
                        'type' => 'comparison',
                        'field' => 'level',
                        'operator' => '>=',
                        'value' => 5,
                    ],
                ],
            ]);

        $this->assertTrue($field->isVisible(['enabled' => true, 'level' => 10]));
        $this->assertFalse($field->isVisible(['enabled' => true, 'level' => 3]));
        $this->assertFalse($field->isVisible(['enabled' => false, 'level' => 10]));
    }

    /** @test */
    public function it_supports_nested_or_conditions(): void
    {
        $field = Text::make('Alternative Field')
            ->showWhen([
                'type' => 'or',
                'conditions' => [
                    [
                        'type' => 'comparison',
                        'field' => 'type',
                        'operator' => '=',
                        'value' => 'premium',
                    ],
                    [
                        'type' => 'comparison',
                        'field' => 'is_beta',
                        'operator' => '=',
                        'value' => true,
                    ],
                ],
            ]);

        $this->assertTrue($field->isVisible(['type' => 'premium', 'is_beta' => false]));
        $this->assertTrue($field->isVisible(['type' => 'basic', 'is_beta' => true]));
        $this->assertFalse($field->isVisible(['type' => 'basic', 'is_beta' => false]));
    }

    /** @test */
    public function it_evaluates_required_when(): void
    {
        $field = Text::make('Tax Number')
            ->requiredWhen('country', 'DE');

        $this->assertTrue($field->isRequired(['country' => 'DE']));
        $this->assertFalse($field->isRequired(['country' => 'US']));
    }

    /** @test */
    public function it_evaluates_disabled_when(): void
    {
        $field = Text::make('Email')
            ->disabledWhen('locked', true);

        $this->assertTrue($field->isDisabled(['locked' => true]));
        $this->assertFalse($field->isDisabled(['locked' => false]));
    }

    /** @test */
    public function it_detects_circular_dependency(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        // Create a field that depends on itself
        $field = Text::make('Self Reference')
            ->showWhen(fn ($data) => (new class extends Text {
                protected function fieldType(): string { return 'text'; }
            })->isVisible($data));

        // This should throw an exception
        $field->isVisible(['self_reference' => 'test']);
    }

    /** @test */
    public function it_includes_visibility_conditions_in_array(): void
    {
        $field = Text::make('Conditional')
            ->dependsOn('type', 'special');

        $array = $field->toArray();

        $this->assertArrayHasKey('meta', $array);
        $this->assertArrayHasKey('dependsOn', $array['meta']);
        $this->assertEquals('type', $array['meta']['dependsOn']['attribute']);
        $this->assertEquals('special', $array['meta']['dependsOn']['value']);
    }

    /** @test */
    public function it_handles_null_values_in_conditions(): void
    {
        $field = Text::make('Optional Details')
            ->dependsOn('category', null, '!=');

        // Visible when category is not null
        $this->assertTrue($field->isVisible(['category' => 'electronics']));

        // Not visible when category is null
        $this->assertFalse($field->isVisible(['category' => null]));
    }

    /** @test */
    public function it_supports_contains_operator_for_arrays(): void
    {
        $field = Text::make('Admin Features')
            ->dependsOn('roles', 'admin', 'contains');

        $this->assertTrue($field->isVisible(['roles' => ['user', 'admin']]));
        $this->assertFalse($field->isVisible(['roles' => ['user', 'editor']]));
    }

    /** @test */
    public function it_supports_not_contains_operator(): void
    {
        $field = Text::make('Non-Admin Features')
            ->dependsOn('roles', 'admin', 'not_contains');

        $this->assertFalse($field->isVisible(['roles' => ['user', 'admin']]));
        $this->assertTrue($field->isVisible(['roles' => ['user', 'editor']]));
    }
}
