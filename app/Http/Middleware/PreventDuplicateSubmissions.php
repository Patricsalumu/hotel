<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDuplicateSubmissions
{
    private const SESSION_KEY = '_submission_ids';
    private const FALLBACK_WINDOW_SECONDS = 12;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isStateChangingRequest($request) || ! $request->hasSession()) {
            return $next($request);
        }

        $submissionId = $this->resolveSubmissionId($request);

        $entries = $this->prune($request->session()->get(self::SESSION_KEY, []));

        foreach ($entries as $entry) {
            if (($entry['id'] ?? null) === $submissionId) {
                return $this->duplicateResponse($request);
            }
        }

        $entries[] = ['id' => $submissionId, 'time' => now()->timestamp];
        $request->session()->put(self::SESSION_KEY, array_slice($entries, -300));

        return $next($request);
    }

    private function isStateChangingRequest(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function resolveSubmissionId(Request $request): string
    {
        $submissionId = (string) ($request->input('__submission_id') ?: $request->header('X-Submission-Id', ''));

        if ($submissionId !== '') {
            return 'id:'.$submissionId;
        }

        $payload = $request->except(['_token']);

        return 'fp:'.hash('sha256', implode('|', [
            $request->method(),
            $request->path(),
            (string) $request->ip(),
            (string) $request->userAgent(),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
        ]));
    }

    private function prune(array $entries): array
    {
        $ttlSeconds = max(((int) config('session.lifetime', 120)) * 60, 300);
        $cutoff = now()->timestamp - $ttlSeconds;

        $strictCutoff = now()->timestamp - self::FALLBACK_WINDOW_SECONDS;

        return array_values(array_filter(
            $entries,
            static function (array $entry) use ($cutoff, $strictCutoff): bool {
                if (! isset($entry['time'], $entry['id'])) {
                    return false;
                }

                if (str_starts_with((string) $entry['id'], 'fp:')) {
                    return $entry['time'] >= $strictCutoff;
                }

                return $entry['time'] >= $cutoff;
            }
        ));
    }

    private function duplicateResponse(Request $request): JsonResponse|RedirectResponse
    {
        $message = 'Requête déjà envoyée. Veuillez patienter.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 409);
        }

        return back()->withInput()->with('status', $message);
    }
}
