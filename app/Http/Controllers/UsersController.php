<?php namespace ImguBox\Http\Controllers;

use ImguBox\Http\Requests;
use ImguBox\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;

class UsersController extends Controller
{
    /**
     * Current Active User
     * @var ImguBox\User;
     */
    protected $user;

    public function __construct(Guard $auth)
    {
        $this->user = $auth->user();
    }

    public function closeAccount(Request $request)
    {
        $tokens = $this->user->tokens;

        foreach ($tokens as $token) {
            $token->delete();
        }

        $this->user->delete();

        \Auth::logout();

        return redirect('/');
    }
}
