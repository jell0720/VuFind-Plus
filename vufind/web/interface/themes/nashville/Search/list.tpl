{* Main Listing *}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
  {* Narrow Search Options *}
  <div id="sidebar">
    {if $sideRecommendations}
      {foreach from=$sideRecommendations item="recommendations"}
        {include file=$recommendations}
      {/foreach}
    {/if}
  </div>
  {* End Narrow Search Options *}

	<div id="main-content">
    
      <div id="searchInfo">
      {* Recommendations *}
      {if $topRecommendations}
        {foreach from=$topRecommendations item="recommendations"}
          {include file=$recommendations}
        {/foreach}
      {/if}

      {* Listing Options *}

      <div class="resulthead">
        {if $replacementTerm}
					<div id="replacementSearchInfo">
						<div style="font-size:120%">Showing Results for: <strong><em>{$replacementTerm}</em></strong></div>
						<div style="font-size:95%">Search instead for: <a href="{$oldSearchUrl}">{$oldTerm}</a></div>
					</div>
				{/if}
	      <!--
        <div class="yui-u first">
        {if $recordCount}
          {translate text="Showing"}
          <b>{$recordStart}</b> - <b>{$recordEnd}</b>
          {translate text='of'} <b>{$recordCount}</b>
          {if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
        {/if}
          {translate text='query time'}: {$qtime}s
          {if $spellingSuggestions}
          <br /><br /><div class="correction"><strong>{translate text='spell_suggest'}</strong>:<br/>
          {foreach from=$spellingSuggestions item=details key=term name=termLoop}
            {$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
          {/foreach}
          </div>
          {/if}
	      -->
        </div>
        <div class="yui-u toggle">
	        {if $recordCount}
	          {translate text='Sort'}
	          <select name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;">
	          {foreach from=$sortList item=sortData key=sortLabel}
	            <option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected="selected"{/if}>{translate text=$sortData.desc}</option>
	          {/foreach}
	          </select>
	        {/if}
        </div>

      </div>

      {* End Listing Options *}

      {if $subpage}
        {include file=$subpage}
      {else}
        {$pageContent}
      {/if}

      {if $prospectorNumTitlesToLoad > 0}
        <script type="text/javascript">getProspectorResults({$prospectorNumTitlesToLoad}, {$prospectorSavedSearchId});</script>
      {/if}
      {* Prospector Results *}
      <div id='prospectorSearchResultsPlaceholder'></div>
        
      
      <div class="searchtools">
        <strong>{translate text='Search Tools'}:</strong>
        <a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
        <a href="{$path}/Search/Email" onclick="getLightbox('Search', 'Email', null, null, '{translate text="Email this"}'); return false;"><span class="silk email">&nbsp;</span>{translate text='Email this Search'}</a>
        {if $savedSearch}<a href="{$path}/MyResearch/SaveSearch?delete={$searchId}"><span class="silk delete">&nbsp;</span>{translate text='save_search_remove'}</a>{else}<a href="{$path}/MyResearch/SaveSearch?save={$searchId}"><span class="silk add">&nbsp;</span>{translate text='save_search'}</a>{/if}
        <a href="{$excelLink|escape}"><span class="silk table_go">&nbsp;</span>{translate text='Export To Excel'}</a>
      </div>
      
      
            {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}

        <div id="noResultsWorldcat">
        <img src="{$path}/interface/themes/nashville/images/noResultsImage_OrangeMangifier.png" alt="Didn't what you were looking for icon" align="left" class="noResultsImage">     
          <h2>Didn't find what you were looking for?</h2>
                <ul class="correctionSuggestionIndent">
                    <li><a href="http://www.library.nashville.org/bmm/bmm_books_suggestionform.asp">Suggest a title for the library to purchase.</a></li>
                    <li><a href="http://npl.worldcat.org/search?q={$lookfor|escape:"html"}">Repeat your search on npl.worldcat.org - we'll try to borrow the item for you.</a></li>
                </ul>
        </div>

      <b class="bbot"><b></b></b>
    </div>
    {* End Main Listing *}
  </div>
</div>

