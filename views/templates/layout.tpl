<?php
use oat\tao\helpers\Template;
use oat\tao\helpers\Layout;
use oat\tao\model\theme\Theme;

$releaseMsgData = Layout::getReleaseMsgData();

// yellow bar if
// never removed by user
// and version considered unstable resp. sandbox
$hasVersionWarning = empty($_COOKIE['versionWarning'])
    && !!$releaseMsgData['msg']
    && ($releaseMsgData['is-unstable']
    || $releaseMsgData['is-sandbox']);
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
    <?= Layout::getAmdLoader(Template::js('loader/tao.min.js', 'tao'), 'controller/app', get_data('client_params')) ?>
</head>
<body class="proctoring-scope">
<?php Template::inc('blocks/requirement-check.tpl', 'tao'); ?>
<div class="content-wrap<?php if (!get_data('showControls')) :?> no-controls<?php endif; ?>">
    <?php if (get_data('showControls')){
        Template::inc('blocks/header.tpl', 'ltiProctoring');
    }?>
    <?php /* alpha|beta|sandbox message */
    if($hasVersionWarning) {
        Template::inc('blocks/version-warning.tpl', 'tao');
    }?>


    <div id="feedback-box"></div>
    <?php /* actual content */
    $contentTemplate = Layout::getContentTemplate();
    Template::inc($contentTemplate['path'], $contentTemplate['ext']); ?>
</div>
<?php if (get_data('showControls')){
    echo Layout::renderThemeTemplate(Theme::CONTEXT_FRONTOFFICE, 'footer');
}?>
<div class="loading-bar"></div>
</body>
</html>
