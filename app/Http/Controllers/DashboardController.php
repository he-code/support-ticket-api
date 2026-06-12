<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        // Consulta base para calcular estadísticas.
        // Si el usuario no es staff, solo se toman en cuenta sus propios tickets.
        $baseQuery = Ticket::query();

        if (! $request->user()->isStaff()) {
            $baseQuery->where('user_id', $request->user()->id);
        }

        return response()->json([
            'total_tickets' => (clone $baseQuery)->count(),

            'by_status' => [
                'open' => (clone $baseQuery)->where('status', 'open')->count(),
                'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
                'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
                'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
            ],

            'by_priority' => [
                'low' => (clone $baseQuery)->where('priority', 'low')->count(),
                'medium' => (clone $baseQuery)->where('priority', 'medium')->count(),
                'high' => (clone $baseQuery)->where('priority', 'high')->count(),
            ],

            // Tickets sin agente asignado.
            // Para usuarios normales cuenta solo sus tickets sin asignar.
            // Para staff cuenta todos los tickets sin asignar.
            'unassigned_tickets' => (clone $baseQuery)
                ->whereNull('assigned_to_id')
                ->count(),

            // Tickets asignados al usuario autenticado.
            // Es útil principalmente para agentes de soporte.
            'assigned_to_me' => Ticket::query()
                ->where('assigned_to_id', $request->user()->id)
                ->count(),
        ]);
    }
}
