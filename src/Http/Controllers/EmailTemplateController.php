<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Http\Requests\EmailTemplateRequest;
use SavyApps\LaravelStudio\Models\EmailTemplate;
use SavyApps\LaravelStudio\Services\EmailTemplateService;
use SavyApps\LaravelStudio\Services\EmailVariableRegistry;

class EmailTemplateController extends Controller
{
    public function __construct(
        protected EmailTemplateService $templateService,
        protected EmailVariableRegistry $variableRegistry
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request->user())) {
            abort(403, 'Unauthorized');
        }

        $templates = EmailTemplate::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->filter_active !== null, fn ($q) => $q->where('is_active', $request->filter_active))
            ->orderBy($request->sort ?? 'created_at', $request->direction ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $templates->items(),
            'meta' => [
                'total' => $templates->total(),
                'per_page' => $templates->perPage(),
                'current_page' => $templates->currentPage(),
            ],
        ]);
    }

    public function store(EmailTemplateRequest $request): JsonResponse
    {
        // Find existing template by key
        $existingTemplate = EmailTemplate::query()->where('key', $request->validated()['key'])->first();

        if ($existingTemplate) {
            // Update existing template
            $existingTemplate->update(array_merge(
                $request->validated(),
                ['updated_by' => $request->user()->id]
            ));
            $template = $existingTemplate;
        } else {
            // Create new template
            $template = EmailTemplate::query()->create(array_merge(
                $request->validated(),
                ['created_by' => $request->user()->id]
            ));
        }

        return response()->json(['data' => $template], $existingTemplate ? 200 : 201);
    }

    public function show(EmailTemplate $template): JsonResponse
    {
        return response()->json(['data' => $template]);
    }

    public function update(EmailTemplateRequest $request, EmailTemplate $template): JsonResponse
    {
        $template->update(array_merge(
            $request->validated(),
            ['updated_by' => $request->user()->id]
        ));

        return response()->json(['data' => $template]);
    }

    public function destroy(EmailTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json(null, 204);
    }

    public function duplicate(EmailTemplate $template): JsonResponse
    {
        $duplicate = $template->replicate()->fill([
            'key' => $template->key.'_copy_'.now()->timestamp,
            'name' => $template->name.' (Copy)',
            'created_by' => request()->user()->id,
        ]);
        $duplicate->save();

        return response()->json(['data' => $duplicate], 201);
    }

    public function preview(Request $request, EmailTemplate $template): JsonResponse
    {
        $sampleData = $this->variableRegistry->getSampleData($template->key);

        $rendered = $this->templateService->preview($template, $sampleData);

        return response()->json($rendered);
    }

    public function sendTest(Request $request, EmailTemplate $template): JsonResponse
    {
        $request->validate([
            'emails' => 'required|array',
            'emails.*' => 'required|email',
        ]);

        $sampleData = $this->variableRegistry->getSampleData($template->key);

        $this->templateService->sendTest($template, $request->emails, $sampleData);

        return response()->json(['message' => 'Test emails sent successfully']);
    }

    public function variables(EmailTemplate $template): JsonResponse
    {
        $variables = $this->variableRegistry->getVariablesForTemplate($template->key);

        return response()->json(['data' => $variables]);
    }

    /**
     * Check if user is admin
     * Uses the user's isAdmin method if available, otherwise checks for admin role
     */
    protected function isAdmin($user): bool
    {
        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super_admin');
        }

        return false;
    }
}
