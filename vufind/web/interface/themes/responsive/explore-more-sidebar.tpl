{strip}
	{* More Like This *}
	{if $showMoreLikeThisInExplore}
		{include file="GroupedWork/exploreMoreLikeThis.tpl"}
	{/if}

	{foreach from=$exploreMoreSections item=section}
		<div class="sectionHeader">{$section.title}</div>

		{if $section.format == 'scroller'}
			{* JCarousel with related titles *}
			<div class="jcarousel-wrapper">
				<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
				<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

				<div class="relatedTitlesContainer jcarousel"> {* relatedTitlesContainer used in initCarousels *}
					<ul>
						{foreach from=$section.values item=title}
							<li class="relatedTitle">
								<a href="{$title.link}">
									<figure class="thumbnail">
										<img src="{$title.image}" alt="{$title.label|removeTrailingPunctuation|truncate:80:"..."}">
										<figcaption>{$title.label|removeTrailingPunctuation|truncate:80:"..."}</figcaption>
									</figure>
								</a>
							</li>
						{/foreach}
					</ul>
				</div>
			</div>
		{elseif $section.format == 'subsections'}
			{foreach from=$section.values item=section}
				<div class="section">

					<div class="row">
						<div class="subsectionTitle col-xs-5">{$section.title}</div>
						<div class="subsection col-xs-7">
							<a href="{$section.link}"><img src="{$section.image}" alt="{$section.description}" class="img-responsive img-thumbnail"></a>
						</div>
					</div>
				</div>
			{/foreach}
		{elseif $section.format == 'scrollerWithLink'}
			{* Related Titles Widget *}
			<div class="jcarousel-wrapper">
				<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
				<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>

				<div class="relatedTitlesContainer jcarousel"> {* relatedTitlesContainer used in initCarousels *}
					<ul>
						{foreach from=$section.values item=title}
							<li class="relatedTitle">
								<a href="{$title.link}">
									<figure class="thumbnail">
										<img src="{$title.image}" alt="{$title.label|removeTrailingPunctuation|truncate:80:"..."}">
										<figcaption>{$title.label|removeTrailingPunctuation|truncate:80:"..."}</figcaption>
									</figure>
								</a>
							</li>
						{/foreach}
					</ul>
				</div>
			</div>
			<a class="explore-more-scroller-link" href="{$section.link}" {if $section.openInNewWindow}target="_blank"{/if}>All Results {if $section.numFound}({$section.numFound}){/if}</a>

		{elseif $section.format == 'tableOfContents'}
			<ul>
				{foreach from=$section.values item=value}
					<li>
						<a href="#" onclick="return VuFind.Archive.handleBookClick('{$bookPid}', '{$value.pid}', VuFind.Archive.activeBookViewer);">
							{$value.label}
						</a>
					</li>
				{/foreach}
			</ul>
		{elseif $section.format == 'textOnlyList'}
			<ul>
			{foreach from=$section.values item=value}
				<li>
					<a href="{$value.link}">
						{$value.label}
					</a>
					{if $value.linkingReason}
						&nbsp;<img src="/images/silk/help.png" title="{$value.linkingReason|escape}"/>
					{/if}
				</li>
			{/foreach}
			</ul>

		{else} {* list *}
			{* Simple display with one thumbnail per item *}
			{foreach from=$section.values item=value}
				<div class="section">
					<a href="{$value.link}">
						{if $value.image}
							<img src="{$value.image}" alt="{$value.label}" class="img-responsive img-thumbnail">
						{else}
							{$value.label}
						{/if}
					</a>
					{if $value.linkingReason}
						&nbsp;<img src="/images/silk/help.png" title="{$value.linkingReason|escape}"/>
					{/if}
				</div>
			{/foreach}
		{/if}
	{/foreach}

	{* Related Articles Widget *}
	{if $relatedArticles}
		<div class="sectionHeader">Articles and More</div>
		<div class="section">
			{foreach from=$relatedArticles item=section}
			<div class="row">
				<a href="{$section.link}">
					<div class="subsection col-xs-5">
						<img src="{$section.image}" alt="{$section.description}" class="img-responsive img-thumbnail">
					</div>
					<div class="subsectionTitle col-xs-7">{$section.title}</div>
				</a>
			</div>
			{/foreach}
		</div>
	{/if}

	{* Sections for Related Content From Novelist  *}
	{foreach from=$exploreMoreInfo item=exploreMoreOption}
		<div class="sectionHeader"{if $exploreMoreOption.hideByDefault} style="display: none;"{/if}>{$exploreMoreOption.label}</div>
		<div class="{*col-sm-12 *}jcarousel-wrapper"{if $exploreMoreOption.hideByDefault} style="display: none;"{/if}>
			<a href="#" class="jcarousel-control-prev"{* data-target="-=1"*}><i class="glyphicon glyphicon-chevron-left"></i></a>
			<a href="#" class="jcarousel-control-next"{* data-target="+=1"*}><i class="glyphicon glyphicon-chevron-right"></i></a>
			{$exploreMoreOption.body}
		</div>
	{/foreach}
{/strip}
