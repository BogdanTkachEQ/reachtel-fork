                                        <!-- Main Header -->
                                        <div class="main-common-header">
                                                <form action="?" method="get" id="search" style="width: 400px; float: right;">
							<div>
								<input type="text" name="search" value="{$search|default:""}" style="width: 300px;"/>&nbsp;&nbsp;
								{if !empty($search)}
								    <span class="famfam" style="background-position: -280px -160px;" title="Cancel Search" alt="Cancel Search" onclick="javascript: window.location='?';"></span>
								    {if !empty($allowrating)}
								        &nbsp;&nbsp;<span class="famfam" style="background-position: -220px -280px;" title="Download rating report" alt="Download rating report" style="border: none;" onclick="javascript: window.location='?search={$search|unescape:'html'|escape:'url'}&action=rate&amp;csrftoken={$smarty.session.csrftoken|default:''}';"></span>
								    {/if}
								{else}
								    <span class="famfam" style="background-position: -260px -480px;" alt="Search" title="Search" onclick="document.getElementById('search').submit();"></span>
								    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								{/if}
							</div>
						</form>
						<h2>{$title|default:" "}</h2>
                                        </div>
                                        <!-- /Main Header -->

                                        <!-- Notification -->
{if !empty($smarty_notifications)}{include file=$notification_template}{/if}
                                        <!-- /Notification -->

                                        <!-- Pagination -->
{if !empty($paginate_template)}{include file=$paginate_template}{else}<div>&nbsp;</div>{/if}
                                        <!-- /Pagination -->

