<?php

namespace App\Http\Controllers;

use App\Ai\Agents\XAUUSDAnalystAgent;
use App\Models\Signal;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    public function stream(Request $request): Response
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $userId = $request->user()?->id;

        return (new XAUUSDAnalystAgent($userId))
            ->stream($request->input('message'))
            ->usingVercelDataProtocol()
            ->then(function ($response) use ($userId) {
                // Persist signal to database after stream completes
                $this->persistSignal($response, $userId);
            });
    }

    private function persistSignal($response, ?int $userId): void
    {
        try {
            $text = $response->text ?? '';

            // Try to extract structured output from the streamed response
            // The structured data comes via tool results or final output
            $structured = null;
            foreach ($response->toolResults ?? [] as $result) {
                // Look for structured signal data in tool results
                $decoded = json_decode($result->content ?? '', true);
                if (isset($decoded['direction'])) {
                    $structured = $decoded;
                }
            }

            if (!$structured) {
                // Attempt to parse from response text as fallback
                return;
            }

            if (isset($structured['confidence']) && $structured['confidence'] < 50) {
                $structured['direction'] = 'WAIT';
            }

            Signal::create([
                'user_id'             => $userId,
                'direction'           => $structured['direction'] ?? 'WAIT',
                'entry_price'         => $structured['entry_price'] ?? null,
                'stop_loss'           => $structured['stop_loss'] ?? null,
                'take_profits'        => $structured['take_profits'] ?? null,
                'confidence'          => $structured['confidence'] ?? 0,
                'reasoning'           => $structured['reasoning'] ?? $text,
                'indicators_snapshot' => [],
                'timeframes_used'     => ['M15', 'H1'],
                'outcome'             => 'pending',
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to persist signal: ' . $e->getMessage());
        }
    }
}
