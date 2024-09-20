                                <!-- Main Common Content Block -->
                                <div class="main-common-block">


                                        <!-- Main Header -->
                                        <div class="main-common-header">
                                                <h2>{$title|default:" "}</h2>
                                        </div>
                                        <!-- /Main Header -->

{if !empty($smarty_notifications)}
        {foreach from=$smarty_notifications key=type item=messages}
                {foreach from=$messages key=messagenumber item=message}
		                        <div class="flash flash-{$type}">{$message}</div>
                {/foreach}
        {/foreach}
{/if}

				</div>
