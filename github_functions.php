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
            CURLOPT_USERAGENT => "WBLandAdmin",
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        
        if (!curl_errno($ch) && $response) {
            curl_close($ch);
            $decoded = json_decode($response, true);
            if ($decoded === null) {
                logGitHubError("JSON decode error: " . json_last_error_msg());
                return null;
            }
            return $decoded;
        }
        
        if ($error) {
            logGitHubError("cURL error on attempt " . ($attempts + 1) . ": " . $error);
        }
        
        curl_close($ch);
        $attempts++;
        if ($attempts < 3) sleep(1);
    }
    
    logGitHubError("Failed to get config after 3 attempts");
    return null;
}

function githubUpdateConfig($config) {
    if (!GITHUB_TOKEN || GITHUB_TOKEN === 'YOUR_GITHUB_PERSONAL_ACCESS_TOKEN') {
        return "Error: GitHub token not configured. Update GITHUB_TOKEN in github_config.php";
    }
    
    $url = "https://api.github.com/repos/" . GITHUB_OWNER . "/" . GITHUB_REPO . "/contents/" . GITHUB_FILE_PATH;
    
    // 1. Get current SHA
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: token " . GITHUB_TOKEN,
            "User-Agent: WBLandAdmin",
            "Accept: application/vnd.github.v3+json"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    logGitHubError("GET SHA - HTTP Code: $httpCode, Response: " . substr($response, 0, 500));
    
    if ($curlError) {
        logGitHubError("cURL error getting SHA: " . $curlError);
        return "Error: Network request failed - " . $curlError;
    }
    
    if ($httpCode !== 200) {
        $errorMsg = isset($data['message']) ? $data['message'] : $response;
        logGitHubError("Failed to get SHA. Code: $httpCode. Message: " . $errorMsg);
        return "Error: Could not retrieve SHA. HTTP Code: $httpCode. Message: " . $errorMsg;
    }
    
    if (!isset($data['sha'])) {
        logGitHubError("SHA not found in response: " . json_encode($data));
        return "Error: SHA not found in response";
    }
    
    $sha = $data['sha'];
    
    // 2. Prepare new content
    $newContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $encodedContent = base64_encode($newContent);
    
    $payload = [
        "message" => "Update config.json from Admin Panel - " . date('Y-m-d H:i:s'),
        "content" => $encodedContent,
        "sha" => $sha,
        "branch" => GITHUB_BRANCH
    ];
    
    logGitHubError("Payload prepared. Content size: " . strlen($newContent) . " bytes");
    
    // 3. Update file via PUT request
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: token " . GITHUB_TOKEN,
            "User-Agent: WBLandAdmin",
            "Content-Type: application/json",
            "Accept: application/vnd.github.v3+json"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $responseData = json_decode($response, true);
    curl_close($ch);
    
    logGitHubError("PUT update - HTTP Code: $httpCode, Response: " . substr($response, 0, 500));
    
    if ($curlError) {
        logGitHubError("cURL error updating file: " . $curlError);
        return "Error: Network request failed - " . $curlError;
    }
    
    if ($httpCode === 200 || $httpCode === 201) {
        logGitHubError("Success: File updated with SHA: " . ($responseData['commit']['sha'] ?? 'unknown'));
        return "Success";
    } else {
        $errorMsg = isset($responseData['message']) ? $responseData['message'] : $response;
        logGitHubError("Failed to update file. HTTP Code: $httpCode. Message: " . $errorMsg);
        return "Error: Failed to update file. HTTP Code: $httpCode. Message: " . $errorMsg;
    }
}
?>