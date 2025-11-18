/**
 * FAQ Accordion
 * Simple accordion functionality for FAQ items using vanilla JavaScript
 * No dependencies required
 */

(function() {
    'use strict';

    /**
     * Initialize FAQ accordion functionality
     */
    function initFaqAccordion() {
        // Find all FAQ toggle buttons
        const toggleButtons = document.querySelectorAll('.faq-toggle');

        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                toggleFaqItem(button);
            });

            // Also support keyboard navigation
            button.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleFaqItem(button);
                }
            });
        });
    }

    /**
     * Toggle a single FAQ item
     * @param {HTMLElement} button - The toggle button element
     */
    function toggleFaqItem(button) {
        const isExpanded = button.getAttribute('aria-expanded') === 'true';
        const answerId = button.getAttribute('aria-controls');
        const answerElement = document.getElementById(answerId);

        if (!answerElement) {
            return;
        }

        if (isExpanded) {
            // Close the FAQ
            collapseFaq(button, answerElement);
        } else {
            // Open the FAQ
            expandFaq(button, answerElement);

            // Dispatch custom event for tracking
            const event = new CustomEvent('faq:opened', {
                detail: {
                    button: button,
                    faqId: button.getAttribute('data-faq-id')
                }
            });
            document.dispatchEvent(event);
        }
    }

    /**
     * Expand a FAQ item
     * @param {HTMLElement} button - The toggle button
     * @param {HTMLElement} answer - The answer element
     */
    function expandFaq(button, answer) {
        button.setAttribute('aria-expanded', 'true');
        answer.removeAttribute('hidden');

        // Set starting state
        answer.style.height = '0px';
        answer.style.overflow = 'hidden';
        answer.style.transition = 'height 0.3s ease';

        // Force reflow
        answer.offsetHeight;

        // Animate to full height
        answer.style.height = answer.scrollHeight + 'px';

        // Clean up after animation
        setTimeout(function() {
            answer.style.height = '';
            answer.style.overflow = '';
            answer.style.transition = '';
        }, 300);
    }

    /**
     * Collapse a FAQ item
     * @param {HTMLElement} button - The toggle button
     * @param {HTMLElement} answer - The answer element
     */
    function collapseFaq(button, answer) {
        // Set current height before animating
        answer.style.height = answer.scrollHeight + 'px';
        answer.style.overflow = 'hidden';
        answer.style.transition = 'height 0.3s ease';

        // Force reflow
        answer.offsetHeight;

        // Animate to 0 height
        answer.style.height = '0px';

        // Hide element after transition
        setTimeout(function() {
            button.setAttribute('aria-expanded', 'false');
            answer.setAttribute('hidden', '');
            answer.style.height = '';
            answer.style.overflow = '';
            answer.style.transition = '';
        }, 300);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFaqAccordion);
    } else {
        // DOM is already loaded
        initFaqAccordion();
    }

})();