<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Core\Permissions\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->orderBy('name')->paginate(15);
        $roles = Role::all();
        $totalUsers = User::count();
        $activeUsers = User::where('status','active')->count();
        return view('users.index', compact('users','roles','totalUsers','activeUsers'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('users.edit', compact('user','roles'));
    }

    public function deactivate(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error','Cannot deactivate your own account.');
        }
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);
        $msg = $newStatus === 'active' ? 'User reactivated.' : 'User deactivated.';
        return redirect()->route('users.index')->with('success', $msg);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'department' => 'nullable|max:100',
            'phone' => 'nullable|max:20',
            'status' => 'required|in:active,inactive,locked',
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('users.index')->with('success','User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role_id' => 'nullable|exists:roles,id',
            'department' => 'nullable|max:100',
            'phone' => 'nullable|max:20',
            'status' => 'required|in:active,inactive,locked',
        ]);
        if ($request->filled('password')) {
            $request->validate(['password'=>'min:8|confirmed']);
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        return redirect()->route('users.index')->with('success','User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error','Cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success','User deleted.');
    }
}
