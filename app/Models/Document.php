<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'organization_id', 'uploaded_by', 'title', 'description',
        'category', 'audience', 'department_id', 'employee_id',
        'file_path', 'file_name', 'file_size', 'file_type',
        'version', 'is_active', 'download_count',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'file_size'      => 'integer',
        'download_count' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function downloads()
    {
        return $this->hasMany(DocumentDownload::class);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $size = $this->file_size;
        if ($size < 1024)    return "{$size} B";
        if ($size < 1048576) return round($size / 1024, 1) . ' KB';
        return round($size / 1048576, 1) . ' MB';
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'policy'            => 'Company Policy',
            'handbook'          => 'Employee Handbook',
            'offer_letter'      => 'Offer Letter',
            'appraisal'         => 'Appraisal Letter',
            'salary_slip'       => 'Salary Slip',
            'experience_letter' => 'Experience Letter',
            'nda'               => 'NDA',
            default             => 'Other',
        };
    }

    public function scopeVisibleTo($query, $user)
    {
        if ($user->hasAnyRole(['hr', 'admin', 'owner', 'super_admin'])) {
            return $query->where('organization_id', $user->organization_id);
        }

        return $query->where(function ($q) use ($user) {
            $q->where(function ($q2) use ($user) {
                $q2->where('audience', 'org')
                   ->where('organization_id', $user->organization_id);
            })->orWhere(function ($q2) use ($user) {
                $q2->where('audience', 'department')
                   ->where('department_id', $user->department_id);
            })->orWhere(function ($q2) use ($user) {
                $q2->where('audience', 'personal')
                   ->where('employee_id', $user->id);
            });
        });
    }
}
