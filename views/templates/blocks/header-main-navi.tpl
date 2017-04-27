<?php
use oat\tao\helpers\Layout;
$mainMenu     = get_data('main-menu');
$settingsMenu = get_data('settings-menu');
$userLabel    = get_data('userLabel');
?>
<nav>
    <div class="settings-menu rgt">
        <ul class="clearfix plain">
            <li data-env="user" class="li-exit">
                <a id="exit" href="<?= get_data('exit') ?>" title="<?= __('Exit') ?>">
                    <span class="icon-logout glyph"></span>
                    <span class="text hidden exit-text"><?= __("Exit"); ?></span>
                </a>
            </li>
        </ul>
    </div>
</nav>

