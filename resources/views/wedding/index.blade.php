@extends('layouts.wedding')

@section('hideFooter', '1')

@section('content')
    {{-- Wedding preloader (logo) --}}
    <div id="wedding-preloader" class="wedding-preloader" aria-busy="true" aria-live="polite" role="status">
        <div class="wedding-preloader__frame">
            <img src="{{ asset('images/fifikiki-logo.png') }}" alt="Fifi &amp; Kiki" class="wedding-preloader__logo"
                loading="eager" decoding="async" fetchpriority="high">
        </div>
    </div>

    <div class="w-full min-h-svh">
        <div class="grid grid-cols-1 lg:grid-cols-2 min-h-svh lg:h-screen">
            {{-- Flyer: mobile uses most of the viewport height; desktop stays split --}}
            @php
                $heroSlides = [
                    ['file' => 'slide-1.png', 'alt' => 'Wedding invitation'],
                    ['file' => 'slide-2.png', 'alt' => 'Wedding invitation'],
                    ['file' => 'slide-3.png', 'alt' => 'Wedding invitation'],
                ];
                $heroSlideCount = count($heroSlides);
            @endphp
            <div
                class="premium-stage--fade-bottom relative flex min-h-[min(78svh,720px)] flex-col premium-stage px-0 pb-4 sm:pb-6 lg:min-h-svh lg:pb-8 xl:pb-10">
                {{-- Logo: absolutely positioned over slideshow (does not reduce image area) --}}
                <header class="wedding-hero-header pointer-events-none absolute left-0 right-0 top-0 z-[4]" role="banner">
                    <div class="wedding-hero-header__frame pointer-events-auto">
                        <img src="{{ asset('images/fifikiki-logo.png') }}" alt="Fifi &amp; Kiki"
                            class="wedding-hero-header__logo" loading="eager" decoding="async" fetchpriority="high">
                    </div>
                </header>

                {{-- Hero slideshow: soft frame on small screens only; lg+ = image + premium-stage bg only --}}
                <div class="relative z-[1] flex min-h-0 w-full flex-1 flex-col items-center justify-center px-0">
                    <div id="wedding-hero-slideshow" class="flyer-frame relative w-full rounded-[2px] lg:rounded-none"
                        data-wedding-slideshow data-slideshow-total="{{ $heroSlideCount }}"
                        aria-label="Wedding invitation images" aria-live="polite">
                        <div class="relative w-full overflow-hidden rounded-[2px] lg:rounded-none">
                            <div class="flex transition-transform duration-700 ease-out motion-reduce:transition-none"
                                style="width: {{ $heroSlideCount * 100 }}%; transform: translateX(0);" data-slideshow-track>
                                @foreach ($heroSlides as $i => $slide)
                                    <div class="flex shrink-0 items-center justify-center"
                                        style="flex: 0 0 calc(100% / {{ $heroSlideCount }});">
                                        <img src="{{ asset('images/' . $slide['file']) }}"
                                            alt="{{ $slide['alt'] }} — {{ $i + 1 }} of {{ $heroSlideCount }}"
                                            class="block h-auto w-full max-h-[min(72svh,620px)] object-contain lg:max-h-[min(88vh,720px)]"
                                            draggable="false"
                                            @if ($i === 0) fetchpriority="high" decoding="async" @else loading="lazy" decoding="async" @endif>
                                    </div>
                                @endforeach
                            </div>
                        </div>
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
                        <h1 class="mt-4 font-script text-4xl sm:text-5xl text-wedding-champagne leading-none">
                            Fifi &amp; Kiki
                        </h1>
                        <p class="mt-7 font-serif text-base sm:text-base font-semibold text-wedding-onion">
                            Wedding
                        </p>
                    </div>

                    <section id="rsvp" class="mt-10 scroll-mt-8">
                        <h2 class="text-center font-serif text-xl text-wedding-primary tracking-wide">
                            RSVP
                        </h2>


                        @if (session('rsvp_success'))
                            <div
                                class="mt-6 border border-wedding-primary/30 bg-wedding-ivory/95 px-6 py-10 text-center shadow-sm sm:px-10">
                                <p class="text-sm leading-relaxed text-wedding-muted sm:text-base">
                                    Thank you for your response. We have received your RSVP. You will receive your
                                    access card shortly before the event. Looking forward to celebrating with you.
                                </p>
                                <a href="{{ route('wedding.home') }}?rsvp=form#rsvp"
                                    class="btn-wired mt-7 inline-flex px-8 py-3 text-sm">
                                    <span class="btn-wired__text">RSVP</span>
                                </a>
                            </div>
                        @else
                            <p class="mt-2 text-center text-sm text-wedding-muted">
                                Kindly respond below. Strictly by invitation. This card admits only one person
                            </p>
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
                                                data-intl-phone autocomplete="tel"
                                                class="mt-2 w-full border border-wedding-primary/30 bg-white px-4 py-3 text-sm text-wedding-ink outline-none focus:border-wedding-onion focus:ring-1 focus:ring-wedding-onion/40"
                                                required>
                                            <p class="mt-1 hidden text-sm text-red-700" data-phone-live-error></p>
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

                                        <button type="submit" class="btn-wired w-full px-7 py-3.5 text-sm">
                                            <span class="btn-wired__text">Submit RSVP</span>
                                        </button>
                                    </div>
                                </form>
                            @endif
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
