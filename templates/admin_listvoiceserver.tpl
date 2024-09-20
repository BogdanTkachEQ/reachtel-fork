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

                                        <!-- Pagination -->
                                        {if !empty($paginate_template)}{include file=$paginate_template}{else}<div>&nbsp;</div>{/if}
                                        <!-- /Pagination -->

					<form action="?" method="post" class="common-form">
						<fieldset>
							<div class="inner">
								<div class="column">
									<div class="field">
										<label>Active:</label>
										<select class="mediumdata selectbox" name="setting[status]">{html_options options=$status selected=$status_selected}</select>
										<p class="help">Servers status</p>
									</div>
									<div class="field">
										<label>Server Address:</label>
										<input class="textbox" type="text" name="setting[ip]" value="{$setting.ip}" />
										<p class="help">Servers IP address or host name</p>
									</div>
									<div class="form-controls">
										<input type="hidden" name="serverid" value="{$serverid}" />
										<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
	                                                                	<button type="submit" name="submit">Save</button>
		                                                        </div>
								</div>
								<div class="column">
									<div class="field">
										<label>Site identifier:</label>
										<input class="textbox" type="text" name="setting[siteidentifier]" value="{$setting.siteidentifier|default:''}" />
										<p class="help">Server grouping identifier</p>
									</div>
								</div>
								<div class="clear-both"></div>
							</div>
						</fieldset>
					</form>

				</div>
