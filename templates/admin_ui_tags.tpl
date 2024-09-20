<table class="subinfo common-object-table" width="750px">
	<thead>
        	<tr>
            		<th class="subinfoHeader">Name</th>
            		<th class="subinfoHeader">Value</th>
					<th class="subinfoHeader">Encrypt</th>
            		<th class="subinfoHeader">&nbsp;</th>
          	</tr>
         </thead>
         <tr class="tags">
         	<td style="width: 200px;"><input type="text" style="text-align: left; width: 190px;" id="tagname"  onkeydown="if (event.keyCode == 13) document.getElementById('tagadd').click();" /></td>
                <td style="width: 500px;"><input type="text" style="text-align: left; width: 490px;" id="tagvalue" onkeydown="if (event.keyCode == 13) document.getElementById('tagadd').click();" /></td>
			 <td><input type="checkbox" style="text-align: left;width: 50px;" id="tagencryption"/></td>
			 	<td style="width: 50px;"><img id="tagadd" onclick="javascript: jQuery.post('?', {ldelim}'name' : '{$name|default:''}', 'action' : 'settag', 'tagname' : $('#tagname').val(), 'tagvalue' : $('#tagvalue').val(), 'encrypttag': $('#tagencryption').is(':checked') ? 1 : 0, 'csrftoken': '{$smarty.session.csrftoken|default:''}'{rdelim}, function() {ldelim} $('#tabs').tabs('load', $('#tabs').tabs('option', 'active')); {rdelim}); return false;"  src="{$TE_IMAGE_LOCATION}icons/add.png" height="16px" width="16px" alt="Add tag" title="Add tag" style="border: none;"/></td>
         </tr>
         <tbody>
{if !empty($tags)}
  {foreach from=$tags key=k item=v}
	  {assign var="is_encrypted" value=(isset($encrypted_tags) && in_array($k, $encrypted_tags))}
			<tr>
				<td style="width: 200px; cursor: pointer" ondblclick="javascript: $('#tagname').val($(this).text().trim()); $('#tagvalue').val(''); $('#tagvalue').focus();">{$k}</td>
				<td style="width: 500px; cursor: pointer" ondblclick="javascript: $('#tagname').val($(this).prev('td').text()); $('#tagvalue').val($(this).text().trim()); $('#tagvalue').focus();">
					{($is_encrypted)?'****************':$v}
				</td>
				<td style="width: 50px;">
					{if $is_encrypted}
						<span class="famfam" style="margin-left:17px;background-position: -182px -261px;" alt="Encrypted tag" title="Encrypted tag"></span>
					{/if}
				</td>
				<td style="width: 50px;"><span onclick="javascript: jQuery.post('?', {ldelim}'name' : '{$name|default:''}', 'action' : 'deletetag', 'tagname' : '{addcslashes($k, "'\\")}', 'csrftoken': '{$smarty.session.csrftoken|default:''}'{rdelim}, function() {ldelim} $('#tabs').tabs('load', $('#tabs').tabs('option', 'active')); {rdelim}); return false;"><span class="famfam" style="background-position: -280px -160px;" alt="Delete tag" title="Delete tag"></span></span></td>
			</tr>
  {/foreach}
{else}
	         <tr>
	         	<td colspan="3">No tags</td>
	         </tr>
{/if}
        </tbody>
</table>

{if !empty($tags) && isset($encrypted_tags)}
		<script>
			var encrypted_tags = {json_encode($encrypted_tags) nofilter};
		{literal}
			var value_changed = false;

			$('#tagname').keyup(function(e) {
				if (!value_changed && $('#tagencryption').prop('checked')) {
					return;
				}

				var matchTag = encrypted_tags.indexOf(e.target.value) !== -1;
				$('#tagencryption').prop('checked', matchTag).trigger('change');
				value_changed = matchTag;
			});

			$('#tagencryption').on('change', function() {
				var inputType = $(this).prop('checked') ? 'password' : 'text';
				$('#tagvalue').attr('type', inputType);
			});
        {/literal}
		</script>
{/if}
