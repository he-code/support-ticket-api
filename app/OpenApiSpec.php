<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Support Ticket API',
    description: 'REST API for support ticket management with authentication, roles, workflow modules, user imports, integrations, reports and notifications.'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Local development server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token'
)]
#[OA\Tag(name: 'Auth', description: 'Authentication endpoints')]
#[OA\Tag(name: 'Profile', description: 'Authenticated user profile')]
#[OA\Tag(name: 'Tickets', description: 'Ticket management')]
#[OA\Tag(name: 'Comments', description: 'Ticket comments')]
#[OA\Tag(name: 'Attachments', description: 'Ticket attachments')]
#[OA\Tag(name: 'Activities', description: 'Ticket activity history')]
#[OA\Tag(name: 'Categories', description: 'Ticket categories')]
#[OA\Tag(name: 'Workflow', description: 'Channels, teams, tags, custom fields, SLA and automations')]
#[OA\Tag(name: 'Imports', description: 'Admin-only user imports')]
#[OA\Tag(name: 'Integrations', description: 'API keys, webhooks and delivery records')]
#[OA\Tag(name: 'Reports', description: 'CSV exports and operational reports')]
#[OA\Tag(name: 'Notifications', description: 'Internal database notifications')]
#[OA\Tag(name: 'Users', description: 'Admin user management')]
#[OA\Tag(name: 'Dashboard', description: 'Dashboard statistics')]
#[OA\Tag(name: 'Support Agents', description: 'Support agent directory')]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Admin User'),
        new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['user', 'support_agent', 'admin'], example: 'admin'),
        new OA\Property(property: 'created_at', type: 'string', nullable: true, example: '2026-06-07T00:04:30.000000Z'),
    ]
)]
#[OA\Schema(
    schema: 'TicketCategory',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Soporte técnico'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Problemas técnicos relacionados con el uso del sistema.'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', nullable: true, example: '2026-06-07T00:04:30.000000Z'),
    ]
)]
#[OA\Schema(
    schema: 'Ticket',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'No puedo iniciar sesión'),
        new OA\Property(property: 'description', type: 'string', example: 'El sistema no acepta mis credenciales.'),
        new OA\Property(property: 'status', type: 'string', enum: ['open', 'in_progress', 'waiting_customer', 'waiting_internal', 'resolved', 'closed', 'reopened'], example: 'open'),
        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'high'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'assigned_to', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'category', ref: '#/components/schemas/TicketCategory', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', nullable: true, example: '2026-06-07T00:04:30.000000Z'),
    ]
)]
#[OA\Schema(
    schema: 'UserImport',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'file_name', type: 'string', example: 'users.csv'),
        new OA\Property(property: 'status', type: 'string', example: 'completed'),
        new OA\Property(property: 'created_count', type: 'integer', example: 12),
        new OA\Property(property: 'updated_count', type: 'integer', example: 2),
        new OA\Property(property: 'skipped_count', type: 'integer', example: 1),
        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'created_at', type: 'string', nullable: true, example: '2026-06-17T00:04:30.000000Z'),
    ]
)]
#[OA\Schema(
    schema: 'Pagination',
    type: 'object',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 25),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'to', type: 'integer', nullable: true, example: 10),
    ]
)]
#[OA\Post(
    path: '/api/register',
    summary: 'Register a new user',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Usuario Demo'),
                new OA\Property(property: 'email', type: 'string', example: 'usuario@example.com'),
                new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'password123'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'User registered successfully'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Post(
    path: '/api/login',
    summary: 'Login and get a Sanctum token',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                new OA\Property(property: 'password', type: 'string', example: 'password123'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Login successful'),
        new OA\Response(response: 401, description: 'Invalid credentials'),
        new OA\Response(response: 422, description: 'Validation error'),
        new OA\Response(response: 429, description: 'Too many login attempts'),
    ]
)]
#[OA\Post(
    path: '/api/logout',
    summary: 'Logout and revoke current token',
    security: [['bearerAuth' => []]],
    tags: ['Auth'],
    responses: [
        new OA\Response(response: 200, description: 'Logged out successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/me',
    summary: 'Get authenticated user profile',
    security: [['bearerAuth' => []]],
    tags: ['Profile'],
    responses: [new OA\Response(response: 200, description: 'Authenticated user')]
)]
#[OA\Patch(
    path: '/api/profile',
    summary: 'Update authenticated user profile',
    security: [['bearerAuth' => []]],
    tags: ['Profile'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Nuevo nombre'),
                new OA\Property(property: 'email', type: 'string', example: 'nuevo@example.com'),
            ]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Profile updated successfully')]
)]
#[OA\Patch(
    path: '/api/password',
    summary: 'Change authenticated user password',
    security: [['bearerAuth' => []]],
    tags: ['Profile'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['current_password', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'current_password', type: 'string', example: 'password'),
                new OA\Property(property: 'password', type: 'string', example: 'new-password'),
                new OA\Property(property: 'password_confirmation', type: 'string', example: 'new-password'),
            ]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Password changed successfully')]
)]
#[OA\Get(
    path: '/api/tickets',
    summary: 'List tickets with filters, search, sorting and pagination',
    security: [['bearerAuth' => []]],
    tags: ['Tickets'],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['open', 'in_progress', 'waiting_customer', 'waiting_internal', 'resolved', 'closed', 'reopened'])),
        new OA\Parameter(name: 'priority', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high', 'urgent'])),
        new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'assigned', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['me', 'unassigned'])),
        new OA\Parameter(name: 'assigned_to_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'channel_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'team_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'tag_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'sla', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['on_track', 'overdue', 'first_response_overdue', 'resolution_overdue'])),
        new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['created_at', 'title', 'priority', 'status'])),
        new OA\Parameter(name: 'sort_direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
    ],
    responses: [new OA\Response(response: 200, description: 'Paginated ticket list')]
)]
#[OA\Post(
    path: '/api/tickets',
    summary: 'Create a ticket',
    security: [['bearerAuth' => []]],
    tags: ['Tickets'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title', 'description', 'priority'],
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'No puedo iniciar sesión'),
                new OA\Property(property: 'description', type: 'string', example: 'El sistema no acepta mis credenciales.'),
                new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'high'),
                new OA\Property(property: 'category_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'channel_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'team_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2]),
                new OA\Property(property: 'custom_fields', type: 'object', example: ['invoice_number' => 'FAC-100']),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Ticket created successfully'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Get(
    path: '/api/tickets/{ticket}',
    summary: 'Get a ticket by ID',
    security: [['bearerAuth' => []]],
    tags: ['Tickets'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 200, description: 'Ticket detail'),
        new OA\Response(response: 403, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Patch(
    path: '/api/tickets/{ticket}',
    summary: 'Update a ticket',
    security: [['bearerAuth' => []]],
    tags: ['Tickets'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Título actualizado'),
                new OA\Property(property: 'description', type: 'string', example: 'Descripción actualizada'),
                new OA\Property(property: 'status', type: 'string', enum: ['open', 'in_progress', 'waiting_customer', 'waiting_internal', 'resolved', 'closed', 'reopened'], example: 'in_progress'),
                new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'medium'),
                new OA\Property(property: 'category_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'channel_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'team_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2]),
                new OA\Property(property: 'custom_fields', type: 'object', example: ['invoice_number' => 'FAC-100']),
            ]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Ticket updated successfully')]
)]
#[OA\Delete(
    path: '/api/tickets/{ticket}',
    summary: 'Delete a ticket',
    security: [['bearerAuth' => []]],
    tags: ['Tickets'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [new OA\Response(response: 200, description: 'Ticket deleted successfully')]
)]
#[OA\Patch(
    path: '/api/tickets/{ticket}/status',
    summary: 'Update ticket status',
    security: [['bearerAuth' => []]],
    tags: ['Tickets'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [new OA\Property(property: 'status', type: 'string', enum: ['open', 'in_progress', 'waiting_customer', 'waiting_internal', 'resolved', 'closed', 'reopened'], example: 'closed')]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Ticket status updated successfully')]
)]
#[OA\Patch(
    path: '/api/tickets/{ticket}/assign',
    summary: 'Assign or unassign a ticket',
    security: [['bearerAuth' => []]],
    tags: ['Tickets'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [new OA\Property(property: 'assigned_to_id', type: 'integer', nullable: true, example: 2)]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Ticket assigned or unassigned successfully')]
)]
#[OA\Get(
    path: '/api/tickets/{ticket}/comments',
    summary: 'List comments for a ticket',
    security: [['bearerAuth' => []]],
    tags: ['Comments'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [new OA\Response(response: 200, description: 'Paginated comment list')]
)]
#[OA\Post(
    path: '/api/tickets/{ticket}/comments',
    summary: 'Create a comment for a ticket',
    security: [['bearerAuth' => []]],
    tags: ['Comments'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['body'],
            properties: [new OA\Property(property: 'body', type: 'string', example: 'Estamos revisando el problema reportado.')]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Comment created successfully')]
)]
#[OA\Delete(
    path: '/api/tickets/{ticket}/comments/{comment}',
    summary: 'Delete a ticket comment',
    security: [['bearerAuth' => []]],
    tags: ['Comments'],
    parameters: [
        new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'comment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Comment deleted successfully')]
)]
#[OA\Get(
    path: '/api/tickets/{ticket}/attachments',
    summary: 'List attachments for a ticket',
    security: [['bearerAuth' => []]],
    tags: ['Attachments'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [new OA\Response(response: 200, description: 'Attachment list')]
)]
#[OA\Post(
    path: '/api/tickets/{ticket}/attachments',
    summary: 'Upload an attachment for a ticket',
    security: [['bearerAuth' => []]],
    tags: ['Attachments'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    new OA\Property(property: 'is_internal', type: 'boolean', example: false),
                ],
                type: 'object'
            )
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Attachment uploaded successfully')]
)]
#[OA\Get(
    path: '/api/tickets/{ticket}/attachments/{attachment}/download',
    summary: 'Download an attachment',
    security: [['bearerAuth' => []]],
    tags: ['Attachments'],
    parameters: [
        new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'attachment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Attachment file download')]
)]
#[OA\Get(
    path: '/api/tickets/{ticket}/attachments/{attachment}/preview',
    summary: 'Preview an attachment',
    security: [['bearerAuth' => []]],
    tags: ['Attachments'],
    parameters: [
        new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'attachment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Attachment preview'),
        new OA\Response(response: 403, description: 'Unauthorized'),
    ]
)]
#[OA\Delete(
    path: '/api/tickets/{ticket}/attachments/{attachment}',
    summary: 'Delete an attachment',
    security: [['bearerAuth' => []]],
    tags: ['Attachments'],
    parameters: [
        new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'attachment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [new OA\Response(response: 200, description: 'Attachment deleted successfully')]
)]
#[OA\Get(
    path: '/api/tickets/{ticket}/activities',
    summary: 'List ticket activity history',
    security: [['bearerAuth' => []]],
    tags: ['Activities'],
    parameters: [new OA\Parameter(name: 'ticket', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [new OA\Response(response: 200, description: 'Paginated activity history')]
)]
#[OA\Get(
    path: '/api/ticket-categories',
    summary: 'List ticket categories',
    security: [['bearerAuth' => []]],
    tags: ['Categories'],
    responses: [new OA\Response(response: 200, description: 'Category list')]
)]
#[OA\Post(
    path: '/api/ticket-categories',
    summary: 'Create a ticket category',
    security: [['bearerAuth' => []]],
    tags: ['Categories'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Facturación'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Consultas relacionadas con pagos.'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Ticket category created successfully')]
)]
#[OA\Get(
    path: '/api/ticket-categories/{ticket_category}',
    summary: 'Get a ticket category',
    security: [['bearerAuth' => []]],
    tags: ['Categories'],
    parameters: [new OA\Parameter(name: 'ticket_category', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [new OA\Response(response: 200, description: 'Category detail')]
)]
#[OA\Patch(
    path: '/api/ticket-categories/{ticket_category}',
    summary: 'Update a ticket category',
    security: [['bearerAuth' => []]],
    tags: ['Categories'],
    parameters: [new OA\Parameter(name: 'ticket_category', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Categoría actualizada'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'Ticket category updated successfully')]
)]
#[OA\Delete(
    path: '/api/ticket-categories/{ticket_category}',
    summary: 'Delete a ticket category',
    security: [['bearerAuth' => []]],
    tags: ['Categories'],
    parameters: [new OA\Parameter(name: 'ticket_category', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [new OA\Response(response: 200, description: 'Ticket category deleted successfully')]
)]
#[OA\Get(
    path: '/api/categories',
    summary: 'List ticket categories using frontend-compatible alias',
    security: [['bearerAuth' => []]],
    tags: ['Categories'],
    responses: [new OA\Response(response: 200, description: 'Category list')]
)]
#[OA\Post(
    path: '/api/categories',
    summary: 'Create a ticket category using frontend-compatible alias',
    security: [['bearerAuth' => []]],
    tags: ['Categories'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Facturacion'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Consultas relacionadas con pagos.'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ]
        )
    ),
    responses: [new OA\Response(response: 201, description: 'Ticket category created successfully')]
)]
#[OA\Get(
    path: '/api/notifications',
    summary: 'List authenticated user notifications',
    security: [['bearerAuth' => []]],
    tags: ['Notifications'],
    responses: [new OA\Response(response: 200, description: 'Paginated notifications')]
)]
#[OA\Patch(
    path: '/api/notifications/{notification}/read',
    summary: 'Mark one notification as read',
    security: [['bearerAuth' => []]],
    tags: ['Notifications'],
    parameters: [new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
    responses: [new OA\Response(response: 200, description: 'Notification marked as read')]
)]
#[OA\Patch(
    path: '/api/notifications/read-all',
    summary: 'Mark all notifications as read',
    security: [['bearerAuth' => []]],
    tags: ['Notifications'],
    responses: [new OA\Response(response: 200, description: 'All notifications marked as read')]
)]
#[OA\Get(
    path: '/api/users',
    summary: 'List users for admin',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(name: 'role', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['user', 'support_agent', 'admin'])),
        new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
    ],
    responses: [new OA\Response(response: 200, description: 'Paginated user list')]
)]
#[OA\Patch(
    path: '/api/users/{user}/role',
    summary: 'Update user role for admin',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['role'],
            properties: [new OA\Property(property: 'role', type: 'string', enum: ['user', 'support_agent', 'admin'], example: 'support_agent')]
        )
    ),
    responses: [new OA\Response(response: 200, description: 'User role updated successfully')]
)]
#[OA\Get(
    path: '/api/users/imports',
    summary: 'List user import history for admin',
    security: [['bearerAuth' => []]],
    tags: ['Imports'],
    responses: [
        new OA\Response(response: 200, description: 'Paginated import list'),
        new OA\Response(response: 403, description: 'Admin only'),
    ]
)]
#[OA\Post(
    path: '/api/users/import',
    summary: 'Import users from CSV or XLSX for admin',
    security: [['bearerAuth' => []]],
    tags: ['Imports'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV, TXT or XLSX file'),
                    new OA\Property(property: 'update_existing', type: 'boolean', example: false),
                    new OA\Property(property: 'default_password', type: 'string', nullable: true, example: 'password123'),
                ],
                type: 'object'
            )
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Users imported successfully'),
        new OA\Response(response: 403, description: 'Admin only'),
        new OA\Response(response: 422, description: 'Validation error or XLSX support unavailable'),
    ]
)]
#[OA\Get(
    path: '/api/support-agents',
    summary: 'List support agents',
    security: [['bearerAuth' => []]],
    tags: ['Support Agents'],
    responses: [new OA\Response(response: 200, description: 'Support agent list')]
)]
#[OA\Get(
    path: '/api/dashboard/stats',
    summary: 'Get dashboard statistics',
    security: [['bearerAuth' => []]],
    tags: ['Dashboard'],
    responses: [new OA\Response(response: 200, description: 'Dashboard statistics')]
)]
class OpenApiSpec {}
