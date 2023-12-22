<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\DemandeConge;
use App\Models\DemandeCongeLogs;
use App\Models\DemandeCongeStack;
use App\Models\IdentityType;
use App\Models\Language;
use App\Models\ModificationSoldeComment;
use App\Models\Nationality;
use App\Models\Operation;
use App\Models\Role;
use App\Models\SourcingType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use stdClass;

class UserController extends Controller
{

    protected static function getDataFromBody($input)
    {
        $output = [];
        if (!empty($input)) {
            foreach ($input as $item) {
                $output[] = $item;
            }
        }
        return $output;
    }

    protected static function validateUser($body)
    {
        $user = User::where('matricule', $body['past_matricule'] ?? $body['matricule'])->first();
        $validator = Validator::make($body, [
            'matricule' => ['required', $body['edit_matricule'] ? Rule::unique('users')->ignore($user->id ?? null) : 'unique:users,matricule', 'min:5'],
            'email_1' => ['required', $body['edit_email'] ? Rule::unique('users')->ignore($user->id ?? null) : 'unique:users,email_1', 'email'],
            'nom' => 'required|min:2',
            'prenom' => 'required|min:2',
            'date_mep' => 'nullable|date',
            'sexe' => 'required',
            'date_entree_formation' => 'nullable|date',
            'type_identite' => 'nullable',
            'cin_number' => 'nullable',
            'passport_number' => 'nullable',
            'carte_sejour_number' => 'nullable',
            'langue_principale' => 'nullable',
            'nationalite' => 'nullable',
            'date_naissance' => 'nullable|date',
            'sourcing_provider' => 'nullable',
            'situation_familiale' => 'nullable',
            'phone_1' => 'nullable',
            'photo' => 'nullable',
            'phone_2' => 'nullable',
            'nombre_enfants' => 'nullable',
            'cnss_number' => 'nullable',
            'address' => 'nullable',
            'comment' => 'nullable'
        ]);
        return $validator;
    }

