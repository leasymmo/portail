<?php
/**
 * ==============================================================================
 * GESTIONNAIRE DE CONNEXION PDO MYSQL (SINGLETON SÉCURISÉ)
 * Conciergerie Carré d'Hôtes - Portail Voyageurs
 * ==============================================================================
 */

namespace Cdh\Portal;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static ?string $videoTableName = null;

    /**
     * Retourne l'instance unique de connexion PDO
     *
     * @throws PDOException
     */
    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host   = Env::get('DB_HOST', 'localhost');
        $port   = Env::get('DB_PORT', '3306');
        $dbname = Env::get('DB_NAME', '');
        $user   = Env::get('DB_USER', '');
        $pass   = Env::get('DB_PASS', '');

        if (empty($dbname) || empty($user) || empty($pass) || 
            $pass === 'METTEZ_VOTRE_MOT_DE_PASSE_ICI' || $pass === 'VOTRE_MOT_DE_PASSE') {
            throw new PDOException("Configuration requise : Veuillez renseigner vos identifiants MySQL complets dans le fichier .env (variables DB_NAME, DB_USER, DB_PASS).");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        self::$instance = new PDO($dsn, $user, $pass, $options);
        return self::$instance;
    }

    /**
     * Détecte et met en cache en mémoire le nom de la table de vidéos ('videos' ou 'tutorials')
     */
    public static function getVideoTableName(): string
    {
        if (self::$videoTableName !== null) {
            return self::$videoTableName;
        }

        $pdo = self::getConnection();
        $stmt = $pdo->query("SHOW TABLES LIKE 'videos'");
        self::$videoTableName = ($stmt->rowCount() > 0) ? 'videos' : 'tutorials';
        return self::$videoTableName;
    }

    /**
     * Réinitialise l'instance (utilisé pour les tests unitaires)
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::$videoTableName = null;
    }
}
