<?php
require_once __DIR__ . '/../vendor/autoload.php';

function sendPusherNotification($eventType, $data) {
    try {
        $options = array(
            'cluster' => 'ap1',
            'useTLS' => true
        );

        $pusher = new Pusher\Pusher(
            '3d5e91994ffcfa8ec0b5',  // app key
            '809a72ce4d916761101d',   // app secret
            '2152150',                // app id
            $options
        );

        $pusher->trigger('my-channel', $eventType, $data);
        return true;
    } catch (Exception $e) {
        error_log("Pusher error: " . $e->getMessage());
        return false;
    }
}
