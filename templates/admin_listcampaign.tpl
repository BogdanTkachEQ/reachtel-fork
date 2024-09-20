                                <!-- Main Common Content Block -->
                                <div class="main-common-block">

                                        <!-- Main Header -->
                                        <div class="main-common-header">
                                                <h2>Campaign</h2>
                                        </div>
                                        <!-- /Main Header -->

                                        <!-- Notification -->
                                        {if !empty($smarty_notifications)}{include file=$notification_template}{/if}
                                        <!-- /Notification -->

										<script type="text/javascript">

											$(document).ready(function() {ldelim}
												mergeData('{$name}');
{if ($type == "phone") OR ($type == "wash")}
												loadPhone('{$name}');

{elseif $type == "sms"}
												loadSMS('{$name}');
{elseif $type == "email"}
												loadEmail('{$name}');
									 	        $('#statistics').load('admin_ui_targets.php', 	{ldelim}'name' : '{$name}'{rdelim}, function() {ldelim} $("#targetsloading").css("display", "none"); {rdelim});
												$('#responses').load('admin_ui_responses.php',  {ldelim}'name' : '{$name}'{rdelim}, function() {ldelim} $("#responsesloading").css("display", "none"); {rdelim});

						   						setInterval(function() {ldelim}
													$("#targetsloading").css("display", "block");
						 	        				$('#statistics').load('admin_ui_targets.php', 	{ldelim}'name' : '{$name}'{rdelim}, function() {ldelim} $("#targetsloading").css("display", "none"); {rdelim});
													$("#responsesloading").css("display", "block");
													$('#responses').load('admin_ui_responses.php',  {ldelim}'name' : '{$name}'{rdelim}, function() {ldelim} $("#responsesloading").css("display", "none"); {rdelim});
				   								{rdelim}, 5000);
{/if}
								   				$.ajaxSetup({ldelim} cache: false {rdelim});
											{rdelim});

										</script>

									<h3 class="secondary-header">
										<span style="float: right; background-position: -280px -20px;" class="famfam" title="Refresh page" alt="Refresh page" onclick="javascript: window.location='admin_listcampaign.php?name={$name|urlencode}';" /></span>
										{$name} ({$campaignid}) {if $is_cascade_template|default:null} - (cascading campaign template){/if}
									</h3>

									<div id="settings">

									<script type="text/javascript">

								        $(function() {ldelim}
							                $( "#tabs" ).tabs({ldelim}
												active   : Cookies.get('activetab-{$name}'),
												activate : function( event, ui ){ldelim}
													Cookies.set( 'activetab-{$name}', ui.newTab.index(),{ldelim}
														expires : 7
													{rdelim});
												{rdelim},
												show: {ldelim}
													effect: 'fade',
													duration: 200
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
						                <li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=campaign">Campaign</a></li>
						                <li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=advanced">Advanced</a></li>
										<li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=hooks">Hooks</a></li>
{if $type == 'phone'}							        	<li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=voice">Voice</a></li>
{elseif $type == 'sms'} 							        <li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=sms">SMS</a></li>
{elseif $type == 'wash'} 							        <li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=wash">Wash</a></li>
{elseif $type == 'email'}               						<li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=email">Email</a></li>{/if}
						                <li><a href="#tabs-data">Data</a></li>
						                <li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=timing">Timing</a></li>
{if api_security_isadmin($smarty.session.userid)}						                <li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=billing">Billing</a></li>{/if}
						                <li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=reporting">Reporting</a></li>
						                <li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=tags">Tags</a></li>
{if !empty($issurvey)}						                <li><a href="admin_ui_settings.php?name={$name|urlencode}&amp;type=survey">Survey</a></li>{/if}
						        </ul>

						        <div id="tabs-data">
						                <div style="text-align: right;">
						                        <span class="famfam" style="background-position: -400px -20px;" title="Reset all targets to READY" alt="Reset all targets to READY" onclick="javascript: var check = prompt('Reset all targets to READY?\n\nPlease type OK to continue.'); if(check.toLowerCase() == 'ok') {ldelim} window.location='?action=resetready&amp;name={$name|urlencode}&amp;csrftoken={$smarty.session.csrftoken|default:''}'; {rdelim}" return false;"></span>
						                        <span class="famfam" style="background-position: -340px -80px;" title="Delete the campaign" alt="Delete the campaign" onclick="javascript: var check = prompt('Delete the campaign?\n\nARE YOU REALLY SURE YOU WANT TO DO THIS?\n\nPlease type DELETE to confirm.'); if(check.toLowerCase() == 'delete') {ldelim} window.location='?action=deletecampaign&amp;name={$name|urlencode}&amp;csrftoken={$smarty.session.csrftoken|default:''}'; {rdelim}" return false;"></span>
						                        <span class="famfam" style="background-position: -180px -40px;" title="Delete all targets and responses" alt="Delete all targets and responses" onclick="javascript: var check = prompt('Delete all targets and responses?\n\nPlease type OK to confirm.'); if(check.toLowerCase() == 'ok') {ldelim} window.location='?action=emptycampaign&amp;name={$name|urlencode}&amp;csrftoken={$smarty.session.csrftoken|default:''}'; {rdelim}" return false;"></span>
						                        <span class="famfam" style="background-position: -180px -180px;" title="Generate report" alt="Generate report" onclick="javascript: if(confirm('Send report?')) {ldelim} window.location='?name={$name|urlencode}&amp;action=emailreport&amp;csrftoken={$smarty.session.csrftoken|default:''}';{rdelim}"></span>
						                        <span class="famfam" style="background-position: -580px -320px;" onclick="javascript: window.location='?name={$name|urlencode}&amp;action=downloadreport&amp;csrftoken={$smarty.session.csrftoken|default:''}';" title="Download Report" alt="Download Report"></span>
						                </div>

                                        <form enctype="multipart/form-data" action="?name={$name}" method="post" class="common-form">
                                            <fieldset>
                                                <legend>File settings</legend>
                                                <div class="inner">
                                                    <table class="campaignTable common-object-table" style="width: 100%;">
                                                        <tr>
                                                            <td>File delimiter</td>
                                                            <td>
                                                                <select class="mediumdata" name="setting[filedelimiter]">
                                                                    {html_options options=$filedelimiteroptions selected=$filedelimiterselected|default:"0"}
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2">
                                                                <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                                                <input type="hidden" name="campaignid" value="{$campaignid}" />
                                                                <input type="submit" name="submit" value="Save"/>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </fieldset>
                                        </form>

						                <form enctype="multipart/form-data" action="?" method="post" class="common-form">
						                    <fieldset>
												<legend>Upload File</legend>
						                       	<div class="inner">
													<div class="field">
							                            <input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
														<input type="hidden" name="name" value="{$name}" />
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<label>Upload targets:</label>
														<input name="uploadedfile" type="file" class="file" />
						                            </div>
													<div class="form-controls">
														<button type="submit" name="submit">Upload File</button>
													</div>
													<div class="field">
														<p class="help">Last file uploaded: {if !empty($lastupload)}{$lastupload}{else}none{/if}</p>
													</div>
						                        </div>
												<div class="field" style="margin-left: 10px;">
													<h5 class="label">Queued Uploads</h5>
													<div id="upload-queue">
														<span id="queue-loader" style="float:right;"><img src="/img/ajax-loading-bar-18.gif" title="Loading..." alt="Loading..." />&nbsp;</span>
														<table id="upload-queue-table" class="common-object-table" style="table-layout: auto; min-width: 20%;">
															<th>Uploaded</th>
															<th>Status</th>
															<th>Ran At</th>
															<th>Completed</th>
															<th>Data</th>
															<tbody id="upload-queue-table-body">

															</tbody>
														</table>
													</div>
												</div>
						                    </fieldset>

						                </form>

						                <form action="?name={$name}" method="post" class="common-form">
					                        <fieldset>
												<legend onclick="$(this).siblings('.inner').toggle();">Add Target</legend>
        					                    <div class="inner" style="display: none;">
													<div class="column">
														<div class="field">
							                                <input type="hidden" name="name" value="{$name}" />
															<input type="hidden" name="action" value="addtarget" />
															<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
															<label>Destination address:</label>
															<input name="destination" type="text" class="textbox" />
														</div>
														<div class="form-controls">
							                                <button type="submit" name="submit">Add Target</button>
														</div>
													</div>
													<div class="column">
														<div class="field">
															<label>Merge Fields</label>
															<input type="text" name="elements" class="textbox" />
															<p class="help">Format: name=value;name2=value2;</p>
														</div>
													</div>
												</div>
							                    <div class="clear-both"></div>
						                    </fieldset>
						                </form>
{if !empty($lists)}
						                <form action="?name={$name}" method="post" class="common-form">
						                    <fieldset>
												<legend onclick="$(this).siblings('.inner').toggle();">Merge in a list</legend>
	        					            	<div class="inner" style="display: none;">
													<div class="field">
							                            <input type="hidden" name="name" value="{$name}" />
														<input type="hidden" name="action" value="mergelist" />
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<label>List:</label>
														<select class="mediumdata selectbox" name="listid">{html_options options=$lists}</select>
													</div>
													<div class="form-controls">
							                        	<button type="submit" name="submit">Upload data</button>
													</div>
												</div>
												<div class="clear-both"></div>
						                    </fieldset>
						                </form>
{/if}
						                <form action="?name={$name}" method="post" class="common-form">
						                    <fieldset>
												<legend onclick="$(this).siblings('.inner').toggle();">Cross-campaign deduplicate</legend>
	        					                <div class="inner" style="display: none;">
													<div class="field">
							                        	<input type="hidden" name="name" value="{$name}" />
														<input type="hidden" name="action" value="crosscampaigndedupe" />
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<label>Source campaign:</label>
														<input name="dedupeorigin" type="text" class="textbox" />
														<p class="help">Note: only the open campaign will have targets removed. The source campaign won't change</p>
													</div>
													<div class="form-controls">
							                        	<button type="submit" name="submit">Dedupe this campaign</button>
													</div>
						                        </div>
						                        <div class="clear-both"></div>
						                    </fieldset>
						                </form>

						                <form action="?name={$name}" method="post" class="common-form">
						                        <fieldset>
													<legend onclick="$(this).siblings('.inner').toggle();">Rename this campaign</legend>
	        				                        <div class="inner" style="display: none;">
														<div class="field">
							                    			<input type="hidden" name="name" value="{$name}" />
															<input type="hidden" name="action" value="rename" />
															<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
															<label>New name:</label>
															<input name="newname" type="text" class="textbox" value="{$name}" />
														</div>
														<div class="form-controls">
				                                       		<button type="submit" name="submit">Rename</button>
														</div>
													</div>
													<div class="clear-both"></div>
						                        </fieldset>
						                </form>
{if ($type == "phone")}
						                <form action="?name={$name}" method="post" class="common-form">
						                    <fieldset>
												<legend onclick="$(this).siblings('.inner').toggle();">Remove recent 0_AMD/HUMAN's</legend>
	       					                    <div class="inner" style="display: none;">
													<div class="field">
						                                <input type="hidden" name="name" value="{$name}" />
														<input type="hidden" name="action" value="removeprevioushumans" />
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<label>Days:</label>
														<input name="days" type="text" class="textbox" value="14">
														<p class="help">Enter the number of days to check back to</p>
													</div>
													<div class="form-controls">
						                                <button type="submit" name="submit">Abandon targets</button>
													</div>
				                                </div>
				                                <div class="clear-both"></div>
					                        </fieldset>
						                </form>

						                <form action="?name={$name}" method="post" class="common-form">
						                    <fieldset>
												<legend onclick="$(this).siblings('.inner').toggle();">Compute average call duration</legend>
	        					                <div class="inner" style="display: none;">
													<div class="column">
														<div class="field">
								                            <input type="hidden" name="name" value="{$name}" />
															<input type="hidden" name="action" value="averageduration" />
															<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
															<label>Item:</label>
															<input name="item" type="text" value="0_AMD" class="textbox" />
														</div>
														<div class="form-controls">
								                            <button type="submit" name="submit">Compute</button>
														</div>
													</div>
													<div class="column">
														<div class="field">
															<label>Value:</label>
															<input type="text" name="value" value="HUMAN" class="textbox" />
														</div>
													</div>
													<div class="clear-both"></div>
						                        </div>
						                        <div class="clear-both"></div>
						                    </fieldset>
						                </form>
{/if}
						                <form action="?name={$name}" method="post" class="common-form" onsubmit="javascript: return (String(prompt('Are you sure you want to remove these records?\n\nPlease type OK to confirm.')).toLowerCase() == 'ok');">
						                    <fieldset>
												<legend onclick="$(this).siblings('.inner').toggle();">Abandon data</legend>
	        					                <div class="inner" style="display: none;">
													<div class="column">
														<div class="field">
															<label>Value / Start of range:</label>
															<input type="text" name="startofrange" class="textbox" maxlength="100" />
															<p class="help">The specific value or start of range that you want to mark as abandoned.</p>
														</div>
														<div class="field">
								                            <input type="hidden" name="name" value="{$name}" />
															<input type="hidden" name="action" value="abandondata" />
															<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
															<label>Search field</label>
															<select id="abandonelements" class="mediumdata selectbox" name="element"></select>
														</div>
														<div class="form-controls">
								                            <button type="submit" name="submit">Compute</button>
														</div>
													</div>
													<div class="column">
														<div class="field">
															<label>End of range:</label>
															<input type="text" name="endofrange" class="textbox" maxlength="100" />
															<p class="help">Optional: If you want to mark a range as abandoned, this is the end of the range.</p>
														</div>
													</div>
													<div class="clear-both"></div>
						                        </div>
						                        <div class="clear-both"></div>
						                    </fieldset>
						                </form>
						        </div>
						</div>

					</div>

					<p></p>

					<h3 class="secondary-header"><span id="refresh_stats" style="float: right; background-position: -280px -20px;" class="famfam" title="Refresh page" alt="Refresh page" onclick="javascript: {if ($type == "phone") OR ($type == "wash")}loadPhone('{$name}');{elseif $type == "sms"}loadSMS('{$name|escape:'quotes'}');{else}$('#statistics').load('admin_ui_targets.php', {ldelim}'campaignid' : {$campaignid}{rdelim}); return false;{/if}"></span><span id="targetsloading" style="float: right; display: block;">&nbsp;<img src="/img/ajax-loading-bar-18.gif" title="Loading..." alt="Loading..." />&nbsp;</span>Statistics</h3>

					<div id="statistics">
{if ($type == "phone") OR ($type == "wash")}
						<div style="float: left; width: 150px;">
							<p>
								Total<br />
								Ready<br />
								In Progress<br />
								Reattempt<br />
								Abandoned<br />
								Complete
							</p>
						</div>
						<div style="float: left; width: 150px; text-align: center;">
							<p>
								<span id="targets-targets">0</span><br />
								<span id="targets-ready">0</span><br />
								<span id="targets-inprogress">0</span><br />
								<span id="targets-reattempt">0</span><br />
								<span id="targets-abandoned">0</span><br />
								<span id="targets-complete">0</span><br />
							</p>
						</div>
						<div style="float: left; width: 150px;">
							<p>
								Calls<br />
								Answered<br />
								Busy<br />
								Ringouts<br />
								Disconnected<br />
								Call issue
							</p>
						</div>
						<div style="float: left; width: 150px; text-align: center;">
							<p>
								<span id="targets-calls">0</span><br />
								<span id="targets-answered">0</span><br />
								<span id="targets-busy">0</span><br />
								<span id="targets-ringout">0</span><br />
								<span id="targets-disconnected">0</span><br />
								<span id="targets-chanunavail">0</span>
							</p>
						</div>
{elseif $type == "sms"}
						<div id="target_chart" style="float: left; height: 275px;width: 300px; text-align: center;"></div>
						<p>
							<span style="background: #000000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-targets">0.0</span> recipients loaded<br />
							<span style="background: #cccc33;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-duplicates-percent">0.0</span>% of recipients were duplicates. (<span class="subtext"><span id="targets-duplicates">0</span> recipients removed)</span><br />
							<span style="background: #cccc33;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-removed-percent">0.0</span>% of recipients previously unsubscribed or bounced. (<span class="subtext"><span id="targets-removed">0</span> recipients removed)</span><br />
							<span style="background: #999999;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-notyetsent-percent">0.0</span>% of the messages have not yet been sent. <span class="subtext">(<span id="targets-notyetsent">0</span> messages not sent yet)</span><br />
							<span style="background: #000000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-sent-percent">0.0</span>% of the messages have been sent. <span class="subtext">(<span id="targets-sent">0</span> messages sent)</span><br />
							<br />
							<span style="background: #269e01;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-delivered-percent">0.0</span>% of messages were delivered. <span class="subtext">(<span id="targets-delivered">0</span> messages were delivered)</span><br />
							<span style="background: #068dc7;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-intheair-percent">0.0</span>% of messages are still in the air. <span class="subtext">(<span id="targets-intheair">0</span> messages still in the air)</span><br />
							<span style="background: #e94e15;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-disconnected-percent">0.0</span>% of messages were sent to disconnected numbers. <span class="subtext">(<span id="targets-disconnected">0</span> could not be delivered)</span><br />
							<span style="background: #e94e15;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-unknown-percent">0.0</span>% of recipients have barring or were otherwise undeliverable. <span class="subtext">(<span id="targets-unknown">0</span> could not be delivered)</span><br />
							<span style="background: #0665c7;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-expired-percent">0.0</span>% of messages expired before they could be delivered. <span class="subtext">(<span id="targets-expired">0</span> messages were undelivered)</span><br />
							<br />
							<span style="background: #000000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-optout-percent">0.0</span>% of recipients opted out. <span class="subtext">(<span id="targets-optout">0</span> opt outs received)</span><br />
							<span style="background: #000000;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;<span id="targets-responses-percent">0.0</span>% of recipients responded. <span class="subtext">(<span id="targets-responses">0</span> responses received)</span><br />
						</p>
{/if}
						<div class="clear-both"></div>
					</div>

					<p></p>
{if $type != "wash"}
					<h3 class="secondary-header">Responses</h3>
					<div id="responses">
						<div style="text-align: center;">
							<a href="#" onclick="javascript: show_charts = true; $('#refresh_stats').click(); $(this).remove(); return false;" style="text-align: center;">show</a>
						</div>
					</div>
{/if}
				</div>
