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

    <div class="w-full min-h-svh">
        <div class="grid grid-cols-1 lg:grid-cols-2 min-h-svh lg:h-screen">
            {{-- Flyer: mobile uses most of the viewport height; desktop stays split --}}
            <div
                class="premium-stage--fade-bottom relative flex min-h-[min(78svh,720px)] items-center justify-center premium-stage px-3 pt-6 pb-4 sm:px-6 sm:py-10 lg:min-h-svh lg:p-10 xl:p-12">
                {{-- Same max-height as before; w-auto + max-h scales the whole image (no cover, no empty mat) --}}
                <div class="flex w-full max-w-[720px] justify-center">
                    <div class="flyer-frame inline-block max-w-full">
                        <img src="{{ asset('images/e-invite.jpg') }}" alt="E-invite"
                            class="mx-auto block h-auto w-auto max-w-full max-h-[min(72svh,620px)] lg:max-h-[min(88vh,720px)]">
                    </div>
                </div>
            </div>

            <div
                class="wedding-content-panel relative flex flex-col px-6 pb-10 pt-0 sm:px-10 sm:pb-12 lg:flex lg:h-screen lg:items-center lg:justify-center lg:overflow-y-auto lg:px-14 lg:py-16 text-wedding-ink">
                {{-- Mobile: gradient bridge from flyer into cream panel (transparent → cream) --}}
                <div class="mobile-flyer-content-bridge pointer-events-none -mt-[4.5rem] mb-0 min-h-[7rem] w-full shrink-0 lg:hidden"
                    aria-hidden="true">
                </div>

                <div class="w-full max-w-2xl mx-auto reveal" data-reveal>
                    <div class="text-center">
                        <div class="gold-divider mt-2"></div>

                        <p class="mt-7 font-serif text-base sm:text-base font-semibold text-wedding-onion">
                            Wedding Ceremony
                        </p>

                        <h1 class="mt-4 font-script text-4xl sm:text-5xl text-wedding-champagne leading-none">
                            Fifi &amp; Kiki
                        </h1>
                    </div>

                    <section id="rsvp" class="mt-10 scroll-mt-8">
                        <h2 class="text-center font-serif text-xl text-wedding-primary tracking-wide">
                            RSVP
                        </h2>
                        <p class="mt-2 text-center text-sm text-wedding-muted">
                            Kindly respond below. Strictly by invitation.
                        </p>

                        @if (session('rsvp_success'))
                            <div
                                class="mt-6 border border-wedding-primary/35 bg-wedding-ivory px-4 py-4 text-center text-wedding-ink shadow-sm">
                                <p class="font-semibold text-wedding-primary">Thank you for your response</p>
                                <p class="mt-1 text-sm text-wedding-muted">We have received your RSVP.</p>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mt-6 border border-red-400/50 bg-red-50/80 px-4 py-4">
                                <p class="font-semibold text-red-800">Please fix the following:</p>
                                <ul class="mt-2 list-disc list-inside text-sm text-red-800">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($rsvpCapacityReached)
                            <div
                                class="mt-6 border border-wedding-primary/30 bg-wedding-ivory/95 px-6 py-8 text-center shadow-sm sm:px-10">
                                <p class="font-serif text-lg font-semibold text-wedding-primary">
                                    You’re so welcome here.
                                </p>
                                <p class="mt-4 text-sm leading-relaxed text-wedding-muted">
                                    Every seat for our celebration has been lovingly spoken for. We’re grateful you
                                    wanted to share the day with us — thank you for your kindness and understanding.
                                </p>
                                <p class="mt-5 font-serif text-sm font-medium text-wedding-onion">
                                    With love, Fifi &amp; Kiki
                                </p>
                            </div>
                        @else
                            <form action="{{ route('rsvp.submit') }}" method="POST"
                                class="font-serif mt-6 border border-wedding-primary/25 bg-wedding-ivory/90 p-6 sm:p-8 shadow-sm">
                                @csrf

                                <div class="space-y-5">
                                    <div>
                                        <label class="block text-sm font-medium normal-case text-wedding-muted"
                                            for="name">Name</label>
                                        <input id="name" name="name" type="text" value="{{ old('name') }}"
                                            class="mt-2 w-full border border-wedding-primary/30 bg-white px-4 py-3 text-sm text-wedding-ink outline-none focus:border-wedding-onion focus:ring-1 focus:ring-wedding-onion/40"
                                            required>
                                        @error('name')
                                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium normal-case text-wedding-muted"
                                            for="phone">Whatsapp Number</label>
                                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}"
                                            class="mt-2 w-full border border-wedding-primary/30 bg-white px-4 py-3 text-sm text-wedding-ink outline-none focus:border-wedding-onion focus:ring-1 focus:ring-wedding-onion/40"
                                            required>
                                        @error('phone')
                                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium normal-case text-wedding-muted"
                                            for="attendance">Attendance</label>
                                        <select id="attendance" name="attendance"
                                            class="mt-2 w-full border border-wedding-primary/30 bg-white px-4 py-3 text-sm text-wedding-ink outline-none focus:border-wedding-onion focus:ring-1 focus:ring-wedding-onion/40"
                                            required>
                                            <option value="" disabled @selected(old('attendance') === null)>Select</option>
                                            <option value="yes" @selected(old('attendance') === 'yes')>Yes</option>
                                            <option value="no" @selected(old('attendance') === 'no')>No</option>
                                        </select>
                                        @error('attendance')
                                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    @php
                                        $partySize = (string) (old('guest_count') ?? '1');
                                    @endphp
                                    <fieldset>
                                        <legend class="block text-sm font-medium normal-case text-wedding-muted">
                                            Guest
                                        </legend>
                                        <div class="mt-3 space-y-2 flex md:flex-row flex-col gap-2 w-full">
                                            <label
                                                class="flex cursor-pointer w-full items-start gap-3 border border-wedding-primary/25 bg-white px-4 py-3 text-sm text-wedding-ink transition has-[:checked]:border-wedding-onion has-[:checked]:ring-1 has-[:checked]:ring-wedding-onion/35">
                                                <input type="radio" name="guest_count" value="1" required
                                                    @checked($partySize === '1')
                                                    class="mt-1 size-4 shrink-0 border-wedding-primary/40 accent-wedding-onion focus:outline-none focus:ring-2 focus:ring-wedding-onion/35 focus:ring-offset-0">
                                                <span class="normal-case leading-snug">Just me</span>
                                            </label>
                                            <label
                                                class="flex cursor-pointer w-full items-start gap-3 border border-wedding-primary/25 bg-white px-4 py-3 text-sm text-wedding-ink transition has-[:checked]:border-wedding-onion has-[:checked]:ring-1 has-[:checked]:ring-wedding-onion/35">
                                                <input type="radio" name="guest_count" value="2"
                                                    @checked($partySize === '2')
                                                    class="mt-1 size-4 shrink-0 border-wedding-primary/40 accent-wedding-onion focus:outline-none focus:ring-2 focus:ring-wedding-onion/35 focus:ring-offset-0">
                                                <span class="normal-case leading-snug">Me and one guest
                                                </span>
                                            </label>
                                        </div>
                                        @error('guest_count')
                                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                                        @enderror
                                    </fieldset>


                                    <button type="submit" class="btn-wired w-full px-7 py-3.5 text-sm">
                                        <span class="btn-wired__text">Submit RSVP</span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    </section>

                    <div class="mt-10 text-center">
                        <p class="text-sm text-wedding-muted">
                            We look forward to celebrating with you.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
