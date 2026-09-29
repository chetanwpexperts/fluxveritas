<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    protected $fillable = [
        'user_id', 'phone', 'alternate_email', 'designation', 'date_of_joining',
        'date_of_birth', 'skills', 'bio', 'github_username',
        'linkedin_url', 'profile_photo', 'is_directory_visible',
    ];

    protected $casts = [
        'skills'               => 'array',
        'date_of_joining'      => 'date',
        'date_of_birth'        => 'date',
        'is_directory_visible' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsCompleteAttribute(): bool
    {
        return !empty($this->designation) && !empty($this->skills) && !empty($this->bio);
    }

    public function getMissingFieldsAttribute(): array
    {
        $missing = [];
        if (empty($this->designation)) $missing[] = 'designation';
        if (empty($this->skills))      $missing[] = 'skills';
        if (empty($this->bio))         $missing[] = 'bio';
        return $missing;
    }
}
