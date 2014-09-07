{strip} {* Your footer *}
<div class="footerCol">
	<p>
		<strong>{translate text='Featured Items'}</strong>
	</p>
	<ul>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_mesa%3A"Month"&amp;filter[]=literary_form_full%3A"Fiction"&amp;filter[]=target_audience%3A"Adult"'>{translate text='New Adult Fiction'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_mesa%3A"Month"&amp;filter[]=literary_form_full%3A"Fiction"&amp;filter[]=target_audience%3A"Juvenile"'>{translate text='New Juvenile Fiction'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_mesa%3A"Month"&amp;filter[]=literary_form_full%3A"Non+Fiction"'>{translate text='New Non-Fiction'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_mesa%3A"Month"&amp;filter[]=format%3A"DVD"'>{translate text='New DVDs'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_mesa%3A"Month"&amp;filter[]=format_category%3A"Audio+Books"'>{translate text='New Audio Books &amp; CDs'}</a></li>
		<li><a href='{$path}/Search/Results?lookfor=&amp;type=Keyword&amp;filter[]=local_time_since_added_mesa%3A"Week"'>{translate text='New This Week'}</a></li>
	</ul>
</div>
<div class="footerCol">
	<p>
		<strong>{translate text='Search Options'}</strong>
	</p>
	<ul>
		{if $user}
		<li><a href="{$path}/Search/History">{translate text='Search History'}</a></li>
		{/if}
		<li><a href="{$path}/Search/Results">{translate text='Standard Search'}</a></li>
		<li><a href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a></li>
	</ul>
</div>
<div class="footerCol">
	<p>
		<strong>{translate text='Find More'}</strong>
	</p>
	<ul>
		<li><a href="{$path}/Browse/Home">{translate text='Browse the Catalog'}</a></li>
		<li><a href="http://guides.mesacountylibraries.org">{translate text='Research and Learning Center'}</a></li>
		<li><a href="http://marmot.lib.overdrive.com" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Digital Downloads'}</a></li>
		<li><a href="http://search.ebscohost.com/login.aspx?authtype=ip,cpid&amp;custid=s9040366&amp;profile=novplus" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Novelist'}</a></li>
	</ul>
</div>
<div class="footerCol">
	<p>
		<strong>{translate text='Library Info'}</strong>
	</p>
	<ul>
		<li><a href="http://mesacountylibraries.libcal.com/events">{translate text='Library Events'}</a></li>
		<li><a href="http://mesacountylibraries.org/locations/all-locations/ ">{translate text='Library Locations and Hours'}</a></li>
		<li><a href="https://sis.d51schools.org/Login_PXP.aspx">{translate text='District 51 ParentVUE'}</a></li>
	</ul>
</div>
<div class="footerCol">
	<p>
		<strong>{translate text='Need Help?'}</strong>
	</p>
	<ul>
		<li><a href="{$path}/Help/Home?topic=search" onclick="window.open('{$path}/Help/Home?topic=search', 'Help', 'width=625, height=510'); return false;">{translate text='Search Tips'}</a></li>
		<li><a href="{$askALibrarianLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Ask a Librarian'}</a></li> {if isset($illLink)}
		<li><a href="{$illLink}" rel="external" onclick="window.open (this.href, 'child'); return false">{translate text='Interlibrary Loan'}</a></li> {/if} {if isset($suggestAPurchaseLink)}
		<li><a href="{$path}/MaterialsRequest/NewRequest">{translate text='Suggest a Purchase'}</a></li> {/if}
		<li><a href="{$path}/Help/Home?topic=faq" onclick="window.open('{$path}/Help/Home?topic=faq', 'Help', 'width=625, height=510, scrollbars=yes'); return false;">{translate text='FAQs'}</a></li>
		<li><a href="{$path}/Help/Suggestion">{translate text='Make a Suggestion'}</a></li>
	</ul>
</div>
<div class="clearer" />
<div id="copyright">
	<a href="#" class='mobile-view'>{translate text="Go to Mobile View"}</a>
</div>
{if !$productionServer}
<div class='location_info'>{$physicalLocation}</div>
{/if}{/strip}
