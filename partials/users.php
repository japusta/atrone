<?php
/** @var array $users */
/** @var string $paginator */
/** @var string $search */
?>
<div class="sub_header">
    <div>
        <div class="btn_sub" onclick="common.user_edit_window();">Add</div>
    </div>
    <div>
        <div id="paginator"><?= $paginator ?></div>
        <div class="input_group">
            <i class="icon_search"></i>
            <input id="search" type="text" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search" oninput="common.search_do('users');">
        </div>
    </div>
</div>
<div id="table">
    <?php include __DIR__.'/users_table.php'; ?>
</div>