<?php
/**
 * GitHub Token Setup Page
 * This page allows administrators to configure a GitHub Personal Access Token
 */

require_once 'config/config.php';

// Check admin access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: index.php?error=access_denied");
    exit;
}

// Get the config file path
$config_file = __DIR__ . '/config/config.php';

// Handle form submission
$message = '';
$status = '';
$current_token = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['github_token'])) {
    $new_token = trim($_POST['github_token']);
    
    // Read the current config file
    $config_content = file_get_contents($config_file);
    
    // Check if GITHUB_TOKEN is already defined
    if (strpos($config_content, "define('GITHUB_TOKEN',") !== false) {
        // Replace the existing token
        $config_content = preg_replace(
            "/define\('GITHUB_TOKEN', ['\"].*['\"]\);/",
            "define('GITHUB_TOKEN', '$new_token');",
            $config_content
        );
    } else {
        // Add the token definition after the GITHUB_ORG line
        $config_content = str_replace(
            "define('GITHUB_ORG', 'BrickMMO');",
            "define('GITHUB_ORG', 'BrickMMO');\ndefine('GITHUB_TOKEN', '$new_token');",
            $config_content
        );
    }
    
    // Write the updated content back to the file
    if (file_put_contents($config_file, $config_content)) {
        $message = "GitHub token successfully updated. The new token will be used for API requests.";
        $status = 'success';
        $current_token = $new_token;
    } else {
        $message = "Failed to update config file. Please check file permissions.";
        $status = 'error';
    }
}

// Test the token if it exists
$token_works = false;
$rate_limit_info = '';

