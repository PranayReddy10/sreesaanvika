<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_filterable' => 'boolean'];
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('position');
    }
}
