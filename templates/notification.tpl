{foreach from=$smarty_notifications key=type item=messages}
	{foreach from=$messages key=messagenumber item=message}
		<div class="flash flash-{$type}">{$message nofilter}</div>
        {/foreach}
{/foreach}
