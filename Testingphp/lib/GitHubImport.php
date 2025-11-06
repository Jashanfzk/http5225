<?php
/**
 * Reusable GitHub import module with debug logs
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class GitHubImporter
{
    private static array $logs = [];

    private static function log(string $msg): void
    {
        self::$logs[] = $msg;
        error_log('[GitHubImporter] ' . $msg);
    }

    public static function buildHeaders(): array
    {
        $headers = [
            'User-Agent: BrickMMO-Timesheets',
            'Accept: application/vnd.github+json'
        ];
        if (defined('GITHUB_TOKEN') && GITHUB_TOKEN) {
            $headers[] = 'Authorization: token ' . GITHUB_TOKEN;
            self::log('Auth token present: yes');
        } else {
            self::log('Auth token present: no');
        }
        return $headers;
    }

    public static function fetch(string $url, array $headers): array
    {
        self::log('Fetching URL: ' . $url);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);
        self::log('Response HTTP ' . $code . ($err ? (' curlError=' . $err) : ''));
        if ($code !== 200) {
            throw new Exception('GitHub API returned HTTP ' . $code);
        }
        $data = json_decode($response, true);
        $count = is_array($data) ? count($data) : 0;
        self::log('Decoded JSON items: ' . $count);
        return is_array($data) ? $data : [];
    }

    /**
     * Import repositories for a user owner (e.g., 'brickmmo') into applications table.
     * Returns ['imported'=>int, 'updated'=>int, 'total'=>int, 'logs'=>array]
     */
    public static function importUserRepos(PDO $db, string $owner): array
    {
        self::$logs = [];
        self::log('Starting import for owner=' . $owner);
        $headers = self::buildHeaders();
        $url     = 'https://api.github.com/users/' . $owner . '/repos?per_page=100&sort=name';
        $repos   = self::fetch($url, $headers);

        if (empty($repos)) {
            self::log('WARNING: No repositories returned from GitHub API');
            throw new Exception('No repositories found for owner: ' . $owner);
        }

        self::log('Found ' . count($repos) . ' repositories to process');
        $imported = 0;
        $updated  = 0;

        foreach ($repos as $repo) {
            $githubId = $repo['id'] ?? null;
            if (!$githubId) {
                self::log('Skipping repo without id');
                continue;
            }
            $name             = $repo['name'] ?? '';
            $visibility       = (isset($repo['private']) && $repo['private'] === true) ? 'private' : 'public';
            $primary_language = $repo['language'] ?? 'N/A';
            self::log('Processing repo id=' . $githubId . ' name=' . $name);

            $check = $db->prepare('SELECT id FROM applications WHERE github_id = ?');
            $check->execute([$githubId]);
            $existing = $check->fetch();

            try {
                if ($existing) {
                    $upd = $db->prepare('UPDATE applications SET name=?, full_name=?, description=?, html_url=?, clone_url=?, language=?, visibility=?, updated_at=CURRENT_TIMESTAMP WHERE github_id=?');
                    $upd->execute([
                        $repo['name'] ?? '',
                        $repo['full_name'] ?? '',
                        $repo['description'] ?? null,
                        $repo['html_url'] ?? null,
                        $repo['clone_url'] ?? null,
                        $primary_language,
                        $visibility,
                        $githubId,
                    ]);
                    $updated++;
                    self::log('Updated repo github_id=' . $githubId . ' name=' . $name);
                } else {
                    $ins = $db->prepare('INSERT INTO applications (github_id, name, full_name, description, html_url, clone_url, language, languages, visibility, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, "{}", ?, 1)');
                    $ins->execute([
                        $githubId,
                        $repo['name'] ?? '',
                        $repo['full_name'] ?? '',
                        $repo['description'] ?? null,
                        $repo['html_url'] ?? null,
                        $repo['clone_url'] ?? null,
                        $primary_language,
                        $visibility,
                    ]);
                    $imported++;
                    self::log('Inserted repo github_id=' . $githubId . ' name=' . $name);
                }
            } catch (Exception $e) {
                self::log('ERROR processing repo github_id=' . $githubId . ': ' . $e->getMessage());
                // Continue with next repo instead of failing entire import
            }
        }

        $total = (int)$db->query('SELECT COUNT(*) FROM applications')->fetchColumn();
        self::log('Import summary: imported=' . $imported . ' updated=' . $updated . ' total=' . $total);
        return ['imported' => $imported, 'updated' => $updated, 'total' => $total, 'logs' => self::$logs];
    }
}
