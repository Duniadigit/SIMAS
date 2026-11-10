<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterType extends Model
{
    protected $fillable = ['name', 'code', 'default_title', 'body_template', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
