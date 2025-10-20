<?php /** @var array $user */ ?>
<div class="modal_head">
    <i class="icon_close" onclick="common.modal_hide()"></i>
</div>
<div class="modal_body">
    <div class="input_group_modal">
        <div>First name</div>
        <input type="text" id="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="input_group_modal">
        <div>Last name</div>
        <input type="text" id="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="input_group_modal">
        <div>Phone</div>
        <input type="text" id="phone" value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="input_group_modal">
        <div>Email</div>
        <input type="text" id="email" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="input_group_modal">
        <div>Plots</div>
        <input type="text" id="plots" value="<?= htmlspecialchars($user['plot_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Example: 101, 102">
    </div>
    <div class="modal_errors" id="modal_errors"></div>
    <div class="modal_controls">
        <div>
            <div class="btn_modal" onclick="common.user_edit_update(<?= (int) ($user['id'] ?? 0) ?>);">Save</div>
        </div>
        <div>
            <div class="btn_modal light" onclick="common.modal_hide();">Cancel</div>
        </div>
    </div>
</div>
