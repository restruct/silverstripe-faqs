<% if Top.Faqs %>
<div id="togglediv" class="faqcontainer">
	<div>
		<% if $ClassName != 'FaqPage' %>
		<h3><%t FAQ.FREQASKEDQUESTIONS 'Frequently asked questions' %></h3>
		<% end_if %>

		<div class="panel-group" id="faqlist" role="tablist" aria-multiselectable="true">

			<% loop Top.Faqs %>
			<div class="panel panel-default">
				<div class="panel-heading" role="tab" id="question-$Pos">
					<p class="panel-title">
						<a role="button" data-toggle="collapse" data-parent="#faqlist" href="#answer-$Pos" aria-expanded="true" aria-controls="collapseOne" data-voteurl="$VoteLink">
							$Title
						</a>
					</p>
				</div>
				<div id="answer-$Pos" class="panel-collapse collapse" role="tabpanel" aria-labelledby="question-$Pos">
					<div class="panel-body">
						$Content
					</div>
				</div>
			</div>
			<% end_loop %>

		</div>

		<% if $ClassName != 'FaqPage' %>
		<p class="faqlink"><a href="$Page_byID(9).Link"><%t FAQ.GOTOALLFAQS 'More FAQs' %> &raquo;</a></p>
		<% end_if %>


	</div>
</div>
<% end_if %>