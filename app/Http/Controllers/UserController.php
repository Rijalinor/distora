<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Closure;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, Closure $next) {
                if (auth()->user()->role !== 'admin') {
                    abort(403, 'Hanya Admin yang dapat mengelola user.');
                }
                return $next($request);
            }),
        ];
    }
    public function index()
    {
        $users = User::orderByRaw("role = 'admin' DESC")->orderBy('name')->get();

        // Get list of salesman names from transactions for the dropdown
        $period = \App\Models\Period::getActive();
        $uploadIds = $period->uploadHistories()->pluck('id');

        $salesmanNames = Transaction::query()
            ->whereIn('upload_history_id', $uploadIds)
            ->whereNotNull('meta')
            ->select(DB::raw("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(meta, '$.sales_name')) as name"))
            ->pluck('name')
            ->filter()
            ->sort()
            ->values();

        return view('users.index', compact('users', 'salesmanNames'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,salesman',
            'linked_salesman_name' => 'nullable|string|max:255',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'linked_salesman_name' => $request->role === 'salesman' ? $request->linked_salesman_name : null,
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,salesman',
            'linked_salesman_name' => 'nullable|string|max:255',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'linked_salesman_name' => $request->role === 'salesman' ? $request->linked_salesman_name : null,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}
