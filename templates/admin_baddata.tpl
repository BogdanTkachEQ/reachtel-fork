                                <!-- Main Common Content Block -->
                                <div class="main-common-block">


                                        <!-- Main Header -->
                                        <div class="main-common-header">
                                                <h2>{$title|default:" "}</h2>
                                        </div>
                                        <!-- /Main Header -->

                                        <!-- Notification -->
                                        {if !empty($smarty_notifications)}{include file=$notification_template}{/if}
                                        <!-- /Notification -->

{if !empty($results)}
					<h4>Results for "{$destination}"</h4>
					<table class="baddata common-object-table" width="100%">
					        <thead>
                               <tr><th>Timestamp</th><th>Type</th><th>Value</th><th>Delete</th></tr>
					        </thead>
					        <tbody>
{foreach from=$results item=match}
    <tr>
        <td>{$match.timestamp}</td>
        <td>{$match.type}</td>
        <td>{$match.destination}</td>
        <td><img src="//static.reachtel.com.au/icons/delete.png" onclick="javascript: if(confirm('Please confirm you want to delete this item from bad data')) { jQuery.post('?', {ldelim}'action' : 'remove_bad_data', 'value' : '{$match.destination}', 'type' : '{$match.type}', 'csrftoken': '{$smarty.session.csrftoken|default:''}'{rdelim}, function() {ldelim} location.reload(); {rdelim}, 'json'); };" title="Delete" alt="Delete" style="border: none; height: 16px; width: 16px;"></td>
    </tr>
{/foreach}
						</tbody>
					</table>
{elseif isset($results)}
					<div class="flash flash-success">
						There are no results for '{$destination}'
					</div>
{/if}
					<form action="?" method="get" class="common-form">
					        <fieldset>
						        <legend>Search</legend>
						        <div class="inner">
						                <div class="field">
						                        <label>Destination / Email:</label>
						                        <input name="destination" value="" type="text" class="textbox" />
						                        <p class="help">Phone number or email address.<br/>You can also use <strong>*</strong> as wildcard!</p>
						                </div>
						                <div class="form-controls">
						                        <button type="submit">Search</button>
						                </div>
						        </div>
					        </fieldset>
					</form>


				</div>
