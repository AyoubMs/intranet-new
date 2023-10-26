<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\IdentityType;
use App\Models\Language;
use App\Models\Nationality;
use App\Models\Operation;
use App\Models\Role;
use App\Models\SourcingType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use stdClass;

class UserController extends Controller
{
    protected static function getUsersFromOperations($operation_ids, $manager_ids)
    {
        $operations = Operation::whereIn('id', $operation_ids)->get();
        $users = [];
        foreach ($operations as $operation) {
            foreach ($operation->users as $user) {
                foreach ($user->managers->pluck('id')->toArray() as $manager_id) {
                    if (in_array($manager_id, $manager_ids) and !in_array($user, $users)) {
                        $users[] = $user;
                    }
                }
                if (empty($manager_ids)) {
                    $users[] = $user;
                }
            }
        }
        return new Collection($users);
    }


    protected static function getQueriesByStatusAndNamesPartial($operation_ids, $role_ids, $language_ids, $status, $manager_ids, $active, $dateDebut, $dateFin)
    {
        $users = self::getUsersFromOperations($operation_ids, $manager_ids);
        if ($status !== 'all_users') {
            $output = [
                0 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereNull('date_entree_production')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                1 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                2 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereNull('date_entree_production')->where('active', $active)->whereIn('language_id', $language_ids)->paginate(),
                3 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('language_id', $language_ids)->paginate(),
                4 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereNull('date_entree_production')->where('active', $active)->whereIn('role_id', $role_ids)->paginate(),
                5 => User::whereNull('date_entree_production')->where('active', $active)->whereIn('role_id', $role_ids)->paginate(),
                6 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereNull('date_entree_production')->where('active', $active)->paginate(),
                7 => User::whereNull('date_entree_production')->where('active', $active)->paginate(),
                8 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                9 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                10 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->where('active', $active)->whereIn('language_id', $language_ids)->paginate(),
                11 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->where('active', $active)->whereIn('language_id', $language_ids)->paginate(),
                12 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->where('active', $active)->whereIn('role_id', $role_ids)->paginate(),
                13 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->where('active', $active)->whereIn('role_id', $role_ids)->paginate(),
                14 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->where('active', $active)->paginate(),
                15 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->where('active', $active)->paginate(),
                16 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                17 => User::whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                18 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->where('active', $active)->whereIn('language_id', $language_ids)->paginate(),
                19 => User::whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->where('active', $active)->whereIn('language_id', $language_ids)->paginate(),
                20 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->where('active', $active)->whereIn('role_id', $role_ids)->paginate(),
                21 => User::whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->where('active', $active)->whereIn('role_id', $role_ids)->paginate(),
                22 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->where('active', $active)->paginate(),
                23 => User::whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->where('active', $active)->paginate(),
                24 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                25 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                26 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('language_id', $language_ids)->paginate(),
                27 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('language_id', $language_ids)->paginate(),
                28 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('role_id', $role_ids)->paginate(),
                29 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->whereIn('role_id', $role_ids)->paginate(),
                30 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->paginate(),
                31 => User::whereBetween('date_entree_production', [$dateDebut, $dateFin])->where('active', $active)->paginate(),
            ];
        } else {
            $output = [
                0 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereNull('date_entree_production')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                1 => User::whereNull('date_entree_production')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                2 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereNull('date_entree_production')->whereIn('language_id', $language_ids)->paginate(),
                3 => User::whereNull('date_entree_production')->whereIn('language_id', $language_ids)->paginate(),
                4 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereNull('date_entree_production')->whereIn('role_id', $role_ids)->paginate(),
                5 => User::whereNull('date_entree_production')->whereIn('role_id', $role_ids)->paginate(),
                6 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereNull('date_entree_production')->paginate(),
                7 => User::whereNull('date_entree_production')->paginate(),
                8 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                9 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                10 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->whereIn('language_id', $language_ids)->paginate(),
                11 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->whereIn('language_id', $language_ids)->paginate(),
                12 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->whereIn('role_id', $role_ids)->paginate(),
                13 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->whereIn('role_id', $role_ids)->paginate(),
                14 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                15 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                16 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                17 => User::whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                18 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->whereIn('language_id', $language_ids)->paginate(),
                19 => User::whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->whereIn('language_id', $language_ids)->paginate(),
                20 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->whereIn('role_id', $role_ids)->paginate(),
                21 => User::whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->whereIn('role_id', $role_ids)->paginate(),
                22 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                23 => User::whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                24 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                25 => User::whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->paginate(),
                26 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->whereIn('language_id', $language_ids)->paginate(),
                27 => User::whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->whereIn('language_id', $language_ids)->paginate(),
                28 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->whereIn('role_id', $role_ids)->paginate(),
                29 => User::whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->whereIn('role_id', $role_ids)->paginate(),
                30 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                31 => User::whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
            ];
        }

        return $output;
    }

