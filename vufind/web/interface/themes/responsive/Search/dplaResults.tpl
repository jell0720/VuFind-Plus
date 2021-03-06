{strip}
	<div id="dplaSearchResults">
		<h2>More from Digital Public Library of America</h2>
		<p>The Digital Public Library of America brings together the riches of America’s libraries, archives, and museums, and makes them freely available to the world. It strives to contain the full breadth of human expression, from the written word, to works of art and culture, to records of America’s heritage, to the efforts and data of science. DPLA aims to expand this crucial realm of openly available materials, and make those riches more easily discovered and more widely usable and used.</p>

		{foreach from=$searchResults item=result}
			<div class="dplaResult row result">
				<div class="col-xs-2">
					{if $result.object}
						<img src="{$result.object}" class="listResultImage img-thumbnail img-responsive"/>
					{/if}
				</div>
				<div class="col-xs-10">
					<div class="result-title"><a href="{$result.link}">{$result.title}</a></div>
					<p>{$result.description}</p>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}