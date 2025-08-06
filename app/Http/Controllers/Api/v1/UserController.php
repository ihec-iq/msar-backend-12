<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateMyPasswordRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Resources\User\UserLiteResource;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\UserResourceCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Opcodes\LogViewer\Logs\Log as LogsLog;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $data = UserResource::collection(User::orderByDesc('updated_at')->get());

        return $this->ok($data);
    }
    public function getLite()
    {
        $data = UserLiteResource::collection(User::all());

        return $this->ok($data);
    }
    public function filter(Request $request)
    {
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        $data = User::orderBy('id', 'desc');

        if (!$request->isNotFilled('email') && $request->email != '') {
            $data = $data->orWhere('email', 'like', '%' . $request->email . '%');
        }
        if (!$request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhere('name', 'like', '%' . $request->name . '%');
            $data = $data->orWhere('email', 'like', '%' . $request->name . '%');
        }
        if (!$request->isNotFilled('sectionId') && $request->sectionId != '') {
            $data = $data->Where('section_id', $request->sectionId);
        }

        $data = $data->orderBy('updated_at', 'desc')->paginate($limit);
        Log::alert($data);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new UserResourceCollection($data));
        }
    }

    public function store(Request $request)
    {
        try {
            $creatorId = Auth::id() ?? 1;
            $validate = $request->validate([
                'name'      => ['required', 'string', 'min:2', 'max:255'],
                'user_name' => ['required', 'string', 'min:2', 'max:255'],
                'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password'  => ['required', 'string', 'min:8', 'confirmed'],
                // 'user_type' => ['nullable', 'string'], // إذا كان لديك user_type
                'any_device' => ['boolean'],
                'active'    => ['boolean'],
            ]);
            $userData = [
                'name'        => $validate['name'],
                'user_name'   => $validate['user_name'],
                'email'       => $validate['email'],
                'password'    => Hash::make($validate['password']),
                // 'user_type'   => $validate['user_type'] ?? null,
                'any_device'  => boolval($validate['any_device']),
                'active'      => boolval($validate['active']),
                'window_id'   => $validate['window_id'] ?? 1,
                'user_id'     => $creatorId,
            ];

            $user = User::create($userData);

            $accessToken = $user->createToken($user->email)->plainTextToken;

            if (!empty($request->roles)) {
                $roles = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
                $user->syncRoles($roles);
            }

            $user->refresh();

            return $this->ok([
                'user'  => new UserResource($user),
                'token' => $accessToken,
            ]);
        } catch (\Throwable $e) {
            Log::error('User Store Error: ' . $e->getMessage());

            return $this->error(__('general.saveUnsuccessfully'));
            // return $this->error($e->getMessage(), __('general.saveUnsuccessfully'));
        }
    }

    public function show(string $id)
    {
        $data = User::find($id);

        return $this->ok(new UserResource($data));
    }

    public function update(Request $request, $user_id)
    {

        //$user = new Request(['user_id' => $user_id]);
        $Validate = $request->validate([
            // 'id' => ['integer', 'exists:users,id'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'user_name' => ['required', 'string', 'min:2', 'max:255'],
            //'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            // 'password' => ['nullable','required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::find($user_id);
        if (!isset($user) || $user == null || $user == '') {
            return $this->error(__('general.saveUnsuccessfully'));
        }
        // if ($user->email != $request->email) {
        //     $validate = $request->validate([
        //         'name' => ['required', 'string', 'min:2', 'max:255'],
        //         'user_name' => ['required', 'string', 'min:2', 'max:255'],
        //         'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        //         'roles' => ['required', 'array', 'exists:roles,id'],
        //     ]);
        // }
        $user->name = $request->name;
        $user->user_name = $request->user_name;
        isset($request->email) && $request->email != '' ? $user->email = $request->email : '';
        isset($request->password) && $request->password != '' ? $user->password = Hash::make($request->password) : '';
        $user->any_device = (isset($request->any_device) && $request->any_device != '') ? $request->any_device : 0;
        $user->active = (isset($request->active) && $request->active != '') ? $request->active : 0;

        if ($request->window_id == null) {
            $user->window_id = 1;
        } else {
            $user->window_id = $request->window_id;
        }

        $user->save();
        $access_token = $user->createToken($request->email)->plainTextToken;

        $user->roles()->detach();
        if (!empty($request->roles)) {
            $roles = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
            $user->syncRoles($roles);
        }

        return $this->ok(
            [
                'user' => new UserResource($user),
                'token' => $access_token,
            ],
            __('general.saveSuccessfully')
        );

        //return $this->error(__('general.saveUnsuccessfully'));
    }
    public function updateMyPassword(Request $request)
    {
        $user = User::find(Auth::user()->id);
        if (!isset($user) || $user == null || $user == '') {
            return $this->error(__('general.saveUnsuccessfully'));
        }
        isset($request->password) && $request->password != '' ? $user->password = Hash::make($request->password) : '';
        $user->save();
        $access_token = $user->createToken($user->email)->plainTextToken;
        return $this->ok([
            'user' => new UserResource($user),
            'token' => $access_token,
        ], __('general.saveSuccessfully'));
        //return $this->error(__('general.saveUnsuccessfully'));
    }

    public function active($id)
    {
        $user = User::find($id);
        if (!isset($user) || $user == null || $user == '') {
            return $this->error(__('general.saveUnsuccessfully'));
        }
        $user->active = true;
        $user->save();

        return $this->ok(new UserResource($user), __('general.saveSuccessfully'));
    }

    public function disActive($id)
    {
        $user = User::find($id);
        if (!isset($user) || $user == null || $user == '') {
            return $this->error(__('general.saveUnsuccessfully'));
        }
        $user->active = false;
        $user->save();

        return $this->ok(new UserResource($user), __('general.saveSuccessfully'));
    }

    public function destroy(string $id)
    {
        $data = User::find($id);
        $data->delete();

        return $this->ok(null);
    }
}
