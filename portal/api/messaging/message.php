<?php
header('Content-Type: application/json');

// Start OpenEMR session
require_once(__DIR__ . "/../../verify_session.php");
require_once("$srcdir/patient.inc.php");
require_once(__DIR__ . '/../../../library/appointments.inc.php');
require_once(dirname(__FILE__) . "/../../lib/portal_mail.inc.php");
require_once("$srcdir/pnotes.inc.php");

use OpenEMR\Common\Csrf\CsrfUtils;

// Check if the user is authenticated
$owner = $_SESSION['portal_username'] ?? $_SESSION['authUser'] ?? null;
if (!$owner) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}


// Get task from request
$task = $_POST['task'] ?? null;

if (!$task) {
    echo json_encode(['error' => 'Missing task parameter']);
    exit();
}

// Confirm CSRF token (bypass for API requests)
if (!isset($_POST["api_request"])) {
    if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"] ?? '', 'messages-portal')) {
        echo json_encode(["error" => "CSRF token validation failed"]);
        exit();
    }
}

// Extract common parameters
$noteid = $_POST['noteid'] ?? 0;
$notejson = isset($_POST['notejson']) ? json_decode($_POST['notejson'], true) : 0;
$reply_noteid = $_POST['replyid'] ?? 0;
$note = $_POST['inputBody'] ?? null;
$title = $_POST['title'] ?? null;
$sid = $_POST['sender_id'] ?? null;
$sn = $_POST['sender_name'] ?? null;
$rid = $_POST['recipient_id'] ?? null;
$rn = $_POST['recipient_name'] ?? null;
$pid = $_POST['pid'] ?? 0;

// Handle the task
$response = handleTask($task, $owner, $noteid, $notejson, $reply_noteid, $note, $title, $sid, $sn, $rid, $rn, $pid);
echo json_encode($response);

/**
 * Handles the API request based on the given task.
 */
function handleTask($task, $owner, $noteid, $notejson, $reply_noteid, $note, $title, $sid, $sn, $rid, $rn, $pid)
{
    switch ($task) {
        case "getlatest":
            return getLatestMails($owner, 3); // Get the latest 3 messages

        case "getinbox":
            return getMails($owner, 'inbox', '', '');

        case "getsent":
            return getMails($owner, 'sent', '', '');

        case "getall":
            return getMails($owner, 'all', '', '');

        case "getdeleted":
            return getMails($owner, 'deleted', '', '');

        case "forward":
            return forwardMessage($noteid, $note, $title, $sid, $pid, $owner);

        case "add":
            return sendMessage($owner, $note, $title, $sid, $sn, $rid, $rn, $reply_noteid);

        case "reply":
            return replyMessage($owner, $note, $title, $sid, $sn, $rid, $rn, $reply_noteid);

        case "delete":
            return deleteMessage($noteid, $owner);

        case "massdelete":
            return deleteMultipleMessages($notejson, $owner);

        case "setread":
            return markMessageAsRead($noteid, $owner);

        default:
            return ['error' => 'Invalid task'];
    }
}

/**
 * Fetches the latest messages for a given user.
 */
function getLatestMails($owner, $limit = 3)
{
    $sql = "SELECT * FROM onsite_mail WHERE owner = ? ORDER BY date DESC LIMIT ?";
    $stmt = sqlStatement($sql, [$owner, (int)$limit]);

    $messages = [];
    while ($row = sqlFetchArray($stmt)) {
        $messages[] = $row;
    }

    return !empty($messages) ? $messages : ["message" => "No messages found"];
}

/**
 * Sends a new message.
 */
function sendMessage($owner, $note, $title, $sid, $sn, $rid, $rn, $reply_noteid)
{
    sendMail($owner, $note, $title, '', 0, $sid, $sn, $rid, $rn, 'New');
    sendMail($rid, $note, $title, '', 0, $sid, $sn, $rid, $rn, 'New', $reply_noteid);
    return ['status' => 'Message sent'];
}

/**
 * Replies to a message.
 */
function replyMessage($owner, $note, $title, $sid, $sn, $rid, $rn, $reply_noteid)
{
    sendMail($owner, $note, $title, '', 0, $sid, $sn, $rid, $rn, 'Reply', '');
    sendMail($rid, $note, $title, '', 0, $sid, $sn, $rid, $rn, 'New', $reply_noteid);
    return ['status' => 'Reply sent'];
}

/**
 * Forwards a message.
 */
function forwardMessage($noteid, $note, $title, $sid, $pid, $owner)
{
    addPnote($pid, $note, 1, 1, $title, $sid, '', 'New');
    updatePortalMailMessageStatus($noteid, 'Sent', $owner);
    return ['status' => 'Message forwarded'];
}

/**
 * Marks a message as read.
 */
function markMessageAsRead($noteid, $owner)
{
    if ($noteid > 0) {
        updatePortalMailMessageStatus($noteid, 'Read', $owner);
        return ['status' => 'Message marked as read'];
    }
    return ['error' => 'Missing note ID'];
}

/**
 * Deletes a single message.
 */
function deleteMessage($noteid, $owner)
{
    updatePortalMailMessageStatus($noteid, 'Delete', $owner);
    return ['status' => 'Message deleted'];
}

/**
 * Deletes multiple messages.
 */
function deleteMultipleMessages($notejson, $owner)
{
    foreach ($notejson as $deleteid) {
        updatePortalMailMessageStatus($deleteid, 'Delete', $owner);
    }
    return ['status' => 'Messages deleted'];
}
