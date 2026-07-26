<?php
// github_functions.php
require_once 'github_config.php';

function logGitHubError($message) {
    // Attempt to create logs dir, suppress errors
    @mkdir('logs', 0777, true);
    @file_put_contents('logs/github.log', "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL, FILE_APPEND);
}

function githubGetConfig() {
    $url = "https://raw.githubusercontent.com/" . GITHUB_OWNER . "/" . GITHUB_REPO . "/" . GITHUB_BRANCH . "/" . GITHUB_FILE_PATH;

    $attempts = 0;
    while ($attempts < 3) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => "WBLandAdmin"
        ]);

        $response = curl_exec($ch);
        if (!curl_errno($ch)) {
            curl_close($ch);
            return json_decode($response, true);
        }
        curl_close($ch);
        $attempts++;
        sleep(1);
    }
    
    return null;
}

function githubUpdateConfig($config) {
    $url = "https://api.github.com/repos/" . GITHUB_OWNER . "/" . GITHUB_REPO . "/contents/" . GITHUB_FILE_PATH . "?ref=" . GITHUB_BRANCH;
    
    // 1. Get SHA
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: token " . GITHUB_TOKEN, "User-Agent: WBLandAdmin"]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $data = json_decode($response, true);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return "Error: Could not retrieve SHA. HTTP Code: $httpCode. Response: " . htmlspecialchars($response);
    }
    
    if (!isset($data['sha'])) {
         return "Error: SHA not found in response. Response: " . htmlspecialchars($response);
    }
    
    $sha = $data['sha'];
    
    // 2. Prepare payload
    $newContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $payload = [
        "message" => "Update config.json from Admin Panel",
        "content" => base64_encode($newContent),
        "sha" => $sha,
        "branch" => GITHUB_BRANCH
    ];
    
    // 3. Update file
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: token " . GITHUB_TOKEN,
            "User-Agent: WBLandAdmin",
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 || $httpCode === 201) {
        return "Success";
    } else {
        return "Error: Failed to update file. HTTP Code: $httpCode. Response: " . htmlspecialchars($response);
    }
}
?>