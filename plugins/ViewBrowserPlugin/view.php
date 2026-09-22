<?php

namespace phpList\plugin\ViewBrowserPlugin;

$container = include __DIR__ . '/dic.php';
$translator = $container->get('FrontendTranslator');

if (!extension_loaded('xsl')) {
    echo $translator->s('The xsl extension must be installed');
    exit;
}

if (!(isset($_GET['m']) && ctype_digit($_GET['m']))) {
    echo $translator->s('A numeric message id must be specified');
    exit;
}

if (getConfig('viewbrowser_anonymous')) {
    $uid = isset($_GET['uid']) ? $_GET['uid'] : '';
} else {
    if (!isset($_GET['uid'])) {
        echo $translator->s('A user uid must be specified');
        exit;
    }
    $uid = $_GET['uid'];
}

if (!(isset($plugins['CommonPlugin']))) {
    echo $translator->s('CommonPlugin must be installed');
    exit;
}
error_reporting(-1);
require 'admin/sendemaillib.php';

$creator = $container->get('ContentCreator');

ob_end_clean();
header('Content-Type: text/html; charset=UTF-8');
echo $creator->createContent($_GET['m'], $uid);
