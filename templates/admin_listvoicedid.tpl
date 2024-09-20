
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

{if $linked}
					<div class="breadcrumbs">
						<ol>
							<li>
								Linked SMS DID: <a href="admin_listsmsdid.php?name={$setting.name|urlencode}">{$setting.name}</a>
							</li>
						</ol>
						<div class="clear-both"></div>
					</div>
{/if}
                    <script type="text/javascript">

                        $(function() {ldelim}
                            $( "#tabs" ).tabs({ldelim}
                                active   : Cookies.get('activetab-sms-{$setting.name}'),
                                activate : function( event, ui ){ldelim}
                                    Cookies.set( 'activetab-sms-{$setting.name}', ui.newTab.index(),{ldelim}
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
                            <li><a href="#tabs-settings">Settings</a></li>
                            <li><a href="?name={$setting.name|urlencode}&template=campaigns">Campaigns</a></li>
                        </ul>

                        <div id="tabs-settings">
                            <form action="?name={$setting.name|urlencode}" method="post" class="common-form">
                                <fieldset>
                                    <legend>General information - {$name} (ID: {$id})</legend>
                                    <div class="inner">
                                        <div class="field">
                                            <label>Use:</label>
                                            <input name="setting[use]" value="{$setting.use|default:""}" type="text" class="textbox" />
                                            <p class="help">Purpose of this DID</p>
                                        </div>
                                        <div class="field">
                                            <label>Group owner:</label>
                                            <select name="setting[groupowner]" class="mediumdata selectbox">{html_options options=$user_groups values=$user_groups selected=$setting.groupowner|default:2}</select>
                                        </div>
                                        <div class="form-controls">
                                            <input type="hidden" name="name" value="{$name}" />
                                            <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                            <button type="submit" name="submit">Save</button>
                                        </div>
                                    </div>
                                </fieldset>
                            </form>

                            <div class="common-form">
                                <fieldset>
                                    <legend>DID details</legend>
                                    <div class="inner">
                                        <div class="field">
                                            <label>Location:</label>
                                            <p>{$did.countryname|default:"Unknown"}</p>
                                        </div>
                                        <div class="field">
                                            <label>Number type:</label>
                                            <p>{$did.numbertype|default:"Unknown"}</p>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </div>
				</div>
