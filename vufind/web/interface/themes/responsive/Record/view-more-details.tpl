{strip}
	{* Details not shown in the Top/Main Section of the Record view should be shown here *}
	{if $recordDriver && !$showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Published'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if !$showFormats}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Format'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordFormat glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver && !$showEditions && $recordDriver->getEdition()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Edition'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getEdition() glue=", "}
			</div>
		</div>
	{/if}

	{if !$showPhysicalDescriptions && $physicalDescriptions}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Physical Desc'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$physicalDescriptions glue="<br/>"}
			</div>
		</div>
	{/if}

	{if $streetDate}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Street Date'}:</div>
			<div class="col-xs-9 result-value">
				{$streetDate|escape}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-xs-3">{translate text='Language'}:</div>
		<div class="col-xs-9 result-value">
			{implode subject=$recordLanguage glue=", "}
		</div>
	</div>

	{if $recordDriver && !$showISBNs && count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='ISBN'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver && count($recordDriver->getISSNs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='ISSN'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getISSNs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver && count($recordDriver->getUPCs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='UPC'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getUPCs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver && $recordDriver->getAcceleratedReaderData() != null}
		{assign var="arData" value=$recordDriver->getAcceleratedReaderData()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Accelerated Reader'}:</div>
			<div class="col-xs-9 result-value">
				{$arData.interestLevel|escape}<br/>
				Level {$arData.readingLevel|escape}, {$arData.pointValue|escape} Points
			</div>
		</div>
	{/if}

	{if $recordDriver && $recordDriver->getLexileCode()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Lexile Code'}:</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileCode()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver && $recordDriver->getLexileScore()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Lexile Score'}:</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileScore()|escape}
			</div>
		</div>
	{/if}

	{if $notes}
		<h4>{translate text='Notes'}</h4>
		{foreach from=$notes item=note name=loop}
			<div class="row">
				<div class="result-label col-sm-3">{$note.label}</div>
				<div class="col-sm-9 result-value">{$note.note}</div>
			</div>
		{/foreach}
	{/if}
{/strip}