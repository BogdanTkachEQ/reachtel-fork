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
						        <legend>{$name}</legend>
						        <div class="inner">
						                <div class="field">
						                        <label>HTML Template:</label>
									<textarea name="file_contents_html" id="file_contents_html" style="font-family: Consolas, Lucida Console, courier; font-size: 9pt; width: 100%; height: 400px;" wrap="off">{$file_contents_html}</textarea>
						                </div>
						                <div class="field">
						                        <label>Text Template:</label>
									<textarea name="file_contents_text" id="file_contents_text" style="font-family: Consolas, Lucida Console, courier; font-size: 9pt; width: 100%; height: 400px;" wrap="off">{$file_contents_text}</textarea>
						                </div>
						                <div class="field">
						                        <label>Attachment:</label>
									<input type="text" name="setting[attachment]" value="{$setting.attachment|default:""}" style="width: 250px;" />
						                </div>
						               <div class="field">
						                        <label>Group owner:</label>
						                        <select name="groupowner" style="width: 350px;">{html_options options=$user_groups values=$user_groups selected=$setting.groupowner}</select>
        						        </div>
						               <div class="field">
						                        <label><a href="{api_hosts_gettrack()}/view.php?tv={$encryptedid}" target="_blank">View template</a></label>
        						        </div>
						                <div class="form-controls">
									<input name="name" value="{$name}" type="hidden"/>
								        <input name="version" value="{$version|default:0}" type="hidden"/>
								        <input name="forceupdate" value="{$forceupdate|default:0}" type="hidden"/>
									<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
						                        <button type="submit" name="submit">Save</button>
						                </div>
						        </div>
				        	</fieldset>
					</form>

				</div>
