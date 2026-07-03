<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomationRuleController;
use App\Http\Controllers\BusinessHolidayController;
use App\Http\Controllers\BusinessHourController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationApiKeyController;
use App\Http\Controllers\KnowledgeBaseArticleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickReplyController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SupportAgentController;
use App\Http\Controllers\SupportTeamController;
use App\Http\Controllers\TicketActivityController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCategoryController;
use App\Http\Controllers\TicketChannelController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketEscalationController;
use App\Http\Controllers\TicketInternalNoteController;
use App\Http\Controllers\TicketKnowledgeBaseArticleController;
use App\Http\Controllers\TicketMentionController;
use App\Http\Controllers\TicketSatisfactionSurveyController;
use App\Http\Controllers\TicketTagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserImportController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('/support-agents', [SupportAgentController::class, 'index']);

    Route::get('/me', [ProfileController::class, 'me']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::patch('/password', [ProfileController::class, 'changePassword']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);
    Route::get('/users/imports', [UserImportController::class, 'index']);
    Route::post('/users/import', [UserImportController::class, 'store']);

    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/reports/tickets/export', [ReportExportController::class, 'tickets']);

    Route::apiResource('ticket-categories', TicketCategoryController::class)
        ->parameters(['ticket-categories' => 'ticketCategory']);
    Route::apiResource('categories', TicketCategoryController::class)
        ->parameters(['categories' => 'ticketCategory']);
    Route::apiResource('ticket-channels', TicketChannelController::class)
        ->parameters(['ticket-channels' => 'ticketChannel']);

    Route::apiResource('support-teams', SupportTeamController::class)
        ->parameters(['support-teams' => 'supportTeam']);
    Route::apiResource('ticket-tags', TicketTagController::class)
        ->parameters(['ticket-tags' => 'ticketTag']);
    Route::apiResource('quick-replies', QuickReplyController::class)
        ->parameters(['quick-replies' => 'quickReply']);
    Route::apiResource('knowledge-base-articles', KnowledgeBaseArticleController::class)
        ->parameters(['knowledge-base-articles' => 'knowledgeBaseArticle']);
    Route::apiResource('automation-rules', AutomationRuleController::class)
        ->parameters(['automation-rules' => 'automationRule']);
    Route::apiResource('custom-fields', CustomFieldController::class)
        ->parameters(['custom-fields' => 'customField']);

    Route::get('/business-hours', [BusinessHourController::class, 'index']);
    Route::post('/business-hours', [BusinessHourController::class, 'store']);
    Route::delete('/business-hours/{businessHour}', [BusinessHourController::class, 'destroy']);
    Route::get('/business-holidays', [BusinessHolidayController::class, 'index']);
    Route::post('/business-holidays', [BusinessHolidayController::class, 'store']);
    Route::delete('/business-holidays/{businessHoliday}', [BusinessHolidayController::class, 'destroy']);

    Route::get('/integrations/api-keys', [IntegrationApiKeyController::class, 'index']);
    Route::post('/integrations/api-keys', [IntegrationApiKeyController::class, 'store']);
    Route::delete('/integrations/api-keys/{apiKey}', [IntegrationApiKeyController::class, 'destroy']);
    Route::apiResource('integrations/webhooks', WebhookController::class)
        ->parameters(['webhooks' => 'webhook']);
    Route::get('/integrations/webhooks/{webhook}/deliveries', [WebhookController::class, 'deliveries']);

    Route::apiResource('tickets', TicketController::class);

    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
    Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign']);

    Route::get('/tickets/{ticket}/activities', [TicketActivityController::class, 'index']);
    Route::get('/tickets/{ticket}/internal-notes', [TicketInternalNoteController::class, 'index']);
    Route::post('/tickets/{ticket}/internal-notes', [TicketInternalNoteController::class, 'store']);
    Route::delete('/tickets/{ticket}/internal-notes/{internalNote}', [TicketInternalNoteController::class, 'destroy']);
    Route::get('/tickets/{ticket}/mentions', [TicketMentionController::class, 'index']);
    Route::get('/tickets/{ticket}/escalations', [TicketEscalationController::class, 'index']);
    Route::post('/tickets/{ticket}/escalations', [TicketEscalationController::class, 'store']);
    Route::patch('/tickets/{ticket}/escalations/{escalation}/resolve', [TicketEscalationController::class, 'resolve']);

    Route::get('/tickets/{ticket}/knowledge-base-articles', [TicketKnowledgeBaseArticleController::class, 'index']);
    Route::post('/tickets/{ticket}/knowledge-base-articles', [TicketKnowledgeBaseArticleController::class, 'store']);
    Route::delete('/tickets/{ticket}/knowledge-base-articles/{knowledgeBaseArticle}', [TicketKnowledgeBaseArticleController::class, 'destroy']);

    Route::get('/tickets/{ticket}/satisfaction', [TicketSatisfactionSurveyController::class, 'show']);
    Route::post('/tickets/{ticket}/satisfaction', [TicketSatisfactionSurveyController::class, 'store']);

    Route::get(
        '/tickets/{ticket}/attachments/{attachment}/download',
        [TicketAttachmentController::class, 'download']
    );
    Route::get(
        '/tickets/{ticket}/attachments/{attachment}/preview',
        [TicketAttachmentController::class, 'preview']
    );

    Route::apiResource('tickets.attachments', TicketAttachmentController::class)
        ->only(['index', 'store', 'destroy']);

    Route::apiResource('tickets.comments', TicketCommentController::class)
        ->only(['index', 'store', 'destroy']);
});
