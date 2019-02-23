<?php if ($show_email_member):?>
<a href="<?=ee('CP/URL')->make('utilities/communicate/member/' . $member->member_id)?>" class="action"><?=lang('v:email_member')?></a>
<?php endif;?>

<?php if ($show_login_as_member):?>
<a href="<?=ee('CP/URL')->make('members/profile/login')->setQueryStringVariable('id', $member->member_id)?>" class="action"><?=lang('v:login_as_member')?></a>
<?php endif;?>

<?php if ($show_resend_activation):?>
<?php endif;?>

<?php if ($show_delete_member):?>
<?php endif;?>