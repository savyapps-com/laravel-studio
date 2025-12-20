<?php

namespace SavyApps\LaravelStudio\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SavyApps\LaravelStudio\Database\Factories\EmailTemplateFactory;

class EmailTemplate extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return EmailTemplateFactory::new();
    }

    protected $fillable = [
        'key',
        'name',
        'subject_template',
        'body_content',
        'preview_thumbnail',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator()
    {
        $userModel = config('studio.authorization.models.user', 'App\\Models\\User');

        return $this->belongsTo($userModel, 'created_by');
    }

    public function updater()
    {
        $userModel = config('studio.authorization.models.user', 'App\\Models\\User');

        return $this->belongsTo($userModel, 'updated_by');
    }
}
