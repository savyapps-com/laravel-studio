<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use SavyApps\LaravelStudio\Models\Panel;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

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
                            {--role= : The required role to access the panel}
                            {--default : Set as default panel}
                            {--inactive : Create as inactive}
                            {--force : Overwrite existing panel}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Laravel Studio panel interactively';

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
        $this->components->info('Creating a new Laravel Studio Panel');
        $this->newLine();

        // Get panel key
        $key = $this->argument('key') ?? $this->promptForKey();

        if (! $key) {
            $this->components->error('Panel key is required.');

            return self::FAILURE;
        }

        // Check if panel already exists
        $existingPanel = Panel::where('key', $key)->first();
        if ($existingPanel && ! $this->option('force')) {
            $this->components->error("Panel [{$key}] already exists!");
            $this->info('Use --force to overwrite.');

            return self::FAILURE;
        }

        // Gather panel details
        $label = $this->option('label') ?? $this->promptForLabel($key);
        $path = $this->option('path') ?? $this->promptForPath($key);
        $icon = $this->option('icon') ?? $this->promptForIcon();
        $role = $this->option('role') ?? $this->promptForRole($key);
        $roles = $this->promptForAdditionalRoles($role);
        $resources = $this->promptForResources();
        $features = $this->promptForFeatures();
        $isDefault = $this->option('default') || $this->promptForDefault();
        $isActive = ! $this->option('inactive');

        if (! $this->option('inactive') && $this->input->isInteractive()) {
            $isActive = $this->promptForActive();
        }

        // Build menu structure
        $menu = $this->buildDefaultMenu($label, $key, $resources, $features);

        // Show summary before creating
        $this->showSummary($key, $label, $path, $icon, $role, $roles, $resources, $features, $isDefault, $isActive);

        if ($this->input->isInteractive()) {
            if (! confirm('Create this panel?', true)) {
                $this->components->warn('Panel creation cancelled.');

                return self::SUCCESS;
            }
        }

        // Create or update the panel
        $panelData = [
            'key' => $key,
            'label' => $label,
            'path' => $path,
            'icon' => $icon,
            'role' => $role,
            'roles' => $roles,
            'middleware' => ['api', 'auth:sanctum', "panel:{$key}"],
            'resources' => $resources,
            'features' => $features,
            'menu' => $menu,
            'settings' => [
                'layout' => 'classic',
                'theme' => 'light',
            ],
            'is_active' => $isActive,
            'is_default' => false, // Set separately to handle other panels
            'priority' => $this->getNextPriority(),
        ];

        if ($existingPanel) {
            $existingPanel->update($panelData);
            $panel = $existingPanel;
            $this->components->info("Panel [{$key}] updated successfully.");
        } else {
            $panel = Panel::create($panelData);
            $this->components->info("Panel [{$key}] created successfully.");
        }

        // Set as default if requested
        if ($isDefault) {
            $panel->setAsDefault();
            $this->components->info("Panel [{$key}] set as default.");
        }

        // Show next steps
        $this->showNextSteps($key, $path);

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
     * Prompt for default panel setting.
     */
    protected function promptForDefault(): bool
    {
        $existingDefault = Panel::where('is_default', true)->first();

        if ($existingDefault) {
            return confirm(
                label: "Set as default panel? (Current default: {$existingDefault->label})",
                default: false,
                hint: 'Default panel is shown when user logs in'
            );
        }

        return confirm(
            label: 'Set as default panel?',
            default: true,
            hint: 'Default panel is shown when user logs in'
        );
    }

    /**
     * Prompt for active status.
     */
    protected function promptForActive(): bool
    {
        return confirm(
            label: 'Activate this panel?',
            default: true,
            hint: 'Inactive panels are hidden from users'
        );
    }

    /**
     * Get existing roles from the database.
     */
    protected function getExistingRoles(): array
    {
        try {
            $roleClass = config('studio.authorization.models.role', 'App\\Models\\Role');

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
     * Get next priority number for ordering.
     */
    protected function getNextPriority(): int
    {
        $maxPriority = Panel::max('priority') ?? 0;

        return $maxPriority + 10;
    }

    /**
     * Show summary before creating.
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
        bool $isDefault,
        bool $isActive
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
                ['Default', $isDefault ? 'Yes' : 'No'],
                ['Active', $isActive ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();
    }

    /**
     * Show next steps after creation.
     */
    protected function showNextSteps(string $key, string $path): void
    {
        $this->newLine();
        $this->components->info('Next steps:');
        $this->components->bulletList([
            "Add resources to the panel using the admin UI or Panel Management API",
            "Configure the panel menu in the database or via API",
            "Create the frontend route for path: {$path}",
            "Ensure users have the required role to access this panel",
        ]);

        $this->newLine();
        $this->line('<fg=gray>// Access panel via API:</>');
        $this->line("<fg=yellow>GET /api/panels/{$key}</>");
        $this->newLine();
        $this->line('<fg=gray>// Switch to this panel:</>');
        $this->line("<fg=yellow>POST /api/panels/{$key}/switch</>");
    }
}
