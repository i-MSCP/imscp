<?php
$container = $this->navigation->getContainer();
/** @var iMSCP_pTemplate $this */
foreach($container as $page) {
    if($page->isActive(true)) {
        $leftMenu = $page;
        break;
    }
}
$sectionPage = $this->navigation->findActive($container, 0, 0)['page'];
$page = $this->navigation->findActive($container, 0)['page'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>{TR_PAGE_TITLE}</title>
    <meta charset="{THEME_CHARSET}">
    <meta name="robots" content="nofollow, noindex">
    <link rel="shortcut icon" href="{THEME_ASSETS_PATH}/images/favicon.ico">
    <link rel="stylesheet" href="{THEME_ASSETS_PATH}/css/jquery-ui-{THEME_COLOR}.css?v={THEME_ASSETS_VERSION}">
    <link rel="stylesheet" href="{THEME_ASSETS_PATH}/css/ui.css?v={THEME_ASSETS_VERSION}">
    <link rel="stylesheet" href="{THEME_ASSETS_PATH}/css/{THEME_COLOR}.css?v={THEME_ASSETS_VERSION}">
    <script>
        imscp_i18n = {JS_TRANSLATIONS};
    </script>
    <script src="{THEME_ASSETS_PATH}/js/jquery/jquery.js?v={THEME_ASSETS_VERSION}"></script>
    <script src="{THEME_ASSETS_PATH}/js/jquery/jquery-ui.js?v={THEME_ASSETS_VERSION}"></script>
    <script src="{THEME_ASSETS_PATH}/js/jquery/plugins/dataTables.js?v={THEME_ASSETS_VERSION}"></script>
    <script src="{THEME_ASSETS_PATH}/js/jquery/plugins/dataTables_naturalSorting.js?v={THEME_ASSETS_VERSION}"></script>
    <script src="{THEME_ASSETS_PATH}/js/jquery/plugins/pGenerator.js?v={THEME_ASSETS_VERSION}"></script>
    <script src="{THEME_ASSETS_PATH}/js/imscp.min.js?v={THEME_ASSETS_VERSION}"></script>
</head>
<body>
<div id="wrapper">
    <div class="header">
        <div class="main_menu">
<?= $this->navigation->menu()->renderMenu(null, [
    'ulClass'  => 'icons' . ($_SESSION['show_main_menu_labels'] ? ' show_labels' : ''),
    'indent'   => 12,
    'maxDepth' => 0,
]); ?>

        </div>
        <div class="logo">
            <img src="{ISP_LOGO}" alt="i-MSCP logo"/>
        </div>
    </div>
    <div class="location">
        <div class="location-area">
            <h1><?= $this->navigation->menu()->htmlify($sectionPage)?></h1>
        </div>
        <ul class="location-menu">
<?php if(isset($_SESSION['logged_from'])):?>
                <li>
                    <a class="backadmin" href="change_user_interface.php?action=go_back">
                        <?= tohtml(tr('%1$s you are now logged as %2$s', $_SESSION['logged_from'], $_SESSION['user_logged']))?>
                    </a>
                </li>
<?php endif ?>
            <li><a class="logout" href="/index.php?action=logout"><?= tohtml(tr('Logout'))?></a></li>
        </ul>
        <ul class="path">
            <?= $this->navigation->breadcrumbs()->setContainer($leftMenu)->setMinDepth(0) ?>
        </ul>
    </div>
    <div class="left_menu">
<?= $this->navigation->menu()->renderMenu($leftMenu, [
    'ulClass'  => '',
    'indent'   => 8,
    'minDepth' => 0,
    'maxDepth' => 0,
    'onlyActiveBranch' => true,
    'renderParents' => false,
]); ?>

    </div>
    <div class="body">
        <h2 class="<?= tohtml($page->get('title_class')) ?>"><span><?= tohtml($page->getLabel()) ?></span></h2>
        <!-- BDP: page_message -->
        <div class="{MESSAGE_CLS}">{MESSAGE}</div>
        <!-- EDP: page_message -->
        {LAYOUT_CONTENT}
    </div>
</div>
<div class="footer">
    <?php $config = iMSCP_Registry::get('config'); ?>
    i-MSCP <?= tohtml($config['Version'] ?: tr('Unknown'))?><br>
    Build: <?= tohtml($config['Build'] ?: tr('Unknown')) ?><br>
    Codename: <?= tohtml($config['CodeName'] ?: tr('Unknown'))?>
</div>
</body>
</html>
