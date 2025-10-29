<?php
/**
 * Lightweight GitHub API client for BrickMMO Timesheets
 * - Sets required headers (User-Agent, Authorization if token provided)
 * - Enforces SSL verification and sane timeouts
 * - Retries on transient errors (502/503/504, connection failures)
 * - ETag-ready via optional If-None-Match header and ETag parsing
 */

class GitHubClient {
    private ?string $token;
    private string $userAgent;
    private int $timeoutSeconds;
    private int $retryCount;
    private int $retryDelayMs;

    public function __construct(?string $token = null, string $userAgent = 'BrickMMO-Timesheets', int $timeoutSeconds = 12, int $retryCount = 2, int $retryDelayMs = 300) {
        $this->token = $token && trim($token) !== '' ? $token : null;
        $this->userAgent = $userAgent;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->retryCount = $retryCount;
        $this->retryDelayMs = $retryDelayMs;
    }

    /**
     * Perform a GET request.
     * @param string $url Full GitHub API URL
     * @param string|null $etag Optional ETag for conditional request
     * @return array{status:int, json:mixed, etag:?string, rate:array{remaining:?int, reset:?int, limit:?int}}
     */
    public function get(string $url, ?string $etag = null): array {
        $attempts = 0;

        do {
            $attempts++;
            $ch = curl_init();

            $headers = [
                'User-Agent: ' . $this->userAgent,
                'Accept: application/vnd.github+json'
            ];
            if ($this->token) {
                $headers[] = 'Authorization: token ' . $this->token;
            }
            if ($etag) {
                $headers[] = 'If-None-Match: ' . $etag;
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
            curl_setopt($ch, CURLOPT_HEADER, true); // fetch headers + body

            $response = curl_exec($ch);
            $curlErrNo = curl_errno($ch);
            $curlErr   = curl_error($ch);
            $status    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $rawHeaders = $response !== false ? substr($response, 0, $headerSize) : '';
            $rawBody    = $response !== false ? substr($response, $headerSize) : '';
            curl_close($ch);

            // Connection-level failure → retry
            if ($response === false || $curlErrNo !== 0) {
                if ($attempts <= $this->retryCount) {
                    usleep($this->retryDelayMs * 1000);
                    continue;
                }
                return [
                    'status' => 0,
                    'json' => null,
                    'etag' => null,
                    'rate' => ['remaining' => null, 'reset' => null, 'limit' => null],
                ];
            }

            // Transient server errors → retry
            if (in_array($status, [502, 503, 504], true) && $attempts <= $this->retryCount) {
                usleep($this->retryDelayMs * 1000);
                continue;
            }

            $parsedHeaders = $this->parseHeaders($rawHeaders);
            $newEtag = $parsedHeaders['ETag'] ?? null;
            $rate = [
                'remaining' => isset($parsedHeaders['X-RateLimit-Remaining']) ? (int) $parsedHeaders['X-RateLimit-Remaining'] : null,
                'reset'     => isset($parsedHeaders['X-RateLimit-Reset']) ? (int) $parsedHeaders['X-RateLimit-Reset'] : null,
                'limit'     => isset($parsedHeaders['X-RateLimit-Limit']) ? (int) $parsedHeaders['X-RateLimit-Limit'] : null,
            ];

            $json = null;
            if ($status !== 304 && $rawBody !== '') {
                $decoded = json_decode($rawBody, true);
                $json = $decoded === null ? $rawBody : $decoded;
            }

            return [
                'status' => $status,
                'json' => $json,
                'etag' => $newEtag,
                'rate' => $rate,
            ];
        } while ($attempts <= $this->retryCount);

        // Fallback (should not reach here)
        return [
            'status' => 0,
            'json' => null,
            'etag' => null,
            'rate' => ['remaining' => null, 'reset' => null, 'limit' => null],
        ];
    }

    /**
     * Convert raw header string to associative array (first occurrence wins).
     * @param string $rawHeaders
     * @return array<string,string>
     */
    private function parseHeaders(string $rawHeaders): array {
        $headers = [];
        $lines = preg_split("/\r?\n/", trim($rawHeaders));
        if (!$lines) {
            return $headers;
        }
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                if ($name !== '' && !isset($headers[$name])) {
                    $headers[$name] = $value;
                }
            }
        }
        return $headers;
    }
}

?>


