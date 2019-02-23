<div class="visitor-field visitor-form">
    <h4><?=lang('v:user_form_header')?></h4>

    <div class="inner">
        <?php if (ee()->session->userdata('member_id') == $member->member_id):?>
        <div class="alert inline warn"><?=lang('v:warning_own_account')?></div>
        <?php endif;?>

        <input type="hidden" name="<?=$fieldname?>[member_id]" value="<?=$member->member_id?>">
        <?=$form_html?>
    </div>
</div> <!-- .visitor-field -->