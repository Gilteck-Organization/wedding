@extends('layouts.wedding')

@section('hideFooter', '1')

@section('content')
    <div class="mx-auto w-full max-w-3xl px-6 py-12 sm:px-10 sm:py-16">
        <div class="text-center">
            <div class="gold-divider mx-auto"></div>
            <h1 class="mt-6 font-script text-4xl text-wedding-champagne sm:text-5xl">
                Fifi &amp; Kiki
            </h1>
            <p class="mt-3 font-serif text-base font-semibold tracking-wide text-wedding-primary">
                Terms &amp; Conditions
            </p>
            <p class="mt-2 text-xs uppercase tracking-[0.2em] text-wedding-muted">
                Last updated: {{ \Illuminate\Support\Carbon::parse('2026-05-25')->format('F j, Y') }}
            </p>
        </div>

        <div class="mt-10 space-y-8 border border-wedding-primary/25 bg-wedding-ivory/90 p-6 shadow-sm sm:p-10">
            <section>
                <p class="font-serif text-sm leading-relaxed text-wedding-ink sm:text-base">
                    These terms apply to your use of this website (the &ldquo;Site&rdquo;), which exists solely to manage
                    invitations and RSVPs for the wedding of Fifi &amp; Kiki (the &ldquo;Event&rdquo;). By using the Site
                    or submitting an RSVP, you agree to these terms.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">1. Invitation only</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    The Site and the Event are strictly by invitation. You may only submit an RSVP if you have been
                    personally invited by the hosts. The hosts reserve the right, at their sole discretion, to accept,
                    decline, limit or revoke any invitation or RSVP, with or without notice.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">2. Accurate information</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    You agree to provide accurate, current and complete information when submitting your RSVP,
                    including your full name and a working WhatsApp number on which you can receive your access card.
                    Information that is incorrect, impersonating another person, or submitted without authorisation may
                    result in your RSVP being declined or your access card being revoked.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">3. Access cards</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    Once your RSVP is approved, you will receive a personal digital access card. The card:
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    <li>Is issued to a specific named guest and is <strong>not transferable</strong>.</li>
                    <li>Admits <strong>only one person</strong> unless explicitly stated otherwise on the card.</li>
                    <li>Must be presented at the entrance of the Event venue for verification.</li>
                    <li>May be revoked or invalidated by the hosts at any time before or during the Event.</li>
                </ul>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    Sharing, copying, forwarding, or attempting to forge an access card is not permitted and will
                    invalidate the card without refund or replacement.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">4. Communications</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    By submitting the RSVP form, you consent to receiving transactional messages from us via WhatsApp at
                    the number you provided. These messages relate to your invitation, access card and Event details.
                    You can stop receiving messages at any time by replying &ldquo;STOP&rdquo; on WhatsApp.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">5. Conduct at the Event</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    Guests are expected to behave respectfully towards the hosts, other guests, venue staff and
                    suppliers. The hosts and the venue reserve the right to refuse entry to, or remove, any guest who
                    behaves in a manner that is disruptive, unsafe, unlawful or otherwise inappropriate.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">6. Photography and media</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    Photographs and video may be taken at the Event by the hosts, their official photographers and other
                    guests. By attending, you acknowledge that your image may appear in photographs or video taken
                    during the celebration. All content published on the Site (including names, logo, photography and
                    design) belongs to the hosts and may not be reused for commercial purposes without permission.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">7. Site availability</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    The Site is provided on an &ldquo;as is&rdquo; basis. We do not guarantee that the Site will always
                    be available, error-free, or free from interruption. We may update, suspend or withdraw the Site at
                    any time without notice.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">8. Limitation of liability</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    To the fullest extent permitted by law, the hosts will not be liable for any indirect or
                    consequential loss arising from your use of the Site or attendance at the Event, including but not
                    limited to travel costs, accommodation, lost time, or messages that are delayed or fail to deliver
                    due to third-party services such as WhatsApp.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">9. Privacy</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    Your information is handled in accordance with our
                    <a href="{{ route('wedding.privacy') }}"
                        class="underline decoration-wedding-primary/40 underline-offset-4 hover:text-wedding-primary">
                        Privacy Policy</a>, which forms part of these terms.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">10. Changes</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    We may update these terms from time to time. The latest version will always be available on this
                    page with a revised &ldquo;last updated&rdquo; date. Continued use of the Site after a change means
                    you accept the updated terms.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">11. Contact</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    If you have any questions about these terms, please contact us via WhatsApp on the number used to
                    send your access card, or reply to any message from us.
                </p>
            </section>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('wedding.home') }}"
                class="text-sm uppercase tracking-[0.2em] text-wedding-muted underline-offset-4 hover:text-wedding-primary hover:underline">
                Back to invitation
            </a>
        </div>
    </div>
@endsection
