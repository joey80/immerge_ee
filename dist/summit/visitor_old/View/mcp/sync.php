<div id="visitor-sync" class="box">
    <?=form_open(ee('CP/URL', $baseUri.'sync-members'))?>
    <div class="tbl-ctrls">
        <h1><?=lang('v:sync_mdata')?></h1>

        <?=ee('CP/Alert')->get('shared-form')?>

        <div class="col-group">
            <div class="col w-6">
                <p><?=lang('v:sync_desc_0');?></p>
                <p><?=lang('v:sync_desc_1');?></p>
                <p><?=lang('v:sync_desc_2');?></p>
                <p><?=lang('v:sync_desc_3');?></p>
            </div>
            <div class="col w-1">&nbsp;</div>
            <div class="col w-9 setting-field">
                <strong>Standard Fields</strong>
                <div class="scroll-wrap">
                    <?php foreach ($fields as $field):
                        $selected = in_array($field, $fields_checked);
                    ?>
                    <label class="choice block<?php if ($selected):?> chosen<?php endif ?>">
                        <input type="checkbox" name="fields[]" value="<?=$field?>"<?php if ($selected):?> checked="checked" <?php endif;?> > <?=$field?>
                    </label>
                    <?php endforeach ?>
                </div>

                <strong>Custom Fields</strong>
                <div class="scroll-wrap">
                    <?php foreach ($custom_fields as $field_id => $fieldName):
                        $selected = in_array($field_id, $custom_fields_checked);
                    ?>
                    <label class="choice block<?php if ($selected):?> chosen<?php endif ?>">
                        <input type="checkbox" name="custom_fields[]" value="<?=$field_id?>"<?php if ($selected):?> checked="checked" <?php endif;?> > <?=$fieldName?>
                    </label>
                    <?php endforeach ?>
                </div>
            </div>
        </div>

        <fieldset class="form-ctrls">
            <input class="btn" type="submit" value="Start Sync">
        </fieldset>
    </div>
    <?php form_close();?>
</div>