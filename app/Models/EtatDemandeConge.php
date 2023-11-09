<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtatDemandeConge extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function demands()
    {
        return $this->hasMany(DemandeConge::class);
    }
}
