<?php
/** @var string $mainContentHtml */
/** @var array $global */
/** @var int $offset */
/** @var string $search */
$path = $global['path'] ?? '';
$offsetValue = isset($offset) ? (int) $offset : 0;
$searchValue = isset($search) ? (string) $search : '';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    <link rel="stylesheet" type="text/css" media="all" href="/css/fonts.css?1" />
    <link rel="stylesheet" type="text/css" media="all" href="/css/common.css?1" />
    <script type="text/javascript" src="/js/lib/common.js?1"></script>
    <script type="text/javascript" src="/js/common.js?1"></script>
    <script type="text/javascript">
        var global = {};
        <?php if ($path === 'plots' || $path === 'users'): ?>
        global.offset = <?= $offsetValue ?>;
        global.search_value = <?= json_encode($searchValue, JSON_UNESCAPED_UNICODE) ?>;
        <?php endif; ?>
    </script>
</head>
<body>
    <?= $mainContentHtml ?>
</body>
</html>
