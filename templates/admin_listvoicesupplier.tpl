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

					<form action="?" method="post" class="common-form">
					        <fieldset>
							<input name="id" value="{$id}" type="hidden" />
						        <legend>{$name} ({$id})</legend>
						        <div class="inner">
								<div class="column">
							                <div class="field">
							                        <label>Status:</label>
							                        <select class="selectbox" name="setting[status]">{html_options options=$status selected=$setting.status}</select>
							                        <p class="help">Supplier status</p>
							                </div>
                                                	                <div class="field">
                                                        	                <label>Voice server</label>
                                                                	        <select class="selectbox" name="setting[voiceserver]">{html_options options=$voiceservers values=$voiceservers selected=$setting.voiceserver}</select>
                                                                        	<p class="help">Sets the voice server to send calls from</p>
	                                                                </div>
							                <div class="field">
							                        <label>Calls per second:</label>
						        	                <input name="setting[callspersecond]" value="{$setting.callspersecond|default:2}" type="text" class="textbox" maxlength="5" />
						                	        <p class="help">Calls per second</p>
							                </div>
							                <div class="field">
							                        <label>Maximum channels:</label>
						        	                <input name="setting[maxchannels]" value="{$setting.maxchannels|default:10}" type="text" class="textbox" maxlength="5" />
						                	        <p class="help">Maximum channels for this supplier</p>
							                </div>
							                <div class="field">
							                        <label>Provider priority:</label>
						        	                <input name="setting[priority]" value="{$setting.priority|default:5}" type="text" class="textbox" maxlength="2" />
						                	        <p class="help">The priority where a higher number is a higher priority</p>
							                </div>
									<div class="field">
										<label>Capabilities</label>
										<select class="selectbox" name="capabilities[]" multiple="multiple">{html_options options=$capabilities values=$capabilities selected=$capabilities_selected}</select>
										<p class="help">Sets the providers capabilities</p>
									</div>

							                <div class="form-controls">
										<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
							                        <button type="submit" name="submit">Save</button>
						        	        </div>
								</div>
								<div class="column">
							                <div class="field">
							                        <label>Host:</label>
						        	                <input name="setting[host]" value="{$setting.host}" type="text" class="textbox" maxlength="50" />
						                	        <p class="help">IP address for the supplier</p>
							                </div>
							                <div class="field">
							                        <label>Provider settings:</label>
										<textarea class="text" name="file_contents" style="height: 385px;">{$file_contents}</textarea>
							                </div>
						        	</div>
							</div>
							<div style="clear: both;">&nbsp;</div>
					        </fieldset>
					</form>


				</div>
