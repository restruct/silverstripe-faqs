<% require css("restruct/silverstripe-faqs:client/dist/css/faqs.css") %>
<% require javascript("restruct/silverstripe-faqs:client/dist/js/faqs.js") %>

<% if $PaginatedItems %>
    <div id="togglediv" class="faqcontainer">

		<% if $ClassName.ShortName != 'FaqPage' %>
            <h3><%t FAQ.FREQASKEDQUESTIONS 'Frequently asked questions' %></h3>
		<% end_if %>

        <div id="faqlist" class="answer-list">

			<% loop $PaginatedItems %>
                <div class="card answer-list__item">
                    <div class="card-header">
                        <a class="" data-bs-toggle="collapse" href="#answer-{$Pos}" data-voteurl="{$VoteLink}">
							{$Title}
                        </a>
                    </div>
                    <div id="answer-{$Pos}" class="collapse" data-parent="#faqlist" data-bs-parent="#faqlist">
                        <div class="card-body">
							$Content
                        </div>
                    </div>
                </div>
			<% end_loop %>


        </div>

		<% if $ClassName.ShortName != 'FaqPage' %>
            <p class="faqlink"><a href="$Parent.Link"><%t FAQ.GOTOALLFAQS 'More FAQs' %> &raquo;</a></p>
		<% end_if %>

    </div>
<% end_if %>
