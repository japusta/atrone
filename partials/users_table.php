<?php /** @var array $users */ ?>
<table>
    <tr>
        <th>Plot ID</th>
        <th>First name</th>
        <th>Last name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Last login</th>
        <th></th>
    </tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['plot_id'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['phone_str'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['last_login'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="right_column">
                <i class="icon icon_ellipsis" onclick="common.menu_popup_toggle(this, event);">
                    <div class="menu_popup">
                        <div onclick="common.user_edit_window(<?= (int) $user['id'] ?>, event);">Edit</div>
                        <div onclick="common.user_delete(<?= (int) $user['id'] ?>, event);">Delete</div>
                    </div>
                </i>
            </td>
        </tr>
    <?php endforeach; ?>
</table>