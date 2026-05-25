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
                Privacy Policy
            </p>
            <p class="mt-2 text-xs uppercase tracking-[0.2em] text-wedding-muted">
                Last updated: {{ \Illuminate\Support\Carbon::parse('2026-05-25')->format('F j, Y') }}
            </p>
        </div>

        <div class="mt-10 space-y-8 border border-wedding-primary/25 bg-wedding-ivory/90 p-6 shadow-sm sm:p-10">
            <section>
                <p class="font-serif text-sm leading-relaxed text-wedding-ink sm:text-base">
                    This website is a private invitation page for the wedding of Fifi &amp; Kiki. It exists solely to
                    collect and manage RSVPs from invited guests and to deliver each approved guest a personal access
                    card. We respect your privacy and only use the limited information you share with us for the
                    purposes described below.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">Information we collect</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    <li>Your <strong>full name</strong>, as submitted on the RSVP form.</li>
                    <li>Your <strong>WhatsApp phone number</strong>, including country code.</li>
                    <li>Your <strong>attendance response</strong> (yes or no).</li>
                    <li>Basic technical information that your browser sends automatically when you visit
                        (such as IP address and user agent), used only to operate the site securely.</li>
                </ul>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    We do not collect payment information, government identifiers, location data, or any sensitive
                    personal data.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">How we use your information</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    <li>To record your RSVP and manage the guest list for the event.</li>
                    <li>To send you your personal access card and event details via WhatsApp once your RSVP has been
                        approved by the hosts.</li>
                    <li>To contact you about your invitation if needed (for example, to confirm details or follow up on
                        attendance).</li>
                    <li>To verify entry on the day of the event using the unique access card linked to your name.</li>
                </ul>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    We do not use your information for marketing, advertising, profiling, or any purpose unrelated to
                    this wedding.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">WhatsApp messaging</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    To deliver your access card and event-related updates, we use the WhatsApp Business Platform,
                    operated by Meta Platforms, Inc. When you submit the RSVP form, you agree that your WhatsApp number
                    may be used to send you transactional messages about your invitation. Sending these messages
                    requires sharing your number with WhatsApp/Meta strictly for delivery purposes.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    Meta&rsquo;s handling of your number is governed by the
                    <a href="https://www.whatsapp.com/legal/privacy-policy" rel="noopener" target="_blank"
                        class="underline decoration-wedding-primary/40 underline-offset-4 hover:text-wedding-primary">
                        WhatsApp Privacy Policy
                    </a>.
                    You can stop receiving messages from us at any time by replying &ldquo;STOP&rdquo; on WhatsApp or by
                    contacting us using the details below.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">Sharing your information</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    We do not sell, rent, or trade your information. We share it only with:
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    <li><strong>Meta / WhatsApp</strong>, as our messaging provider, solely to deliver messages to you.</li>
                    <li><strong>Our hosting and infrastructure providers</strong>, who process data on our behalf in
                        order to run the website and database.</li>
                    <li><strong>Trusted family members or staff</strong> assisting with event logistics on the day, who
                        may see the guest list in order to verify access at the venue.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">How long we keep your information</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    We keep your RSVP information only as long as it is needed for the wedding. After the event has
                    concluded, your information will be permanently deleted from our systems within a reasonable period,
                    or earlier on your request.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">Your rights</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    You may, at any time, ask us to:
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    <li>Tell you what information we hold about you.</li>
                    <li>Correct any information that is wrong.</li>
                    <li>Delete your information from our records.</li>
                    <li>Stop sending you WhatsApp messages.</li>
                </ul>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    Please send any request to the contact below and we will respond as soon as we reasonably can.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">Security</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    We use industry-standard measures to protect the information you share with us, including encrypted
                    connections (HTTPS), restricted access to the guest list, and unique access cards tied to each
                    approved guest. No method of transmission over the internet is fully secure, but we take reasonable
                    steps to keep your information safe.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">Children</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    This website is intended for adults responding to a personal invitation. We do not knowingly collect
                    information from children.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">Changes to this policy</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    We may update this privacy policy from time to time. Any changes will be published on this page with
                    a revised &ldquo;last updated&rdquo; date.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-lg text-wedding-primary">Contact</h2>
                <p class="mt-3 text-sm leading-relaxed text-wedding-muted sm:text-base">
                    If you have any questions about this privacy policy or how your information is handled, please
                    contact us via WhatsApp on the number used to send your access card, or reply to any message from
                    us.
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
