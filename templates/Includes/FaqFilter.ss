<% if $ClassName = 'FaqPage' %>
<div class="sidebar">
	<div id="filter">
		<strong><% with $Top %><%t FAQ.FILTERBYCAT "Filter: " %><% end_with %></strong>
		<div class="styled-select but">
			<form action="$Link">
				$FaqCatDropdown
			</form>
		</div>
	</div>
</div>
<% end_if %>
