{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="GroupedWork/search-results-navigation.tpl"}
		<h2>
			{$title|escape}
		</h2>
		<div class="row">
			<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
				<div class="main-project-image">
					<img src="{$medium_image}" class="img-responsive"/>
				</div>

				{* Display map if it exists *}
				{if $mapsKey && $addressInfo.latitude && $addressInfo.longitude}
					<iframe width="100%" height="" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?q={$addressInfo.latitude|escape}%2C%20{$addressInfo.longitude|escape}&key={$mapsKey}" allowfullscreen></iframe>
					{if $addressInfo.latitude && $addressInfo.longitude}
						<div class="row">
							<div class="result-label col-sm-4">Position: </div>
							<div class="result-value col-sm-8">
								{$addressInfo.latitude}, {$addressInfo.longitude}
							</div>
						</div>
					{/if}
				{/if}
			</div>
			<div id="main-content" class="col-xs-8 col-sm-7 col-md-8 col-lg-9">
				{if $alternateNames}
					<div class="row">
						<div class="result-label col-sm-4">Alternate Name{if count($alternateNames)}s{/if}: </div>
						<div class="result-value col-sm-8">
							{foreach from=$alternateNames item=alternateName}
								{$alternateName}<br/>
							{/foreach}
						</div>
					</div>
				{/if}
				{if $addressInfo && $addressInfo.hasDetailedAddress}
					<div class="row">
						<div class="result-label col-sm-4">Address: </div>
						<div class="result-value col-sm-8">
							{if $addressInfo.addressStreetNumber || $addressInfo.addressStreet}
								{$addressInfo.addressStreetNumber} {$addressInfo.addressStreet}<br/>
							{/if}
							{if $addressInfo.addressCity || $addressInfo.addressState || $addressInfo.addressZipCode}
								{$addressInfo.addressCity}{if $addressInfo.addressCity && $addressInfo.addressState}, {/if}{$addressInfo.addressState} {$addressInfo.addressZipCode}<br/>
							{/if}
							{if $addressInfo.addressCounty}
								{$addressInfo.addressCounty} County<br/>
							{/if}
							{if $addressInfo.addressCountry}
								{$addressInfo.addressCountry}
							{/if}
						</div>
					</div>
				{/if}

				{if strlen($marmotExtension->marmotLocal->placeDateStart) || strlen($marmotExtension->marmotLocal->placeDateEnd)}
					<div class="row">
						<div class="result-label col-sm-4">Dates: </div>
						<div class="result-value col-sm-8">
							{$marmotExtension->marmotLocal->placeDateStart}{if $marmotExtension->marmotLocal->placeDateEnd} to {/if}{$marmotExtension->marmotLocal->placeDateEnd}
						</div>
					</div>
				{/if}

				{if strlen($marmotExtension->marmotLocal->eventStartDate) || strlen($marmotExtension->marmotLocal->eventEndDate)}
					<div class="row">
						<div class="result-label col-sm-4">Dates: </div>
						<div class="result-value col-sm-8">
							{$marmotExtension->marmotLocal->eventStartDate}{if $marmotExtension->marmotLocal->eventEndDate} to {/if}{$marmotExtension->marmotLocal->eventEndDate}
						</div>
					</div>
				{/if}

				{if strlen($marmotExtension->marmotLocal->dateEstablished) || strlen($marmotExtension->marmotLocal->dateDisbanded)}
					<div class="row">
						<div class="result-label col-sm-4">Dates: </div>
						<div class="result-value col-sm-8">
							{$marmotExtension->marmotLocal->dateEstablished}{if $marmotExtension->marmotLocal->dateDisbanded} to {/if}{$marmotExtension->marmotLocal->dateDisbanded}
						</div>
					</div>
				{/if}

				{if $relatedPlaces && $recordDriver->getType() == 'event'}
					<div class="row">
						<div class="result-label col-sm-4">Took place at: </div>
						<div class="result-value col-sm-8">
							{foreach from=$relatedPlaces item=entity}
								<a href='{$entity.link}'>
									{$entity.label}
								</a>
								{if $entity.role}
									&nbsp;({$entity.role})
								{/if}
								{if $entity.note}
									&nbsp;- {$entity.note}
								{/if}
								<br>
							{/foreach}
						</div>
					</div>
				{/if}

				{if $primaryUrl}
					<div class="row">
						<div class="result-label col-sm-4">Website: </div>
						<div class="result-value col-sm-8">
							<a href="{$primaryUrl}">{$primaryUrl}</a>
						</div>
					</div>
				{/if}

				{if $description}
					<div class="row">
						<div class="result-label col-sm-4">Description: </div>
						<div class="col-sm-8 result-value">
							{$description}
						</div>
					</div>
				{/if}
				{if $wikipediaData}
					{$wikipediaData.description}
					<div class="row smallText">
						<div class="col-xs-12">
							<a href="http://{$wiki_lang}.wikipedia.org/wiki/{$wikipediaData.name|escape:"url"}" rel="external" onclick="window.open (this.href, 'child'); return false"><span class="note">{translate text='wiki_link'}</span></a>
						</div>
					</div>
				{/if}
			</div>
		</div>

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
