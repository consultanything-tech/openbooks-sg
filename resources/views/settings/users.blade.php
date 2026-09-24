@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">User Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Manage user accounts, roles, and access permissions</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left" aria-hidden="true"></i>
                Back to Settings
            </a>
            <button onclick="document.getElementById('addUserModal').classList.remove('hidden')" class="btn btn-primary">
                <i data-lucide="plus" aria-hidden="true"></i>
                New User
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">All Users</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $users->count() }} {{ Str::plural('user', $users->count()) }} registered</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 text-[10px] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    {{ $users->where('role', 'ADMIN')->count() }} Admin
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30 text-[10px] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    {{ $users->where('role', 'ACCOUNTANT')->count() }} Accountant
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 text-[10px] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    {{ $users->where('role', 'VIEWER')->count() }} Viewer
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="text-left px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">User</th>
                        <th class="text-left px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Role</th>
                        <th class="text-left px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                        <th class="text-left px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Created</th>
                        <th class="text-right px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-[10px]">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                                    <p class="text-slate-500 dark:text-slate-400 text-[11px]">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3">
                            @if(strtoupper($user->role) === 'ADMIN')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30">Admin</span>
                            @elseif(strtoupper($user->role) === 'ACCOUNTANT')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30">Accountant</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600">Viewer</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            @if($user->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-slate-500 dark:text-slate-400">
                            {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                        </td>
                        <td class="px-6 py-3 text-right">
                            @if((int)$user->id !== (int)Auth::id())
                            <div class="flex items-center justify-end gap-1.5">
                                <button onclick="openEditModal({{ $user->id }}, {{ json_encode($user->name) }}, {{ json_encode($user->email) }}, {{ json_encode($user->role) }}, {{ $user->is_active ? 'true' : 'false' }})"
                                    class="btn-icon" title="Edit user" aria-label="Edit user">
                                    <i data-lucide="pencil" aria-hidden="true"></i>
                                </button>
                                <form action="{{ route('settings.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete user" aria-label="Delete user">
                                        <i data-lucide="trash-2" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                            @else
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 italic">Current user</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl w-full max-w-md mx-4 modal-content">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add New User</h3>
                <button onclick="document.getElementById('addUserModal').classList.add('hidden')" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>
            <form action="{{ route('settings.users.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Full Name *</label>
                    <input type="text" name="name" required placeholder="Enter full name"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Email Address *</label>
                    <input type="email" name="email" required placeholder="user@company.com"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Password *</label>
                    <div class="relative">
                        <input type="password" name="password" id="add-user-password" required minlength="8" placeholder="Minimum 8 characters"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 pr-10 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <button type="button" onclick="var i=document.getElementById('add-user-password'); var s=i.type==='password'; i.type=s?'text':'password'; this.querySelector('.eye-on').classList.toggle('hidden',s); this.querySelector('.eye-off').classList.toggle('hidden',!s); this.setAttribute('aria-pressed',String(s)); this.setAttribute('aria-label', s?'Hide password':'Show password');" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Show password" aria-pressed="false">
                            <i data-lucide="eye" class="eye-on w-4 h-4" aria-hidden="true"></i>
                            <i data-lucide="eye-off" class="eye-off w-4 h-4 hidden" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Role *</label>
                    <select name="role" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="ADMIN">Admin - Full access</option>
                        <option value="ACCOUNTANT" selected>Accountant - Manage finances</option>
                        <option value="VIEWER">Viewer - Read-only access</option>
                    </select>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Admin: full access | Accountant: manage data, no settings | Viewer: read-only</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')"
                        class="btn btn-ghost">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" aria-hidden="true"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal (JS-driven) -->
    <div id="editUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl w-full max-w-md mx-4 modal-content">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Edit User</h3>
                <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="btn-icon" aria-label="Close">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </div>
            <form id="editUserForm" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">User</label>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-[10px]" id="editUserAvatar"></div>
                        <div>
                            <p class="font-semibold text-xs text-slate-900 dark:text-white" id="editUserName"></p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400" id="editUserEmail"></p>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Role *</label>
                    <select name="role" id="editUserRole" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="ADMIN">Admin - Full access</option>
                        <option value="ACCOUNTANT">Accountant - Manage finances</option>
                        <option value="VIEWER">Viewer - Read-only access</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Account Status *</label>
                    <select name="is_active" id="editUserActive" required
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="1">Active</option>
                        <option value="0">Inactive (blocked from login)</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')"
                        class="btn btn-ghost">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" aria-hidden="true"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openEditModal(id, name, email, role, isActive) {
        document.getElementById('editUserForm').action = '/settings/users/' + id;
        document.getElementById('editUserAvatar').textContent = name.substring(0, 2).toUpperCase();
        document.getElementById('editUserName').textContent = name;
        document.getElementById('editUserEmail').textContent = email;
        document.getElementById('editUserRole').value = role.toUpperCase();
        document.getElementById('editUserActive').value = isActive ? '1' : '0';
        document.getElementById('editUserModal').classList.remove('hidden');
    }
</script>
@endsection
