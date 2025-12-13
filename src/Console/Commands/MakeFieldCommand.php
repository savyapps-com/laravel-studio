<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFieldCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:make-field
                            {name : The name of the field class (e.g., ColorPicker)}
                            {--extends= : Base field class to extend (e.g., Text, Number)}
                            {--vue : Also generate a Vue component}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new custom field class for Laravel Studio';

    /**
     * Available base field classes.
     */
    protected array $availableBaseFields = [
        'Field',
        'Text',
        'Number',
        'Boolean',
        'DateTime',
        'Select',
        'Textarea',
        'Media',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $extends = $this->option('extends') ?? 'Field';

        // Validate name
        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->components->error('Field name must be PascalCase (e.g., ColorPicker, DateRange)');
            return self::FAILURE;
        }

        // Validate base class
        if (! in_array($extends, $this->availableBaseFields)) {
            $this->components->error("Unknown base field: {$extends}");
            $this->components->info('Available base fields: ' . implode(', ', $this->availableBaseFields));
            return self::FAILURE;
        }

        // Create PHP field class
        $phpCreated = $this->createFieldClass($name, $extends);

        if (! $phpCreated) {
            return self::FAILURE;
        }

        // Optionally create Vue component
        if ($this->option('vue')) {
            $this->createVueComponent($name);
        }

        $this->newLine();
        $this->displayNextSteps($name, $extends);

        return self::SUCCESS;
    }

    /**
     * Create the PHP field class.
     */
    protected function createFieldClass(string $name, string $extends): bool
    {
        $directory = app_path('Resources/Fields');
        $filePath = "{$directory}/{$name}.php";

        // Create directory if it doesn't exist
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
            $this->components->info("Created directory: app/Resources/Fields");
        }

        // Check if file exists
        if (File::exists($filePath) && ! $this->option('force')) {
            $this->components->error("Field already exists: {$filePath}");
            $this->line('<fg=gray>Use --force to overwrite</>');
            return false;
        }

        // Generate class content
        $content = $this->generateFieldClass($name, $extends);

        // Write file
        File::put($filePath, $content);

        $this->components->info("Created field: app/Resources/Fields/{$name}.php");

        return true;
    }

    /**
     * Generate the PHP class content.
     */
    protected function generateFieldClass(string $name, string $extends): string
    {
        $fieldType = Str::kebab($name);
        $baseUse = $extends === 'Field'
            ? 'SavyApps\LaravelStudio\Resources\Fields\Field'
            : "SavyApps\\LaravelStudio\\Resources\\Fields\\{$extends}";

        $extraMethods = $this->generateExtraMethods($name, $extends);

        return <<<PHP
<?php

namespace App\Resources\Fields;

use {$baseUse};

/**
 * Custom {$name} field for Laravel Studio.
 *
 * Usage:
 *   {$name}::make('Label', 'attribute')
 *       ->rules('required')
 */
class {$name} extends {$extends}
{
    /**
     * The field type identifier for the frontend.
     */
    protected function fieldType(): string
    {
        return '{$fieldType}';
    }
{$extraMethods}
    /**
     * Set the default value for this field.
     */
    public function default(mixed \$value): static
    {
        \$this->meta['default'] = \$value;

        return \$this;
    }

    /**
     * Add a custom option to the field meta.
     */
    public function withOption(string \$key, mixed \$value): static
    {
        \$this->meta[\$key] = \$value;

        return \$this;
    }

    /**
     * Customize how the value is displayed on index/detail views.
     */
    public function displayUsing(callable \$callback): static
    {
        \$this->meta['displayCallback'] = \$callback;

        return \$this;
    }

    /**
     * Transform the value before saving to the database.
     */
    public function saveUsing(callable \$callback): static
    {
        \$this->meta['saveCallback'] = \$callback;

        return \$this;
    }
}

PHP;
    }

    /**
     * Generate extra methods based on the base field type.
     */
    protected function generateExtraMethods(string $name, string $extends): string
    {
        $methods = '';

        switch ($extends) {
            case 'Number':
                $methods = <<<'PHP'

    /**
     * Set the minimum value.
     */
    public function min(int|float $value): static
    {
        $this->meta['min'] = $value;

        return $this;
    }

    /**
     * Set the maximum value.
     */
    public function max(int|float $value): static
    {
        $this->meta['max'] = $value;

        return $this;
    }

    /**
     * Set the step increment.
     */
    public function step(int|float $value): static
    {
        $this->meta['step'] = $value;

        return $this;
    }

PHP;
                break;

            case 'Select':
                $methods = <<<'PHP'

    /**
     * Set the available options.
     */
    public function options(array $options): static
    {
        $this->meta['options'] = $options;

        return $this;
    }

    /**
     * Allow multiple selections.
     */
    public function multiple(): static
    {
        $this->meta['multiple'] = true;

        return $this;
    }

PHP;
                break;

            case 'Text':
                $methods = <<<'PHP'

    /**
     * Set a placeholder text.
     */
    public function placeholder(string $text): static
    {
        $this->meta['placeholder'] = $text;

        return $this;
    }

    /**
     * Set maximum character length.
     */
    public function maxLength(int $length): static
    {
        $this->meta['maxLength'] = $length;

        return $this;
    }

PHP;
                break;

            default:
                $methods = '';
        }

        return $methods;
    }

    /**
     * Create the Vue component.
     */
    protected function createVueComponent(string $name): void
    {
        $kebabName = Str::kebab($name);
        $directory = resource_path('js/components/fields');
        $filePath = "{$directory}/{$name}Field.vue";

        // Create directory if it doesn't exist
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
            $this->components->info("Created directory: resources/js/components/fields");
        }

        // Check if file exists
        if (File::exists($filePath) && ! $this->option('force')) {
            $this->components->warn("Vue component already exists: {$filePath}");
            return;
        }

        $content = $this->generateVueComponent($name);

        File::put($filePath, $content);

        $this->components->info("Created Vue component: resources/js/components/fields/{$name}Field.vue");
    }

    /**
     * Generate Vue component content.
     */
    protected function generateVueComponent(string $name): string
    {
        $kebabName = Str::kebab($name);

        return <<<VUE
<template>
  <div class="{$kebabName}-field">
    <!-- Index/Detail view -->
    <template v-if="mode === 'index' || mode === 'detail'">
      <span class="text-gray-900 dark:text-white">
        {{ displayValue }}
      </span>
    </template>

    <!-- Form view -->
    <template v-else>
      <label
        v-if="field.label"
        :for="field.attribute"
        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
      >
        {{ field.label }}
        <span v-if="field.required" class="text-red-500">*</span>
      </label>

      <input
        :id="field.attribute"
        v-model="localValue"
        type="text"
        :placeholder="field.meta?.placeholder"
        :disabled="field.disabled"
        :class="inputClasses"
        @input="updateValue"
      />

      <p v-if="field.helpText" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ field.helpText }}
      </p>

      <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
        {{ error }}
      </p>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [String, Number, Boolean, Array, Object],
    default: null,
  },
  mode: {
    type: String,
    default: 'form', // 'index', 'detail', 'form'
  },
  error: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue ?? props.field.meta?.default ?? '')

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
})

