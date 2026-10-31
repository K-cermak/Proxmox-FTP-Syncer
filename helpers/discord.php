<?php
    function testWebhook($ping) {
        $data = [
            'embeds' => [
                [
                    'title' => '🔔 Proxmox FTP Syncer Test Notification 🔔',
                    'description' => 'This is a test notification from Proxmox FTP Syncer.',
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
                    'name' => 'Detected files:',
                    'value' => $syncStats["detected"],
                    'inline' => true
                ],
                [
                    'name' => 'Lost files:',
                    'value' => $syncStats["lost"],
                    'inline' => true
                ],
                [
                    'name' => 'Uploaded files:',
                    'value' => $syncStats["uploaded"],
                    'inline' => true
                ],
                [
                    'name' => 'Deleted files:',
                    'value' => $syncStats["deleted"],
                    'inline' => true
                ]
            ],
            'timestamp' => date('c')
        ];

        if ($syncStats["isok"]) {
            $embed['title'] = '✅ Proxmox FTP Syncer finished successfully ✅';
            $embed['color'] = 0x00FF00;
        } else {
            $embed['title'] = '❌ Proxmox FTP Syncer finished with errors ❌';
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
            errorMessage("ERROR: The cURL extension is not installed, so the Discord webhook cannot be sent.");
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
            successMessage("Discord webhook sent successfully.");
        } else {
            errorMessage("Failed to send the Discord webhook (HTTP code $httpCode).");
        }
    }
?>
