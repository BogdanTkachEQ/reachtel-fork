                                <link rel="stylesheet" href="/css/codemirror/codemirror.css">
                                <link rel="stylesheet" href="/css/codemirror/dialog.css">
                                <link rel="stylesheet" href="/css/codemirror/matchesonscrollbar.css">

                                <script src="/js/codemirror/codemirror.js"></script>
                                <script src="/js/codemirror/asterisk.js"></script>
                                <script src="/js/codemirror/dialog.js"></script>
                                <script src="/js/codemirror/searchcursor.js"></script>
                                <script src="/js/codemirror/search.js"></script>
                                <script src="/js/codemirror/annotatescrollbar.js"></script>
                                <script src="/js/codemirror/matchesonscrollbar.js"></script>
                                <script src="/js/codemirror/jump-to-line.js"></script>
                                <style> .CodeMirror { height: {$dianplan_viewport_height}vh; } </style>

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
                                	{if !empty($paginate_template)}{include file=$paginate_template}{/if}
                                	<!-- /Pagination -->
                                        {if !empty($dialplan_errors)}
                                        <div class="flash flash-notice">{foreach from=$dialplan_errors item=error}<strong style="color: red">{$error}</strong><br/>{/foreach}</div>
                                        {/if}
                                	<p class="pagination"><strong>{$validation.audio.total}</strong> audio files identified: <strong>{$validation.audio.present}</strong> present; <span style="{if count($validation.audio.missing) > 0}color: red;{/if}"><strong>{$validation.audio.missing|@count}</strong> missing;</span> <strong>{$validation.audio.ambiguous|@count}</strong> ambiguous.</p>
                                    <p class="pagination"><strong>{$validation.goto.total}</strong> gotos identified: <strong>{$validation.goto.present}</strong> present; <span style="{if count($validation.goto.missing) > 0}color: red;{/if}"><strong>{$validation.goto.missing|@count}</strong> missing;</span> <strong>{$validation.goto.ambiguous|@count}</strong> ambiguous; <strong>{$validation.goto.external|@count}</strong> external.</p>
					<form action="?name={$name}" method="post" class="common-form">
                            			<fieldset>
                                            <legend>{$name} (ID: {$dialplanid})</legend>
                            				<div class="inner">
                                                            <div>
                                                                <a onclick="$('#codemirror_help').toggle(); return false;" style="font-size: 8pt; cursor:pointer;">want search tips?</a>
                                                                <div id="codemirror_help" class="common-form" style="font-size: 8pt; display: none; background: #f7f6dc;">
                                                                    <fieldset style="padding: 5px;">
                                                                    <strong>Ctrl-F / Cmd-F:</strong> Start searching<br/>
                                                                    <strong>Ctrl-G / Cmd-G:</strong> Find next<br/>
                                                                    <strong>Shift-Ctrl-G / Shift-Cmd-G:</strong> Find previous<br/>
                                                                    <strong>Shift-Ctrl-F / Cmd-Option-F:</strong> Replace<br/>
                                                                    <strong>Shift-Ctrl-R / Shift-Cmd-Option-F:</strong> Replace all<br/>
                                                                    <strong>Alt-F:</strong> Persistent search (dialog doesn't autoclose, enter to find next, Shift-Enter to find previous)<br/>
                                                                    <strong>Alt-G:</strong> Jump to line<br/>
                                                                    </fieldset>
                                                                </div>
                                                            </div>
                            					<div class="field">
                                                                    <textarea id="asterisk" name="file_contents" style="font-family: Consolas, Lucida Console, courier; height: 640px; width: 100%;" wrap="off">{$file_contents|default:"" nofilter}</textarea>
                            					</div>
                            					<div class="field">
                            						<label>Group owner:</label>
                            						<select name="groupowner" style="width: 350px;">{html_options options=$user_groups values=$user_groups selected=$user_groups_selected}</select>
                            					</div>
                            					<div class="form-controls">
                            						<input name="dialplanid" value="{$dialplanid}" type="hidden"/>
                            						<input name="version" value="{$version|default:0}" type="hidden"/>
                            						<input name="forceupdate" value="{$forceupdate|default:0}" type="hidden"/>
                            						<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                            						<button type="submit" name="submit">Save</button>
                            					</div>
                            				</div>
                            			</fieldset>
                            		</form>

                            		<p style="text-decoration: underline;"><strong>Missing audio files:</strong></p>
                            		{if $validation.audio.missing}
                            		{foreach from=$validation.audio.missing key=k item=v}
                            		<p>{$v}</p>
                            		{/foreach}
                            		{else}
                            		<p class="audiofiles errors">None.</p>
                            		{/if}

                                    <p style="text-decoration: underline;"><strong>Missing gotos:</strong></p>
                                    {if $validation.goto.missing}
                                    {foreach from=$validation.goto.missing key=k item=v}
                                    <p>{$v}</p>
                                    {/foreach}
                                    {else}
                                    <p class="audiofiles errors">None.</p>
                                    {/if}

                                    <p style="text-decoration: underline;"><strong>External gotos that are unchecked:</strong></p>
                                    {if $validation.goto.external}
                                    {foreach from=$validation.goto.external key=k item=v}
                                    <p>{$v}</p>
                                    {/foreach}
                                    {else}
                                    <p class="audiofiles errors">None.</p>
                                    {/if}
                                </div>
                                <script>
                                var editor = CodeMirror.fromTextArea(document.getElementById("asterisk"), {
	                              mode: "text/x-asterisk",
	                              matchBrackets: true,
	                              lineNumbers: true,
	                              autofocus: true,
	                              extraKeys: {ldelim} "Alt-F": "findPersistent"{rdelim}
	                            });
                                </script>
