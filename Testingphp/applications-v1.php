<?php
/**
 * Applications V1 Page
 * Shows both repository details and user dashboard in one page
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Require login
requireLogin();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user information
    $user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch();
    
    // Get active applications
    $apps_stmt = $db->prepare("SELECT * FROM applications WHERE is_active = 1 ORDER BY name ASC");
    $apps_stmt->execute();
    $applications = $apps_stmt->fetchAll();
    
    // Get APPLICATIONS-V1 repository data
    $repo_stmt = $db->prepare("SELECT * FROM applications WHERE name = 'APPLICATIONS-V1' AND is_active = 1");
    $repo_stmt->execute();
    $repo = $repo_stmt->fetch();
    
    if ($repo) {
        // Get repository statistics
        $stats_stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT user_id) as contributors,
                SUM(duration) as total_hours,
                COUNT(*) as time_entries
            FROM hours 
            WHERE application_id = ?
        ");
        $stats_stmt->execute([$repo['id']]);
        $stats = $stats_stmt->fetch();
        
        // Get top contributors
        $contributors_stmt = $db->prepare("
            SELECT 
                u.name,
                u.login,
                u.avatar_url,
                SUM(h.duration) as total_hours
            FROM hours h
            JOIN users u ON h.user_id = u.id
            WHERE h.application_id = ?
            GROUP BY u.id, u.name, u.login, u.avatar_url
            ORDER BY total_hours DESC
            LIMIT 3
        ");
        $contributors_stmt->execute([$repo['id']]);
        $top_contributors = $contributors_stmt->fetchAll();
    }
    
    // Process form submission
    $message = '';
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_hours'])) {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            // Validate and sanitize input
            $application_id = intval($_POST['application_id'] ?? 0);
            $work_date = sanitizeInput($_POST['work_date'] ?? '');
            $duration = floatval($_POST['duration'] ?? 0);
            $description = sanitizeInput($_POST['description'] ?? '');
            
            // Validate required fields
            if ($application_id <= 0) {
                $error = 'Please select a repository.';
            } elseif (empty($work_date)) {
                $error = 'Please select a work date.';
            } elseif ($duration < MIN_HOURS_PER_ENTRY || $duration > MAX_HOURS_PER_DAY) {
                $error = 'Duration must be between ' . MIN_HOURS_PER_ENTRY . ' and ' . MAX_HOURS_PER_DAY . ' hours.';
            } elseif (strtotime($work_date) > time()) {
                $error = 'Work date cannot be in the future.';
            } else {
                // Check if application exists and is active
                $app_stmt = $db->prepare("SELECT id FROM applications WHERE id = ? AND is_active = 1");
                $app_stmt->execute([$application_id]);
                if (!$app_stmt->fetch()) {
                    $error = 'Invalid repository selected.';
                } else {
                    // Insert time entry
                    $insert_stmt = $db->prepare("
                        INSERT INTO hours (user_id, application_id, work_date, duration, description) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    if ($insert_stmt->execute([$_SESSION['user_id'], $application_id, $work_date, $duration, $description])) {
                        $message = 'Hours logged successfully!';
                        // Refresh the page to show updated data
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error = 'Failed to log hours. Please try again.';
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Applications V1 error: " . $e->getMessage());
    $error = 'An error occurred. Please try again later.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BrickMMO - Repository & Dashboard</title>
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
  
  <!-- Repository Details Section -->
  <section id="repository" style="padding: 3rem 1rem;">
    <div class="w3-container" style="max-width: 1200px; margin: 0 auto;">
      <div class="w3-card bg-brick brick-card" style="padding: 2rem;">
        <h2 class="section-title">APPLICATIONS-V1</h2>
        <p class="section-subtitle">Detailed view of repository information and statistics</p>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
          <div class="stats-card">
            <h4 class="text-subtext" style="margin: 0;">Total Hours</h4>
            <p class="stats-num brick-orange" style="margin: 0.5rem 0 0 0;"><?= number_format($stats['total_hours'] ?? 0, 0) ?></p>
          </div>
          <div class="stats-card">
            <h4 class="text-subtext" style="margin: 0;">Contributors</h4>
            <p class="stats-num brick-orange" style="margin: 0.5rem 0 0 0;"><?= (int)($stats['contributors'] ?? 0) ?></p>
          </div>
          <div class="stats-card">
            <h4 class="text-subtext" style="margin: 0;">Time Entries</h4>
            <p class="stats-num brick-orange" style="margin: 0.5rem 0 0 0;"><?= (int)($stats['time_entries'] ?? 0) ?></p>
          </div>
        </div>

        <!-- Top Contributors -->
        <h3 class="subsection-title">Top Contributors</h3>
        <div style="max-width: 100%;">
          <?php if (!empty($top_contributors)): ?>
            <?php foreach ($top_contributors as $contributor): ?>
              <div class="contributor-item">
                <div class="contributor-left">
                  <img alt="Contributor avatar" class="avatar" src="<?= htmlspecialchars($contributor['avatar_url']) ?>"/>
                  <span class="contributor-name"><?= htmlspecialchars($contributor['name'] ?? $contributor['login']) ?></span>
                </div>
                <span class="contributor-hours"><?= number_format($contributor['total_hours'], 0) ?> hours</span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="w3-padding w3-center w3-text-grey">No time entries recorded yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- User Dashboard Section -->
  <section id="dashboard" style="padding: 3rem 1rem;">
    <div class="w3-container" style="max-width: 1200px; margin: 0 auto;">
      <div class="w3-card bg-brick brick-card" style="padding: 2rem;">
        
        <!-- Dashboard Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
          <div>
            <h2 class="section-title">User Dashboard</h2>
            <p class="section-subtitle" style="margin: 0;">Log your hours and view your contribution history.</p>
          </div>
          <div class="user-info" style="margin: 0;">
            <img alt="User avatar" class="avatar avatar-lg" src="<?= htmlspecialchars($user['avatar_url']) ?>"/>
            <div class="user-details">
              <p class="user-name" style="margin: 0;"><?= htmlspecialchars($user['name'] ?? $user['login']) ?></p>
              <a href="auth/logout.php" class="logout-link">Logout</a>
            </div>
          </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
          <div class="w3-panel w3-green w3-padding w3-round w3-margin-bottom">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
          <div class="w3-panel w3-red w3-padding w3-round w3-margin-bottom">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
          
          <!-- Left Column: Log New Hours Form -->
          <div>
            <h3 class="subsection-title">Log New Hours</h3>
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
              
              <div style="margin-bottom: 1rem;">
                <label class="form-label" for="application_id">Repository</label>
                <select class="form-input" id="application_id" name="application_id" required>
                  <option value="">Select a repository...</option>
                  <?php foreach ($applications as $app): ?>
                    <option value="<?= $app['id'] ?>" <?= (isset($_POST['application_id']) && $_POST['application_id'] == $app['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($app['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div style="margin-bottom: 1rem;">
                <label class="form-label" for="work_date">Date</label>
                <input class="form-input" id="work_date" name="work_date" type="date" 
                       value="<?= isset($_POST['work_date']) ? htmlspecialchars($_POST['work_date']) : date('Y-m-d') ?>"
                       max="<?= date('Y-m-d') ?>" required/>
              </div>
              
              <div style="margin-bottom: 1rem;">
                <label class="form-label" for="duration">Hours</label>
                <input class="form-input" id="duration" name="duration" min="<?= MIN_HOURS_PER_ENTRY ?>" max="<?= MAX_HOURS_PER_DAY ?>" 
                       step="0.25" type="number" 
                       value="<?= isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : '' ?>"
                       placeholder="0.00" required/>
              </div>
              
              <div style="margin-bottom: 1rem;">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-input" id="description" name="description" rows="3" 
                          placeholder="Describe your work..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
              </div>
              
              <button type="submit" name="log_hours" class="w3-btn brick-btn" style="width: 100%; padding: 0.5rem 1rem;">
                Log Hours
              </button>
            </form>
          </div>

          <!-- Right Column: Personal History -->
          <div>
            <h3 class="subsection-title">Personal History</h3>
            <?php
            // Get user's recent time entries
            $recent_stmt = $db->prepare("
                SELECT 
                    h.*,
                    a.name as app_name,
                    a.html_url as app_url
                FROM hours h
                JOIN applications a ON h.application_id = a.id
                WHERE h.user_id = ?
                ORDER BY h.work_date DESC, h.created_at DESC
                LIMIT 10
            ");
            $recent_stmt->execute([$_SESSION['user_id']]);
            $recent_entries = $recent_stmt->fetchAll();
            ?>
            
            <?php if (!empty($recent_entries)): ?>
              <div class="w3-responsive">
                <table class="w3-table w3-bordered" style="background: white; border-radius: 0.375rem; overflow: hidden;">
                  <thead style="background: #F3E9E5;">
                    <tr>
                      <th style="padding: 0.75rem; font-weight: 600; color: #666666; font-size: 0.75rem;">REPOSITORY</th>
                      <th style="padding: 0.75rem; font-weight: 600; color: #666666; font-size: 0.75rem;">DATE</th>
                      <th style="padding: 0.75rem; font-weight: 600; color: #666666; font-size: 0.75rem;">HOURS</th>
                      <th style="padding: 0.75rem; font-weight: 600; color: #666666; font-size: 0.75rem;">DESCRIPTION</th>
                      <th style="padding: 0.75rem; font-weight: 600; color: #666666; font-size: 0.75rem;"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recent_entries as $entry): ?>
                      <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 0.75rem; font-weight: 600; color: #DD5A3A;"><?= htmlspecialchars($entry['app_name']) ?></td>
                        <td style="padding: 0.75rem; color: #666666; font-size: 0.875rem;"><?= date('Y-m-d', strtotime($entry['work_date'])) ?></td>
                        <td style="padding: 0.75rem; color: #666666; font-weight: 600;"><?= number_format($entry['duration'], 1) ?></td>
                        <td style="padding: 0.75rem; color: #666666; font-size: 0.875rem;">
                          <?php if ($entry['description']): ?>
                            <?= htmlspecialchars(substr($entry['description'], 0, 40)) ?><?= strlen($entry['description']) > 40 ? '...' : '' ?>
                          <?php else: ?>
                            <span style="color: #9ca3af;">No description</span>
                          <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem;">
                          <a href="#" style="color: #DD5A3A; text-decoration: none; font-size: 0.875rem; font-weight: 500;">Edit</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div style="background: white; padding: 2rem; text-align: center; border-radius: 0.375rem; border: 2px dashed #d1d5db;">
                <p class="text-subtext">No entries yet. Start logging your hours!</p>
              </div>
            <?php endif; ?>
          </div>
          
        </div>
      </div>
    </div>
  </section>

  <script>
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId.length > 1) {
          document.querySelector(targetId).scrollIntoView({
            behavior: 'smooth'
          });
        }
      });
    });
  </script>

</body>
</html>
