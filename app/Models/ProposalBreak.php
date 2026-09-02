<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'new_break_in',
        'new_break_out',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
