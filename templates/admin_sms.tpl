{assign var='is_child_view'  value=(isset($child_view) && $child_view)}

<!-- Main Common Content Block -->
<div class="main-common-block">


    <form action="?" method="post" class="common-form">
        <input type="hidden" name="action" value="createinboundsms"/>
        <fieldset>
            <legend>Generate Inbound SMS</legend>
            <div class="inner">
                <p class="subtext">Generate a fake inbound SMS which is injected into Morpheus.</p>

                <div class="field">
                    <label>DID</label>
                    <select name="sms_account" class="mediumdata selectbox">{html_options options=$dids}</select>
                    <p class="help"></p>
                </div>

                <div class="field">
                    <label for="mobile_number">Mobile Number</label>
                    <input name="mobile_number" value="" type="text" class="textbox"/>
                </div>

                <div class="field">
                    <label>SMS Received Date Time</label>
                    <input name="received_time" value="{$smarty.now|date_format:"%F %T"}" type="text" class="textbox" maxlength="10" />
                    <p class="help">In the format of "{$smarty.now|date_format:"%F %T"}"</p>
                </div>

                <div class="field">
                    <label for="content">SMS Content</label>
                    <textarea rows="10" cols="60" name="sms_content" value="" type="text" ></textarea>
                </div>

                <div class="form-controls">
                    <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}"/>
                    <button type="submit" name="submit">Generate</button>
                </div>
            </div>
        </fieldset>
    </form>
</div>