<?php

namespace phpList\plugin\ViewBrowserPlugin;

use phpList\plugin\Common\FileServer;

error_reporting(-1);
ob_end_clean();

if (getConfig('viewbrowser_anonymous_attachments')) {
    // for readability
} elseif (isset($_GET['uid'])) {
    $uid = $_GET['uid'];
    $row = Sql_Fetch_Assoc_Query(
        sprintf('select exists (select * from %s where uniqid = "%s") as `exists`', $tables['user'], sql_escape($uid))
    );

    if (!$row['exists']) {
        FileNotFound();
    }
} else {
    FileNotFound();
}

if (isset($_GET['attach']) && ctype_digit($_GET['attach'])) {
    $attachId = $_GET['attach'];
    $row = Sql_Fetch_Assoc_Query("select filename,mimetype,remotefile from {$tables['attachment']} where id = $attachId");

    if (!$row) {
        FileNotFound();
    }
} else {
    FileNotFound();
}
$attachFilePath = $attachment_repository . '/' . $row['filename'];

if (!is_file($attachFilePath)) {
    FileNotFound();
}
$mimeType = $row['mimetype'] ?: 'application/octetstream';
$inlineTypes = explode(',', getConfig('viewbrowser_inline_attachments'));
$disposition = in_array($mimeType, $inlineTypes) ? 'inline' : 'attachment';
$extraHeaders = [sprintf('Content-Disposition: %s; filename="%s"', $disposition, basename($row['remotefile']))];

$fileServer = new FileServer();
$fileServer->serveFile($attachFilePath, $mimeType, $extraHeaders);

exit;