if (!empty($current_token)) {
    $ch = curl_init();
    $headers = [
        "User-Agent: BrickMMO-Timesheets",
        "Authorization: token $current_token"
    ];
    
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/rate_limit");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $token_works = true;
        $rate_data = json_decode($response, true);
        if (isset($rate_data['resources']['core'])) {
            $core = $rate_data['resources']['core'];
            $remaining = $core['remaining'];
            $limit = $core['limit'];
            $reset_time = date('Y-m-d H:i:s', $core['reset']);
            
            $rate_limit_info = "$remaining/$limit requests remaining (resets at $reset_time)";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Token Setup | BrickMMO</title>
    <link rel="icon" type="image/x-icon" href="./assets/BrickMMO_Logo_Coloured.png" />
    
    <!-- Google Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS Styling -->
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .token-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px auto;
            max-width: 800px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .token-form {
            margin-top: 20px;
        }
        
        .token-form input[type="text"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
        }
        
        .status-message {
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .status-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .status-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .token-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            margin-left: 10px;
            font-size: 14px;
        }
        
        .token-working {
            background-color: #d4edda;
            color: #155724;
        }
        
        .token-not-working {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .step-card {
            background: #f9f9f9;
            border-left: 4px solid #ff6b6b;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .step-card h3 {
            margin-top: 0;
            color: #333;
        }
        
        .step-card img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .token-scopes {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .token-scopes code {
            background: #e0e0e0;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <!-- header section -->
    <header>
        <!-- container for desktop header section, including logo and horizontal nav -->
        <nav id="desktop-nav">
            <!-- container for logo -->
            <div class="logo">
                <a href="index.php">
                    <img src="./assets/BrickMMO_Logo_Coloured.png" alt="brickmmo logo" width="80px">
                </a>
            </div>

            <!-- container for menu links -->
            <div>
                <ul class="nav-links">
                    <li><a href="index.php">Repositories</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="admin/dashboard.php">Admin</a></li>
                </ul>
            </div>
        </nav>

        <section id="hero">
            <h1>GitHub Token Setup</h1>
            <p>Configure a Personal Access Token to increase GitHub API rate limits</p>
        </section>
    </header>
    
    <!-- main section -->
    <main>
        <div class="token-container">
            <?php if (!empty($message)): ?>
                <div class="status-message status-<?php echo $status; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <h2>Current Token Status</h2>
            <p>
                GitHub Token: 
                <?php if (empty($current_token)): ?>
                    <strong>Not configured</strong>
                    <span class="token-status token-not-working">Not Working</span>
                <?php else: ?>
                    <strong><?php echo substr($current_token, 0, 6) . '...' . substr($current_token, -4); ?></strong>
                    <span class="token-status <?php echo $token_works ? 'token-working' : 'token-not-working'; ?>">
                        <?php echo $token_works ? 'Working' : 'Not Working'; ?>
                    </span>
                <?php endif; ?>
            </p>
            
            <?php if ($token_works && !empty($rate_limit_info)): ?>
                <p><strong>Rate limit:</strong> <?php echo $rate_limit_info; ?></p>
            <?php endif; ?>
            
            <div class="token-form">
                <h2>Update GitHub Token</h2>
                <form method="POST" action="">
                    <div>
                        <label for="github_token">Personal Access Token:</label>
                        <input type="text" id="github_token" name="github_token" placeholder="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" value="<?php echo htmlspecialchars($current_token); ?>" required>
                    </div>
                    <button type="submit" class="button-app-github">Save Token</button>
                </form>
            </div>
            
            <div style="margin-top: 40px;">
                <h2>How to Create a GitHub Personal Access Token</h2>
                <p>Follow these steps to generate a token for this application:</p>
                
                <div class="step-card">
                    <h3>Step 1: Access GitHub Developer Settings</h3>
                    <p>Log in to your GitHub account and navigate to <a href="https://github.com/settings/tokens" target="_blank">Settings > Developer settings > Personal access tokens > Fine-grained tokens</a>.</p>
                </div>
                
                <div class="step-card">
                    <h3>Step 2: Generate New Token</h3>
                    <p>Click on "Generate new token" and then "Generate new token (classic)".</p>
                </div>
                
                <div class="step-card">
                    <h3>Step 3: Configure Token</h3>
                    <p>Enter a name for your token (e.g., "BrickMMO Timesheets") and select an expiration date.</p>
                    <p>For scope, select the following permissions:</p>
                    <div class="token-scopes">
                        <p><code>public_repo</code> - Access public repositories</p>
                        <p><code>read:org</code> - Read organization data</p>
                    </div>
                </div>
                
                <div class="step-card">
                    <h3>Step 4: Generate and Copy Token</h3>
                    <p>Click "Generate token" at the bottom of the page. Copy the generated token immediately - you won't be able to see it again!</p>
                </div>
                
                <div class="step-card">
                    <h3>Step 5: Paste Token Here</h3>
                    <p>Paste the copied token into the form above and click "Save Token".</p>
                </div>
                
                <p><strong>Note:</strong> The token will be stored in the config file on the server. Make sure to use a token with minimal permissions and set an appropriate expiration date.</p>
                
                <div style="margin-top: 20px;">
                    <a href="test-github-api.php" class="button-app-info">Test GitHub API Connection</a>
                    <a href="index.php" class="button-app-github">Back to Repository List</a>
                </div>
            </div>
        </div>
    </main>
    
    <!-- footer section -->
    <footer>
        <div class="footer-container">
            <div class="social-icons">
                <a href="https://www.instagram.com/brickmmo/" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/channel/UCJJPeP10HxC1qwX_paoHepQ" target="_blank"><i class="fab fa-youtube"></i></a>
                <a href="https://x.com/brickmmo" target="_blank"><i class="fab fa-x"></i></a>
                <a href="https://github.com/BrickMMO" target="_blank"><i class="fab fa-github"></i></a>
                <a href="https://www.tiktok.com/@brickmmo" target="_blank"><i class="fab fa-tiktok"></i></a>
            </div>
            <div id="copyright-container">
                <p id="brickmmo copyright">&copy; BrickMMO. 2025. All rights reserved.</p>
                <p id="lego copyright">LEGO, the LEGO logo and the Minifigure are trademarks of the LEGO Group.</p>
            </div>
        </div>
    </footer>
</body>
</html>