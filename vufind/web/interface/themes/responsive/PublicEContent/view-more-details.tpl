{strip}
	{* Details not shown in the Top/Main Section of the Record view should be shown here *}
	{if !$showPublicationDetails}
		{if $recordDriver->getPublicationDetails()}
			<div class="row">
				<div class="result-label col-md-3">{translate text='Published'}:</div>
				<div class="col-md-9 result-value">
					{implode subject=$recordDriver->getPublicationDetails() glue=", "}
				</div>
			</div>
		{/if}
	{/if}

	{if !$showFormats}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Format'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getFormats() glue=", "}
			</div>
		</div>
	{/if}

	{if !$showEditions}
		{if $recordDriver->getEdition()}
			<div class="row">
				<div class="result-label col-md-3">{translate text='Edition'}:</div>
				<div class="col-md-9 result-value">
					{implode subject=$recordDriver->getEdition() glue=", "}
				</div>
			</div>
		{/if}
	{/if}

<div class="row">
	<div class="result-label col-md-3">{translate text='Language'}:</div>
	<div class="col-md-9 result-value">
		{implode subject=$recordDriver->getLanguage() glue=", "}
	</div>
</div>

{if count($recordDriver->getISBNs()) > 0}
	<div class="row">
		<div class="result-label col-md-3">{translate text='ISBN'}:</div>
		<div class="col-md-9 result-value">
			{implode subject=$recordDriver->getISBNs() glue=", "}
		</div>
	</div>
{/if}

{if count($recordDriver->getUPCs()) > 0}
	<div class="row">
		<div class="result-label col-md-3">{translate text='UPC'}:</div>
		<div class="col-md-9 result-value">
			{implode subject=$recordDriver->getUPCs() glue=", "}
		</div>
	</div>
{/if}

{if $recordDriver->getAcceleratedReaderData() != null}
	{assign var="arData" value=$recordDriver->getAcceleratedReaderData()}
	<div class="row">
		<div class="result-label col-md-3">{translate text='Accelerated Reader'}:</div>
		<div class="col-md-9 result-value">
			{if $arData.interestLevel}
				{$arData.interestLevel|escape}<br/>
			{/if}
			Level {$arData.readingLevel|escape}, {$arData.pointValue|escape} Points
		</div>
	</div>
{/if}

{if $recordDriver->getLexileCode()}
	<div class="row">
		<div class="result-label col-md-3">{translate text='Lexile Code'}:</div>
		<div class="col-md-9 result-value">
			{$recordDriver->getLexileCode()|escape}
		</div>
	</div>
{/if}

{if $recordDriver->getLexileScore()}
	<div class="row">
		<div class="result-label col-md-3">{translate text='Lexile Score'}:</div>
		<div class="col-md-9 result-value">
			{$recordDriver->getLexileScore()|escape}
		</div>
	</div>
{/if}

{/strip}