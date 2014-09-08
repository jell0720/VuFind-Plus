{strip}
	{* New Search Box *}
	{include file="Search/searchbox-home.tpl"}

	{* Navigate within the results *}
	<div class="search-results-navigation text-center">
		{if $lastsearch}
			<div id="returnToSearch">
				<a href="{$lastsearch|escape}#record{$id|escape:"url"}">&laquo; {translate text="Return to Search Results"|strtoupper}</a>
			</div>
		{/if}
		<div class="btn-group">
			{if isset($previousId)}
				<div id="previousRecordLink" class="btn"><a href="{$path}/{$previousType}/{$previousId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$previousIndex}&amp;page={if isset($previousPage)}{$previousPage}{else}{$page}{/if}" title="{if !$previousTitle}{translate text='Previous'}{else}{$previousTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/prev.png" alt="Previous Record"/></a></div>
			{/if}
			{if isset($nextId)}
				<div id="nextRecordLink"class="btn"><a href="{$path}/{$nextType}/{$nextId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$nextIndex}&amp;page={if isset($nextPage)}{$nextPage}{else}{$page}{/if}" title="{if !$nextTitle}{translate text='Next'}{else}{$nextTitle|truncate:180:"..."|replace:"&":"&amp;"}{/if}"><img src="{$path}/interface/themes/default/images/next.png" alt="Next Record"/></a></div>
			{/if}
		</div>
	</div>

	{* Display Book Cover *}
	{if $user->disableCoverArt != 1}
		<div id = "recordcover" class="text-center">
			<a href="{$path}/Person/{$summShortId}">
				{if $person->picture}
					<a target='_blank' href='{$path}/files/original/{$person->picture|escape}'><img src="{$path}/files/medium/{$person->picture|escape}" class="alignleft listResultImage" alt="{translate text='Picture'}"/></a><br />
				{else}
					<img src="{$path}/interface/themes/default/images/person.png" class="alignleft listResultImage" alt="{translate text='No Cover Image'}"/><br />
				{/if}
			</a>
		</div>
	{/if}

	<div id="xs-main-content-insertion-point" class="row"></div>

	{if $user}
		{* Account Menu *}
		{include file="MyAccount/menu.tpl"}
	{/if}

	{include file="library-sidebar.tpl"}
{/strip}