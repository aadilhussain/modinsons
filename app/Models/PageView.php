<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $fillable = ['path', 'title', 'visitor_hash', 'referrer', 'device', 'viewed_on'];

    protected $casts = ['viewed_on' => 'date'];
}
