{{-- English guide. Same structure as _id.blade.php. --}}

<section id="start" data-help-section class="card p-6 scroll-mt-6">
    <h2 class="text-lg font-semibold mb-1">Getting started</h2>
    <p class="text-sm text-gray-600 mb-4">Nusa Hotspot runs your MikroTik hotspot from one panel: routers, internet plans, vouchers, the login page design and customer monitoring. Customer logins are checked by the Nusa RADIUS server, so you never manage users on the router one by one.</p>
    <ol class="grid sm:grid-cols-3 gap-3 text-sm">
        <li class="rounded-xl border border-gray-200 p-4"><span class="badge bg-brand-50 text-brand-700 mb-2">1</span><div class="font-medium">Connect a router</div><p class="text-gray-500 mt-1"><b>Router</b> menu → Add Router. RADIUS is set up automatically.</p></li>
        <li class="rounded-xl border border-gray-200 p-4"><span class="badge bg-brand-50 text-brand-700 mb-2">2</span><div class="font-medium">Create a plan</div><p class="text-gray-500 mt-1"><b>Plan / Profile</b> menu: speed, validity, number of devices.</p></li>
        <li class="rounded-xl border border-gray-200 p-4"><span class="badge bg-brand-50 text-brand-700 mb-2">3</span><div class="font-medium">Create vouchers</div><p class="text-gray-500 mt-1"><b>User</b> menu: one at a time or hundreds at once.</p></li>
    </ol>
    <p class="text-sm text-gray-600 mt-4">Optional: design the login page in the <b>Login Template</b> menu, then apply it to your routers.</p>
</section>

<section id="router" data-help-section class="card p-6 scroll-mt-6 space-y-4 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Connecting a MikroTik router</h2>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Prepare the router</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li>The router has an active <b>hotspot server</b> (IP → Hotspot → Hotspot Setup).</li>
            <li>The API service is enabled: <code class="bg-gray-100 px-1 rounded">/ip service enable api</code> (default port 8728).</li>
            <li>The server can reach the router: via a <b>public IP</b>, or via <b>WireGuard</b> (10.88.x.x) when the router is behind CGNAT.</li>
            <li>A router username &amp; password with API access.</li>
        </ul>
    </div>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Adding the router</h3>
        <p>Open <b>Router → Add Router</b>, enter the IP, username, password and API port, then click <b>Connect</b>. The system automatically:</p>
        <ul class="list-disc pl-5 space-y-1 mt-1">
            <li>adds the Nusa RADIUS server to the router and enables RADIUS incoming (to disconnect sessions from the panel);</li>
            <li>creates the <code class="bg-gray-100 px-1 rounded">radius-nusa</code> hotspot server profile and user profile;</li>
            <li>enables RADIUS on the hotspot profile in use;</li>
            <li>reads the router name from System Identity.</li>
        </ul>
    </div>
    <div class="info-box">
        <span>💡</span>
        <span>Changed the API password or router IP? Open the router detail and click <b>Reconfigure</b>. If the server can't reach the API, use the <b>manual script</b> on the router detail page and paste it into the MikroTik Terminal.</span>
    </div>
    <p><b>Online/Offline</b> status updates every 5 minutes. A router counts as offline once it hasn't answered for more than 10 minutes.</p>
</section>

