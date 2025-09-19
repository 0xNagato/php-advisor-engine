<?php

namespace App\Actions\Risk\Analyzers;

use App\Models\Booking;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsAction;

class AnalyzeIPRisk
{
    use AsAction;

    // Stub datacenter/VPN IP ranges (in production, use a proper database)
    protected array $datacenterRanges = [
        '104.16.0.0/12',    // Cloudflare
        '172.64.0.0/13',    // Cloudflare
        '188.114.96.0/20',  // Cloudflare
        '198.41.128.0/17',  // Cloudflare
        '162.158.0.0/15',   // Cloudflare
        '141.101.64.0/18',  // Cloudflare
        '108.162.192.0/18', // Cloudflare
        '173.245.48.0/20',  // Cloudflare
        '103.21.244.0/22',  // Cloudflare
        '103.22.200.0/22',  // Cloudflare
        '103.31.4.0/22',    // Cloudflare
        '190.93.240.0/20',  // Cloudflare
        '197.234.240.0/22', // Cloudflare
        '198.41.128.0/17',  // Cloudflare
        '18.0.0.0/8',       // AWS
        '52.0.0.0/8',       // AWS
        '35.0.0.0/8',       // Google Cloud
        '34.0.0.0/8',       // Google Cloud
        '104.40.0.0/13',    // Azure
        '104.208.0.0/13',   // Azure
        '13.64.0.0/11',     // Azure
        '13.96.0.0/12',     // Azure
        '20.0.0.0/8',       // Azure
        '40.74.0.0/15',     // Azure
        '40.76.0.0/14',     // Azure
        '40.80.0.0/12',     // Azure
        '40.96.0.0/12',     // Azure
        '40.112.0.0/13',    // Azure
        '40.120.0.0/14',    // Azure
        '51.0.0.0/8',       // Azure
        '52.0.0.0/8',       // Azure/AWS overlap
        '65.52.0.0/14',     // Azure
        '104.210.0.0/15',   // Azure
        '138.91.0.0/16',    // Azure
        '168.61.0.0/16',    // Azure
        '168.62.0.0/15',    // Azure
        '191.232.0.0/13',   // Azure
        '191.234.0.0/16',   // Azure
        '191.235.0.0/17',   // Azure
        '191.237.0.0/17',   // Azure
        '191.238.0.0/18',   // Azure
        '191.239.0.0/18',   // Azure
        '207.46.0.0/16',    // Azure
        '207.68.128.0/18',  // Azure
    ];

    /**
     * Analyze IP address for risk indicators
     *
     * @return array{score: int, reasons: array<string>, features: array<string, mixed>}
     */
    public function handle(string $ipAddress, ?string $venueRegion = null): array
    {
        $score = 0;
        $reasons = [];
        $features = [];

        if (empty($ipAddress) || !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return [
                'score' => 0, // Don't penalize missing IP
                'reasons' => [],
                'features' => ['ip_valid' => false]
            ];
        }

        $features['ip_valid'] = true;
        $features['ip_version'] = filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 6 : 4;

        // Check if IP is from datacenter/VPN
        if ($this->isDatacenterIP($ipAddress)) {
            $score += 30;
            $reasons[] = 'Datacenter/VPN IP address';
            $features['datacenter_ip'] = true;
        }

        // Check for private/local IP ranges
        // Note: Don't penalize private IPs as they're normal for local testing
        if ($this->isPrivateIP($ipAddress)) {
            // $score += 10;  // Disabled - normal for local/corporate networks
            // $reasons[] = 'Private IP address';  // Don't flag as suspicious
            $features['private_ip'] = true;
        }

        // Check velocity - how many bookings from this IP recently
        $velocityCheck = $this->checkVelocity($ipAddress);
        if ($velocityCheck['is_burst']) {
            $score += $velocityCheck['score'];
            $reasons[] = $velocityCheck['reason'];
            $features['velocity_burst'] = true;
            $features['velocity_count'] = $velocityCheck['count'];
        }

        // Geo location check (stub - in production use GeoIP database)
        if ($venueRegion) {
            $geoCheck = $this->checkGeoMismatch($ipAddress, $venueRegion);
            if ($geoCheck['mismatch']) {
                $score += 20;
                $reasons[] = 'IP location mismatch with venue region';
                $features['geo_mismatch'] = true;
                $features['ip_location'] = $geoCheck['location'];
            }
        }

        // Check for Tor exit nodes (stub)
        if ($this->isTorExitNode($ipAddress)) {
            $score += 40;
            $reasons[] = 'Tor exit node';
            $features['tor_exit'] = true;
        }

        $features['ip_address'] = $ipAddress;

        return [
            'score' => min(100, $score),
            'reasons' => $reasons,
            'features' => $features
        ];
    }

    /**
     * Check if IP is from a datacenter/VPN
     */
    protected function isDatacenterIP(string $ip): bool
    {
        foreach ($this->datacenterRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is private/local
     */
    protected function isPrivateIP(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Check velocity of bookings from this IP
     */
    protected function checkVelocity(string $ip): array
    {
        $cacheKey = 'ip_velocity:' . hash('sha256', $ip);
        $timestamps = Cache::get($cacheKey, []);

        // Clean old entries (older than 1 hour)
        $timestamps = array_filter($timestamps, fn($ts) => $ts > now()->subHour()->timestamp);

        // Add current timestamp
        $timestamps[] = now()->timestamp;

        // Update cache
        Cache::put($cacheKey, $timestamps, now()->addHours(2));

        $count = count($timestamps);

        // Check for burst activity - be more aggressive
        if ($count > 20) {
            // Extreme abuse - 20+ bookings
            return [
                'is_burst' => true,
                'score' => 100,  // Maximum score
                'reason' => "Extreme velocity abuse: {$count} bookings from same IP in last hour",
                'count' => $count
            ];
        } elseif ($count > 10) {
            // Very high - likely abuse
            return [
                'is_burst' => true,
                'score' => 80,
                'reason' => "Very high velocity: {$count} bookings from same IP in last hour",
                'count' => $count
            ];
        } elseif ($count > 5) {
            // Suspicious
            return [
                'is_burst' => true,
                'score' => 50,
                'reason' => "High velocity: {$count} bookings from same IP in last hour",
                'count' => $count
            ];
        } elseif ($count > 3) {
            // Worth noting
            return [
                'is_burst' => true,
                'score' => 20,
                'reason' => "Multiple bookings: {$count} from same IP in last hour",
                'count' => $count
            ];
        }

        return [
            'is_burst' => false,
            'score' => 0,
            'count' => $count
        ];
    }

    /**
     * Check for geo mismatch (stub implementation)
     */
    protected function checkGeoMismatch(string $ip, string $venueRegion): array
    {
        // In production, this would use a GeoIP database
        // For now, just return no mismatch
        return [
            'mismatch' => false,
            'location' => 'Unknown'
        ];
    }

    /**
     * Check if IP is a Tor exit node (stub)
     */
    protected function isTorExitNode(string $ip): bool
    {
        // In production, check against Tor exit node list
        // For now, check a few known ones
        $torExits = [
            '192.42.116.16',
            '199.87.154.255',
            '176.10.99.200'
        ];

        return in_array($ip, $torExits);
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range);
            $ip_binary = sprintf("%032b", ip2long($ip));
            $subnet_binary = sprintf("%032b", ip2long($subnet));
            return substr($ip_binary, 0, $bits) === substr($subnet_binary, 0, $bits);
        }

        return $ip === $range;
    }
}