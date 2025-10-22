<?php
/**
 * GitHub API Connection Test
 * This file tests the connection to the GitHub API
 */

require_once 'config/config.php';

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the GitHub organization to test
$organization = defined('GITHUB_ORG') ? GITHUB_ORG : 'BrickMMO';
echo "<h1>Testing GitHub API Connection for: $organization</h1>";

// Function to format JSON for display
function prettyPrintJson($json) {
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen($json);

    for($i = 0; $i < $json_length; $i++) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if($ends_line_level !== NULL) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if($in_escape) {
            $in_escape = false;
        } else if($char === '"') {
            $in_quotes = !$in_quotes;
        } else if(!$in_quotes) {
            switch($char) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;
                case '{': case '[':
                    $level++;
                    break;
                case ',':
                    $ends_line_level = $level;
                    break;
                case ':':
                    $post = " ";
                    break;
                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if($char === '\\') {
            $in_escape = true;
        }
        if($new_line_level !== NULL) {
            $result .= "\n".str_repeat("\t", $new_line_level);
        }
        $result .= $char.$post;
    }

    return "<pre>" . htmlspecialchars($result) . "</pre>";
}

// Test 1: Basic connection
echo "<h2>Test 1: Basic Organization Information</h2>";

$basic_headers = [
    "User-Agent: BrickMMO-Timesheets-Test",
    "Accept: application/vnd.github.v3+json"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.github.com/orgs/$organization");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $basic_headers);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Status Code: <strong>$httpCode</strong></p>";
if ($httpCode == 200) {
    echo "<p style='color: green;'>✓ Connection successful!</p>";
    $data = json_decode($response, true);
    echo "<p>Organization name: <strong>{$data['login']}</strong></p>";
    echo "<p>Public repositories: <strong>{$data['public_repos']}</strong></p>";
    
    if (isset($data['message'])) {
        echo "<p style='color: red;'>Error message: {$data['message']}</p>";
    }
    
    echo "<details><summary>View raw response</summary>";
    echo prettyPrintJson($response);
    echo "</details>";
} else {
    echo "<p style='color: red;'>✗ Connection failed!</p>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    
    // Try user endpoint instead of org
    echo "<h3>Trying user endpoint as fallback...</h3>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/users/$organization");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $basic_headers);
    $user_response = curl_exec($ch);
    $user_httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Status Code: <strong>$user_httpCode</strong></p>";
    if ($user_httpCode == 200) {
        echo "<p style='color: green;'>✓ User endpoint connection successful!</p>";
        echo "<details><summary>View raw response</summary>";
        echo prettyPrintJson($user_response);
        echo "</details>";
    }
}

// Test 2: Authenticated connection with OAuth credentials
echo "<h2>Test 2: Authenticated Connection (OAuth)</h2>";

$auth_query = "";
if (defined('GITHUB_CLIENT_ID') && !empty(GITHUB_CLIENT_ID)) {
    $auth_query = "client_id=" . GITHUB_CLIENT_ID;
    echo "<p>Using Client ID: " . substr(GITHUB_CLIENT_ID, 0, 5) . "...</p>";
    
    if (defined('GITHUB_CLIENT_SECRET') && !empty(GITHUB_CLIENT_SECRET)) {
        $auth_query .= "&client_secret=" . GITHUB_CLIENT_SECRET;
        echo "<p>Using Client Secret: " . substr(GITHUB_CLIENT_SECRET, 0, 5) . "...</p>";
    }
} else {
    echo "<p style='color: orange;'>No OAuth credentials configured</p>";
}

if (!empty($auth_query)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/orgs/$organization?$auth_query");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $basic_headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Status Code: <strong>$httpCode</strong></p>";
    if ($httpCode == 200) {
        echo "<p style='color: green;'>✓ OAuth authentication successful!</p>";
    } else {
        echo "<p style='color: red;'>✗ OAuth authentication failed!</p>";
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    }
}

// Test 3: Authenticated connection with token
echo "<h2>Test 3: Authenticated Connection (Token)</h2>";

