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

						<div id="user">

							<script type="text/javascript">

								$(function() {ldelim}
									$( "#tabs" ).tabs({ldelim}
										active   : Cookies.get('activetab-user-{$setting.username}'),
										activate : function( event, ui ){ldelim}
											Cookies.set( 'activetab-user-{$setting.username}', ui.newTab.index(),{ldelim}
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

								function newPassword() {ldelim}

									var charset = "0123456789";
									charset += "abcdefghijklmnopqrstuvwxyz";
									charset += "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

									var length = 10;

									var password = "";

									for (var i = 0; i < length; i++)
										password += charset.charAt(randomInt(charset.length));

									if(meetsPasswordPolicy(password)) return password;
									else return newPassword();

								{rdelim}

								function meetsPasswordPolicy(password) {ldelim}

									if(password.length != 10) return false;
									else if (!password.match(/[0-9]/)) return false;
									else if (!password.match(/[a-z]/)) return false;
									else if (!password.match(/[A-Z]/)) return false;
									else return true;

								{rdelim}

								// Returns a random integer in the range [0, n) using a variety of methods
								function randomInt(n) {ldelim}
									var x = randomIntMathRandom(n);
									x = (x + randomIntBrowserCrypto(n)) % n;
									return x;
								{rdelim}


								// Not secure or high quality, but always available
								function randomIntMathRandom(n) {ldelim}
									var x = Math.floor(Math.random() * n);
									if (x < 0 || x >= n)
										throw "Arithmetic exception";
									return x;
								{rdelim}


								// Uses a secure, unpredictable random number generator if available; otherwise returns 0
								function randomIntBrowserCrypto(n) {ldelim}
									if (typeof Uint32Array == "function" && "crypto" in window && "getRandomValues" in window.crypto) {ldelim}
										// Generate an unbiased sample
										var x = new Uint32Array(1);
										do window.crypto.getRandomValues(x);
										while (x[0] - x[0] % n > 4294967296 - n);
										return x[0] % n;
									{rdelim} else
										return 0;
								{rdelim}

								function addSecurityZones(zones) {ldelim}
									$.each(zones, function( k, zone ) {ldelim}
										$("#securityzones option[value='" + zone + "']").prop("selected", true);
									{rdelim});
								{rdelim}

							</script>

							<div id="tabs" style="width: 100%; border: none;">
								<ul style="width: 100%;">
									<li><a href="#tabs-personal">Personal</a></li>
					{if api_security_isadmin($smarty.session.userid)}
									<li><a href="#tabs-security">Security</a></li>
									<li><a href="#tabs-api">API</a></li>
									<li><a href="#tabs-miscellaneous">Miscellaneous</a></li>
									<li><a href="?name={$name|urlencode}&template=tags">Tags</a></li>
					{/if}
								</ul>

								<div id="tabs-personal">
									<form action="?name={$name}" method="post" class="common-form">
										<fieldset style="padding-bottom: 15px">
											<legend>Personal information - {$setting.username} (user ID: {$id})</legend>
											<div class="inner">
												<div class="column">
													<div class="field">
														<label>First name *</label>
														<input class="textbox" type="text" name="setting[firstname]" value="{$setting.firstname|default:""}" required tabindex="1" />
														<p class="help">The user's first name</p>
													</div>
													<div class="field">
														<label>Description</label>
														<input class="textbox" type="text" name="setting[description]" value="{$setting.description|default:""}" tabindex="3" />
														<p class="help">The purpose or use for this account</p>
													</div>
													<div class="field">
														<label>Default region</label>
														<select class="selectbox" name="setting[region]" tabindex="5">
															{html_options options=$regions values=$regions selected=$setting.region|default:'Australia'}
														</select>
														<p class="help">Sets the default format for phone numbers</p>
													</div>
													<div class="field">
														<label>Update password</label>
														<input id="password" class="textbox" type="password" name="setting[password]" value="" autofill="off" autocomplete="off" />
														<p class="help"><a href="#" onclick="$('#password').val(prompt('New password:', newPassword())); return false;">Auto-generate new password</a>. <a href="#" onclick="javascript: if(confirm('Send password reset email?')) { window.location='?action=sendpasswordreset&amp;name={$name|urlencode}&amp;csrftoken={$smarty.session.csrftoken|default:''}'; }; return false;"><span class="famfam" title="Send password reset email" alt="Send password reset email" style="background-position: -180px -180px;"></span></a></p>
													</div>
													{if api_security_isadmin($smarty.session.userid)}
														<div class="field">
															<label>Enable Login Using Google Authenticator</label>
															<input type="checkbox" name="setting[enableGA]" {if !empty($enableGA) AND ($enableGA)}CHECKED{/if}/>
															<p class="help">Enables user to login using google auth token</p>
														</div>
													{/if}

													{if isset($google_auth_url)}
														<div class="field">
															<p>Scan the QR code below from google authenticator</p>
															<img src="{$google_auth_url}">
															<a href="#" onclick="javascript: if(confirm('Send Google Auth QR code email?')) { window.location='?action=sendgaqr&amp;name={$name}&amp;csrftoken={$smarty.session.csrftoken|default:''}'; }; return false;"><span class="famfam" title="Send Google Auth QR code email" alt="Send Google Auth QR code email" style="background-position: -180px -180px;"></span></a></p>

														</div>
													{/if}
													<div class="form-controls">
														<input type="hidden" name="name" value="{$name}" />
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<button type="submit" name="submit" tabindex="7">Save</button>
													</div>
												</div>
												<div class="column">
													<div class="field">
														<label>Last name *</label>
														<input class="textbox" type="text" name="setting[lastname]" value="{$setting.lastname|default:""}" required tabindex="2" />
														<p class="help">The user's last name</p>
													</div>
													<div class="field">
														<label>Email address *</label>
														<input class="textbox" type="email" name="setting[emailaddress]" value="{$setting.emailaddress|default:""}" required tabindex="4" />
														<p class="help">The user's email address</p>
													</div>
													<div class="field">
														<label>Time zone</label>
														<select class="selectbox" name="setting[timezone]" tabindex="6">
															{html_options output=$timezones values=$timezones selected=$setting.timezone|default:$smarty.const.DEFAULT_TIMEZONE}
														</select>
														<p class="help">Sets the time zone for the user</p>
													</div>
												</div>
												<div class="column">
													<div class="field">
														<label>User Type</label>
														<select class="selectbox" name="setting[usertype]" tabindex="6">
															<option value=""></option>
															{html_options output=$usertypes|Capitalize values=$usertypes selected=$setting.usertype}
														</select>
														<p class="help">
															Sets the user's type.  Be aware that this does not infer any access control or permissions - it's a categorisation only.
															<br />

															- Client: Regular user, last login date is monitored for activity and disabled if inactive<br />
															- Api: API only user, last login date is monitored for activity and disabled if inactive<br />
															- System: Internal system user<br />
															- Admin: Administrative user<br />

														</p>
													</div>
													<div class="field">
														<label>Account status</label>
														<select class="selectbox" name="setting[status]">
															<option value="{$USER_STATUS_INITIAL}" disabled="disabled"{if $setting.status eq $USER_STATUS_INITIAL or $setting.status eq $USER_STATUS_INITIAL_LEGACY} selected="selected"{/if}>Disabled (initial)</option>
															<option value="{$USER_STATUS_LOCKED}" disabled="disabled"{if $setting.status eq $USER_STATUS_LOCKED} selected="selected"{/if}>Disabled (locked out)</option>
															<option value="{$USER_STATUS_INACTIVE}" disabled="disabled"{if $setting.status eq $USER_STATUS_INACTIVE} selected="selected"{/if}>Disabled (inactive)</option>
															<option value="{$USER_STATUS_CLOSED}" disabled="disabled"{if $setting.status eq $USER_STATUS_CLOSED} selected="selected"{/if}>Disabled (closed)</option>
															<option value="{$USER_STATUS_DISABLED}"{if $setting.status eq $USER_STATUS_DISABLED or $setting.status eq $USER_STATUS_DISABLED_LEGACY} selected="selected"{/if}>Disabled</option>
															{html_options options=$status selected=$setting.status}
														</select>
														<p class="help">Allow or restrict access to the account</p>
													</div>
												</div>
											</div>
										</fieldset>
									</form>
								</div>

					{if api_security_isadmin($smarty.session.userid)}
								<div id="tabs-security">
									<form action="?name={$name}" method="post" class="common-form">
										<fieldset style="padding-bottom: 15px">
											<legend>Security</legend>
											<div class="inner">
												<div class="column">
													<div class="field">
														<label>Hardware token public name</label>
														<input class="textbox" type="text" name="setting[yubikeypublic]" value="{$setting.yubikeypublic|default:""}" />
														<p class="help">Yubico public identifier</p>
													</div>
													<div class="field">
														<label>Two factor SMS destination</label>
														<input class="textbox" type="text" name="setting[smstokendestination]" value="{$setting.smstokendestination|default:""}" />
														<p class="help">Enter the mobile number to receive log on requets</p>
													</div>
													<div class="field">
														<label>Require two-factor for REST</label>
														<input type="checkbox" name="setting[requires2fa]" {if !empty($setting.requires2fa) AND ($setting.requires2fa == "on")}CHECKED{/if}/>
														<p class="help">Require two-factor authentication for REST requests</p>
													</div>
													<div class="field">
														<label>Security zones</label>
														<select id="securityzones" name="securityzones[]" class="selectbox" multiple="multiple" size="7">{html_options options=$security_zones values=$security_zones selected=$security_zones_selected}</select>
														<p class="help">Selects which actions this account can access or perform</p>
														<p class="help"><a href="#" onclick="addSecurityZones(['125', '92']); return false;">+ Email2SMS</a>.</p>
														<p class="help"><a href="#" onclick="addSecurityZones(['163','164','165','168','170','171','172','173']); return false;">+ Monitor</a>. <a href="#" onclick="addSecurityZones(['163','164','165','170','172','173','166','167','168','169','171']); return false;">+ Monitor user admin</a>.</p>
														<p class="help"><a href="#" onclick="addSecurityZones(['119','120']); return false;">+ REST SMS</a>. <a href="#" onclick="addSecurityZones(['117','118']); return false;">+ REST Wash</a>.
															<a href="#" onclick="addSecurityZones(['168','119','120','138','122','117','118','97']); return false;">+ Portal User</a>.
														</p>
													</div>
													<div class="field">
														<p class="help">
															Created: {$setting.created|date_format:"%H:%M:%S %d/%m/%Y"|default:unknown} {if !empty($setting.created)}({$setting.created|api_misc_timeformat}){/if}<br />
															Last authentication: {$setting.lastauth|date_format:"%H:%M:%S %d/%m/%Y"|default:never} {if isset($setting.lastauth)}({$setting.lastauth|api_misc_timeformat}){/if}<br />
															Last password reset: {$setting.passwordresettime|date_format:"%H:%M:%S %d/%m/%Y"|default:never} {if isset($setting.passwordresettime)}({$setting.passwordresettime|api_misc_timeformat}){/if}
														</p>
													</div>
													<div class="form-controls">
														<input type="hidden" name="name" value="{$name}" />
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<button type="submit" name="submit">Save</button>
													</div>
												</div>
												<div class="column">
													<div class="field">
														<label>Group owner *</label>
														<select name="setting[groupowner]" class="selectbox" required>
															<option value="">-- Select a group owner --</option>
															{html_options options=$user_groups values=$user_groups selected=$setting.groupowner}
														</select>
														<p class="help">Specify the group owner for invoice purposes</p>
													</div>
													<div class="field">
														<label>Google OAuth token</label>
														<input class="textbox" type="text" name="setting[oauthgoogle]" value="{$setting.oauthgoogle|default:""}" />
														<p class="help">Google OAuth token key</p>
													</div>
													<div class="field">
														<label>Hardware token private name</label>
														<input class="textbox" type="text" name="setting[yubikeyprivate]" value="{$setting.yubikeyprivate|default:""}" />
														<p class="help">Yubico private identifier</p>
													</div>
													<div class="field">
														<label>IP address restrictions</label>
														<input class="textbox" type="text" name="setting[ipaccesslist]" value="{$setting.ipaccesslist|default:""}" />
														<p class="help">A comma separated list of allowed IP address or hosts</p>
													</div>
													<div class="field">
														<label>User groups</label>
														<select name="usergroups[]" class="selectbox" multiple="multiple" size="7">
															{html_options options=$user_groups values=$user_groups selected=$user_groups_selected}
														</select>
														<p class="help">Selects which groups this user can access</p>
													</div>
												</div>
											</div>
										</fieldset>
									</form>
								</div>

								<div id="tabs-api">
									<form action="?name={$name}" method="post" class="common-form">
										<fieldset style="padding-bottom: 15px">
											<legend>API</legend>
											<div class="inner">
												<div class="column">
													<div class="field">
														<label>SMS DID</label>
														<select name="setting[smsapidid]" class="selectbox">{html_options options=$sms_dids values=$sms_dids selected=$setting.smsapidid}</select>
														<p class="help">SMS DID used for API sms</p>
													</div>
													<div class="field">
														<label>Job priority</label>
														<select class="selectbox" name="setting[jobpriority]">{html_options options=$jobpriority selected=$setting.jobpriority|default:"normal"}</select>
														<p class="help">How should these API requests be prioritised?</p>
													</div>
													<div class="field">
														<label>REST SMS delivery receipt postback URL</label>
														<input class="textbox" type="text" name="setting[{$smarty.const.USER_SETTING_RESTPOSTBACK_URL}]" value="{$setting["{$smarty.const.USER_SETTING_RESTPOSTBACK_URL}"]|default:""}" />
														<p class="help">URL to send REST SMS delivery receipts to</p>
														<input class="textbox" type="text" name="setting[{$smarty.const.USER_SETTING_RESTPOSTBACK_USERNAME}]" value="{$setting[{$smarty.const.USER_SETTING_RESTPOSTBACK_USERNAME}]|default:""}" placeholder="User (optional)" style="height: 5px; width: 35%;"/>
                                                        <input class="textbox" type="password" name="setting[{$smarty.const.USER_SETTING_RESTPOSTBACK_PASSWORD}]" autofill="off" autocomplete="new-password" value="" placeholder="Password {if empty($setting[{$smarty.const.USER_SETTING_RESTPOSTBACK_PASSWORD}])}(optional){else}is set{/if}" style="height: 5px; width: 35%;"/>
                                                        <p class="help">Optional username & password postback URL</p>
													</div>
													<div class="form-controls">
														<input type="hidden" name="name" value="{$name}" />
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<button type="submit" name="submit">Save</button>
													</div>
												</div>
												<div class="column">
													<div class="field">
														<label>Request POST limit</label>
														<select name="setting[apirequest.post.limit]" class="selectbox">{html_options options=$ratelimits values=$ratelimits selected=$setting["apirequest.post.limit"]}</select>
														<p class="help">POST requests per minute for the API rate limiting</p>
													</div>
													<div class="field">
														<label>Request GET limit</label>
														<select name="setting[apirequest.get.limit]" class="selectbox">{html_options options=$ratelimits values=$ratelimits selected=$setting["apirequest.get.limit"]}</select>
														<p class="help">GET requests per minute for the API rate limiting</p>
													</div>
													<div class="field">
														<label>Add delay to REST request</label>
														<input class="textbox" type="number" name="setting[restartificialdelay]" value="{$setting.restartificialdelay|default:""}" />
														<p class="help">Adds a delay to each REST call (in miliseconds)</p>
													</div>
												</div>
											</div>
										</fieldset>
									</form>
								</div>

								<div id="tabs-miscellaneous">
									<form action="?name={$name}" method="post" class="common-form">
										<fieldset style="padding-bottom: 15px">
											<legend>Miscelaneous settings</legend>
											<div class="inner">
												<div class="column">
													<div class="field">
														<label>GUI pagination size</label>
														<input class="textbox" type="text" name="setting[guipagination]" value="{$setting.guipagination|default:5}" />
														<p class="help">Set the number of items to display in the GUI</p>
													</div>
													<div class="form-controls">
														<input type="hidden" name="name" value="{$name}" />
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<button type="submit" name="submit">Save</button>
													</div>
												</div>
												<div class="column">
												</div>
											</div>
										</fieldset>
									</form>
								</div>

					{/if}

							</div>

						</div>

					</div>
