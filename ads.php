<?php
// Process BEFORE header.php include
require_once '../github_functions.php';

$message = '';
$debug_info = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $config = githubGetConfig();
    if (!$config) {
        $message = '<div class="alert alert-danger">Error: Could not fetch current config from GitHub. Check logs/github.log</div>';
        $debug_info = @file_get_contents('../logs/github.log') ?? 'No logs found';
    } else {
        // Only process if config was loaded successfully
        $config['ads']['global_ad_status'] = isset($_POST['global_ad_status']) ? 1 : 0;
        
        $config['ads']['banner']['status'] = isset($_POST['banner_ad_status']) ? 1 : 0;
        $config['ads']['banner']['id'] = trim($_POST['banner_ad_id'] ?? '');

        $config['ads']['interstitial']['status'] = isset($_POST['interstitial_ad_status']) ? 1 : 0;
        $config['ads']['interstitial']['id'] = trim($_POST['interstitial_ad_id'] ?? '');
        $config['ads']['interstitial']['interval_min'] = (int)($_POST['interstitial_interval_min'] ?? 0);
        $config['ads']['interstitial']['daily_limit'] = (int)($_POST['interstitial_daily_limit'] ?? 0);

        $config['ads']['app_open']['status'] = isset($_POST['app_open_ad_status']) ? 1 : 0;
        $config['ads']['app_open']['id'] = trim($_POST['app_open_ad_id'] ?? '');
        $config['ads']['app_open']['interval_min'] = (int)($_POST['app_open_interval_min'] ?? 0);
        $config['ads']['app_open']['daily_limit'] = (int)($_POST['app_open_daily_limit'] ?? 0);

        $config['ads']['native']['status'] = isset($_POST['native_ad_status']) ? 1 : 0;
        $config['ads']['native']['id'] = trim($_POST['native_ad_id'] ?? '');
        $config['ads']['native']['interval_min'] = (int)($_POST['native_interval_min'] ?? 0);
        $config['ads']['native']['daily_limit'] = (int)($_POST['native_daily_limit'] ?? 0);

        $config['ads']['rewarded']['status'] = isset($_POST['rewarded_ad_status']) ? 1 : 0;
        $config['ads']['rewarded']['id'] = trim($_POST['rewarded_ad_id'] ?? '');
        $config['ads']['rewarded']['interval_min'] = (int)($_POST['rewarded_interval_min'] ?? 0);
        $config['ads']['rewarded']['daily_limit'] = (int)($_POST['rewarded_daily_limit'] ?? 0);

        $result = githubUpdateConfig($config);
        
        if (stripos($result, "Success") !== false) {
            // Redirect with header BEFORE any output
            header("Location: ads.php?updated=1");
            exit;
        } else {
            $message = '<div class="alert alert-danger"><strong>Error:</strong> ' . htmlspecialchars($result) . '</div>';
            $debug_info = @file_get_contents('../logs/github.log') ?? 'No logs found';
        }
    }
}

// Fetch Current Settings - Fresh from GitHub
$ad_settings = githubGetConfig();
if (!$ad_settings) {
    die("Error: Could not fetch config from GitHub.");
}
$ad_data = $ad_settings['ads'] ?? [];

$ad_data['banner'] ??= [];
$ad_data['interstitial'] ??= [];
$ad_data['native'] ??= [];
$ad_data['rewarded'] ??= [];
$ad_data['app_open'] ??= [];

$updated = isset($_GET['updated']);

// NOW include header AFTER all processing
require_once 'header.php';
?>