<section id="profile" data-help-section class="card p-6 scroll-mt-6 space-y-4 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Plans / Profiles</h2>
    <p>A plan sets the rules for every voucher on it: speed, validity and number of devices.</p>
    <dl class="divide-y divide-gray-100 rounded-xl border border-gray-200">
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">Speed</dt><dd class="sm:col-span-2">Upload / download using <code class="bg-gray-100 px-1 rounded">K</code> or <code class="bg-gray-100 px-1 rounded">M</code>, e.g. <code class="bg-gray-100 px-1 rounded">512K</code>, <code class="bg-gray-100 px-1 rounded">5M</code>. Leave empty for no limit.</dd></div>
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">Validity</dt><dd class="sm:col-span-2">How long a voucher lasts (hours/days/months), counted from the <b>first login</b> or from <b>creation</b>.</dd></div>
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">Simultaneous Use</dt><dd class="sm:col-span-2">How many devices may use one voucher at the same time.</dd></div>
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">Session / idle timeout</dt><dd class="sm:col-span-2">Disconnect after this many seconds per session / of inactivity. Only applies to <b>Plain hotspot</b>.</dd></div>
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">MikroTik Group</dt><dd class="sm:col-span-2">Keep <code class="bg-gray-100 px-1 rounded">radius-nusa</code> unless you know the router's user profiles.</dd></div>
    </dl>

    <div>
        <h3 class="font-medium text-gray-900 mb-2">Connection system: Binding or Plain hotspot</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border border-gray-200 rounded-xl overflow-hidden">
                <thead class="bg-gray-50 text-gray-500"><tr><th class="p-3"></th><th class="p-3">Binding</th><th class="p-3">Plain hotspot</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="p-3 font-medium">Log in again</td><td class="p-3">Not until the voucher ends</td><td class="p-3">When the session drops</td></tr>
                    <tr><td class="p-3 font-medium">Shared bandwidth</td><td class="p-3">Yes</td><td class="p-3">No (per device)</td></tr>
                    <tr><td class="p-3 font-medium">Usage history</td><td class="p-3">First ±1 minute only</td><td class="p-3">Complete</td></tr>
                    <tr><td class="p-3 font-medium">Idle / session timeout</td><td class="p-3">Not applied</td><td class="p-3">Applied</td></tr>
                    <tr><td class="p-3 font-medium">Customer Logout button</td><td class="p-3">Doesn't work</td><td class="p-3">Works</td></tr>
                </tbody>
            </table>
        </div>
        <p class="mt-2">With <b>Binding</b>, about a minute after login the device moves to an IP binding (bypass) + simple queue. With <b>Plain hotspot</b>, it stays a hotspot session limited directly by RADIUS.</p>
    </div>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Bandwidth mode (Binding only)</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li><b>Per device</b>: every device gets the full speed. 5M × 3 devices = 5M each.</li>
            <li><b>Shared</b>: all devices on one voucher share the speed. 5M × 3 devices = 5M in total.</li>
        </ul>
    </div>
</section>

<section id="user" data-help-section class="card p-6 scroll-mt-6 space-y-4 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Users / Vouchers</h2>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Creating vouchers</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li><b>Single user</b>: enter your own username &amp; password, for one customer.</li>
            <li><b>Batch</b>: up to 1000 vouchers at once. Set the quantity, code length, prefix (e.g. <code class="bg-gray-100 px-1 rounded">WIFI-</code>) and charset.</li>
        </ul>
    </div>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Batch password options</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li><b>Random per user</b>: each voucher gets its own password.</li>
            <li><b>Same for all</b>: one password for the whole batch.</li>
            <li><b>Same as username</b>: required when the Login Template uses <b>"Voucher code only"</b>.</li>
        </ul>
        <p class="mt-1">Tip: for codes customers type on a phone, use the <b>Numbers</b> or <b>Uppercase</b> charset to avoid case mistakes.</p>
    </div>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Voucher status</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li><b>Active</b>: can log in. <b>Used</b>: has logged in before.</li>
            <li><b>Expired</b>: validity is over. Expired vouchers are deleted automatically and their devices disconnected.</li>
            <li><b>Disabled</b>: blocked manually via Edit.</li>
        </ul>
    </div>
    <p>Click the <b>Online</b> count in the user list to see that voucher's active sessions and disconnect a device (<b>Kill Session</b>). Tick several users and use <b>Delete selected</b> to remove them in bulk.</p>
</section>

