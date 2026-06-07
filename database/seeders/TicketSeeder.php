<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@example.com')->first();
        $agent = User::where('email', 'agent@example.com')->first();
        $secondAgent = User::where('email', 'agent2@example.com')->first();

        $technicalSupport = TicketCategory::where('name', 'Soporte técnico')->first();
        $userAccount = TicketCategory::where('name', 'Cuenta de usuario')->first();
        $billing = TicketCategory::where('name', 'Facturación')->first();

        // Ticket abierto y sin asignar
        $openTicket = Ticket::updateOrCreate(
            ['title' => 'No puedo iniciar sesión'],
            [
                'description' => 'El sistema no acepta mis credenciales aunque son correctas.',
                'status' => 'open',
                'priority' => 'high',
                'user_id' => $user->id,
                'category_id' => $userAccount?->id,
                'assigned_to_id' => null,
            ]
        );

        $openTicket->recordActivity(
            type: 'ticket_created',
            user: $user,
            description: 'Ticket created from database seeder'
        );

        // Ticket en progreso y asignado al agente principal
        $inProgressTicket = Ticket::updateOrCreate(
            ['title' => 'Error al cargar el dashboard'],
            [
                'description' => 'El dashboard se queda cargando y no muestra estadísticas.',
                'status' => 'in_progress',
                'priority' => 'medium',
                'user_id' => $user->id,
                'category_id' => $technicalSupport?->id,
                'assigned_to_id' => $agent->id,
            ]
        );

        $inProgressTicket->recordActivity(
            type: 'ticket_created',
            user: $user,
            description: 'Ticket created from database seeder'
        );

        $inProgressTicket->recordActivity(
            type: 'ticket_assigned',
            user: $agent,
            description: 'Ticket assigned from database seeder',
            oldValue: null,
            newValue: (string) $agent->id
        );

        $inProgressTicket->comments()->updateOrCreate(
            [
                'body' => 'Estamos revisando el problema reportado.',
            ],
            [
                'user_id' => $agent->id,
            ]
        );

        $inProgressTicket->recordActivity(
            type: 'comment_created',
            user: $agent,
            description: 'Comment added from database seeder'
        );

        // Ticket resuelto y asignado al segundo agente
        $resolvedTicket = Ticket::updateOrCreate(
            ['title' => 'Consulta sobre factura pendiente'],
            [
                'description' => 'Necesito confirmar el estado de una factura emitida.',
                'status' => 'resolved',
                'priority' => 'low',
                'user_id' => $user->id,
                'category_id' => $billing?->id,
                'assigned_to_id' => $secondAgent->id,
            ]
        );

        $resolvedTicket->recordActivity(
            type: 'ticket_created',
            user: $user,
            description: 'Ticket created from database seeder'
        );

        $resolvedTicket->recordActivity(
            type: 'status_changed',
            user: $secondAgent,
            description: 'Ticket status changed from database seeder',
            oldValue: 'open',
            newValue: 'resolved'
        );
    }
}
