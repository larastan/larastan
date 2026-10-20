<?php

namespace NoMixedRequestInputInQuery;

use App\MemberWithNonIncrementingStringKey;
use App\Post;
use App\User;
use App\UuidModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as RequestFacade;

class LoginRequest extends FormRequest
{
}

/**
 * @property string|null $slack_id
 * @method static Builder<static>|static query()
 */
class IdeHelperUser extends Model
{
}

function stringColumns(Request $request, LoginRequest $loginRequest, User $user): void
{
    User::where('email', $request->input('email'));
    User::query()->where('email', '=', $request->get('email'));
    User::query()->orWhere('email', $request->post('email'));
    User::query()->firstWhere('email', $request->json('email'));
    User::where(['email' => $request->input('email')]);
    User::query()->whereIn('email', $request->input('emails'));
    User::where('users.email', $request->input('email'));
    $user->where('email', $request->email);
    User::where('email', request('email'));
    User::where('email', request()->input('email'));
    User::where('email', RequestFacade::input('email'));
    User::where('email', $loginRequest->input('email'));
    User::where('email', $loginRequest->validated('email'));
    User::where('email', $loginRequest->validated()['email']);
    User::where('email', $loginRequest->safe()->input('email'));
    User::where('email', $loginRequest->safe()->email);
    User::where('email', $loginRequest->safe()['email']);
    User::where('email', $request->all()['email']);
    User::where('enum_status', $request->input('status'));
    User::where('backed_enum', $request->input('status'));
    IdeHelperUser::query()->where('slack_id', $request->input('user_id'));
}

function stringKeys(Request $request): void
{
    UuidModel::find($request->input('id'));
    UuidModel::query()->findOrFail($request->input('id'));
    MemberWithNonIncrementingStringKey::query()->whereKey($request->input('id'));
}

function unknownColumns(Request $request, string $column): void
{
    DB::table('users')->where('email', $request->input('email'));
    User::where('not_a_column', $request->input('value'));
    User::where($column, $request->input('value'));
}

function numericColumns(Request $request, User $user): void
{
    User::where('int', $request->input('int'));
    User::where('blocked', $request->input('blocked'));
    User::where(['int' => $request->input('int')]);
    User::find($request->input('id'));
    User::query()->findOrFail($request->input('id'));
    Post::find($request->input('id'));
    $user->accounts()->find($request->input('id'));
}

function notRequestInput(Request $request, mixed $mixed, string $email): void
{
    User::where('email', $request->string('email')->toString());
    User::where('email', $request->query('email'));
    User::where('email', (string) $request->input('email'));
    User::where('email', $email);
    User::where('email', $mixed);
    User::where('email', $request->query);
    User::query()->with($request->input('relations'));
}