<style>
.ad-card { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
.ad-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.ad-header h5 { margin: 0; font-weight: bold; font-size: 16px; display: flex; align-items: center; }
.ad-header h5 i { margin-right: 10px; color: #8b5cf6; }
.global-ad-card { background: #1f2937; color: white; border-radius: 10px; padding: 25px 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
.global-ad-card h4 { margin: 0; font-size: 18px; font-weight: bold; }
.global-ad-card p { margin: 5px 0 0 0; font-size: 13px; color: #9ca3af; }

/* Custom Toggle Switch */
.switch { position: relative; display: inline-block; width: 50px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: #3b82f6; }
input:checked + .slider:before { transform: translateX(26px); }

.form-label { font-size: 12px; font-weight: 600; color: #4b5563; text-transform: uppercase; margin-bottom: 8px; }
.form-control { border-radius: 6px; border: 1px solid #d1d5db; padding: 10px 15px; font-size: 14px; }
.form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
</style>

<div class="page-header">
    <div>
        <h2>Manage Ads Settings</h2>
        <p>Control all application advertisements (AdMob) directly from here via GitHub.</p>
    </div>
    <button type="submit" form="adsForm" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
</div>

<?php if ($message): ?>
    <?= $message ?>
<?php endif; ?>

<?php if ($debug_info && $message): ?>
<div class="alert alert-info">
    <strong>Debug Info:</strong><br>
    <pre style="font-size: 11px; max-height: 200px; overflow-y: auto;"><?= htmlspecialchars($debug_info) ?></pre>
</div>
<?php endif; ?>

<?php if ($updated): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>✓ Success!</strong> Ad settings updated successfully and synced from GitHub!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form id="adsForm" method="POST" action="ads.php">

    <!-- Global Ad Status -->
    <div class="global-ad-card">
        <div>
            <h4>Global Ad Status</h4>
            <p>Turn OFF to hide all ads immediately across the entire mobile application.</p>
        </div>
        <label class="switch">
            <input type="checkbox" name="global_ad_status" value="1" <?= !empty($ad_data['global_ad_status']) ? 'checked' : ''; ?>>
            <span class="slider"></span>
        </label>
    </div>

    <div class="row">
        <!-- Banner Ads -->
        <div class="col-md-6">
            <div class="ad-card">
                <div class="ad-header">
                    <h5><i class="fas fa-image"></i> Banner Ads</h5>
                    <label class="switch">
                        <input type="checkbox" name="banner_ad_status" value="1" <?= !empty($ad_data['banner']['status']) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Android Unit ID</label>
                    <input type="text" class="form-control" name="banner_ad_id" value="<?= htmlspecialchars($ad_data['banner']['id'] ?? ''); ?>" placeholder="ca-app-pub-xxxxxxxxxxxxxxxx/xxxxxxxxxx">
                </div>
            </div>
        </div>

        <!-- App Open Ads -->
        <div class="col-md-6">
            <div class="ad-card">
                <div class="ad-header">
                    <h5><i class="fas fa-door-open"></i> App Open Ads</h5>
                    <label class="switch">
                        <input type="checkbox" name="app_open_ad_status" value="1" <?= !empty($ad_data['app_open']['status']) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Android Unit ID</label>
                    <input type="text" class="form-control" name="app_open_ad_id" value="<?= htmlspecialchars($ad_data['app_open']['id'] ?? ''); ?>" placeholder="ca-app-pub-xxxxxxxxxxxxxxxx/xxxxxxxxxx">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Daily Limit</label>
                        <input type="number" class="form-control" name="app_open_daily_limit" value="<?= htmlspecialchars($ad_data['app_open']['daily_limit'] ?? 0); ?>" min="0">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Interval (Min)</label>
                        <input type="number" class="form-control" name="app_open_interval_min" value="<?= htmlspecialchars($ad_data['app_open']['interval_min'] ?? 0); ?>" min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Interstitial Ads -->
        <div class="col-md-6">
            <div class="ad-card">
                <div class="ad-header">
                    <h5><i class="fas fa-expand"></i> Interstitial Ads</h5>
                    <label class="switch">
                        <input type="checkbox" name="interstitial_ad_status" value="1" <?= !empty($ad_data['interstitial']['status']) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Android Unit ID</label>
                    <input type="text" class="form-control" name="interstitial_ad_id" value="<?= htmlspecialchars($ad_data['interstitial']['id'] ?? ''); ?>" placeholder="ca-app-pub-xxxxxxxxxxxxxxxx/xxxxxxxxxx">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Daily Limit</label>
                        <input type="number" class="form-control" name="interstitial_daily_limit" value="<?= htmlspecialchars($ad_data['interstitial']['daily_limit'] ?? 0); ?>" min="0">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Interval (Min)</label>
                        <input type="number" class="form-control" name="interstitial_interval_min" value="<?= htmlspecialchars($ad_data['interstitial']['interval_min'] ?? 0); ?>" min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Native Ads -->
        <div class="col-md-6">
            <div class="ad-card">
                <div class="ad-header">
                    <h5><i class="fas fa-id-card"></i> Native Ads</h5>
                    <label class="switch">
                        <input type="checkbox" name="native_ad_status" value="1" <?= !empty($ad_data['native']['status']) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="mb-3">
                    <label class="form-label">AdMob Native ID</label>
                    <input type="text" class="form-control" name="native_ad_id" value="<?= htmlspecialchars($ad_data['native']['id'] ?? ''); ?>" placeholder="ca-app-pub-xxxxxxxxxxxxxxxx/xxxxxxxxxx">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Daily Limit</label>
                        <input type="number" class="form-control" name="native_daily_limit" value="<?= htmlspecialchars($ad_data['native']['daily_limit'] ?? 0); ?>" min="0">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Interval (Min)</label>
                        <input type="number" class="form-control" name="native_interval_min" value="<?= htmlspecialchars($ad_data['native']['interval_min'] ?? 0); ?>" min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Rewarded Ads -->
        <div class="col-md-6">
            <div class="ad-card">
                <div class="ad-header">
                    <h5><i class="fas fa-video"></i> Rewarded Video</h5>
                    <label class="switch">
                        <input type="checkbox" name="rewarded_ad_status" value="1" <?= !empty($ad_data['rewarded']['status']) ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Android Unit ID</label>
                    <input type="text" class="form-control" name="rewarded_ad_id" value="<?= htmlspecialchars($ad_data['rewarded']['id'] ?? ''); ?>" placeholder="ca-app-pub-xxxxxxxxxxxxxxxx/xxxxxxxxxx">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Daily Limit</label>
                        <input type="number" class="form-control" name="rewarded_daily_limit" value="<?= htmlspecialchars($ad_data['rewarded']['daily_limit'] ?? 0); ?>" min="0">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Interval (Min)</label>
                        <input type="number" class="form-control" name="rewarded_interval_min" value="<?= htmlspecialchars($ad_data['rewarded']['interval_min'] ?? 0); ?>" min="0">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once 'footer.php'; ?>