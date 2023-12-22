<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeCongeStack extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['modificationCongeComment'];

    public function modificationCongeComment()
    {
        return $this->belongsTo(ModificationSoldeComment::class, 'modification_solde_comment_id');
    }

    public function demandeCongeLogs()
    {
        return $this->hasMany(DemandeCongeLogs::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function demandesConge()
    {
        return $this->belongsTo(DemandeConge::class, 'demande_conge_id');
    }
}
