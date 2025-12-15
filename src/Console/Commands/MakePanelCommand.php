<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class MakePanelCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:make-panel
                            {key? : The panel key/slug}
                            {--label= : The panel display name}
                            {--path= : The panel URL path}
                            {--icon= : The panel icon}
                            {--role= : The required role to access the panel}';

    /**
     * The console command description.
     */
    protected $description = 'Generate panel configuration for config/studio.php';

    /**
     * Available icons for selection.
     */
    protected array $icons = [
        'layout' => 'Layout (Default)',
        'home' => 'Home',
        'users' => 'Users',
        'settings' => 'Settings',
        'shield' => 'Shield',
        'database' => 'Database',
        'chart-bar' => 'Chart Bar',
        'clipboard' => 'Clipboard',
        'folder' => 'Folder',
        'globe' => 'Globe',
        'briefcase' => 'Briefcase',
        'building' => 'Building',
        'code' => 'Code',
        'cog' => 'Cog',
        'document' => 'Document',
        'inbox' => 'Inbox',
        'key' => 'Key',
        'lightning-bolt' => 'Lightning Bolt',
        'lock-closed' => 'Lock',
        'mail' => 'Mail',
        'puzzle' => 'Puzzle',
        'server' => 'Server',
        'tag' => 'Tag',
        'terminal' => 'Terminal',
        'truck' => 'Truck',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Generating Laravel Studio Panel Configuration');
        $this->newLine();

        // Get panel key
        $key = $this->argument('key') ?? $this->promptForKey();

        if (! $key) {
            $this->components->error('Panel key is required.');

            return self::FAILURE;
        }

        // Check if panel already exists in config
        $existingPanels = config('studio.panels', []);
        if (isset($existingPanels[$key])) {
            warning("Panel [{$key}] already exists in config/studio.php!");
            $this->newLine();

            if ($this->input->isInteractive()) {
                if (! confirm('Continue anyway?', false)) {
                    $this->components->warn('Panel generation cancelled.');
                    return self::SUCCESS;
                }
            }
        }

        // Gather panel details
        $label = $this->option('label') ?? $this->promptForLabel($key);
        $path = $this->option('path') ?? $this->promptForPath($key);
        $icon = $this->option('icon') ?? $this->promptForIcon();
        $role = $this->option('role') ?? $this->promptForRole($key);
        $roles = $this->promptForAdditionalRoles($role);
        $resources = $this->promptForResources();
        $features = $this->promptForFeatures();
        $allowRegistration = $this->promptForAllowRegistration();

        // Build menu structure
        $menu = $this->buildDefaultMenu($label, $key, $resources, $features);

        // Build panel configuration
        $panelConfig = [
            'label' => $label,
            'path' => $path,
            'icon' => $icon,
            'middleware' => ['api', 'auth:sanctum', "panel:{$key}"],
            'role' => $role,
            'roles' => $roles,
            'allow_registration' => $allowRegistration,
            'default_role' => $role,
            'resources' => $resources,
            'features' => $features,
            'menu' => $menu,
            'settings' => [
                'layout' => 'classic',
                'theme' => 'light',
            ],
        ];

        // Show summary
        $this->showSummary($key, $label, $path, $icon, $role, $roles, $resources, $features, $allowRegistration);

        if ($this->input->isInteractive()) {
            if (! confirm('Generate configuration?', true)) {
                $this->components->warn('Panel generation cancelled.');
                return self::SUCCESS;
            }
        }

        // Generate and display config code
        $this->generateConfigOutput($key, $panelConfig);

        return self::SUCCESS;
    }

    /**
     * Prompt for panel key.
     */
    protected function promptForKey(): string
    {
        return text(
            label: 'Panel key (slug)',
            placeholder: 'e.g., admin, manager, support',
            required: true,
            validate: function (string $value) {
                if (! preg_match('/^[a-z][a-z0-9-]*$/', $value)) {
                    return 'Key must start with a letter and contain only lowercase letters, numbers, and hyphens.';
                }
                if (strlen($value) < 2) {
                    return 'Key must be at least 2 characters.';
                }

                return null;
            },
            hint: 'Used in URLs and as unique identifier'
        );
    }

    /**
     * Prompt for panel label.
     */
    protected function promptForLabel(string $key): string
    {
        $defaultLabel = Str::headline($key).' Panel';

        return text(
            label: 'Panel display name',
            default: $defaultLabel,
            required: true,
            hint: 'Shown in the UI and panel switcher'
        );
    }

    /**
     * Prompt for panel path.
     */
    protected function promptForPath(string $key): string
    {
        return text(
            label: 'Panel URL path',
            default: "/{$key}",
            required: true,
            validate: function (string $value) {
                if (! str_starts_with($value, '/')) {
                    return 'Path must start with /';
                }

                return null;
            },
            hint: 'Frontend route path for this panel'
        );
    }

    /**
     * Prompt for panel icon.
     */
    protected function promptForIcon(): string
    {
        return select(
            label: 'Panel icon',
            options: $this->icons,
            default: 'layout',
            hint: 'Icon shown in the panel switcher'
        );
    }

    /**
     * Prompt for primary role.
     */
    protected function promptForRole(string $key): string
    {
        $existingRoles = $this->getExistingRoles();

        if (! empty($existingRoles)) {
            $options = array_merge(
                ['_new' => '+ Create new role'],
                $existingRoles
            );

            $selected = select(
                label: 'Primary role required to access this panel',
                options: $options,
                default: in_array($key, array_keys($existingRoles)) ? $key : '_new',
                hint: 'Users must have this role to access the panel'
            );

            if ($selected === '_new') {
                return text(
                    label: 'New role name',
                    default: $key,
                    required: true,
                    hint: 'This role will be required for panel access'
                );
            }

            return $selected;
        }

        return text(
            label: 'Role required to access this panel',
            default: $key,
            required: true,
            hint: 'Users must have this role to access the panel'
        );
    }

    /**
     * Prompt for additional roles.
     */
    protected function promptForAdditionalRoles(string $primaryRole): array
    {
        $existingRoles = $this->getExistingRoles();

        // Remove primary role from options
        unset($existingRoles[$primaryRole]);

        if (empty($existingRoles)) {
            return [$primaryRole];
        }

        $additionalRoles = multiselect(
            label: 'Additional roles that can access this panel (optional)',
            options: $existingRoles,
            default: [],
            hint: 'Select any additional roles, or leave empty'
        );

        return array_merge([$primaryRole], $additionalRoles);
    }

    /**
     * Prompt for resources to include.
     */
    protected function promptForResources(): array
    {
        $availableResources = $this->getAvailableResources();

        if (empty($availableResources)) {
            $this->components->warn('No resources registered. You can add resources later in the panel settings.');

            return [];
        }

        return multiselect(
            label: 'Select resources to include in this panel',
            options: $availableResources,
            default: [],
            hint: 'Resources will be accessible from this panel\'s menu'
        );
    }

    /**
     * Prompt for features to include.
     */
    protected function promptForFeatures(): array
    {
        $availableFeatures = $this->getAvailableFeatures();

        if (empty($availableFeatures)) {
            return [];
        }

        return multiselect(
            label: 'Select features to enable for this panel',
            options: $availableFeatures,
            default: [],
            hint: 'Features are special pages like Email Templates, Settings, etc.'
        );
    }

    /**
     * Prompt for allow registration setting.
     */
    protected function promptForAllowRegistration(): bool
    {
        return confirm(
            label: 'Allow user registration for this panel?',
            default: false,
            hint: 'If enabled, users can self-register for this panel'
        );
    }

    /**
     * Get existing roles from the database.
     */
    protected function getExistingRoles(): array
    {
        try {
            $roleClass = config('studio.authorization.models.role', \SavyApps\LaravelStudio\Models\Role::class);

            if (! class_exists($roleClass)) {
                return [];
            }

            return $roleClass::pluck('name', 'slug')->toArray()
                ?: $roleClass::pluck('name', 'name')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get available resources from config.
     */
    protected function getAvailableResources(): array
    {
        $resources = config('studio.resources', []);
        $formatted = [];

        foreach ($resources as $key => $resource) {
            if (is_array($resource)) {
                $formatted[$key] = $resource['label'] ?? Str::headline($key);
            } else {
                // Resource is a class name
                $formatted[$key] = Str::headline($key);
            }
        }

        return $formatted;
    }

    /**
     * Get available features from config.
     */
    protected function getAvailableFeatures(): array
    {
        $features = config('studio.features', []);
        $formatted = [];

        foreach ($features as $key => $feature) {
            $formatted[$key] = $feature['label'] ?? Str::headline($key);
        }

        return $formatted;
    }

    /**
     * Build default menu structure.
     */
    protected function buildDefaultMenu(string $label, string $key, array $resources, array $features): array
    {
        $menu = [
            [
                'type' => 'link',
                'label' => 'Dashboard',
                'route' => "{$key}.dashboard",
                'icon' => 'home',
            ],
        ];

        // Add resources to menu
        if (! empty($resources)) {
            $menu[] = [
                'type' => 'divider',
                'label' => 'Resources',
            ];

            foreach ($resources as $resourceKey) {
                $menu[] = [
                    'type' => 'resource',
                    'resource' => $resourceKey,
                    'label' => Str::headline($resourceKey),
                    'icon' => $this->guessResourceIcon($resourceKey),
                ];
            }
        }

        // Add features to menu
        if (! empty($features)) {
            $menu[] = [
                'type' => 'divider',
                'label' => 'Settings',
            ];

            $featureConfig = config('studio.features', []);
            foreach ($features as $featureKey) {
                $feature = $featureConfig[$featureKey] ?? [];
                $menu[] = [
                    'type' => 'feature',
                    'feature' => $featureKey,
                    'label' => $feature['label'] ?? Str::headline($featureKey),
                    'icon' => $feature['icon'] ?? 'cog',
                ];
            }
        }

        return $menu;
    }

    /**
     * Guess icon for a resource based on its name.
     */
    protected function guessResourceIcon(string $resource): string
    {
        $iconMap = [
            'users' => 'users',
            'user' => 'user',
            'roles' => 'shield',
            'role' => 'shield',
            'permissions' => 'key',
            'settings' => 'settings',
            'countries' => 'globe',
            'emails' => 'mail',
            'email-templates' => 'mail',
            'comments' => 'chat',
            'posts' => 'document',
            'pages' => 'document',
            'products' => 'shopping-bag',
            'orders' => 'clipboard',
            'categories' => 'folder',
            'tags' => 'tag',
            'files' => 'folder',
            'media' => 'photograph',
            'notifications' => 'bell',
            'logs' => 'terminal',
            'activities' => 'clock',
        ];

        return $iconMap[$resource] ?? 'collection';
    }

    /**
     * Show summary before generating.
     */
    protected function showSummary(
        string $key,
        string $label,
        string $path,
        string $icon,
        string $role,
        array $roles,
        array $resources,
        array $features,
        bool $allowRegistration
    ): void {
        $this->newLine();
        $this->components->info('Panel Summary:');
        $this->newLine();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Key', $key],
                ['Label', $label],
                ['Path', $path],
                ['Icon', $icon],
                ['Primary Role', $role],
                ['All Roles', implode(', ', $roles) ?: '-'],
                ['Resources', implode(', ', $resources) ?: 'None'],
                ['Features', implode(', ', $features) ?: 'None'],
                ['Allow Registration', $allowRegistration ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();
    }

    /**
     * Generate and display configuration output.
     */
    protected function generateConfigOutput(string $key, array $config): void
    {
        $this->newLine();
        info('Panel configuration generated successfully!');
        $this->newLine();

        $this->components->info("Add the following to config/studio.php under 'panels' array:");
        $this->newLine();

        $this->line("<fg=yellow>'{$key}' => [</>");
        $this->line($this->generateConfigCode($config, 1));
        $this->line('<fg=yellow>],</>');

        $this->newLine();
        $this->line(str_repeat('-', 80));
        $this->newLine();

        $this->components->info('Next steps:');
        $this->components->bulletList([
            'Copy the above configuration to config/studio.php',
            "Add '{$key}' to the 'panel_priority' array in config/studio.php",
            'Run: php artisan config:clear',
            'Ensure users have the required role to access this panel',
        ]);
    }

    /**
     * Generate PHP array code for panel config.
     */
    protected function generateConfigCode(array $config, int $indent = 0): string
    {
        $spaces = str_repeat('    ', $indent);
        $lines = [];

        foreach ($config as $key => $value) {
            $keyStr = is_numeric($key) ? $key : "'{$key}'";

            if (is_array($value)) {
                if (empty($value)) {
                    $lines[] = "{$spaces}{$keyStr} => [],";
                } else {
                    $lines[] = "{$spaces}{$keyStr} => [";
                    $lines[] = $this->generateConfigCode($value, $indent + 1);
                    $lines[] = "{$spaces}],";
                }
            } elseif (is_bool($value)) {
                $valueStr = $value ? 'true' : 'false';
                $lines[] = "{$spaces}{$keyStr} => {$valueStr},";
            } elseif (is_null($value)) {
                $lines[] = "{$spaces}{$keyStr} => null,";
            } elseif (is_numeric($value)) {
                $lines[] = "{$spaces}{$keyStr} => {$value},";
            } else {
                $valueStr = "'" . addslashes($value) . "'";
                $lines[] = "{$spaces}{$keyStr} => {$valueStr},";
            }
        }

        return implode("\n", $lines);
    }
}
