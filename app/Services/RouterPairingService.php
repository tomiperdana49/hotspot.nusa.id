<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Router;
use Illuminate\Support\Str;

class RouterPairingService
{
    /**
     * Create the pending router row (radius_secret + nas_identifier are
     * generated here; pairing_code is kept only as a legacy fallback for the
     * manual copy-paste script, since live API provisioning verifies
     * ownership via the MikroTik credentials instead).
     */
    public function start(Client $client, string $name): Router
    {
        return Router::create([
            'client_id' => $client->id,
            'name' => $name,
            'nas_identifier' => 'nas-'.Str::lower(Str::random(8)),
            'radius_secret' => Str::random(32),
            'pairing_code' => $this->uniquePairingCode(),
            'status' => 'pending',
        ]);
    }

    public function mikrotikScript(Router $router, string $serverIp, bool $viaWireguard = false): string
    {
        if ($viaWireguard) {
            return <<<SCRIPT
            /radius
            add service=hotspot address=10.88.0.1 secret={$router->radius_secret} \\
                timeout=5000ms comment="nusa-hotspot"

            /radius incoming
            set accept=yes port=3799

            /ip hotspot profile
            set [find name=hsprof1] use-radius=yes radius-accounting=yes \\
                radius-interim-update=00:02:00
            SCRIPT;
        }

        return <<<SCRIPT
        /radius
        add service=hotspot address={$serverIp} secret={$router->radius_secret} \\
            timeout=5000ms comment="nusa-hotspot"

        /radius incoming
        set accept=yes port=3799

        /ip hotspot profile
        set [find name=hsprof1] use-radius=yes radius-accounting=yes \\
            radius-interim-update=00:02:00 radius-mac-format=XX:XX:XX:XX:XX:XX \\
            nas-port-type=wireless-802.11
        SCRIPT;
    }

    private function uniquePairingCode(): string
    {
        do {
            $code = 'PAIR-'.Str::upper(Str::random(6));
        } while (Router::where('pairing_code', $code)->exists());

        return $code;
    }
}
