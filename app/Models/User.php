<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $guarded = [];

    protected $with = ['team_type', 'role', 'operation', 'department', 'primaryLanguage', 'secondaryLanguage', 'identityType'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function familySituation()
    {
        $this->belongsTo(FamilySituation::class, 'family_situation_id');
    }

    public function sourcingType()
    {
        $this->belongsTo(User::class, 'sourcing_type_id');
    }

    public function nationality()
    {
        $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function identityType()
    {
        return $this->belongsTo(IdentityType::class, 'identity_type_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function primaryLanguage()
    {
        return $this->belongsTo(Language::class, 'primary_language_id');
    }

    public function secondaryLanguage()
    {
        return $this->belongsTo(Language::class, 'secondary_language_id');
    }

    public function motif()
    {
        return $this->belongsTo(MotifDepart::class, 'motif_depart_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function team_type()
    {
        return $this->belongsTo(TeamType::class, 'team_type_id');
    }
}
