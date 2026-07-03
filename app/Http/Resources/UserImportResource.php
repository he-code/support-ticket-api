<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserImportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'created_count' => $this->created_count,
            'updated_count' => $this->updated_count,
            'skipped_count' => $this->skipped_count,
            'errors' => $this->errors,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
