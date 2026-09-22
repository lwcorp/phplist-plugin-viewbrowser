<?php

namespace phpList\plugin\ViewBrowserPlugin;

function displayPublicPage($page, $title)
{
    global $pagedata, $PoweredBy;

    $title = htmlspecialchars($title);

    echo <<<END
<title>$title</title>
{$pagedata['header']}
$page
$PoweredBy
{$pagedata['footer']}
END;
}

$container = include __DIR__ . '/dic.php';
$archive = $container->get('ArchiveCreator');
$translator = $container->get('FrontendTranslator');
$title = $translator->s('Campaign archive');

if (!empty($_GET['uid'])) {
    displayPublicPage($archive->createSubscriberArchive($_GET['uid']), $title);

    return;
}

if (isset($_GET['list']) && ctype_digit($_GET['list'])) {
    $result = getConfig('viewbrowser_anonymous')
        ? $archive->createListArchive($_GET['list'])
        : $translator->s('Not allowed to view campaigns for list %d', $_GET['list']);
} else {
    $result = $translator->s('A user uid or a list id must be specified');
}
displayPublicPage($result, $title);
