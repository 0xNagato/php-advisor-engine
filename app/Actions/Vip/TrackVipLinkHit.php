<?php

namespace App\Actions\Vip;

use App\Models\VipCode;
use App\Models\VipLinkHit;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

class TrackVipLinkHit
{
    use AsAction;

    public function handle(string $code, Request $request): void
    {
        $vipCode = VipCode::query()
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();

        // Capture raw and parsed query params
        $rawQuery = $request->getQueryString();
        $queryParams = $this->normalizeParams($request->query());

        VipLinkHit::query()->create([
            'vip_code_id' => $vipCode?->id,
            'code' => $code,
            'visited_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer_url' => $request->headers->get('referer'),
            'full_url' => $request->fullUrl(),
            'raw_query' => $rawQuery ? ($this->truncate($rawQuery, 4000)) : null,
            'query_params' => $queryParams,
        ]);
    }

    /**
     * Ensure arrays are preserved and strings are trimmed to reasonable size.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function normalizeParams(array $params): array
    {
        $normalized = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = array_map(function ($v) {
                    return is_string($v) ? $this->truncate($v, 1000) : $v;
                }, $value);
            } else {
                $normalized[$key] = is_string($value) ? $this->truncate($value, 1000) : $value;
            }
        }

        return $normalized;
    }

    private function truncate(string $value, int $limit): string
    {
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
    }
}
