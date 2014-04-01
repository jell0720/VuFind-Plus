{strip}
{if $topFacetSet}
	{foreach from=$topFacetSet item=cluster key=title}
		{if $cluster.label == 'Category' || $cluster.label == 'Format Category'}
			{if ($categorySelected == false)}
				<div class="formatCategories well text-center top-facet" id="formatCategories">
					<div id='categoryValues' class="text-center">
						{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
							{if $thisFacet.isApplied}
								<div class='categoryValue categoryValue_{translate text=$thisFacet.value|lower|replace:' ':''} span2'>
									<img src="{$path}/interface/themes/responsive/images/{$thisFacet.value|lower|replace:' ':''}.png" alt="{translate text=$thisFacet.value|escape}">{*<br/>
									{$thisFacet.value|escape}<br/>*}
									<div><a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', 'formatCategory', '{$thisFacet.value|escape}');">(remove filter)</a></div>
								</div>
							{else}
								<div class='categoryValue categoryValue_{translate text=$thisFacet.value|lower|replace:' ':''} span2' >
									<a href="{$thisFacet.url|escape}" onclick="trackEvent('Apply Facet', 'formatCategory', '{$thisFacet.value|escape}');">
										<img src="{$path}/interface/themes/responsive/images/{$thisFacet.value|lower|replace:' ':''}.png" alt="{translate text=$thisFacet.value|escape}">{*<br/>
										{translate text=$thisFacet.value|escape}<br/>*}<div>({$thisFacet.count})</div>
									</a>
								</div>
							{/if}
						{/foreach}
					</div>
					<div class="clearfix"></div>
				</div>
			{/if}
		{elseif preg_match('/available/i', $cluster.label)}
			<div class="row-fluid text-center top-facet">
				<div id="availabilityControl" class='btn-group' data-toggle="buttons-radio">
					{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
						{if $thisFacet.isApplied}
							<button type="button" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" class="btn btn-primary" name="availabilityControls">{$thisFacet.value|escape} ({$thisFacet.count})</button>
						{else}
							<button type="button" id="{$thisFacet.value|escape|regex_replace:'/[()\s]/':''}" class="btn" name="availabilityControls" data-url="{$thisFacet.url|escape}" onclick="window.location = $(this).data('url')" >{$thisFacet.value|escape} ({$thisFacet.count})</button>
						{/if}
					{/foreach}
				</div>
			</div>
		{else}
			<div class="authorbox top-facet">
				<h5>{translate text=$cluster.label}<span>{translate text="top_facet_suffix"}</span></h5>
				<table class="facetsTop navmenu narrow_begin benjie">
					{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
						{if $smarty.foreach.narrowLoop.iteration == ($topFacetSettings.rows * $topFacetSettings.cols) + 1}
							<tr id="more{$title}"><td><a href="#" onclick="moreFacets('{$title}'); return false;">{translate text='more'} ...</a></td></tr>
							</table>
							<table class="facetsTop navmenu narrowGroupHidden" id="narrowGroupHidden_{$title}">
							<tr><th colspan="{$topFacetSettings.cols}"><div class="top_facet_additional_text">{translate text="top_facet_additional_prefix"}{translate text=$cluster.label}<span>{translate text="top_facet_suffix"}</span></div></th></tr>
						{/if}
						{if $smarty.foreach.narrowLoop.iteration % $topFacetSettings.cols == 1}
							<tr>
						{/if}
						{if $thisFacet.isApplied}
							<td>{$thisFacet.value|escape}</a> <img src="{$path}/images/silk/tick.png" alt="Selected" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$thisFacet.value|escape}');">(remove)</a></td>
						{else}
							<td><a href="{$thisFacet.url|escape}" onclick="trackEvent('Apply Facet', '{$cluster.label}', '{$thisFacet.value|escape}');">{$thisFacet.value|escape}</a> ({$thisFacet.count})</td>
						{/if}
						{if $smarty.foreach.narrowLoop.iteration % $topFacetSettings.cols == 0 || $smarty.foreach.narrowLoop.last}
							</tr>
						{/if}
						{if $smarty.foreach.narrowLoop.total > ($topFacetSettings.rows * $topFacetSettings.cols) && $smarty.foreach.narrowLoop.last}
							<tr><td><a href="#" onclick="lessFacets('{$title}'); return false;">{translate text='less'} ...</a></td></tr>
						{/if}
					{/foreach}
				</table>
			</div>
		{/if}
	{/foreach}
{/if}
{/strip}
