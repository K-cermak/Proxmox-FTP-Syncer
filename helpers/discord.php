<?php
    function testWebhook($ping) {
        $data = [
            'embeds' => [
                [
                    'title' => '🔔 Proxmox Syncer Test Notification 🔔',
                    'description' => 'This is a test notification from Proxmox Syncer.',
                    'color' => 0x0000FF,
                    'timestamp' => date('c')
                ]
            ]
        ];

        if ($ping) {
            $data['content'] = '<@' . DISCORD_PING_ID . '>';
        }

        sendRequest($data);
    }

    function sendWebhook() {
        global $syncStats;
        if ((SEND_DISCORD_HOOKS == "no") || (SEND_DISCORD_HOOKS == "on_error" && $syncStats["isok"])) {
            return;
        }

        $embed = [
            'fields' => [
                [
                    'name' => 'Duration:',
                    'value' => $syncStats["time"],
                    'inline' => true
                ],
                [
                    'name' => 'Detected Files:',
                    'value' => $syncStats["detected"],
                    'inline' => true
                ],
                [
                    'name' => 'Lost Files:',
                    'value' => $syncStats["lost"],
                    'inline' => true
                ],
                [
                    'name' => 'Uploaded Files:',
                    'value' => $syncStats["uploaded"],
                    'inline' => true
                ],
                [
                    'name' => 'Deleted Files:',
                    'value' => $syncStats["deleted"],
                    'inline' => true
                ]
            ],
            'timestamp' => date('c')
        ];

        if ($syncStats["isok"]) {
            $embed['title'] = '✅ Proxmox Syncer ended succesfully ✅';
            $embed['color'] = 0x00FF00;
        } else {
            $embed['title'] = '❌ Proxmox Syncer ended with errors ❌';
            $embed['color'] = 0xFF0000;
            $embed['fields'][] = [
                'name' => 'Errors:',
                'value' => implode("\n", $syncStats["errors"]),
                'inline' => false
            ];
        }

        $data = [
            'embeds' => [$embed]
        ];

        if ((PING_USER == "always") || (PING_USER == "on_error" && !$syncStats["isok"])) {
            $data['content'] = '<@' . DISCORD_PING_ID . '>';
        }

        sendRequest($data);
    }

    function sendRequest($data) {
        if (!extension_loaded('curl')) {
            errorMessage("ERROR: cURL extension is not installed. Discord Webhook cannot be sent.");
            return;
        }

        $jsonData = json_encode($data);
        $ch = curl_init(DISCORD_WEBHOOK);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 204) {
            successMessage("Discord Webhook sended successfully.");
        } else {
            errorMessage("Discord Webhook sending error, code ($httpCode).");
        }
    }
?>