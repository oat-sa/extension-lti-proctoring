<?php
use oat\tao\helpers\Template;
use oat\tao\helpers\Layout;
use oat\tao\model\theme\Theme;
?><!doctype html>
<html class="no-js no-version-warning" lang="<?= tao_helpers_I18n::getLangCode() ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Layout::getTitle() ?></title>
    <?= tao_helpers_Scriptloader::render() ?>
    <link rel="stylesheet" href="<?= Template::css('tao-main-style.css', 'tao') ?>"/>
    <link rel="stylesheet" href="<?= Template::css('tao-3.css', 'tao') ?>"/>
    <link rel="stylesheet" href="<?= Template::css('proctoring.css', 'taoProctoring') ?>"/>
    <link rel="stylesheet" href="<?= Layout::getThemeStylesheet(Theme::CONTEXT_FRONTOFFICE) ?>"/>
    <link rel="shortcut icon" href="<?= Template::img('favicon.ico', 'tao') ?>"/>
    <?= Layout::getAmdLoader(Template::js('loader/backoffice.min.js', 'tao'), 'controller/backoffice') ?>
</head>
<body class="proctoring-scope">
<?php Template::inc('blocks/requirement-check.tpl', 'tao'); ?>
<div class="content-wrap">
    <div id="feedback-box"></div>
    <div class="delegated-view"
        <?php foreach (get_data('data') as $name => $value): ?>
            data-<?= $name; ?>="<?= _dh($value); ?>"
        <?php endforeach; ?>></div>
</div>
<div class="loading-bar"></div>
</body>
</html>
