<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeCongeLogs extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['modifier', 'demande', 'demandeCongeStack', 'user'];

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modifier_id');
    }

    public function demande()
    {
        return $this->belongsTo(DemandeConge::class, 'demande_conge_id');
    }

    public function demandeCongeStack()
    {
        return $this->belongsTo(DemandeCongeStack::class, 'demande_conge_stack_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
