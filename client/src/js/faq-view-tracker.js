/**
 * FAQ View Tracker
 * Tracks when users open FAQ accordion items and sends analytics to the server
 * Secured with CSRF tokens and session-based tracking
 */

(function() {
    'use strict';

    // Track which FAQs have been tracked in this page session (client-side deduplication)
    const trackedFaqs = new Set();

    /**
     * Initialize FAQ view tracking
     */
    function initFaqTracking() {
        // Listen for custom 'faq:opened' event dispatched by faq-accordion.js
        document.addEventListener('faq:opened', function(event) {
            if (event.detail && event.detail.button) {
                trackFaqView(event.detail.button);
            }
        });
    }

    /**
     * Track a FAQ view by sending AJAX request to server
     * @param {HTMLElement} button - The accordion button element
     */
    function trackFaqView(button) {
        const faqId = button.getAttribute('data-faq-id');
        const securityToken = button.getAttribute('data-security-token');

        // Validate data
        if (!faqId || !securityToken) {
            console.error('FAQ tracking: Missing FAQ ID or security token');
            return;
        }

        // Check if already tracked in this page session
        if (trackedFaqs.has(faqId)) {
            return;
        }

        // Get the name of the security token parameter (usually 'SecurityID')
        const tokenName = 'SecurityID';

        // Prepare form data
        const formData = new FormData();
        formData.append('faqId', faqId);
        formData.append(tokenName, securityToken);

        // Send AJAX request to dedicated API endpoint
        fetch('/faq-api/incrementView', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Mark as tracked
                trackedFaqs.add(faqId);

                // Optional: Log for debugging (remove in production)
                if (window.console && console.log) {
                    console.log('FAQ view tracked:', {
                        faqId: faqId,
                        viewCount: data.viewCount,
                        alreadyCounted: data.alreadyCounted
                    });
                }
            }
        })
        .catch(function(error) {
            // Silently fail - we don't want to disrupt user experience
            if (window.console && console.error) {
                console.error('FAQ tracking error:', error);
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFaqTracking);
    } else {
        // DOM is already loaded
        initFaqTracking();
    }

})();