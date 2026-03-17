@php
    $buttonLabel = $buttonLabel ?? 'Save';
@endphp

<div class="space-y-5">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $user->name ?? '') }}"
            class="mt-1 dark:border-gray-600 app-input"
            required
        >
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            value="{{ old('email', $user->email ?? '') }}"
            class="mt-1 dark:border-gray-600 app-input"
            required
        >
        @error('email')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Password</label>
        <input
            id="password"
            name="password"
            type="password"
            class="mt-1 dark:border-gray-600 app-input"
            {{ isset($user) ? '' : 'required' }}
        >
        @if (isset($user))
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank if you do not want to change the password.</p>
        @endif
        @error('password')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Confirm Password</label>
        <input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            class="mt-1 dark:border-gray-600 app-input"
            {{ isset($user) ? '' : 'required' }}
        >
    </div>

    <div>
        <p class="block text-sm font-medium text-gray-700 dark:text-gray-200">Role</p>
        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
            @foreach ($roles as $role)
                <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                    <input
                        type="checkbox"
                        name="roles[]"
                        value="{{ $role }}"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        @checked(in_array($role, old('roles', $selectedRoles ?? []), true))
                    >
                    <span class="text-gray-700 dark:text-gray-200">{{ $role }}</span>
                </label>
            @endforeach
        </div>
        @error('roles')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('roles.*')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-2">
        <button
            type="submit"
             class="btn-primary"
        >
            {{ $buttonLabel }}
        </button>

        <a
            href="{{ route('users.index') }}"
             class="btn-secondary"
        >
            Cancel
        </a>
    </div>
</div>





