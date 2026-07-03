<?php

namespace App\Http\Requests;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKnowledgeBaseArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $article = $this->route('knowledge_base_article') ?? $this->route('knowledgeBaseArticle');
        $articleId = $article instanceof KnowledgeBaseArticle ? $article->id : $article;

        return [
            'category_id' => 'nullable|integer|exists:ticket_categories,id',
            'title' => 'sometimes|required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('knowledge_base_articles', 'slug')->ignore($articleId),
            ],
            'summary' => 'nullable|string|max:255',
            'content' => 'sometimes|required|string',
            'is_published' => 'nullable|boolean',
        ];
    }
}
