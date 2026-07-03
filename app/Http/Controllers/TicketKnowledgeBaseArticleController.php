<?php

namespace App\Http\Controllers;

use App\Http\Resources\KnowledgeBaseArticleResource;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketKnowledgeBaseArticleController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        if ($request->user()->cannot('view', $ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $articles = $ticket->knowledgeBaseArticles()
            ->with(['category', 'createdBy'])
            ->latest('knowledge_base_article_ticket.created_at')
            ->get();

        return response()->json([
            'articles' => KnowledgeBaseArticleResource::collection($articles),
        ]);
    }

    public function store(Request $request, Ticket $ticket)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'article_id' => 'required|integer|exists:knowledge_base_articles,id',
        ]);

        $article = KnowledgeBaseArticle::findOrFail($validated['article_id']);

        $ticket->knowledgeBaseArticles()->syncWithoutDetaching([
            $article->id => ['attached_by_id' => $request->user()->id],
        ]);

        $ticket->recordActivity(
            type: 'knowledge_base_article_attached',
            user: $request->user(),
            description: 'Knowledge base article attached',
            metadata: [
                'article_id' => $article->id,
                'title' => $article->title,
            ]
        );

        $article->load(['category', 'createdBy']);

        return response()->json([
            'message' => 'Knowledge base article attached successfully',
            'article' => new KnowledgeBaseArticleResource($article),
        ], 201);
    }

    public function destroy(Request $request, Ticket $ticket, KnowledgeBaseArticle $knowledgeBaseArticle)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->knowledgeBaseArticles()->detach($knowledgeBaseArticle->id);

        $ticket->recordActivity(
            type: 'knowledge_base_article_detached',
            user: $request->user(),
            description: 'Knowledge base article detached',
            metadata: [
                'article_id' => $knowledgeBaseArticle->id,
                'title' => $knowledgeBaseArticle->title,
            ]
        );

        return response()->json([
            'message' => 'Knowledge base article detached successfully',
        ]);
    }
}
