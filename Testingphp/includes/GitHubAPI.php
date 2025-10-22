<?php
/**
 * GitHub API Helper Functions
 * Functions to interact with GitHub API for repository data
 */

/**
 * Fetches all GitHub repositories for a given organization with pagination
 * 
 * @param string $organization The GitHub organization name
 * @param string $token Optional GitHub API token for authenticated requests
 * @param bool $debug Whether to output debug information
 * @return array Array of repositories
 */
function fetchAllGitHubRepos($organization, $token = null, $debug = false) {
    $allRepos = [];
    $page = 1;
    $headers = [
        "User-Agent: BrickMMO-Timesheets",
        "Accept: application/vnd.github.v3+json"
    ];
    
    // Add authorization header if token is provided
    if ($token) {
        $headers[] = "Authorization: token $token";
    }
    
    // Check for cached repos (cache for 15 minutes to avoid rate limits)
    $cache_file = __DIR__ . '/../cache/github_repos_' . $organization . '.json';
    $cache_dir = __DIR__ . '/../cache';
    
    // Create cache directory if it doesn't exist
    if (!is_dir($cache_dir)) {
        if (!mkdir($cache_dir, 0755, true)) {
            if ($debug) {
                error_log("GitHub API Debug - Failed to create cache directory");
            }
        }
    }
    
    // Check for valid cache
    if (file_exists($cache_file)) {
        $cache_time = filemtime($cache_file);
        if (time() - $cache_time < 15 * 60) { // 15 minutes cache
            if ($debug) {
                error_log("GitHub API Debug - Using cached repository data");
            }
            $cached_data = file_get_contents($cache_file);
            $repos = json_decode($cached_data, true);
            if (is_array($repos) && !empty($repos)) {
                return $repos;
            }
        }
    }
    
    // Direct authentication with GitHub OAuth client credentials
    $auth_query = "";
    if (defined('GITHUB_CLIENT_ID') && !empty(GITHUB_CLIENT_ID)) {
        $auth_query = "client_id=" . GITHUB_CLIENT_ID;
        
        // Add client secret if available for higher rate limits
        if (defined('GITHUB_CLIENT_SECRET') && !empty(GITHUB_CLIENT_SECRET)) {
            $auth_query .= "&client_secret=" . GITHUB_CLIENT_SECRET;
        }
    }
    
    // Personal access token has higher priority if available
    if (defined('GITHUB_TOKEN') && !empty(GITHUB_TOKEN)) {
        $headers[] = "Authorization: token " . GITHUB_TOKEN;
        // If using token, don't use client credentials
        $auth_query = "";
    }
    
    // First, try to get a single repository to debug API access
    $ch = curl_init();
    $org_url = "https://api.github.com/orgs/$organization";
    if (!empty($auth_query)) {
        $org_url .= "?" . $auth_query;
    }
    curl_setopt($ch, CURLOPT_URL, $org_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL certificate
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set timeout to 10 seconds
    $orgResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($debug) {
        error_log("GitHub API Debug - Organization check: $organization, Status: $httpCode");
        error_log("GitHub API Debug - Response: " . substr($orgResponse, 0, 200) . "...");
    }
    
    // If we can't access the organization, try the alternative public repositories endpoint
    if ($httpCode != 200) {
        if ($debug) {
            error_log("GitHub API Debug - Failed to access organization via orgs endpoint, trying public repos");
        }
        
        // Try alternate approach using the users endpoint instead of orgs
        $alt_ch = curl_init();
        $alt_url = "https://api.github.com/users/$organization/repos?per_page=100&sort=updated";
        if (!empty($auth_query)) {
            $alt_url .= "&" . $auth_query;
        }
        
        curl_setopt($alt_ch, CURLOPT_URL, $alt_url);
        curl_setopt($alt_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($alt_ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($alt_ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($alt_ch, CURLOPT_TIMEOUT, 10);
        $alt_response = curl_exec($alt_ch);
        $alt_httpCode = curl_getinfo($alt_ch, CURLINFO_HTTP_CODE);
        curl_close($alt_ch);
        
        if ($debug) {
            error_log("GitHub API Debug - Alternative endpoint status: $alt_httpCode");
            error_log("GitHub API Debug - First 200 chars: " . substr($alt_response, 0, 200));
        }
        
        // If alternative endpoint works, use it
        if ($alt_httpCode == 200) {
            $alt_repos = json_decode($alt_response, true);
            if (is_array($alt_repos) && !empty($alt_repos)) {
                return $alt_repos;
            }
        }
        
        // If both approaches fail, return demo repositories
        if ($debug) {
            error_log("GitHub API Debug - Both approaches failed, using demo data");
        }
        
        return [
            [
                'name' => 'demo-repository',
                'full_name' => "$organization/demo-repository",
                'description' => 'Demo repository for testing. GitHub API access issue detected.',
                'html_url' => "https://github.com/$organization/demo-repository",
                'language' => 'PHP',
                'id' => 0,
                'is_demo' => true
            ],
            [
                'name' => 'another-demo-repo',
                'full_name' => "$organization/another-demo-repo",
                'description' => 'Second demo repository for testing. Please check GitHub API access.',
                'html_url' => "https://github.com/$organization/another-demo-repo",
                'language' => 'JavaScript',
                'id' => 1,
                'is_demo' => true
            ]
        ];
    }
    
    // If organization check is successful, proceed with fetching repos
    do {
        $ch = curl_init();
        $repos_url = "https://api.github.com/orgs/$organization/repos?per_page=100&page=$page";
        if (!empty($auth_query)) {
            $repos_url .= "&" . $auth_query;
        }
        
        curl_setopt($ch, CURLOPT_URL, $repos_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($debug && $page === 1) {
            error_log("GitHub API Debug - Repos fetch status: $httpCode");
            error_log("GitHub API Debug - First 200 chars: " . substr($response, 0, 200));
            
            // Check for rate limit info
            $rate_limit = curl_getinfo($ch, CURLINFO_HEADER_OUT);
            $remaining = @$http_response_header['X-RateLimit-Remaining'] ?? 'Unknown';
            $limit = @$http_response_header['X-RateLimit-Limit'] ?? 'Unknown';
            error_log("GitHub API Debug - Rate limits: $remaining/$limit remaining");
        }
        
        // Check for cURL errors
        if ($response === false) {
            error_log("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return [];
        }
        
        curl_close($ch);
        
        // Decode JSON response
        $repos = json_decode($response, true);
        
        // Check if response is valid and an array
        if (!is_array($repos)) {
            error_log("GitHub API Error: Invalid response format - " . substr($response, 0, 200));
            return [];
        }
        
        if (empty($repos)) {
            break;
        }
        
        $allRepos = array_merge($allRepos, $repos);
        $page++;
        
    } while (count($repos) === 100);
    
    if ($debug) {
        error_log("GitHub API Debug - Total repos fetched: " . count($allRepos));
    }
    
    // Save to cache if we have repositories
    if (!empty($allRepos)) {
        if ($debug) {
            error_log("GitHub API Debug - Saving repositories to cache");
        }
        file_put_contents($cache_file, json_encode($allRepos));
    }
    
    return $allRepos;
}

/**
 * Fetches repository details from GitHub API
 * 
 * @param string $owner Repository owner (user or organization)
 * @param string $repo Repository name
 * @param string $token Optional GitHub API token for authenticated requests
 * @return array|null Repository data or null on error
 */
function fetchGitHubRepoDetails($owner, $repo, $token = null) {
    $url = "https://api.github.com/repos/$owner/$repo";
    
    $headers = [
        "User-Agent: BrickMMO-Timesheets",
        "Accept: application/vnd.github.v3+json"
    ];
    
    // Add authorization header if token is provided
    if ($token) {
        $headers[] = "Authorization: token $token";
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($statusCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Fetches repository contributors from GitHub API
 * 
 * @param string $owner Repository owner (user or organization)
 * @param string $repo Repository name
 * @param string $token Optional GitHub API token for authenticated requests
 * @return array|null Contributors data or null on error
 */
function fetchGitHubRepoContributors($owner, $repo, $token = null) {
    $url = "https://api.github.com/repos/$owner/$repo/contributors";
    
    $headers = [
        "User-Agent: BrickMMO-Timesheets",
        "Accept: application/vnd.github.v3+json"
    ];
    
    // Add authorization header if token is provided
    if ($token) {
        $headers[] = "Authorization: token $token";
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($statusCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Fetches repository commit statistics from GitHub API
 * 
 * @param string $owner Repository owner (user or organization)
 * @param string $repo Repository name
 * @param string $token Optional GitHub API token for authenticated requests
 * @return array|null Commit stats or null on error
 */
function fetchGitHubRepoCommitStats($owner, $repo, $token = null) {
    $url = "https://api.github.com/repos/$owner/$repo/stats/commit_activity";
    
    $headers = [
        "User-Agent: BrickMMO-Timesheets",
        "Accept: application/vnd.github.v3+json"
    ];
    
    // Add authorization header if token is provided
    if ($token) {
        $headers[] = "Authorization: token $token";
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($statusCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Fetches GitHub user details
 * 
 * @param string $username GitHub username
 * @param string $token Optional GitHub API token for authenticated requests
 * @return array|null User details or null on error
 */
function fetchGitHubUserDetails($username, $token = null) {
    $url = "https://api.github.com/users/$username";
    
    $headers = [
        "User-Agent: BrickMMO-Timesheets",
        "Accept: application/vnd.github.v3+json"
    ];
    
    // Add authorization header if token is provided
    if ($token) {
        $headers[] = "Authorization: token $token";
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($statusCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}