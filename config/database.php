<?php
/**
 * Database Configuration
 * 
 * Configure your MySQL connection settings here.
 * For XAMPP, default values should work out of the box.
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'credit_risk_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP has no password
define('DB_CHARSET', 'utf8mb4');

// PDO options for better error handling and security
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// Application settings
define('APP_NAME', 'Credit Risk Simulator');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/credit-risk-simulator');

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'credit_risk_session');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// Date/Time settings
date_default_timezone_set('Africa/Casablanca');
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'French');

/**
 * Get PDO database connection
 * 
 * @return PDO Database connection instance
 * @throws PDOException If connection fails
 */
function getConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, PDO_OPTIONS);
            
        } catch (PDOException $e) {
            // Log error in production, show message in development
            error_log("Database connection failed: " . $e->getMessage());
            
            // User-friendly error page
            die('
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Erreur de connexion</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 50px; text-align: center; }
                        .error-box { background: #fee; border: 1px solid #fcc; padding: 20px; border-radius: 8px; display: inline-block; }
                        h1 { color: #c00; }
                    </style>
                </head>
                <body>
                    <div class="error-box">
                        <h1>Erreur de connexion à la base de données</h1>
                        <p>Veuillez vérifier que MySQL est démarré et que la base de données est configurée.</p>
                        <p><small>Consultez le fichier config/database.php pour les paramètres.</small></p>
                    </div>
                </body>
                </html>
            ');
        }
    }
    
    return $pdo;
}
