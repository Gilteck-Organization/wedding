import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    /** Wire-style loading: disable submit + spinner on forms using .btn-wired */
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            if (form.dataset.noWiredLoading === 'true') {
                return;
            }

            let button =
                event.submitter instanceof HTMLButtonElement && event.submitter.type === 'submit'
                    ? event.submitter
                    : form.querySelector('button[type="submit"]');

            if (!(button instanceof HTMLButtonElement) || !button.classList.contains('btn-wired')) {
                return;
            }

            if (button.disabled || button.classList.contains('is-loading')) {
                return;
            }

            button.disabled = true;
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');
        },
        true
    );

    const preloader = document.getElementById('wedding-preloader');
    if (preloader) {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.body.classList.add('overflow-hidden');

        const hidePreloader = () => {
            preloader.classList.add('wedding-preloader--done');
            preloader.setAttribute('aria-busy', 'false');
            document.body.classList.remove('overflow-hidden');
            window.setTimeout(() => {
                preloader.remove();
            }, 900);
        };

        if (prefersReducedMotion) {
            hidePreloader();
        } else {
            const minDisplayMs = 1000;
            const startedAt = performance.now();

            const scheduleHide = () => {
                const elapsed = performance.now() - startedAt;
                const wait = Math.max(0, minDisplayMs - elapsed);
                window.setTimeout(hidePreloader, wait);
            };

            if (document.readyState === 'complete') {
                scheduleHide();
            } else {
                window.addEventListener('load', scheduleHide, { once: true });
            }
        }
    }

    const revealElements = document.querySelectorAll('[data-reveal]');

    if (revealElements.length === 0) {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        revealElements.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            }
        },
        { threshold: 0.15 }
    );

    revealElements.forEach((el) => {
        el.classList.add('reveal');
        observer.observe(el);
    });
});
