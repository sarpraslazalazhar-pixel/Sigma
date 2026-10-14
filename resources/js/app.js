import { animate, inView, stagger } from 'motion';

function initMotion() {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        return;
    }

    // Micro-animation for cards and containers on load/navigation
    const cards = document.querySelectorAll('[data-motion="card"], .motion-card');
    if (cards.length > 0) {
        animate(
            cards,
            { opacity: [0, 1], y: [8, 0] },
            { delay: stagger(0.04), duration: 0.25, easing: 'ease-out' }
        );
    }
}

// Initial page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMotion);
} else {
    initMotion();
}

// Livewire SPA navigation support
document.addEventListener('livewire:navigated', () => {
    initMotion();
});
