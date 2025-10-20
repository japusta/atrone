<?php /** @var array $plot */ ?>
<div class="modal_head">
    <i class="icon_close" onclick="common.modal_hide()"></i>
</div>
<div class="modal_body">
    <div class="input_group_modal">
        <div>Status</div>
        <select id="status">
            <option value="0"<?= (int) ($plot['status_id'] ?? 0) === 0 ? ' selected' : '' ?>>Free</option>
            <option value="1"<?= (int) ($plot['status_id'] ?? 0) === 1 ? ' selected' : '' ?>>Reserved</option>
            <option value="2"<?= (int) ($plot['status_id'] ?? 0) === 2 ? ' selected' : '' ?>>Sold</option>
        </select>
    </div>
    <div class="input_group_modal">
        <div>Billing</div>
        <select id="billing">
            <option value="0"<?= (int) ($plot['billing'] ?? 0) === 0 ? ' selected' : '' ?>>No</option>
            <option value="1"<?= (int) ($plot['billing'] ?? 0) === 1 ? ' selected' : '' ?>>Yes</option>
        </select>
    </div>
    <div class="input_group_modal">
        <div>Lot number</div>
        <input type="text" id="number" value="<?= htmlspecialchars($plot['number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="input_group_modal">
        <div>Size, acres</div>
        <input type="text" id="size" value="<?= htmlspecialchars($plot['size'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="input_group_modal">
        <div>Price, AED</div>
        <input type="text" id="price" value="<?= htmlspecialchars($plot['price'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="modal_controls">
        <div>
            <div class="btn_modal" onclick="common.plot_edit_update(<?= (int) ($plot['id'] ?? 0) ?>);">Save</div>
        </div>
        <div>
            <div class="btn_modal light" onclick="common.modal_hide();">Cancel</div>
        </div>
    </div>
</div>
