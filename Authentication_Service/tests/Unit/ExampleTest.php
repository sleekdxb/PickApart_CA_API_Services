<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\StrSession;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_logout_deletes_str_session_and_returns_200()
    {
        // Arrange: create a fake STR session row to log out
        $now = Carbon::now();
        $sessionId = (string) Str::ulid();

        StrSession::create([
            'session_id' => $sessionId,
            'acc_id' => (string) Str::ulid(), // no FK constraint assumed
            'ipAddress' => '127.0.0.1',
            'isActive' => 1,
            'start_time' => $now,
            'end_time' => $now->copy()->addDay(),
            'life_time' => 60,
            'created_at' => $now,
            'updated_at' => $now,
            'lastAccessed' => $now,
            'session_type' => 'MAIN',
            // a bogus token is fine; your controller catches invalidate() errors
            'access_token' => 'bogus-token-for-test',
            'fcm_token' => null,
            'sessionData' => json_encode([]),
        ]);

        // Act: call the logout endpoint with required payload
        $response = $this->postJson('/logout', [
            'session_id' => $sessionId,
            'account_type' => 'STR', // case-insensitive in your controller
        ]);

        // Assert: 200 + session deleted
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'code' => 200,
            ]);

        $this->assertDatabaseMissing('str_sessions', ['session_id' => $sessionId]);
    }
}
