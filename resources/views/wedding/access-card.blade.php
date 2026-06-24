@extends('layouts.wedding')

@section('hideFooter', '1')

@section('content')
    {{-- Wedding monogram preloader (F & K) --}}
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

    @php
        $pdfBaseName = trim(preg_replace('/[^A-Za-z0-9 _-]/', '', $guest->name) ?? '');
        $pdfFileName = ($pdfBaseName !== '' ? $pdfBaseName : 'guest').' invitation.pdf';
    @endphp

    {{-- items-start + modest top padding: less empty space above the card on tall screens --}}
    <div class="access-card-page flex flex-col min-h-svh items-start justify-center px-3 pt-6 pb-12 sm:px-6 sm:pt-8 sm:pb-16">
        @include('wedding.partials.access-card-stage', ['guest' => $guest])

        {{-- @if ($guest->is_approved && $guest->qr_code)
            <div class="mt-5 flex flex-col items-center gap-2">
                <button type="button"
                    class="btn-wired px-6 py-2.5 text-xs sm:text-sm"
                    data-share-access-card
                    data-share-title="Fifi &amp; Kiki Invitation"
                    data-share-filename="{{ $pdfFileName }}">
                    <span class="btn-wired__text">Share access card</span>
                </button>
                <p class="text-xs text-wedding-muted" data-share-access-card-feedback aria-live="polite"></p>
            </div>
        @endif --}}
    </div>
@endsection
