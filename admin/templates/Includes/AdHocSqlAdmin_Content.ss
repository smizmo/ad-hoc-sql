<div class="cms-content center $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
		    <h2>
            <% if $SectionTitle %>
                $SectionTitle
            <% end_if %>
            </h2>
		</div>

		<div class="cms-content-header-tabs cms-tabset-nav-primary ss-ui-tabs-nav">
            <% if $SearchForm || $ImportForm %>
			    <button id="filters-button" class="icon-button font-icon-search no-text" title="<%t CMSPagesController_Tools_ss.FILTER 'Filter' %>"></button>
            <% end_if %>
			<ul class="cms-tabset-nav-primary">
				<% loop $ManagedModelTabs %>
				<li class="tab-$ClassName $LinkOrCurrent<% if $LinkOrCurrent == 'current' %> ui-tabs-active<% end_if %>" aria-controls="Form_EditForm">
					<a href="$Link" class="cms-panel-link" title="$Title.ATT">$Title</a>
				</li>
				<% end_loop %>
			</ul>
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content cms-panel-padded" data-layout-type="border">

		<div class="cms-content-view">
			$EditForm
		</div>
	</div>

</div>