    protected static function getQueriesByStatusAndNamesFinal($operation_ids, $role_ids, $language_ids, $status, $names, $active, $dateDebut, $dateFin)
    {
        $managers_ids = [];
        if (empty($names)) {
            return self::getQueriesByStatus($operation_ids, $role_ids, $language_ids, $status, $active, $dateDebut, $dateFin, $managers_ids);
        } else {
            foreach ($names as $name) {
                $wholeName = explode(' ', $name);
                $managers_ids[] = User::where('first_name', $wholeName[1])->where('last_name', $wholeName[2])->first()->id;
            }
            return self::getQueriesByStatusAndNamesPartial($operation_ids, $role_ids, $language_ids, $status, $managers_ids, $active, $dateDebut, $dateFin);
        }
    }

    protected static function getQueriesByStatus($operation_ids, $role_ids, $language_ids, $status, $active, $dateDebut, $dateFin, $manager_ids)
    {
        $users = self::getUsersFromOperations($operation_ids, $manager_ids);
        if ($status !== 'all_users') {
            $output = [
                0 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                1 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                2 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                3 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                4 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('role_id', $role_ids)->whereNull('date_entree_production')->paginate(),
                5 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereNull('date_entree_production')->paginate(),
                6 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereNull('date_entree_production')->paginate(),
                7 => User::where('active', $active)->whereNull('date_entree_production')->paginate(),
                8 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                9 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                10 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                11 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                12 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                13 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                14 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                15 => User::where('active', $active)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                16 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                17 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                18 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                19 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                20 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                21 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                22 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                23 => User::where('active', $active)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                24 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                25 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                26 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                27 => User::where('active', $active)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                28 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereIn('role_id', $role_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                29 => User::where('active', $active)->whereIn('role_id', $role_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                30 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->where('active', $active)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                31 => User::where('active', $active)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
            ];
        } else {
            $output = [
                0 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                1 => User::whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                2 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                3 => User::whereIn('language_id', $language_ids)->whereNull('date_entree_production')->paginate(),
                4 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('role_id', $role_ids)->whereNull('date_entree_production')->paginate(),
                5 => User::whereIn('role_id', $role_ids)->whereNull('date_entree_production')->paginate(),
                6 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereNull('date_entree_production')->paginate(),
                7 => User::whereNull('date_entree_production')->paginate(),
                8 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                9 => User::whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                10 => User::whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                11 => User::whereIn('language_id', $language_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                12 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                13 => User::whereIn('role_id', $role_ids)->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                14 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                15 => User::whereDate('date_entree_production', '>=', $dateDebut ?? '1980-01-01')->paginate(),
                16 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                17 => User::whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                18 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                19 => User::whereIn('language_id', $language_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                20 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('role_id', $role_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                21 => User::whereIn('role_id', $role_ids)->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                22 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                23 => User::whereDate('date_entree_production', '<=', $dateFin ?? '2100-01-01')->paginate(),
                24 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                25 => User::whereIn('role_id', $role_ids)->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                26 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                27 => User::whereIn('language_id', $language_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                28 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereIn('role_id', $role_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                29 => User::whereIn('role_id', $role_ids)->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                30 => $users->isEmpty() ? [] : $users->toQuery()->with('comment')->with('motif')->with('operation')->with('operations')->with('managers')->whereBetween('date_entree_production', [$dateDebut ?? '1980-01-01', $dateFin ?? '2100-01-01'])->paginate(),
                31 => User::whereBetween('date_entree_production', [$dateDebut ?? '', $dateFin ?? ''])->paginate(),
            ];
        }

        return $output;
    }

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

    protected static function bundleConditionsAndQueries($operation_ids, $role_ids, $language_ids, $status, $names, $active, $dateDebut, $dateFin)
    {
        $conditions = [
            0 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            1 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            2 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            3 => $operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            4 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            5 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            6 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            7 => $operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and is_null($dateFin),
            8 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            9 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            10 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            11 => $operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            12 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            13 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            14 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            15 => $operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and is_null($dateFin),
            16 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            17 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            18 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            19 => $operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            20 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            21 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            22 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            23 => $operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and is_null($dateDebut) and !is_null($dateFin),
            24 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            25 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            26 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            27 => $operation_ids->isEmpty() and $role_ids->isEmpty() and !$language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            28 => !$operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            29 => $operation_ids->isEmpty() and !$role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            30 => !$operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
            31 => $operation_ids->isEmpty() and $role_ids->isEmpty() and $language_ids->isEmpty() and !is_null($dateDebut) and !is_null($dateFin),
        ];

        $output = [];
        foreach ($conditions as $key => $condition) {
            $obj = new StdClass();
            $obj->condition = $condition;
            $obj->query = self::getQueriesByStatusAndNamesFinal($operation_ids, $role_ids, $language_ids, $status, $names, $active, $dateDebut, $dateFin)[$key];
            $output[] = $obj;
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
            'solde_rjf' => 0
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

    public static function affectUser($body)
    {
        $operations = [];
        $names = [];
        $first_names = [];
        $last_names = [];
        $user = User::where('matricule', $body['matricule'])->first();
        list($operations, $names) = self::getOperationsAndNames($body, $operations, $names);
        foreach ($names as $name) {
            $explosion = explode(' ', $name);
            $first_names[] = $explosion[0];
            $last_names[] = $explosion[1];
        }
        $operations_ids_array = Operation::whereIn('name', $operations)->pluck('id')->toArray();
        $manager_ids = User::whereIn('first_name', $first_names)->whereIn('last_name', $last_names)->get();
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
            $user->operation_id = Operation::where('name', $body['principal_operation'])->first()->id;
        }
        $user->save();
        return "done";
    }

    public static function getUsers($status, $body)
    {
        $operations = [];
        $names = [];
        list($operations, $names) = self::getOperationsAndNames($body, $operations, $names);
        $profiles = self::getDataFromBody($body['profiles'] ?? []);
        $languages = self::getDataFromBody($body['languages'] ?? []);
        $operation_ids = Operation::whereIn('name', $operations)->pluck('id');
        $role_ids = Role::whereIn('name', $profiles)->pluck('id');
        $language_ids = Language::whereIn('name', $languages)->pluck('id');
        $active = null;
        if ($status === 'active_users' || $status === 'inactive_users') {
            $active = ($status === 'active_users');
        }

        $objectToTake = [];
        foreach (self::bundleConditionsAndQueries($operation_ids, $role_ids, $language_ids, $status, $names, $active, $body['dateDebut'] ?? null, $body['dateFin'] ?? null) as $key => $obj) {
            if ($obj->condition) {
                $objectToTake = $obj->query;
            }
        }

        return $objectToTake;
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
}
