<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    use HasFactory;

    public const STATUSES = ['new', 'contacted', 'quoted', 'won', 'closed'];

    protected $fillable = [
        'product_id', 'reference', 'name', 'company', 'phone', 'email', 'city',
        'buyer_type', 'quantity', 'unit', 'message', 'source_page',
        'status', 'admin_note', 'ip_hash', 'user_agent',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getStatusColourAttribute(): string
    {
        return match ($this->status) {
            'new' => 'blue',
            'contacted' => 'amber',
            'quoted' => 'violet',
            'won' => 'green',
            default => 'slate',
        };
    }
}
