<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentDownload extends Model
{
    public $timestamps = false;

    protected $fillable = ['document_id', 'user_id', 'downloaded_at', 'ip_address'];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
