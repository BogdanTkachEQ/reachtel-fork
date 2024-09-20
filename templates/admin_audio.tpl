                                <!-- Main Common Content Block -->
                                <div class="main-common-block">

                                	{include file=$search_notification_pagination_template}

                                        <script type="text/javascript">

                                            function audioContentEdit(item) {ldelim}

                                                var oldcontent = (($('#audioContent' + item).text() != "unknown") ? $('#audioContent' + item).text() : "");

                                                var newcontent = prompt('New content:', oldcontent);

                                                if(newcontent != oldcontent) {ldelim}

                                                    if(newcontent != null) {ldelim}

                                                        $('#audioContent' + item).text(newcontent.trim())

                                                        if(newcontent.trim().length == 0) $('#audioContent' + item).text('unknown');

	                                                    jQuery.post('?', {ldelim} 'id' : item, 'action' : 'updatecontent', 'content' : newcontent.trim(), 'csrftoken': '{$smarty.session.csrftoken|default:''}' {rdelim}, function(result) {ldelim}
    	                                                    if((typeof result.status == 'undefined') || (result.status != "OK")) {ldelim}
        	                                                    alert("Failed to update content.");
            	                                            {rdelim}
                	                                    {rdelim}, 'json');
                    	                            {rdelim}
                        	                    {rdelim}
                        	                {rdelim}

                                        </script>

                                	<table class="campaignTableCenter common-object-table" width="1000px">
                                		<thead>
                                			<tr>
                                				<th style="min-width: 200px;">Audio File</th>
                                				<th>Content</th>
                                                <th style="width: 300px;">&nbsp;</th>
                                				<th style="width: 50px; text-align: center;">Delete</th>
                                			</tr>
                                		</thead>
                                		<tbody>
                                			{if !empty($files)}
                                			{foreach from=$files key=k item=v}
                                			<tr>
                                				<td><a href="admin_audio.php?action=play&name={$v.name|urlencode}">{$v.name|escape:html|substr:"0":"-4"|highlight:$search nofilter}</a></td>
                                				<td onclick="audioContentEdit('{$k}');"><i id="audioContent{$k}">{if !empty($v.content)}{$v.content|escape:html|default:""|highlight:$search nofilter}{else}unknown{/if}</i></td>
                                                <td><audio controls="true" preload="none"><source src="admin_audio.php?action=play&name={$v.name|urlencode}" type="audio/wav">Your browser does not support the audio element.</audio></td>
                                				<td style="width: 50px; text-align: center;"><a href="?action=delete&name={$v.name|urlencode}&amp;csrftoken={$smarty.session.csrftoken|default:''}" onclick="javascript: if(!confirm('Confirm delete?')) {ldelim} return false; {rdelim}"><span class="famfam" title="Delete File" alt="Delete File" style="background-position: -280px -160px;"></span></a></td>
                                			</tr>

                                			{/foreach}
                                			{else}
                                			<tr>
                                				<td colspan="4">No Audio</td>
                                			</tr>
                                			{/if}
                                		</tbody>
                                	</table>

                                	<form action="?" enctype="multipart/form-data" method="post" class="common-form">
                                		<fieldset>
                                			<legend>Upload an audio file</legend>
                                			<div class="inner">
                                				<div class="field">
                                					<label>Audio file:</label>
                                					<input type="hidden" name="MAX_FILE_SIZE" value="20000000" />

                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content"/><br />
                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content" /><br />
                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content" /><br />
                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content" /><br />
                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content" /><br />
                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content" /><br />
                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content" /><br />
                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content" /><br />
                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content" /><br />
                                					<input name="uploadedfile[]" type="file" />&nbsp;<input type="text" name="uploadcontent[]" placeholder="File content" /><br />

                                					<p class="help">Maximum 20MB per file</p>
                                				</div>
                                				<div class="form-controls">
                                					<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                					<button type="submit" name="submit">Upload</button>
                                				</div>
                                			</div>
                                		</fieldset>
                                	</form>

                                </div>