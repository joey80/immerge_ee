<div id="visitor-install" class="box">
    <?=form_open(ee('CP/URL', $baseUri.'install'))?>

    <div class="tbl-ctrls">
        <h1><?=lang('v:installation')?></h1>
        <!--
        <div class="alert inline warn">
            <p><?=lang('v:install_exp_1')?></p>
            <p class="enhance"><?=lang('v:install_exp_2')?></p>
        </div>
        -->
        <table cellspacing="0" style="width:100%">
            <thead>
                <tr>
                    <th style="width:50px">Step</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
            <?php $count = 0;?>
            <?php foreach($steps as $stepKey => $step):?>
            <?php $count++;?>
                <tr>
                    <td><?=$count?></td>
                    <td><?=lang('v:' . $stepKey)?></td>
                    <td>
                        <?php if ($step['status'] == 'success'):?>
                            <span class="st-open">Done</span>
                        <?php else:?>
                            <span class="st-pending">Pending</span>
                        <?php endif;?>
                    </td>
                    <td><small><?=$step['msg']?></small></td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
        <fieldset class="form-ctrls">
            <input class="btn" type="submit" value="Start Install">
        </fieldset>
    </div>
    <?php form_close();?>
</div>