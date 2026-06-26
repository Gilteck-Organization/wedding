@extends('layouts.wedding')

@section('hideFooter', '1')

@section('content')
    <div class="flex min-h-svh items-start justify-center px-4 py-10 sm:px-6 sm:py-14">
        <div class="w-full max-w-lg">
            <div class="border border-[#803b48]/35 bg-[#fffdf8] p-6 shadow-md sm:p-8">
                <p class="text-center text-xs font-semibold uppercase tracking-widest text-[#803b48]">Staff verification
                </p>
                <h1 class="mt-3 text-center font-serif text-2xl text-[#2c2418]">Already scanned</h1>
                <p class="mt-2 text-center text-sm text-[#2c2418]/65">
                    This access card was already used for check-in. It cannot be scanned again.
                </p>

                <div class="mt-8 space-y-4 border-t border-[#946112]/15 pt-6 text-[#2c2418]">
                    <div class="flex justify-between gap-4 text-sm">
                        <span class="text-[#2c2418]/60">Guest</span>
                        <span class="font-semibold text-right">{{ $guest->name }}</span>
                    </div>
                    <div class="flex justify-between gap-4 text-sm">
                        <span class="text-[#2c2418]/60">Checked in at</span>
                        <span class="font-semibold text-right">
                            {{ optional($guest->qr_verified_at)->format('M d, Y g:i A') }}
                        </span>
                    </div>
                </div>

                <div
                    class="mt-8 flex items-center justify-center gap-2 rounded-sm border border-[#803b48]/40 bg-[#803b48]/10 px-4 py-3 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 shrink-0 text-[#803b48]" viewBox="0 0 24 24"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-semibold text-[#803b48]">Do not admit — card already used</span>
                </div>
            </div>
        </div>
    </div>
@endsection
