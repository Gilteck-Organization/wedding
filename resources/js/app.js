import './bootstrap';
import { toPng } from 'html-to-image';
import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/build/css/intlTelInput.css';

document.addEventListener('DOMContentLoaded', () => {
    const shareButton = document.querySelector('[data-share-access-card]');
    if (shareButton instanceof HTMLButtonElement) {
        const feedback = document.querySelector('[data-share-access-card-feedback]');
        const shareTarget = document.querySelector('[data-share-access-card-target]');
        const shareTitle = shareButton.dataset.shareTitle || 'Access Card';
        const shareFilename = shareButton.dataset.shareFilename || 'access-card.png';

        const setFeedback = (message) => {
            if (feedback instanceof HTMLElement) {
                feedback.textContent = message;
            }
        };

        const dataUrlToFile = async (dataUrl, filename) => {
            const response = await fetch(dataUrl);
            const blob = await response.blob();
            return new File([blob], filename, { type: 'image/png' });
        };

        const waitForShareAssets = async (target) => {
            const images = Array.from(target.querySelectorAll('img'));
            await Promise.all(
                images.map(async (image) => {
                    if (!(image instanceof HTMLImageElement)) {
                        return;
                    }

                    if (!image.complete || image.naturalWidth === 0) {
                        await new Promise((resolve) => {
                            const done = () => resolve();
                            image.addEventListener('load', done, { once: true });
                            image.addEventListener('error', done, { once: true });
                        });
                    }

                    if (typeof image.decode === 'function') {
                        try {
                            await image.decode();
                        } catch {
                            // no-op
                        }
                    }
                })
            );

            if (document.fonts?.ready) {
                try {
                    await document.fonts.ready;
                } catch {
                    // no-op
                }
            }
        };

        const captureCardPng = async (target) => {
            const rect = target.getBoundingClientRect();
            const width = Math.max(1, Math.round(rect.width));
            const height = Math.max(1, Math.round(rect.height));

            let attempt = 0;
            let lastError = null;
            while (attempt < 3) {
                try {
                    return await toPng(target, {
                        cacheBust: true,
                        backgroundColor: '#fffdf8',
                        pixelRatio: 2,
                        canvasWidth: width * 2,
                        canvasHeight: height * 2,
                        fontEmbedCSS: '',
                        skipFonts: true,
                    });
                } catch (error) {
                    lastError = error;
                    attempt += 1;
                    await new Promise((resolve) => window.setTimeout(resolve, 180));
                }
            }

            throw lastError ?? new Error('Failed to capture access card image.');
        };

        shareButton.addEventListener('click', async () => {
            if (!(shareTarget instanceof HTMLElement)) {
                setFeedback('Access card image not found.');
                return;
            }

            try {
                shareButton.disabled = true;
                setFeedback('Preparing card image...');

                await waitForShareAssets(shareTarget);
                const dataUrl = await captureCardPng(shareTarget);

                const file = await dataUrlToFile(dataUrl, shareFilename);
                if (
                    navigator.share &&
                    navigator.canShare &&
                    navigator.canShare({ files: [file] })
                ) {
                    await navigator.share({
                        title: shareTitle,
                        files: [file],
                    });
                    setFeedback('Access card shared.');
                    return;
                }

                const downloadLink = document.createElement('a');
                downloadLink.href = dataUrl;
                downloadLink.download = shareFilename;
                document.body.append(downloadLink);
                downloadLink.click();
                downloadLink.remove();
                setFeedback('Sharing is not available here. Downloaded image instead.');
            } catch (error) {
                if (error instanceof Error && error.name === 'AbortError') {
                    setFeedback('');
                } else {
                    setFeedback('Could not generate image. Please try again.');
                }
            } finally {
                shareButton.disabled = false;
            }
        });
    }

    const phoneInputs = document.querySelectorAll('[data-intl-phone]');
    phoneInputs.forEach((input) => {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const iti = intlTelInput(input, {
            initialCountry: 'ng',
            countrySearch: true,
            strictMode: true,
            loadUtils: () => import('intl-tel-input/utils'),
        });

        const form = input.closest('form');
        const phoneError = form?.querySelector('[data-phone-live-error]');
        const submitButton = form?.querySelector('button[type="submit"]');
        let debounceTimer = 0;
        let activeController = null;

        const setLiveError = (message) => {
            if (!(phoneError instanceof HTMLElement)) {
                return;
            }
            if (message) {
                phoneError.textContent = message;
                phoneError.classList.remove('hidden');
                input.setCustomValidity(message);
                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.disabled = true;
                }
                return;
            }

            phoneError.textContent = '';
            phoneError.classList.add('hidden');
            input.setCustomValidity('');
            if (submitButton instanceof HTMLButtonElement) {
                submitButton.disabled = false;
            }
        };

        const checkPhoneAvailability = async () => {
            if (!iti.isValidNumber()) {
                setLiveError('');
                return;
            }

            const e164Phone = iti.getNumber();
            if (!e164Phone) {
                setLiveError('');
                return;
            }

            if (activeController instanceof AbortController) {
                activeController.abort();
            }

            const controller = new AbortController();
            activeController = controller;

            try {
                const response = await fetch(
                    `/rsvp/phone-availability?phone=${encodeURIComponent(e164Phone)}`,
                    {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                        },
                        signal: controller.signal,
                    }
                );

                if (!response.ok) {
                    setLiveError('');
                    return;
                }

                const payload = await response.json();
                setLiveError(payload.available ? '' : 'This phone number has already RSVP’d.');
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }
                setLiveError('');
            }
        };

        const scheduleAvailabilityCheck = () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(checkPhoneAvailability, 300);
        };

        input.addEventListener('input', scheduleAvailabilityCheck);
        input.addEventListener('countrychange', scheduleAvailabilityCheck);
        input.addEventListener('blur', () => {
            window.clearTimeout(debounceTimer);
            void checkPhoneAvailability();
        });

        if (form instanceof HTMLFormElement) {
            form.addEventListener('submit', (event) => {
                if (iti.isValidNumber()) {
                    input.value = iti.getNumber();
                }

                if (!input.checkValidity()) {
                    event.preventDefault();
                    input.reportValidity();
                }
            });
        }
    });

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

    const slideshowRoot = document.querySelector('[data-wedding-slideshow]');
    if (slideshowRoot instanceof HTMLElement) {
        const track = slideshowRoot.querySelector('[data-slideshow-track]');
        const total = Number.parseInt(slideshowRoot.getAttribute('data-slideshow-total') || '0', 10);

        if (track instanceof HTMLElement && total > 1) {
            let index = 0;
            let timerId = 0;
            const intervalMs = 2000;
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const stepPct = 100 / total;
            const swipeThresholdPx = 48;

            const setTransform = () => {
                track.style.transform = `translateX(-${index * stepPct}%)`;
            };

            const go = (nextIndex) => {
                index = (nextIndex + total) % total;
                setTransform();
            };

            const startAutoplay = () => {
                if (reducedMotion) {
                    return;
                }
                window.clearInterval(timerId);
                timerId = window.setInterval(() => go(index + 1), intervalMs);
            };

            const stopAutoplay = () => {
                window.clearInterval(timerId);
                timerId = 0;
            };

            slideshowRoot.addEventListener('mouseenter', stopAutoplay);
            slideshowRoot.addEventListener('mouseleave', startAutoplay);

            let touchStartX = 0;
            let touchStartY = 0;

            const resumeAutoplayAfterInteraction = () => {
                startAutoplay();
            };

            slideshowRoot.addEventListener(
                'touchstart',
                (event) => {
                    if (event.touches.length !== 1) {
                        return;
                    }
                    touchStartX = event.touches[0].clientX;
                    touchStartY = event.touches[0].clientY;
                    stopAutoplay();
                },
                { passive: true }
            );

            slideshowRoot.addEventListener(
                'touchend',
                (event) => {
                    const touch = event.changedTouches[0];
                    if (!touch) {
                        resumeAutoplayAfterInteraction();
                        return;
                    }
                    const deltaX = touch.clientX - touchStartX;
                    const deltaY = touch.clientY - touchStartY;
                    if (Math.abs(deltaX) > swipeThresholdPx && Math.abs(deltaX) > Math.abs(deltaY)) {
                        if (deltaX < 0) {
                            go(index + 1);
                        } else {
                            go(index - 1);
                        }
                    }
                    resumeAutoplayAfterInteraction();
                },
                { passive: true }
            );

            slideshowRoot.addEventListener('touchcancel', resumeAutoplayAfterInteraction, { passive: true });

            setTransform();
            startAutoplay();
        }
    }

    const revealElements = document.querySelectorAll('[data-reveal]');

    if (revealElements.length > 0) {
        const prefersReducedMotionReveal = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotionReveal) {
            revealElements.forEach((el) => el.classList.add('is-visible'));
        } else {
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
        }
    }
});
