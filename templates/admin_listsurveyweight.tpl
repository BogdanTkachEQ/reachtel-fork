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


<form action="?" method="post" class="common-form" id="surveyweight">
    <fieldset>
    <legend>{$name}</legend>
    <input type="hidden" name="id" value="{$id}" />
    <div class="inner">

	     <table class="campaignTable common-object-table" width="600px">
              <tr>
                <td>Age Bracket</td><td>MALE</td><td>FEMALE</td>
              </tr>
              <tr>
                <td>18 to 34 years</td><td><input class="mediumdata" type="text" name="setting[male18to34]" value="{(isset($setting.male18to34)) ? $setting.male18to34 : 0}" /></td><td><input class="mediumdata" type="text" name="setting[female18to34]" value="{(isset($setting.female18to34)) ? $setting.female18to34 : 0}" /></td>
              </tr>
              <tr>
                <td>35 to 50 years</td><td><input class="mediumdata" type="text" name="setting[male35to50]" value="{(isset($setting.male35to50)) ? $setting.male35to50 : 0}" /></td><td><input class="mediumdata" type="text" name="setting[female35to50]" value="{(isset($setting.female35to50)) ? $setting.female35to50 : 0}" /></td>
              </tr>
              <tr>
                <td>51 to 65 years</td><td><input class="mediumdata" type="text" name="setting[male51to65]" value="{(isset($setting.male51to65)) ? $setting.male51to65 : 0}" /></td><td><input class="mediumdata" type="text" name="setting[female51to65]" value="{(isset($setting.female51to65)) ? $setting.female51to65 : 0}" /></td>
              </tr>
              <tr>
                <td>65 plus</td><td><input class="mediumdata" type="text" name="setting[male65plus]" value="{(isset($setting.male65plus)) ? $setting.male65plus : 0}" /></td><td><input class="mediumdata" type="text" name="setting[female65plus]" value="{(isset($setting.female65plus)) ? $setting.female65plus : 0}" /></td>
              </tr>
	      <tr>
		<td>TOTAL:</td><td><span id="totalmale">0</span></td><td><span id="totalfemale">0</span></td>
	      </tr>
             </table>

	<div class="form-controls">
		<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
		<button type="submit" class="designated">Save</button>
	</div>

    </div>
    </fieldset>
</form>

<script type="text/javascript">

	sumSurveyWeights();

	$("input[type='text']").change(function(data) {ldelim} 
		sumSurveyWeights();
	{rdelim});

	function sumSurveyWeights(){ldelim}

		var male = 0;
		var female = 0;

		$("input[type='text']").each(function(){ldelim}
			if($.isNumeric($(this).val())){ldelim}
				if($(this).prop('name').indexOf('setting[male') == 0){ldelim}
					male = male + parseInt($(this).val());
				{rdelim} else if($(this).prop('name').indexOf('setting[female') == 0){ldelim}
					female = female + parseInt($(this).val());
				{rdelim}
			{rdelim}
		{rdelim});

		$('#totalmale').html(male);
		$('#totalfemale').html(female);
		

	{rdelim}

</script>

                                </div>

