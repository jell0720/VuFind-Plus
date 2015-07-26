{strip}
<div class="result row">
	<div class="col-xs-12 col-sm-3">
		<div class="row">
			<div class="selectTitle col-xs-2">
				{if $record.cancelValue}
					<input type="checkbox" name="cancelId[{$record.cancelName}]" value="{$record.cancelValue}" id="selected{$record.cancelValue}" class="titleSelect">&nbsp;
				{/if}
			</div>
			<div class="col-xs-9 text-center">
				{if $record.id}
				<a href="{$path}/Record/{$record.id|escape:"url"}{*?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page} Needed? plb*}">
					{/if}
					<img src="{$coverUrl}/bookcover.php?id={$record.id}&amp;issn={$record.issn}&amp;isn={$record.isbn|@formatISBN}&amp;size=small&amp;upc={$record.upc}&amp;category={$record.format_category.0|escape:"url"}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}">
					{if $record.id}
				</a>
				{/if}
			</div>
		</div>
	</div>

	<div class="col-xs-12 col-sm-9">
		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				{if $record.id}
					<a href="{$path}/Record/{$record.id|escape:"url"}" class="result-title notranslate">
				{/if}
				{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}{/if}
				{if $record.id}
					</a>
				{/if}
				{if $record.title2}
					<div class="searchResultSectionInfo">
						{$record.title2|removeTrailingPunctuation|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
			</div>
		</div>

		<div class="row">
			<div class="resultDetails col-xs-12 col-md-9">

				{if $record.author}
					<div class="row">
						<div class="result-label col-xs-3">{translate text='Author'}</div>
						<div class="col-xs-9 result-value">
							{if is_array($record.author)}
								{foreach from=$record.author item=author}
									<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
								{/foreach}
							{else}
								<a href="{$path}/Author/Home?author={$record.author|escape:"url"}">{$record.author|highlight:$lookfor}</a>
							{/if}
						</div>
					</div>
				{/if}

				{if $record.format}
					<div class="row">
						<div class="result-label col-xs-3">{translate text='Format'}</div>
						<div class="col-xs-9 result-value">
							{implode subject=$record.format glue=", "}
						</div>
					</div>
				{/if}
				{* TODO: location needed for Bookings?
										<div class="row">
											<div class="result-label col-xs-3">{translate text='Pickup'}</div>
											<div class="col-xs-9 result-value">
												{$record.location}
											</div>
										</div>*}

				{if $record.startDateTime}
					<div class="row">
						<div class="result-label col-xs-3">{translate text='Starting at'}</div>
						<div class="col-xs-9 result-value">
							{$record.startDateTime|date_format:"%b %d, %Y at %l:%M %p"}
						</div>
					</div>
				{/if}

				{if $record.endDateTime}
					<div class="row">
						<div class="result-label col-xs-3">{translate text='Ending at'}</div>
						<div class="col-xs-9 result-value">
							{$record.endDateTime|date_format:"%b %d, %Y at %l:%M %p"}
						</div>
					</div>
				{/if}

				{if $record.status}
					<div class="row">
						<div class="result-label col-xs-3">{translate text='Status'}</div>
						<div class="col-xs-9 result-value">{$record.status}</div>
					</div>
				{/if}

			</div>

			<div class="col-xs-12 col-md-3">
				<div class="btn-group btn-group-vertical btn-block">
					{if $record.cancelValue}
						<button onclick="return VuFind.Account.cancelBooking('{$record.cancelValue}')" class="btn btn-sm btn-warning">Cancel Booking</button>
					{/if}
				</div>
			</div>

		</div>
	</div>


</div>
{/strip}