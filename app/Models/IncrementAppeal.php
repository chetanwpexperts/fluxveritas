<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncrementAppeal extends Model
{
    protected $fillable = [
        'review_id', 'user_id', 'organization_id', 'appeal_reason', 'evidence',
        'status', 'reviewed_by', 'reviewer_notes', 'original_increment', 'revised_increment',
    ];

    protected $casts = ['original_increment' => 'float', 'revised_increment' => 'float'];

    public function review() { return $this->belongsTo(IncrementReview::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
