<section class="faq-page">
    <div class="faq-container">

        <% if $Content %>
            <div class="faq-intro">
                $Content
            </div>
        <% end_if %>

        <% if $CategoriesWithFaqs %>
            <% loop $CategoriesWithFaqs %>
                <div class="faq-category">
                    <h2 class="faq-category__title">$Category.Title</h2>

                    <div class="faq-accordion">
                        <% loop $Faqs %>
                            <div class="faq-item">
                                <h3 class="faq-question">
                                    <button
                                        class="faq-toggle"
                                        type="button"
                                        aria-expanded="false"
                                        aria-controls="faq-answer-{$ID}"
                                        data-faq-id="{$ID}"
                                        data-security-token="{$ViewToken}">
                                        $Question
                                    </button>
                                </h3>
                                <div id="faq-answer-{$ID}" class="faq-answer" hidden>
                                    <div class="faq-answer-content">
                                        $Answer
                                    </div>
                                </div>
                            </div>
                        <% end_loop %>
                    </div>
                </div>
            <% end_loop %>
        <% else %>
            <div class="faq-empty">
                <p><%t Restruct\FAQ\Pages\FAQPage.NoFaqsMessage 'No FAQs have been added to this page yet.' %></p>
            </div>
        <% end_if %>

    </div>
</section>