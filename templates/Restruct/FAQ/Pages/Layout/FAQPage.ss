<section class="faq-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">

                <% if $Content %>
                    <div class="row mb-4">
                        <div class="col typography">
                            $Content
                        </div>
                    </div>
                <% end_if %>

                <% if $CategoriesWithFaqs %>
                    <div class="row">
                        <div class="col">
                            <% loop $CategoriesWithFaqs %>
                                <div class="faq-category mb-5">
                                    <h2 class="faq-category__title mb-3">$Category.Title</h2>

                                    <div class="accordion" id="accordion-category-{$Category.ID}">
                                        <% loop $Faqs %>
                                            <div class="accordion-item">
                                                <h3 class="accordion-header" id="heading-{$ID}">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{$ID}" aria-expanded="false" aria-controls="collapse-{$ID}">
                                                        $Question
                                                    </button>
                                                </h3>
                                                <div id="collapse-{$ID}" class="accordion-collapse collapse" aria-labelledby="heading-{$ID}" data-bs-parent="#accordion-category-{$Up.Category.ID}">
                                                    <div class="accordion-body typography">
                                                        $Answer
                                                    </div>
                                                </div>
                                            </div>
                                        <% end_loop %>
                                    </div>
                                </div>
                            <% end_loop %>
                        </div>
                    </div>
                <% else %>
                    <div class="row">
                        <div class="col">
                            <div class="alert alert-info">
                                <p><%t Restruct\FAQ\Pages\FAQPage.NoFaqsMessage 'No FAQs have been added to this page yet.' %></p>
                            </div>
                        </div>
                    </div>
                <% end_if %>

            </div>
        </div>
    </div>
</section>