<?php

namespace App\Support;

/**
 * SSRF protection for URLs the server fetches on a user's behalf.
 *
 * Only http(s) URLs whose host resolves exclusively to public IP addresses are allowed,
 * so an import can't be pointed at localhost, the Docker network or cloud metadata endpoints.
 * Redirect targets are checked with the same rules (see ImportVacancy).
 */
class UrlGuard
{
    public function isAllowed(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }

        $host = trim($parts['host'], '[]');
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);

        if ($addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string> IPv4 and IPv6 addresses of the host; empty if it doesn't resolve
     */
    protected function resolve(string $host): array
    {
        $ipv4 = gethostbynamel($host) ?: [];
        $ipv6 = array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6');

        return [...$ipv4, ...$ipv6];
    }
}
