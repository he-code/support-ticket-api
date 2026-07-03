<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKnowledgeBaseArticleRequest;
use App\Http\Requests\UpdateKnowledgeBaseArticleRequest;
use App\Http\Resources\KnowledgeBaseArticleResource;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KnowledgeBaseArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = KnowledgeBaseArticle::query()
            ->with(['category', 'createdBy'])
            ->latest();

        if (! $request->user()->isStaff()) {
            $query->where('is_published', true);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('summary', 'like', '%'.$search.'%')
                    ->orWhere('content', 'like', '%'.$search.'%');
            });
        }

        $articles = $query->paginate(10);

        return response()->json([
            'articles' => KnowledgeBaseArticleResource::collection($articles),
            'pagination' => [
                'total' => $articles->total(),
                'per_page' => $articles->perPage(),
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'from' => $articles->firstItem(),
                'to' => $articles->lastItem(),
            ],
        ]);
    }

    public function store(StoreKnowledgeBaseArticleRequest $request)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();
        $validated['slug'] = $validated['slug'] ?? $this->uniqueSlug($validated['title']);
        $validated['created_by_id'] = $request->user()->id;

        $article = KnowledgeBaseArticle::create($validated);
        $article->load(['category', 'createdBy']);

        return response()->json([
            'message' => 'Knowledge base article created successfully',
            'article' => new KnowledgeBaseArticleResource($article),
        ], 201);
    }

    public function show(Request $request, KnowledgeBaseArticle $knowledgeBaseArticle)
    {
        if (! $knowledgeBaseArticle->is_published && ! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $knowledgeBaseArticle->load(['category', 'createdBy']);

        return response()->json([
            'article' => new KnowledgeBaseArticleResource($knowledgeBaseArticle),
        ]);
    }

    public function update(UpdateKnowledgeBaseArticleRequest $request, KnowledgeBaseArticle $knowledgeBaseArticle)
    {
        if (! $request->user()->isAdmin() && $knowledgeBaseArticle->created_by_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        if (! isset($validated['slug']) && isset($validated['title'])) {
            $validated['slug'] = $this->uniqueSlug($validated['title'], $knowledgeBaseArticle->id);
        }

        $knowledgeBaseArticle->update($validated);
        $knowledgeBaseArticle->load(['category', 'createdBy']);

        return response()->json([
            'message' => 'Knowledge base article updated successfully',
            'article' => new KnowledgeBaseArticleResource($knowledgeBaseArticle),
        ]);
    }

    public function destroy(Request $request, KnowledgeBaseArticle $knowledgeBaseArticle)
    {
        if (! $request->user()->isAdmin() && $knowledgeBaseArticle->created_by_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $knowledgeBaseArticle->delete();

        return response()->json([
            'message' => 'Knowledge base article deleted successfully',
        ]);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (
            KnowledgeBaseArticle::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
