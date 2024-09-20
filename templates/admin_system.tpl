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

                                	<div class="breadcrumbs">
                                		<ol>
                                			<li class="first"><a href="admin_servers.php">Voice Servers</a></li>
                                			<li><a href="admin_voice.php">Voice Suppliers</a></li>
                                			<li><a href="admin_sms_suppliers.php">SMS Suppliers</a></li>
                                			<li><a href="admin_hlr_suppliers.php">HLR Suppliers</a></li>
                                			<li><a href="admin_securityzones.php">Security Zones</a></li>
                                			<li><a href="admin_cron.php">Cron tasks</a></li>
                                		</ol>
                                		<div class="clear-both"></div>
                                	</div>

                                	<div id="settings">

                                		<script type="text/javascript">

                                			$(function() {ldelim}
												loadGlobalQueue();
                                				$( "#tabs" ).tabs({ldelim}
                                                                        active   : Cookies.get('activetab-system'),
                                                                        activate : function( event, ui ){ldelim}
                                                                                Cookies.set( 'activetab-system', ui.newTab.index(),{ldelim}
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
                                				<li><a href="#tabs-speech">Speech</a></li>
												<li><a href="admin_system.php?template=email">Email</a></li>
												<li><a href="admin_system.php?template=sms">SMS</a></li>
                                				<li><a href="#tabs-competitions">Competitions</a></li>
                                				<li><a href="#tabs-tools">Tools</a></li>
												<li><a href="#tabs-conferences">Conferences</a></li>
                                				<li><a href="admin_system.php?template=tags">Tags</a></li>

                                			</ul>

                                			<div id="tabs-speech">

                                				<form action="?" method="post" class="common-form">
                                					<fieldset>
                                						<legend>Text To Speech</legend>
                                						<div class="inner">
                                							<div class="column">
                                								<div class="field">
                                									<label>Generate Text To Speech file:</label>
                                									<input name="ttstext" value="" type="text" class="textbox" maxlength="100" />
                                									<p class="help">100 characters maximum</p>
                                								</div>
                                								<div class="form-controls">
                                									<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                									<button type="submit"  name="action" value="texttospeech">Generate</button>
                                								</div>
                                							</div>
                                							<div class="column">
                                								<div class="field">
                                									<label>Text To Speech voice:</label>
                                									<select name="ttsvoice" class="selectbox"><option label="Karen" value="ScanSoft Karen_Full_22kHz">Karen</option><option label="Lee" value="ScanSoft Lee_Full_22kHz">Lee</option><option label="Nicole" value="IVONA 2 Nicole">Nicole</option><option label="Russell" value="IVONA 2 Russell">Russell</option><option label="Tian-tian" value="Vocalizer Expressive Tian-tian Premium High 22kHz">Tian-tian</option><option label="Sin-ji" value="Vocalizer Expressive Sin-ji Premium High 22kHz">Sin-ji</option></select>
                                									<p class="help">&nbsp;</p>
                                								</div>
                                							</div>
                                							<div class="clear-both"></div>
                                						</div>
                                					</fieldset>
                                				</form>

                                				<form enctype="multipart/form-data" action="?" method="post" class="common-form">
                                					<fieldset>
                                						<legend>Speech Recognition:</legend>
                                						<div class="inner">
                                                                                        <div class="column">
                                        							<div class="field">
                                        								<input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
                                        								<label>Upload audio file:</label>
                                        								<input name="uploadedfile" type="file" class="file" />
                                        							</div>
                                        							<div class="form-controls">
                                        								<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                        								<button type="submit" name="action" value="speechrecognition">Upload File</button>
                                        							</div>
                                                                                        </div>
                                                                                        <div class="column">
                                                                                                <div class="field">
                                                                                                        <label>Phrase hints:</label>
                                                                                                        <input name="phrasehints" value="" type="text" class="textbox" maxlength="400" />
                                                                                                        <p class="help">A comma separated list of hints</p>
                                                                                                </div>
                                                                                        </div>
                                                                                        <div class="clear-both"></div>
                                						</div>
                                					</fieldset>
                                				</form>
                                			</div>


                                			<div id="tabs-tools">

                                				<form action="?" method="post" class="common-form">
                                					<fieldset>
                                						<legend>Restart daemons</legend>
                                						<div class="inner">
                                							<div class="column">
                                								<div class="form-controls">
                                									<input type="hidden" name="action" value="restartdaemons" />
                                									<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                									<button type="submit" name="submit">Restart</button>
                                								</div>
                                							</div>
                                							<div class="clear-both"></div>
                                						</div>
                                					</fieldset>
                                				</form>

												<form action="?" method="post" class="common-form">
													<fieldset>
														<legend>Process Queue</legend>
														<div class="inner">
															<div class="field">
																<h5>Queued Uploads</h5>
																<div id="upload-queue">
																	<span id="queue-loader" style="float:right;"><img src="/img/ajax-loading-bar-18.gif" title="Loading..." alt="Loading..." />&nbsp;</span>
																	<table id="upload-queue-table" class="common-object-table" style="min-width: 50%;">
																		<th>Uploaded</th>
																		<th>User Id</th>
																		<th>Campaign Id</th>
																		<th>Status</th>
																		<th>Ran At</th>
																		<th>Completed</th>
																		<th>Data</th>
																		<th>Actions</th>
																		<tbody id="upload-queue-table-body">

																		</tbody>
																	</table>
																</div>
															</div>
														</div>
													</fieldset>
												</form>


                                			</div>

                                			<div id="tabs-competitions">

                                				<form action="?" method="post" class="common-form">
                                					<fieldset>
                                						<legend>Export competition entries</legend>
                                						<div class="inner">
                                							<div class="column">
                                								<div class="field">
                                									<label>Start date:</label>
                                									<input name="startdate" value="{$smarty.now|date_format:"%F"}" type="text" class="textbox" maxlength="10" />
                                									<p class="help">In the format of "{$smarty.now|date_format:"%F"}"</p>
                                								</div>
                                								<div class="field">
                                									<label>End date:</label>
                                									<input name="enddate" value="{$smarty.now|date_format:"%F"}" type="text" class="textbox" maxlength="10" />
                                									<p class="help">In the format of "{$smarty.now|date_format:"%F"}"</p>
                                								</div>
                                								<div class="form-controls">
                                									<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                									<button type="submit" name="action" value="exportcompetition">Generate</button>
                                								</div>
                                							</div>
                                							<div class="column">
                                								<div class="field">
                                									<label>Competition name:</label>
                                									<select class="mediumdata selectbox" name="competition">{html_options values=$competitions output=$competitions}</select>
                                								</div>
                                							</div>
                                							<div class="clear-both"></div>
                                						</div>
                                					</fieldset>
                                				</form>

                                			</div>

                                                        <div id="tabs-conferences">

                                                                <form action="?" method="post" class="common-form">
                                                                        <fieldset>
                                                                                <legend>Conference reporting</legend>
                                                                                <div class="inner">
                                                                                        <div class="field">
                                                                                                <label>Group:</label>
                                                                                                <select class="mediumdata selectbox" name="groupid">{html_options options=$groups}</select>
                                                                                        </div>
                                                                                        <div class="field">
                                                                                                <label>Billing month:</label>
                                                                                                <input name="billingmonth" value="{$lastmonth|date_format:"%Y-%m"}" type="text" class="textbox" maxlength="7" />
                                                                                                <p class="help">In the format of "{$lastmonth|date_format:"%Y-%m"}"</p>
                                                                                        </div>
                                                                                        <div class="form-controls">
                                                                                                <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                                                                                <button type="submit" name="action" value="exportconferences">Generate</button>
                                                                                        </div>
                                                                                </div>
                                                                        </fieldset>
                                                                </form>

                                                        </div>

                                		</div>



                                	</div>
                                </div>