// Computed display value for index/detail views
const displayValue = computed(() => {
  if (props.field.meta?.displayCallback) {
    return props.field.meta.displayCallback(localValue.value)
  }
  return localValue.value ?? '-'
})

// Input classes with error state
const inputClasses = computed(() => {
  const base = 'block w-full rounded-md shadow-sm sm:text-sm'
  const normal = 'border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500'
  const errorClass = 'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500'
  const disabled = 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed'

  return [
    base,
    props.error ? errorClass : normal,
    props.field.disabled ? disabled : '',
  ].join(' ')
})

// Update parent value
function updateValue() {
  let value = localValue.value

  // Apply save transformation if defined
  if (props.field.meta?.saveCallback) {
    value = props.field.meta.saveCallback(value)
  }

  emit('update:modelValue', value)
}
</script>

<style scoped>
.{$kebabName}-field {
  /* Add custom styles here */
}
</style>

VUE;
    }

    /**
     * Display next steps after creation.
     */
    protected function displayNextSteps(string $name, string $extends): void
    {
        $this->components->info('Next steps:');
        $this->newLine();

        $this->line('1. <fg=yellow>Use your field in a resource:</>');
        $this->line("   use App\\Resources\\Fields\\{$name};");
        $this->newLine();
        $this->line("   public function fields(): array");
        $this->line('   {');
        $this->line('       return [');
        $this->line("           {$name}::make('Label', 'attribute'),");
        $this->line('       ];');
        $this->line('   }');

        if ($this->option('vue')) {
            $this->newLine();
            $this->line('2. <fg=yellow>Register the Vue component:</>');
            $this->line("   // In resources/js/app.js or a plugin");
            $this->line("   import {$name}Field from './components/fields/{$name}Field.vue'");
            $this->line("   app.component('{$name}Field', {$name}Field)");
        }

        $this->newLine();
        $this->line('<fg=gray>Tip: Customize the fieldType() method to match your Vue component name.</>');
    }
}
