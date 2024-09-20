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

                                        <div id="smssupplier">

                                                <script type="text/javascript">

                                                        $(function() {ldelim}
                                                                $( "#tabs" ).tabs({ldelim}
                                                                        active   : Cookies.get('activetab-smssupplier-{$setting.name}'),
                                                                        activate : function( event, ui ){ldelim}
                                                                                Cookies.set( 'activetab-smssupplier-{$setting.name}', ui.newTab.index(),{ldelim}
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
                                                                <li><a href="?name={$setting.name|urlencode}&template=tags">Tags</a></li>
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
                                                                                                        <p class="help">Supplier status</p>
                                                                                                </div>
                                                                                                <div class="field">
                                                                                                        <label>SMS per second:</label>
                                                                                                        <input name="setting[smspersecond]" value="{$setting.smspersecond|default:2}" type="text" class="textbox" maxlength="25" />
                                                                                                        <p class="help">SMS per second</p>
                                                                                                </div>
                                                                                                <div class="form-controls">
													<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                                                                                        <button type="submit" name="submit">Save</button>
                                                                                                </div>
                                                                                        </div>
                                                                                        <div class="column">
                                                                                                <div class="field">
                                                                                                        <label>Provider priority:</label>
                                                                                                        <input name="setting[priority]" value="{$setting.priority|default:5}" type="text" class="textbox" maxlength="25" />
                                                                                                        <p class="help">The priority where a higher number is a higher priority</p>
                                                                                                </div>
                                                                                                <div class="field">
                                                                                                        <label>Capabilities</label>
                                                                                                        <select class="selectbox" name="capabilities[]" multiple="multiple">{html_options options=$capabilities values=$capabilities selected=$capabilities_selected}</select>
                                                                                                        <p class="help">Sets the providers capabilities</p>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                <div style="clear: both;">&nbsp;</div>
                                                                        </fieldset>
                                                                </form>
                                                        </div>
                                                </div>
                                        </div>
                                </div>
