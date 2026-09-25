<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Router;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'clients_total' => Client::count(),
            'clients_active' => Client::where('status', 'active')->count(),
            'routers_verified' => Router::where('status', 'verified')->count(),
        ];

        $recentClients = Client::latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'recentClients'));
    }
}
