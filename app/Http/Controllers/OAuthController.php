<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Exception;
use http\Header;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use MongoDB\Driver\Exception\ConnectionException;

class OAuthController extends Controller
{
    public function redirect(Request $request)
    {
        Redis::set('token', $request->get('token'));
        return Socialite::driver('azure')->redirect();
    }

    public function callback(Request $request)
    {
        $azureUser = Socialite::driver('azure')->stateless()->user();
        $user = User::where(['email' => $azureUser->getEmail()])->first();
        if ($user === null) {
            $user = User::firstOrCreate([
                'email' => $azureUser->getEmail(),
                'name' => $azureUser->getName()
            ]);
        }
        $user->provider_id = $azureUser->getId();

        auth()->login($user, true);
        Redis::set(Redis::get('token'), json_encode($user));
        return redirect()->to('http://localhost:3000/dashboard')->header('user', $user);
    }
}