    protected static function createUser($body, $request)
    {
        $user = User::factory()->create([
            'matricule' => $body['matricule'],
            'email_1' => $body['email_1'],
            'first_name' => $body['nom'],
            'last_name' => $body['prenom'],
            'date_entree_production' => $body['date_mep'],
            'Sexe' => $body['sexe'] === 'homme' ? 'H' : 'F',
            'date_entree_formation' => $body['date_entree_formation'],
            'primary_language_id' => Language::where('name', 'like', "%" . $body['langue_principale'] . "%")->first()->id,
            'nationality_id' => Nationality::where('name', 'like', $body['nationalite'])->first()->id,
            'date_naissance' => $body['date_naissance'],
            'sourcing_type_id' => SourcingType::where('name', 'like', "%" . $body['sourcing_provider'] . "%")->first()->id,
            'situation_familiale' => $body['situation_familiale'],
            'phone_1' => $body['phone_1'],
            'photo' => $body['photo'],
            'phone_2' => $body['phone_2'],
            'nombre_enfants' => $body['nombre_enfants'],
            'cnss_number' => $body['cnss_number'],
            'address' => $body['address'],
            'creator_id' => json_decode(Redis::get($request->headers->get('Uuid')))->id,
            'solde_cp' => 0,
            'solde_rjf' => 0,
            'active' => true
        ]);
        $user->save();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'comment' => $body['comment']
        ]);
        $comment->save();
        return $user;
    }

    protected static function createIdentity($name, $user_id, $identity_number)
    {
        IdentityType::factory()->create([
            'name' => $name,
            'user_id' => $user_id,
            'identity_number' => $identity_number
        ]);
    }

    protected static function updateUser($body, $request)
    {
        $user = User::where('matricule', $body['past_matricule'])->first();
        $user->matricule = $body['matricule'];
        $user->email_1 = $body['email_1'];
        $user->first_name = $body['nom'];
        $user->last_name = $body['prenom'];
        $user->date_entree_production = $body['date_mep'];
        $user->Sexe = $body['sexe'] === 'homme' ? 'H' : 'F';
        $user->date_entree_formation = $body['date_entree_formation'];
        $user->primary_language_id = Language::where('name', 'like', "%" . $body['langue_principale'] . "%")->first()->id;
        $user->nationality_id = Nationality::where('name', 'like', $body['nationalite'])->first()->id;
        $user->date_naissance = $body['date_naissance'];
        $user->sourcing_type_id = SourcingType::where('name', 'like', "%" . ($body['sourcing_provider'] ?? 'null') . "%")->first()->id ?? null;
        $user->situation_familiale = $body['situation_familiale'];
        $user->phone_1 = $body['phone_1'];
        $user->photo = $body['photo'];
        $user->phone_2 = $body['phone_2'];
        $user->nombre_enfants = $body['nombre_enfants'];
        $user->cnss_number = $body['cnss_number'];
        $user->address = $body['address'];
        $user->creator_id = json_decode(Redis::get($request->headers->get('Uuid')))->id;
        $demande_conge_stack = DemandeCongeStack::factory()->create([
            "solde_cp" => $user->solde_cp,
            "solde_rjf" => $user->solde_rjf,
            "modification_solde_comment_id" => self::getSoldeCommentId("%Modification par RH in editing%"),
            "user_id" => $user->id
        ]);
        $demande_conge_stack->save();
        $demande_conge_log = DemandeCongeLogs::factory()->create([
            "modifier_id" => $user->creator_id,
            "nouveau_solde_cp" => $body['solde_cp'],
            "nouveau_solde_rjf" => $body['solde_rjf'],
            "demande_conge_stack_id" => $demande_conge_stack->id,
            "user_id" => $user->id
        ]);
        $demande_conge_log->save();
        $user->solde_cp = $body['solde_cp'];
        $user->solde_rjf = $body['solde_rjf'];

        $user->save();

        $comment = Comment::where('user_id', $user->id)->first();
        $comment->comment = $body['comment'];
        $comment->save();
    }

    /**
     * @param $body
     * @param array $operations
     * @param array $names
     * @return array
     */
    protected static function getOperationsAndNames($body, array $operations, array $names): array
    {
        if (!empty($body['teams'])) {
            foreach ($body['teams'] as $team) {
                if (str_contains($team, '/')) {
                    $output = explode("/", $team);
                    if (!in_array($output[0], $operations)) {
                        $operations[] = $output[0];
                    }
                    $names[] = $output[1];
                } else {
                    $operations[] = $team;
                }
            }
        }
        return array($operations, $names);
    }

    public static function getUser($request)
    {
        $user = json_decode(Redis::get($request->headers->get('Uuid')));
        if (!is_null($user)) {
            $user = User::where('matricule', $user->matricule ?? '')->first();
            $user->totalDesDemandes = DemandeConge::where('user_id', $user->id)->count();
//                    $user->demandesEnCours = DemandeConge::whereIn('etat_demande_id', EtatDemandeConge::whereNotIn('etat_demande', ['canceled', 'rejected', 'closed'])->pluck('id')->toArray())->count();
            Redis::set($request->headers->get('Uuid'), $user);
        }
        return Redis::get($request->headers->get('Uuid'));
    }

    public static function deactivateUser($body)
    {
        $validator = Validator::make($body, [
            'date_depart' => 'required|date'
        ]);
        if ($validator->fails()) {
            return $validator->messages()->toArray();
        } else {
            $user = User::where('matricule', $body['matricule'])->first();
            $user->date_depart = $body['date_depart'];
            $user->active = false;
            $user->save();
            return 'done';
        }

    }

    public static function affectUser($body, $request)
    {
        $operations = [];
        $names = [];
        $first_names = [];
        $last_names = [];
//        info($body);
        $user = User::where('matricule', $body['matricule'])->first();
        list($operations, $names) = self::getOperationsAndNames($body, $operations, $names);
        foreach ($names as $name) {
            $name = trim($name);
            $explosion = explode(' ', $name);
            $first_names[] = $explosion[0];
            $last_names[] = $explosion[1];
        }
        $operations_ids_array = Operation::whereIn('name', $operations)->pluck('id')->toArray();
        $manager_ids = User::whereIn('first_name', $first_names)->whereIn('last_name', $last_names)->pluck('id')->toArray();
        $user_operations_ids = $user->operations->pluck('id')->toArray();
        $user_manager_ids = $user->managers->pluck('id')->toArray();
        foreach ($operations_ids_array as $operation_id) {
            if (!in_array($operation_id, $user_operations_ids)) {
                $user->operations()->attach($operation_id);
            }
        }
        foreach ($user_operations_ids as $user_operations_id) {
            if (!in_array($user_operations_id, $operations_ids_array)) {
                $user->operations()->detach($user_operations_id);
            }
        }
        foreach ($manager_ids as $manager_id) {
            if (!in_array($manager_id, $user_manager_ids)) {
                $user->managers()->attach($manager_id);
            }
        }
        $user->date_entree_formation = $body['date_entree_formation'];
        if (!is_null($body['principal_operation'])) {
//            info($body['principal_operation']);
            $operation_id = Operation::where('name', 'like', "%".$body['principal_operation']."%")->first()->id;
            $user->operation_id = $operation_id;
            if (!in_array($operation_id, $user_operations_ids)) {
                $user->operations()->attach($operation_id);
            }
        }
        if ($role = Role::where('name', $body['profile'])->first()) {
            $user->role_id = $role->id;
        }
        $user->save();
        return "done";
    }

    protected static function filterWithInput($query, $input)
    {
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'role_ids':
                    if (!$value->isEmpty()) {
                        $query->whereIn('role_id', $value);
                    }
                    break;
                case 'language_ids':
                    if (!$value->isEmpty()) {
                        $query->whereIn('language_id', $value);
                    }
                    break;
                case 'date_debut':
                    if (!is_null($value)) {
                        $query->whereDate('date_entree_production', '>=', $value ?? '1980-01-01');
                    }
                    break;
                case 'date_fin':
                    if (!is_null($value)) {
                        $query->whereDate('date_entree_production', '<=', $value ?? '2100-01-01');
                    }
                    break;
                case 'active':
                    if (!is_null($value)) {
                        $query->where('active', $value);
                    }
                    break;
            }
        }
    }

    public static function getUsers($status, $body)
    {
        $operations = [];
        $names = [];
        list($operations, $names) = self::getOperationsAndNames($body, $operations, $names);
        $profiles = self::getDataFromBody($body['profiles'] ?? []);
        $languages = self::getDataFromBody($body['languages'] ?? []);
        $operation_ids = Operation::whereIn('name', $operations)->pluck('id') ?? new Collection([]);
        $role_ids = Role::whereIn('name', $profiles)->pluck('id') ?? [];
        $language_ids = Language::whereIn('name', $languages)->pluck('id') ?? new Collection([]);
        $dateDebut = $body['dateDebut'];
        $dateFin = $body['dateFin'];
        $active = null;
        if ($status === 'active_users' || $status === 'inactive_users') {
            $active = ($status === 'active_users');
        }
        $managers_ids = [];
        if (!empty($names)) {
            foreach ($names as $name) {
                $wholeName = explode(' ', $name);
                $managers_ids[] = User::where('first_name', $wholeName[1])->where('last_name', $wholeName[2])->first()->id;
            }
        }
        $managers_ids = new Collection($managers_ids);

        $input = array('role_ids' => $role_ids, 'language_ids' => $language_ids, 'date_debut' => $dateDebut, 'date_fin' => $dateFin, 'active' => $active);
        $user_ids = [];
        $operations = Operation::whereIn('id', $operation_ids)->get();
        if ($operation_ids->isEmpty()) {
            $users = User::when($input, function ($query, $input) {
                self::filterWithInput($query, $input);
            })->get();
            return (new Collection($users))->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->with('role')->paginate();
        } else {
            foreach ($operations as $operation) {
                $user_ids[] = $operation->users->pluck('id')->toArray();
                if (!$managers_ids->isEmpty()) {
                    $user_ids = [];
                    foreach ($operation->users as $user) {
                        foreach ($user->managers->pluck('id')->toArray() as $manager_id) {
                            if (in_array($manager_id, $managers_ids->toArray())) {
                                $user_ids[] = array($user->id);
                            }
                        }
                    }
                }
            }
            $users = User::whereIn('id', array_merge(...$user_ids) ?? $user_ids)->when($input, function ($query, $input) {
                self::filterWithInput($query, $input);
            })->get();
            if (!(new Collection($users))->isEmpty()) {
                return (new Collection($users))->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->with('role')->paginate();
            } else {
                return User::paginate();
            }
        }
    }

    public static function addUser($body, $request)
    {
        $validator = self::validateUser($body);
        if ($validator->fails()) {
            return $validator->messages()->toArray();
        } else {
            $user = self::createUser($body, $request);
            self::createIdentity('CIN', $user->id, $body['cin_number']);
            self::createIdentity('Carte sejour', $user->id, $body['carte_sejour_number']);
            self::createIdentity('Passeport', $user->id, $body['passport_number']);
            return 'done';
        }
    }

    public static function editUser($body, $request)
    {
        $validator = self::validateUser($body);
        if ($validator->fails()) {
            return $validator->messages()->toArray();
        } else {
            self::updateUser($body, $request);
            return 'done';
        }
    }

    public static function getSoldeCommentId($solde_comment)
    {
        return ModificationSoldeComment::where('comment_on_solde', 'like', $solde_comment)->first()->id;
    }
}
