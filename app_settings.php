<?php
require_once 'header.php';
require_once '../github_functions.php';

$message = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $config = githubGetConfig();
    if (!$config) {
        $message = '<div class="alert alert-danger">Error: Could not fetch current config from GitHub. Check logs/github.log</div>';
        $debug_info = file_get_contents('logs/github.log') ?? 'No logs found';
    } else {
        $config['app']['latest_version'] = $_POST['app_version'];
        $config['app']['minimum_version'] = $_POST['app_version_old'];
        $config['app']['update_url'] = $_POST['app_update_url'];
        $config['app']['update_text'] = $_POST['app_update_text'];
        $config['app']['cancellable'] = isset($_POST['app_update_cancellable']) ? true : false;
        $config['app']['update_required'] = (($_POST['update_required'] ?? '0') == '1');

        $result = githubUpdateConfig($config);
        
        if (stripos($result, "Success") !== false) {
            $message = '<div class="alert alert-success"><strong>✓ Success!</strong> Settings updated successfully in GitHub!</div>';
        } else {
            $message = '<div class="alert alert-danger"><strong>✗ Error:</strong> ' . htmlspecialchars($result) . '</div>';
            $debug_info = file_get_contents('logs/github.log') ?? 'No logs found';
        }
    }
}

// Fetch current settings
$config = githubGetConfig();
if (!$config) {
    die('<div class="alert alert-danger">Error: Could not fetch config from GitHub. Please check:<br>1. Your GITHUB_TOKEN is valid<br>2. Internet connection is working<br>3. Check logs/github.log for details</div>');
}
$settings = $config['app'] ?? [];
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>App Update Settings (GitHub)</h5>
        </div>
        <div class="card-body">
            <?= $message ?>
            
            <?php if ($debug_info): ?>
            <div class="alert alert-info">
                <strong>Debug Info:</strong><br>
                <pre style="font-size: 11px; max-height: 200px; overflow-y: auto;"><?= htmlspecialchars($debug_info) ?></pre>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Latest (New) App Version</label>
                        <input type="text" name="app_version" class="form-control" value="<?= htmlspecialchars($settings['latest_version'] ?? '1.0.1') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Minimum (Old) App Version</label>
                        <input type="text" name="app_version_old" class="form-control" value="<?= htmlspecialchars($settings['minimum_version'] ?? '1.0.0') ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Update Text</label>
                    <textarea name="app_update_text" class="form-control" rows="3" required><?= htmlspecialchars($settings['update_text'] ?? '') ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Play Store URL</label>
                    <input type="url" name="app_update_url" class="form-control" value="<?= htmlspecialchars($settings['update_url'] ?? '') ?>" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="app_update_cancellable" id="cancellable" <?= ($settings['cancellable'] ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="cancellable">Allow Users to Skip Update (Cancellable)</label>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Update Required</label>
                    <select name="update_required" class="form-control">
                        <option value="1" <?= ($settings['update_required'] ?? false) ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !($settings['update_required'] ?? false) ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Settings</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>