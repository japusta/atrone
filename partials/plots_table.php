<?php /** @var array $plots */ ?>
<table>
    <tr>
        <th>Plot, number</th>
        <th>Size, acres</th>
        <th>Status</th>
        <th>Billing</th>
        <th>Price</th>
        <th>Owners</th>
        <th></th>
    </tr>
    <?php foreach ($plots as $plot): ?>
        <tr>
            <td><?= htmlspecialchars($plot['number'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $plot['size'], ENT_QUOTES, 'UTF-8') ?></td>
            <td<?= (int) $plot['status'] === 0 ? ' class="green"' : '' ?>><?= htmlspecialchars($plot['status_str'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int) $plot['billing'] === 1 ? 'Yes' : 'No' ?></td>
            <td><?= htmlspecialchars($plot['price'], ENT_QUOTES, 'UTF-8') ?> AED</td>
            <td>
                <?php if (!empty($plot['users'])): ?>
                    <?php foreach ($plot['users'] as $owner): ?>
                        <div><?= htmlspecialchars($owner['first_name'], ENT_QUOTES, 'UTF-8') ?>, <span class="gray"><?= htmlspecialchars($owner['phone_str'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td class="right_column">
                <i class="icon icon_ellipsis" onclick="common.menu_popup_toggle(this, event);">
                    <div class="menu_popup">
                        <div onclick="common.plot_edit_window(<?= (int) $plot['id'] ?>, event);">Edit</div>
                    </div>
                </i>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
