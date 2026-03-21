@extends('layouts.wedding')

@section('hideFooter', '1')

@section('content')
    <div id="wedding-preloader" class="wedding-preloader" aria-busy="true" aria-live="polite" role="status">
        <div class="wedding-preloader__frame">
            <p class="wedding-preloader__eyebrow">Together with their families</p>
            <div class="wedding-preloader__monogram" aria-hidden="true">
                <span class="wedding-preloader__letter">F</span>
                <span class="wedding-preloader__divider"></span>
                <span class="wedding-preloader__letter">K</span>
            </div>
            <p class="wedding-preloader__names">Fifi &amp; Kiki</p>
        </div>
    </div>

    <div class="flex min-h-svh items-center justify-center px-4 py-12 sm:px-6">
        <div class="w-full max-w-md">
            <div class="text-center">
                <div class="gold-divider mx-auto"></div>
                <h1 class="mt-6 font-serif text-xl text-wedding-primary tracking-wide">Welcome</h1>
                <p class="mt-2 text-sm text-wedding-muted">
                    Enter the access name you were given to confirm this invitation.
                </p>
            </div>

            <form action="{{ route('access-card.verify.submit', $guest) }}" method="POST"
                class="mt-8 border border-wedding-primary/25 bg-wedding-ivory/90 p-6 shadow-sm sm:p-8">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-wedding-muted" for="access-name">Access name</label>
                    <input id="access-name" name="name" type="text" value="{{ old('name') }}" autocomplete="off"
                        required
                        class="mt-2 w-full border border-wedding-primary/30 bg-white px-4 py-3 text-sm text-wedding-ink outline-none focus:border-wedding-onion focus:ring-1 focus:ring-wedding-onion/40"
                        placeholder="Shared code or phrase">
                    @error('name')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-wired mt-6 w-full px-6 py-3 text-sm">
                    <span class="btn-wired__text">Continue</span>
                </button>
            </form>
        </div>
    </div>
@endsection