if (defined('GITHUB_TOKEN') && !empty(GITHUB_TOKEN)) {
    echo "<p>Using Personal Access Token: " . substr(GITHUB_TOKEN, 0, 4) . "...</p>";
    
    $token_headers = $basic_headers;
    $token_headers[] = "Authorization: token " . GITHUB_TOKEN;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/orgs/$organization");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $token_headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Status Code: <strong>$httpCode</strong></p>";
    if ($httpCode == 200) {
        echo "<p style='color: green;'>✓ Token authentication successful!</p>";
    } else {
        echo "<p style='color: red;'>✗ Token authentication failed!</p>";
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>No personal access token configured</p>";
}

// Test 4: Rate limit check
echo "<h2>Test 4: Rate Limit Status</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.github.com/rate_limit");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $basic_headers);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Status Code: <strong>$httpCode</strong></p>";
if ($httpCode == 200) {
    echo "<p style='color: green;'>✓ Rate limit check successful!</p>";
    $rate_data = json_decode($response, true);
    if (isset($rate_data['resources']['core'])) {
        $core = $rate_data['resources']['core'];
        $remaining = $core['remaining'];
        $limit = $core['limit'];
        $reset_time = date('Y-m-d H:i:s', $core['reset']);
        
        echo "<p>Rate limit: <strong>$remaining/$limit</strong> requests remaining</p>";
        echo "<p>Resets at: <strong>$reset_time</strong></p>";
        
        if ($remaining < 10) {
            echo "<p style='color: red;'>Warning: Rate limit almost reached!</p>";
        }
    }
    
    echo "<details><summary>View raw rate limit data</summary>";
    echo prettyPrintJson($response);
    echo "</details>";
} else {
    echo "<p style='color: red;'>✗ Rate limit check failed!</p>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
}

// Test 5: List repositories
echo "<h2>Test 5: List First 5 Repositories</h2>";

// Try the organization repos endpoint first
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.github.com/orgs/$organization/repos?per_page=5");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $basic_headers);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$endpoint_used = "orgs";

// If that fails, try the user repos endpoint
if ($httpCode != 200) {
    echo "<p style='color: orange;'>Organization repos endpoint failed, trying user repos endpoint...</p>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/users/$organization/repos?per_page=5");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $basic_headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $endpoint_used = "users";
}

echo "<p>HTTP Status Code: <strong>$httpCode</strong></p>";
echo "<p>Using endpoint: <strong>$endpoint_used</strong></p>";

if ($httpCode == 200) {
    echo "<p style='color: green;'>✓ Repository list successful!</p>";
    $repos = json_decode($response, true);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Name</th><th>Description</th><th>Language</th><th>Stars</th></tr>";
    
    foreach ($repos as $repo) {
        echo "<tr>";
        echo "<td><a href='{$repo['html_url']}' target='_blank'>{$repo['name']}</a></td>";
        echo "<td>" . (empty($repo['description']) ? 'No description' : htmlspecialchars($repo['description'])) . "</td>";
        echo "<td>" . (empty($repo['language']) ? 'N/A' : htmlspecialchars($repo['language'])) . "</td>";
        echo "<td>{$repo['stargazers_count']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<details><summary>View raw repository data</summary>";
    echo prettyPrintJson($response);
    echo "</details>";
} else {
    echo "<p style='color: red;'>✗ Repository list failed!</p>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
}

// Display configuration suggestions
echo "<h2>Configuration Suggestions</h2>";

$suggestions = [];

if (!defined('GITHUB_TOKEN') || empty(GITHUB_TOKEN)) {
    $suggestions[] = "Add a GitHub Personal Access Token to config/config.php to increase your rate limit.";
}

if (!defined('GITHUB_CLIENT_ID') || empty(GITHUB_CLIENT_ID)) {
    $suggestions[] = "Add GitHub OAuth client ID and secret to config/config.php as a fallback authentication method.";
}

if ($httpCode != 200 && strtoupper($organization) !== $organization) {
    $suggestions[] = "Check the case of your organization name. GitHub API is case-sensitive.";
}

if (empty($suggestions)) {
    echo "<p style='color: green;'>✓ No issues detected with your configuration!</p>";
} else {
    echo "<ul style='color: orange;'>";
    foreach ($suggestions as $suggestion) {
        echo "<li>$suggestion</li>";
    }
    echo "</ul>";
}

// Summary
echo "<h2>Summary</h2>";
if ($httpCode == 200) {
    echo "<p style='color: green; font-size: 18px;'>✓ GitHub API connection is working properly!</p>";
    echo "<p>You should be able to use the GitHub API integration in the main application.</p>";
} else {
    echo "<p style='color: red; font-size: 18px;'>✗ GitHub API connection has issues that need to be resolved.</p>";
    echo "<p>Please check the suggestions above and ensure your GitHub organization exists and is accessible.</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}
h1, h2, h3 {
    color: #24292e;
}
pre {
    background-color: #f6f8fa;
    border: 1px solid #e1e4e8;
    border-radius: 6px;
    padding: 16px;
    overflow: auto;
}
details {
    margin: 10px 0;
    padding: 10px;
    background-color: #f6f8fa;
    border-radius: 6px;
}
summary {
    cursor: pointer;
    font-weight: bold;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th {
    background-color: #f6f8fa;
    text-align: left;
}
</style>