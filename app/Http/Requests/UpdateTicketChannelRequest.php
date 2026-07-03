<?php

namespace App\Http\Requests;

use App\Models\TicketChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $channel = $this->route('ticket_channel') ?? $this->route('ticketChannel');
        $channelId = $channel instanceof TicketChannel ? $channel->id : $channel;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_channels', 'name')->ignore($channelId),
            ],
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('ticket_channels', 'key')->ignore($channelId),
            ],
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
