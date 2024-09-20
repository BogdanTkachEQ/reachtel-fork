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

                                	<table class="campaignTableCenter common-object-table" width="600px">
                                		<thead>
                                			<tr>
                                				<th>Security Zone</th>
                                				<th style="width: 50px; text-align: center;">Delete</th>
                                			</tr>
                                		</thead>
                                		<tbody>
{if !empty($securityzones)}
{foreach from=$securityzones key=k item=v}
                                			<tr>
                                				<td>{$v|escape:html|highlight:$search nofilter}</td>
                                				<td style="width: 50px; text-align: center;"><a href="?action=delete&amp;zoneid={$k}&amp;csrftoken={$smarty.session.csrftoken|default:''}" onclick="javascript: if(!confirm('Confirm delete?')) {ldelim} return false; {rdelim}"><span class="famfam" style="background-position: -280px -160px;" title="Delete" alt="Delete"></span></a></td>
                                			</tr>
{/foreach}
{else}
                                			<tr>
                                				<td colspan="2">No Security Zones</td>
                                			</tr>
{/if}
                                		</tbody>
                                	</table>

                                	<form action="?" method="post" class="common-form">
                                		<fieldset>
                                			<legend>New security zone</legend>
                                			<div class="inner">
                                				<div class="field">
                                					<label>Zone name:</label>
                                					<input name="name" value="" type="text" class="textbox" maxlength="100" />
                                					<p class="help">Maximum 100 characters</p>
                                				</div>
                                				<div class="form-controls">
                                					<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                					<button type="submit" name="submit">Create</button>
                                				</div>
                                			</div>
                                		</fieldset>
                                	</form>
                                </div>