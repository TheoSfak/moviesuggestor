<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Security.php';

use MovieSuggestor\Database;
use MovieSuggestor\Security;

// Initialize session and require authentication
Security::initSession();
Security::requireAuth();

$userId = Security::getUserId();
$db = new Database();
$pdo = $db->connect();

// Fetch user data
$stmt = $pdo->prepare("
    SELECT id, email, username, created_at, last_login, is_verified
    FROM users 
    WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user statistics
$stats = [];

// Favorites count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['favorites'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Watch later count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM watch_later WHERE user_id = ? AND watched = 0");
$stmt->execute([$userId]);
$stats['watch_later'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Watched count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM watch_later WHERE user_id = ? AND watched = 1");
$stmt->execute([$userId]);
$stats['watched'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Ratings count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ratings WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['ratings'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent activity
$stmt = $pdo->prepare("
    SELECT 'favorite' as type, movie_title as title, created_at as date
    FROM favorites 
    WHERE user_id = ? AND movie_title IS NOT NULL
    UNION ALL
    SELECT 'rating' as type, movie_title as title, created_at as date
    FROM ratings 
    WHERE user_id = ? AND movie_title IS NOT NULL
    ORDER BY date DESC 
    LIMIT 10
");
$stmt->execute([$userId, $userId]);
$recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Movie Suggestor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .header nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .header nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            font-weight: bold;
        }
        
        .profile-info h2 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .profile-info p {
            color: #666;
            margin: 5px 0;
        }
        
        .profile-info .badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section h3 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .activity-icon.favorite {
            background: #ffe0e0;
            color: #e74c3c;
        }
        
        .activity-icon.rating {
            background: #fff3e0;
            color: #f39c12;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-details strong {
            color: #333;
            font-size: 15px;
        }
        
        .activity-details small {
            color: #999;
            font-size: 13px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🎬 Movie Suggestor</h1>
            <nav>
                <a href="../index.php">Home</a>
                <a href="profile.php">Profile</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </div>
    </div>
    
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['username'], 0, 2)) ?>
            </div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($user['username']) ?></h2>
                <p><?= htmlspecialchars($user['email']) ?></p>
                <p>Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
                <?php if ($user['is_verified']): ?>
                    <span class="badge">✓ Verified Account</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">❤️</div>
                <div class="number"><?= $stats['favorites'] ?></div>
                <div class="label">Favorite Movies</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">📋</div>
                <div class="number"><?= $stats['watch_later'] ?></div>
                <div class="label">Watch Later</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">✅</div>
                <div class="number"><?= $stats['watched'] ?></div>
                <div class="label">Watched</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">⭐</div>
                <div class="number"><?= $stats['ratings'] ?></div>
                <div class="label">Ratings Given</div>
            </div>
        </div>
        
        <div class="section">
            <h3>Recent Activity</h3>
            
            <?php if (empty($recentActivity)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎬</div>
                    <p>No activity yet. Start by adding movies to your favorites or watch later list!</p>
                    <br>
                    <a href="../index.php" class="btn">Browse Movies</a>
                </div>
            <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($recentActivity as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-icon <?= $activity['type'] ?>">
                                <?= $activity['type'] === 'favorite' ? '❤️' : '⭐' ?>
                            </div>
                            <div class="activity-details">
                                <strong><?= htmlspecialchars($activity['title']) ?></strong><br>
                                <small>
                                    <?= $activity['type'] === 'favorite' ? 'Added to favorites' : 'Rated' ?> 
                                    • <?= date('F j, Y', strtotime($activity['date'])) ?>
                                </small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h3>Account Settings</h3>
            <p style="color: #666; margin-bottom: 20px;">Manage your account preferences and security settings</p>
            <a href="change-password.php" class="btn btn-secondary">Change Password</a>
            <a href="account-settings.php" class="btn btn-secondary">Account Settings</a>
        </div>
    </div>
</body>
</html>
