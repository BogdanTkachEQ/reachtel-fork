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

                                	<div id="cron">

                                		<script type="text/javascript">

                                			$(function() {ldelim}
                                				$( "#tabs" ).tabs({ldelim}
                                					active   : Cookies.get('activetab-system-cron'),
                                					activate : function( event, ui ){ldelim}
                                						Cookies.set( 'activetab-system-cron', ui.newTab.index(),{ldelim}
                                							expires : 7
                                						{rdelim});
                                					{rdelim},
                                					fx: {ldelim}
                                						opacity: 'toggle',
                                						duration: 'fast'
                                					{rdelim},
                                					ajaxOptions: {ldelim}
                                						error: function( xhr, status, index, anchor ) {ldelim}
                                							$( anchor.hash ).html("Woops...that didn't work." );
                                						{rdelim}
                                					{rdelim}
                                				{rdelim});
                                			{rdelim});
                                		</script>

                                		<div id="tabs" style="width: 100%; border: none;">
                                			<ul style="width: 100%;">
                                				<li><a href="#tabs-settings">Settings</a></li>
                                				<li><a href="#tabs-lastrun">Last run</a></li>
                                				<li><a href="?name={$setting.name|urlencode}&amp;template=tags">Tags</a></li>
                                			</ul>

                                			<div id="tabs-settings">

                                				<form action="?" method="post" class="common-form">
                                					<fieldset>
                                						<input name="name" value="{$name}" type="hidden" />
                                						<legend>{$name} ({$id})</legend>
                                						<div class="inner">
                                							<div class="column">
                                								<div class="field">
                                									<label>Status:</label>
                                									<select class="selectbox" name="setting[status]">{html_options options=$status selected=$setting.status}</select>
                                									<p class="help">Task status</p>
                                								</div>
                                								<div class="field">
                                									<label>Description:</label>
                                									<input name="setting[description]" value="{$setting.description}" type="text" class="textbox" maxlength="200" />
                                									<p class="help">A description of what this script does</p>
                                								</div>
                                								<div class="field">
                                									<label>Script location:</label>
                                									<input name="setting[scriptname]" value="{$setting.scriptname}" type="text" class="textbox" maxlength="100" />
                                									<p class="help">The script name relative to the Morpheus/scripts directory</p>
                                								</div>
                                								<div class="field">
                                									<label>Time zone</label>
                                									<select class="selectbox" name="setting[timezone]">{html_options output=$timezones values=$timezones selected=$setting.timezone|default:'Australia/Sydney'}</select>
                                									<p class="help">Sets the time zone for the task</p>
                                								</div>
                                								<div class="form-controls">
                                									<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                									<button type="submit" name="submit">Save</button>
                                								</div>
                                								<div class="field">
                                									<p class="help">
                                										<a href="?action=adhocrun&name={$name|urlencode}&csrftoken={$smarty.session.csrftoken|default:''}" onclick="javascript: if(!confirm('Are you sure?')) {ldelim} return false; {rdelim}">Schedule an ad-hoc run</a>
                                									</p>
                                								</div>
                                							</div>
                                							<div class="column">
                                								<div class="field">
                                									<label>Hour:</label>
                                									<input name="setting[hour]" value="{$setting.hour}" type="text" class="textbox" maxlength="15" />
                                									<p class="help">Which hours to run on (e.g. 1,12-15,18)</p>
                                								</div>
                                								<div class="field">
                                									<label>Minute:</label>
                                									<input name="setting[minute]" value="{$setting.minute}" type="text" class="textbox" maxlength="15" />
                                									<p class="help">Which minutes to run on (e.g. 0,30,45)</p>
                                								</div>
                                								<div class="field">
                                									<label>Day of week:</label>
                                									<input name="setting[dayofweek]" value="{$setting.dayofweek}" type="text" class="textbox" maxlength="15" />
                                									<p class="help">Which days of week to run on (e.g. Mon-Tue,Fri)</p>
                                								</div>
                                								<div class="field">
                                									<label>Day of month:</label>
                                									<input name="setting[dayofmonth]" value="{$setting.dayofmonth}" type="text" class="textbox" maxlength="15" />
                                									<p class="help">Which days of month to run on (e.g. 1,4,7-9)</p>
                                								</div>
                                								<div class="field">
                                									<label>Month:</label>
                                									<input name="setting[month]" value="{$setting.month}" type="text" class="textbox" maxlength="15" />
                                									<p class="help">Which months to run on (e.g. 1,6,12)</p>
                                								</div>
                                							</div>
                                						</div>
                                						<div style="clear: both;">&nbsp;</div>
                                					</fieldset>
                                				</form>
                                			</div>
                                			<div id="tabs-lastrun">
                                				<form class="common-form">
                                					<fieldset>
                                						<legend>Last run</legend>
                                						<div class="inner">
                                							<p class="help">
                                								{$setting.lastrun|date_format:"%H:%M:%S %d/%m/%Y"|default:'never'} {if !empty($setting.lastrun)}({$setting.lastrun|api_misc_timeformat}){/if}
                                							</p>
                                						</div>
                                					</fieldset>
                                				</form>
                                				<form class="common-form">
                                					<fieldset>
                                						<legend>Script output</legend>
                                						<div class="inner">
                                							<p class="help">
                                								{$setting.lastrunoutput|escape|nl2br|default:'no output' nofilter}
                                							</p>
                                						</div>
                                					</fieldset>
                                				</form>
                                			</div>
                                		</div>
                                	</div>

                                </div>
