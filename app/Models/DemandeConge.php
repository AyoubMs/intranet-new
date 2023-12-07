<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeConge extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['typeDemande'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function demand()
    {
        return $this->belongsTo(EtatDemandeConge::class, 'etat_demande_id');
    }

    public function typeDemande()
    {
        return $this->belongsTo(TypeConge::class, 'type_conge_id');
    }
}
