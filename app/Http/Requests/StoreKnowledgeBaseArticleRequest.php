<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeBaseArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|integer|exists:ticket_categories,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:knowledge_base_articles,slug',
            'summary' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_published' => 'nullable|boolean',
        ];
    }
}