<section id="template" data-help-section class="card p-6 scroll-mt-6 space-y-4 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Login Template</h2>
    <p>Design the hotspot login page customers see when they join the WiFi.</p>
    <ol class="list-decimal pl-5 space-y-1">
        <li>Pick a design (Modern, Minimal, Dark), color, language, and optionally a background and logo. The preview on the right updates instantly.</li>
        <li>Fill in the hotspot name, welcome text, help contact, footer and <b>URL after login</b> (empty opens the Status page).</li>
        <li>Choose the login mode: <b>Username &amp; password</b> or <b>Voucher code only</b>.</li>
        <li>Click <b>Save template</b>.</li>
        <li>Under <b>Apply to routers</b>, click <b>Apply</b> on each router.</li>
    </ol>
    <div class="info-box">
        <span>💡</span>
        <span>Saving does <b>not</b> change the routers. After every change click <b>Apply again</b>; routers that need it are marked "Changed".</span>
    </div>
    <ul class="list-disc pl-5 space-y-1">
        <li>On apply, the router downloads the files once from hotspot.nusa.id and stores them itself, so the login page shows even before customers have internet.</li>
        <li>The template size and the router's free storage are shown. If storage is short, applying stops and the old page stays.</li>
        <li>If a download fails, the old login page stays, so the hotspot is never left without one.</li>
        <li><b>Restore default</b> puts back the router's previous login page.</li>
        <li>File limits: logo 200 KB, background 500 KB. Use compressed images.</li>
    </ul>
</section>

<section id="devices" data-help-section class="card p-6 scroll-mt-6 space-y-3 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Device Online</h2>
    <p>Customer devices connected right now across your routers: device name, router, IP, MAC, speed, connected time and data used.</p>
    <p>Click <b>Disconnect</b> to cut a device off. With Binding, its binding and queue are removed too, so the customer must log in again.</p>
</section>

<section id="history" data-help-section class="card p-6 scroll-mt-6 space-y-3 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Connection History</h2>
    <p>Every customer session from the <b>last 90 days</b>: start &amp; end, duration, upload/download and why it ended. Older data is deleted automatically.</p>
    <ul class="list-disc pl-5 space-y-1">
        <li>Filter by date and router, or search by username, device name, MAC or IP.</li>
        <li>Click <b>Export CSV</b> to download the data for Excel.</li>
        <li>On <b>Binding</b> plans, history only records the initial login session (±1 minute). Use <b>Plain hotspot</b> for complete usage records.</li>
    </ul>
</section>

<section id="faq" data-help-section class="card p-6 scroll-mt-6 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900 mb-3">FAQ &amp; troubleshooting</h2>
    <div class="divide-y divide-gray-100 border border-gray-200 rounded-xl">
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">The login popup / page doesn't appear on the WiFi</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>Forget the WiFi network on the phone and reconnect.</li><li>Open a plain <code class="bg-gray-100 px-1 rounded">http://</code> site, e.g. <code class="bg-gray-100 px-1 rounded">http://neverssl.com</code>.</li><li>The device may still be bypassed from an earlier login (Binding plan). Disconnect it in Device Online first.</li><li>If you just applied a template, check its status in Login Template. When in doubt, click <b>Apply again</b>.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">A voucher can't log in</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>Check the voucher status in User (Expired / Disabled).</li><li>The plan's Simultaneous Use device limit is already reached.</li><li>"Voucher code only" login only works for vouchers whose password equals the username.</li><li>Alphanumeric codes are case-sensitive.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">A router shows Offline</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>Make sure the router is on and connected to the internet / WireGuard.</li><li>Make sure the API service is enabled and its port isn't blocked by a firewall.</li><li>If the IP or API password changed, click <b>Reconfigure</b> on the router detail.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Applying the template fails</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>Read the red message: router offline, download failed, or not enough storage.</li><li>The router must be able to open <b>hotspot.nusa.id</b> (check its DNS &amp; internet).</li><li>Low storage: delete old files in the router's Files menu, or use a smaller logo/background.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Customers keep having to log in again</summary>
            <p class="mt-2">That's normal for <b>Plain hotspot</b> plans when a session drops or hits the idle timeout. To keep customers logged in until the voucher ends, switch the plan to <b>Binding</b>.</p>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Speed doesn't match the plan</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>With Binding, the speed is applied about a minute after login.</li><li><b>Shared</b> mode splits the speed across all devices on a voucher.</li><li>Make sure no other queue on the router limits it further.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">My router is behind CGNAT (no public IP)</summary>
            <p class="mt-2">Connect the router to the Nusa server over <b>WireGuard</b> and enter its WireGuard IP (10.88.x.x) when adding the router. Contact the Nusa admin for the WireGuard configuration.</p>
        </details>
    </div>
</section>
