@extends('layouts.admin')

@section('admin_content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <div class="reveal" data-reveal>
            <h1 class="font-serif text-3xl text-[#2c2418]">Access names</h1>
            <p class="mt-2 text-sm text-[#2c2418]/70">
                These are <strong>shared codes or phrases</strong> you create — not invitation names. Anyone who
                enters a matching access name after scanning <strong>any</strong> guest’s QR code will see the verified
                screen for <strong>that</strong> card. Matching ignores extra spaces and letter case.
            </p>
            @if ($accessNames->isEmpty())
                <p class="mt-3 rounded-sm border border-amber-200/80 bg-amber-50/90 px-3 py-2 text-sm text-amber-950">
                    Add at least one access name or guests will not be able to verify via QR (admins can still verify
                    while logged in).
                </p>
            @endif
        </div>

        @if (session('success'))
            <div class="mt-6 border border-[#946112]/30 bg-[#fffdf8] px-4 py-3 text-sm text-[#2c2418] reveal"
                data-reveal>
                <p class="font-semibold text-[#946112]">{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 border border-red-300 bg-red-50/90 px-4 py-3 text-sm text-red-900 reveal" data-reveal>
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.access-names.store') }}" method="POST"
            class="mt-8 border border-[#946112]/25 bg-white/80 p-6 shadow-sm reveal" data-reveal>
            @csrf
            <label class="block text-xs font-semibold uppercase tracking-wide text-[#2c2418]/70" for="access-name-input">New
                access name</label>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end">
                <input id="access-name-input" name="name" type="text" value="{{ old('name') }}"
                    class="min-w-0 flex-1 border border-[#946112]/30 bg-white px-4 py-3 text-sm text-[#2c2418] outline-none focus:border-[#946112] focus:ring-1 focus:ring-[#946112]/35"
                    placeholder="e.g. family passphrase or event code" required maxlength="255">
                <button type="submit" class="btn-wired shrink-0 px-6 py-3 text-xs">
                    <span class="btn-wired__text">Add</span>
                </button>
            </div>
        </form>

        <div class="mt-8 border border-[#946112]/20 bg-white/70 reveal" data-reveal>
            <div class="border-b border-[#946112]/15 px-4 py-3">
                <h2 class="text-sm font-semibold text-[#2c2418]">Active access names ({{ $accessNames->count() }})</h2>
            </div>
            <ul class="divide-y divide-[#946112]/10">
                @forelse ($accessNames as $accessName)
                    <li class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                        <span class="font-medium text-[#2c2418]">{{ $accessName->name }}</span>
                        <form action="{{ route('admin.access-names.destroy', $accessName) }}" method="POST"
                            onsubmit="return confirm({{ \Illuminate\Support\Js::from('Remove this access name?') }})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-[#803b48] hover:underline">Remove</button>
                        </form>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-[#2c2418]/60">No access names yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
