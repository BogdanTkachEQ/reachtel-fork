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
	                                				<th>Supplier</th>
	                                				<th style="width: 50px; text-align: center;">Status</th>
	                                				<th style="width: 50px; text-align: center;">Delete</th>
	                                			</tr>
	                                		</thead>
	                                		<tbody>
{if !empty($suppliers)}
{foreach from=$suppliers key=k item=v}
	                                			<tr>
	                                				<td><a href="admin_listhlrsupplier.php?name={$v.name|urlencode}">{$v.name|escape:html|highlight:$search nofilter}</a></td>
	                                				<td style="width: 50px; text-align: center;"><span class="famfam" style="background-position: {if $v.status == "ACTIVE"}0px 0px{else}-100px -420px{/if};"></span></td>
	                                				<td style="width: 50px; text-align: center;"><a href="admin_hlr_suppliers.php?supplierid={$k}&amp;action=delete&amp;csrftoken={$smarty.session.csrftoken|default:''}" onclick="javascript: if(!confirm('Confirm delete?')) {ldelim} return false; {rdelim}"><span class="famfam" style="background-position: -280px -160px;" title="Delete" alt="Delete"></span></a></td>
	                                			</tr>
{/foreach}
{else}
	                                			<tr>
	                                				<td colspan="3">No HLR Suppliers</td>
	                                			</tr>
{/if}
	                                		</tbody>
	                                	</table>

	                                	<form action="?" method="post" class="common-form">
	                                		<fieldset>
	                                			<legend>New supplier</legend>
	                                			<div class="inner">
	                                				<div class="field">
	                                					<label>Supplier name:</label>
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
