<?php
/**
 * CampusFlow - Supabase Cloud Database Integration & Configuration
 */

declare(strict_types=1);

require_once __DIR__ . '/backend/supabase.php';

$env_file = __DIR__ . '/backend/supabase_env.php';
$config = SupabaseClient::loadConfig();
$test_result = null;
$save_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save' || $action === 'test') {
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1';
        $url = trim($_POST['url'] ?? '');
        $key = trim($_POST['key'] ?? '');
        $db_host = trim($_POST['db_host'] ?? '');
        $db_pass = trim($_POST['db_pass'] ?? '');

        $newConfig = [
            'enabled' => $enabled,
            'url' => $url,
            'key' => $key,
            'db_host' => $db_host,
            'db_port' => '5432',
            'db_name' => 'postgres',
            'db_user' => 'postgres',
            'db_pass' => $db_pass
        ];

        // Save to file
        $content = "<?php\nreturn " . var_export($newConfig, true) . ";\n";
        file_put_contents($env_file, $content);
        $config = $newConfig;
        $save_message = "Configuration saved successfully!";

        // Run test if requested
        if ($action === 'test' || $enabled) {
            $test_result = SupabaseClient::testConnection();
        }
    }
}

$sql_content = file_exists(__DIR__ . '/database/supabase_schema.sql') 
    ? file_get_contents(__DIR__ . '/database/supabase_schema.sql') 
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supabase Database Integration – CampusFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .supabase-badge {
            background: #10b981;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-full);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .step-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }
        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            color: #ffffff;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }
        .code-box {
            background: #0f172a;
            color: #38bdf8;
            font-family: 'Consolas', 'Fira Code', monospace;
            font-size: 0.85rem;
            padding: 1.25rem;
            border-radius: var(--radius-md);
            max-height: 280px;
            overflow-y: auto;
            position: relative;
        }
    </style>
</head>
<body style="background: #f8fafc; color: var(--text-main); min-height: 100vh;">

    <!-- Navigation Bar -->
    <header class="main-header">
        <div class="container nav-wrapper">
            <a href="index.html" class="brand-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                </svg>
                CampusFlow
                <span class="supabase-badge">⚡ Supabase Ready</span>
            </a>

            <div class="nav-actions">
                <a href="tables.php" class="btn btn-secondary btn-sm">📊 View DB Tables</a>
                <a href="login.html" class="btn btn-primary btn-sm">Portal Login →</a>
            </div>
        </div>
    </header>

    <main class="container" style="max-width: 960px; padding: 2.5rem 1.5rem;">
        
        <!-- Header Banner -->
        <div style="background: linear-gradient(135deg, #0f172a 0%, #064e3b 100%); color: #ffffff; padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; box-shadow: var(--shadow-md);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h1 style="font-size: 1.85rem; margin-bottom: 0.4rem;">⚡ Supabase Cloud Database Integration</h1>
                    <p style="color: #a7f3d0; font-size: 0.95rem;">Connect CampusFlow to your Supabase PostgreSQL cluster with Realtime & REST API.</p>
                </div>
                <div>
                    <span class="status-badge <?= SupabaseClient::isEnabled() ? 'status-green' : 'status-yellow' ?>" style="font-size: 0.9rem; padding: 0.4rem 1rem;">
                        <?= SupabaseClient::isEnabled() ? '● Supabase Active' : '○ Local Database (Standby)' ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($save_message): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                ✅ <?= htmlspecialchars($save_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($test_result !== null): ?>
            <?php if ($test_result['success']): ?>
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                    🎉 <strong>Connection Successful!</strong> Successfully connected to Supabase REST API (HTTP <?= $test_result['status'] ?>).
                </div>
            <?php else: ?>
                <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                    ❌ <strong>Connection Failed:</strong> <?= htmlspecialchars($test_result['error']) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 2rem;">
            
            <!-- Column 1: Supabase Configuration Form -->
            <div>
                <div class="step-card">
                    <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: #0f172a; display: flex; align-items: center;">
                        <span class="step-num">1</span> Supabase Project Credentials
                    </h2>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1.25rem;">
                        Get these from your Supabase dashboard at <strong>Project Settings → API</strong>.
                    </p>

                    <form method="POST" action="supabase_config.php">
                        <div class="form-group">
                            <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="enabled" value="1" <?= !empty($config['enabled']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                                <strong>Enable Supabase as Primary Database</strong>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supabase Project URL *</label>
                            <input type="url" name="url" class="form-control" placeholder="https://your-project-id.supabase.co" value="<?= htmlspecialchars($config['url'] ?? '') ?>" required>
                            <small style="color: #64748b; font-size: 0.75rem;">Example: https://abcdefghijklm.supabase.co</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supabase API Key (anon public or service_role) *</label>
                            <input type="text" name="key" class="form-control" placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." value="<?= htmlspecialchars($config['key'] ?? '') ?>" required>
                            <small style="color: #64748b; font-size: 0.75rem;">Found under Project Settings → API → anon public or service_role key</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">PostgreSQL Host (Optional for direct PDO connection)</label>
                            <input type="text" name="db_host" class="form-control" placeholder="db.your-project-id.supabase.co" value="<?= htmlspecialchars($config['db_host'] ?? '') ?>">
                        </div>

                        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                            <button type="submit" name="action" value="test" class="btn btn-primary" style="flex: 1;">
                                ⚡ Save & Test Connection
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Column 2: 4-Step Setup Guide & SQL Script -->
            <div>
                <div class="step-card">
                    <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: #0f172a; display: flex; align-items: center;">
                        <span class="step-num">2</span> 4-Step Supabase Setup Guide
                    </h2>

                    <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.88rem;">
                        <div style="padding-bottom: 0.75rem; border-bottom: 1px dashed #e2e8f0;">
                            <strong>1. Create Free Project:</strong><br>
                            Visit <a href="https://supabase.com" target="_blank" style="color: #059669; font-weight: 600;">supabase.com ↗</a> and create a new project.
                        </div>

                        <div style="padding-bottom: 0.75rem; border-bottom: 1px dashed #e2e8f0;">
                            <strong>2. Open SQL Editor:</strong><br>
                            In your project sidebar, click <strong>SQL Editor</strong> and then click <strong>"New query"</strong>.
                        </div>

                        <div style="padding-bottom: 0.75rem; border-bottom: 1px dashed #e2e8f0;">
                            <strong>3. Run Database Schema:</strong><br>
                            Copy the SQL script below and click <strong>RUN</strong> in Supabase.
                            <button type="button" class="btn btn-secondary btn-sm" onclick="copySqlSchema()" style="margin-top: 0.5rem; width: 100%;">
                                📋 Copy Full Supabase SQL Script
                            </button>
                        </div>

                        <div>
                            <strong>4. Connect CampusFlow:</strong><br>
                            Paste your Project URL & API Key in the form on the left, check <em>"Enable Supabase"</em>, and click <strong>Save & Test</strong>!
                        </div>
                    </div>
                </div>

                <!-- SQL Schema Code Box -->
                <div class="step-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                        <h3 style="font-size: 0.95rem; color: #0f172a; margin: 0;">database/supabase_schema.sql</h3>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="copySqlSchema()">Copy</button>
                    </div>
                    <pre class="code-box" id="sqlSchemaText"><?= htmlspecialchars($sql_content) ?></pre>
                </div>
            </div>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script src="assets/js/supabase_client.js"></script>
    <script>
        function copySqlSchema() {
            const text = document.getElementById('sqlSchemaText').innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('✓ Supabase SQL Schema copied to clipboard! Paste it into Supabase SQL Editor.');
            }).catch(err => {
                console.error(err);
                alert('Could not copy to clipboard. Please select and copy manually.');
            });
        }

        // Auto sync anon key to browser localStorage
        const keyInput = document.querySelector('input[name="key"]');
        if (keyInput && keyInput.value) {
            CampusDB.setAnonKey(keyInput.value);
        }

        document.querySelector('form').addEventListener('submit', (e) => {
            const k = keyInput.value.trim();
            if (k) {
                CampusDB.setAnonKey(k);
            }
        });
    </script>
</body>
</html>
