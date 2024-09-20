                                <!-- Main Common Content Block -->
                                <div class="main-common-block">

                                	{include file=$search_notification_pagination_template}

                                	<div class="breadcrumbs">
                                		<ol>
                                			<li class="first"><a href="admin_servers.php">Voice Servers</a></li>
                                			<li><a href="admin_voice.php">Voice Suppliers</a></li>
                                			<li><a href="admin_sms_suppliers.php">SMS Suppliers</a></li>
                                			<li><a href="admin_hlr_suppliers.php">HLR Suppliers</a></li>
                                			<li><a href="admin_securityzones.php">Security Zones</a></li>
                                		</ol>
                                		<div class="clear-both"></div>
                                	</div>

                                	<table class="campaignTableCenter common-object-table" width="300px">
                                		<thead>
                                			<tr>
                                				<th>Server</th>
                                				<th style="width: 50px; text-align: center;">Delete</th>
                                			</tr>
                                		</thead>
                                		<tbody>
{if !empty($servers)}
{foreach from=$servers key=k item=v}
                                			<tr>
                                				<td><a href="admin_listvoiceserver.php?serverid={$k}">{$v|escape:html|highlight:$search nofilter}</a></td>
                                				<td style="width: 50px; text-align: center;"><a href="admin_servers.php?serverid={$k}&amp;action=delete" onclick="javascript: if(!confirm('Confirm delete?')) {ldelim} return false; {rdelim}"><span class="famfam" style="background-position: -280px -160px;" title="Delete" alt="Delete"></span></a></td>
                                			</tr>
{/foreach}
{else}
											<tr>
												<td colspan="2">No Servers</td>
											</tr>
{/if}
                                			</tbody>
                                		</table>

                                		<form action="?" method="post" class="common-form">
                                			<fieldset>
                                				<legend>Add a server</legend>
                                				<div class="inner">
                                					<div class="field">
                                						<label>Name:</label>
                                						<input name="name" value="" type="text" class="textbox" maxlength="25" />
                                						<p class="help">Maximum 25 characters</p>
                                					</div>
                                					<div class="form-controls">
                                						<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                						<button type="submit" name="submit">Create</button>
                                					</div>
                                				</div>
                                			</fieldset>
                                		</form>

                                	</div>