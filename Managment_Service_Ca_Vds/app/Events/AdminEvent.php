<?php

namespace App\Events;

use App\Models\Account;
use App\Models\AdminChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class AdminEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $acc_id;
    public array $payload;
    public string $dataUrl;

    /**
     * Create a new event instance.
     */
    public function __construct(string $acc_id, array $payload = [])
    {
        $this->acc_id = $acc_id;
        $this->payload = $payload;

        // Fetch the latest account data
        $accounts = Account::query()->with([
            'session',
            'account_states',
            'vendor.vendorState',
            'vendor.mediaFiles.state',
            'memberships.payments',
            'amendment',
        ])->get();

        // Try to fetch the existing AdminChannel
        $adminChannel = AdminChannel::first();

        if (!$adminChannel) {
            // If no AdminChannel exists, create one
            $adminChannel = AdminChannel::create([
                'uuid' => (string) Str::uuid(),
                'data' => $accounts,
                'channel_frequency' => Str::random(16),
            ]);
        } else {
            // If it exists, update its data with the latest accounts
            $adminChannel->update([
                'data' => $accounts,
            ]);
        }

        // Ensure channel_frequency is set
        if (!isset($adminChannel->channel_frequency)) {
            throw new \Exception("channel_frequency is not set on AdminChannel.");
        }

        // Set the public data URL
        $this->dataUrl = url('public/' . route('admin.channel.data', $adminChannel->channel_frequency, false));
    }

    /**
     * The channel the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('admin.' . $this->acc_id);
    }

    /**
     * The data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'url' => $this->dataUrl,
        ];
    }

    /**
     * Custom event name.
     */
    public function broadcastAs(): string
    {
        return 'client-message';
    }
}
