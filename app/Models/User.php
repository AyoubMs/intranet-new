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

    protected $with = ['team_type', 'role', 'operations', 'department', 'primaryLanguage', 'secondaryLanguage', 'identityTypes', 'sourcingType', 'nationality', 'familySituation', 'managers', 'operation', 'motif', 'comment'];

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

    public function conges()
    {
        return $this->hasMany(DemandeConge::class);
    }

    public function comment()
    {
        return $this->hasOne(Comment::class);
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class, 'operation_id');
    }

    public function operations()
    {
        return $this->belongsToMany(Operation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function familySituation()
    {
        return $this->belongsTo(FamilySituation::class, 'family_situation_id');
    }

    public function sourcingType()
    {
        return $this->belongsTo(User::class, 'sourcing_type_id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function identityTypes()
    {
        return $this->hasMany(IdentityType::class);
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

    public function users()
    {
        return $this->belongsToMany(User::class, 'manager_user', 'manager_id', 'user_id');
    }

    public function managers()
    {
        return $this->belongsToMany(User::class, 'manager_user','user_id', 'manager_id');
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
