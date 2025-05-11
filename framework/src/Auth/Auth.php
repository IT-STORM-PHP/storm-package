<?php

namespace StormBin\Package\Auth;

class Auth
{
    /**
     * Initialise la session de manière sécurisée.
     * Démarre la session si elle n'est pas déjà active.
     * @return void
     */
    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Vérifie si un utilisateur est connecté.
     * @return bool True si un utilisateur est connecté, False sinon.
     */
    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION['user']);
    }

    /**
     * Récupère les informations de l'utilisateur connecté.
     * @return array|null Retourne les informations de l'utilisateur ou null s'il n'est pas connecté.
     */
    public static function user(): ?array
    {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }

    /**
     * Connecte un utilisateur et sécurise sa session.
     * @param array $user Données de l'utilisateur (ex: depuis la base de données).
     * @return void
     */
    public static function login(array $user): void
{
    self::startSession();
    session_regenerate_id(true);  // Sécurise la session

    // Vérifie et stocke dynamiquement toutes les données utilisateur
    foreach ($user as $key => $value) {
        // On ne veut pas écraser les informations déjà stockées
        if (!isset($_SESSION['user'][$key])) {
            $_SESSION['user'][$key] = $value;
        }
    }

    // Ajoute aussi l'heure de début de session
    $_SESSION['user']['session_start'] = time();
}

    /**
     * Déconnecte l'utilisateur en détruisant la session.
     * @return void
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    /**
     * Redirige vers une autre page si l'utilisateur n'est pas connecté.
     * @param string $redirectUrl URL de redirection si l'utilisateur n'est pas connecté.
     * @return void
     */
    public static function requireAuth(string $redirectUrl = '/login/page'): void
    {
        if (!self::check()) {
            header("Location: $redirectUrl");
            exit();
        }
    }

    /**
     * Vérifie si l'utilisateur connecté possède un rôle spécifique.
     * @param string $role Rôle à vérifier.
     * @return bool True si l'utilisateur possède ce rôle, False sinon.
     */
    public static function hasRole(string $role): bool
    {
        self::startSession();
        return in_array($role, $_SESSION['user']['roles'] ?? []);
    }

    /**
     * Vérifie si la session est expirée (ex: après 30 minutes d'inactivité).
     * @param int $timeout Durée en secondes avant expiration (1800s = 30 minutes).
     * @return bool True si la session est expirée, False sinon.
     */
    public static function isSessionExpired(int $timeout = 1800): bool
    {
        self::startSession();

        if (!isset($_SESSION['user']['session_start'])) {
            return true;
        }

        if ((time() - $_SESSION['user']['session_start']) > $timeout) {
            self::logout();
            return true;
        }

        return false;
    }

    /**
     * Renouvelle la session si elle est valide.
     * @param int $timeout Durée avant expiration (ex: 30 minutes).
     * @return void
     */
    public static function refreshSession(int $timeout = 1800): void
    {
        if (self::check() && !self::isSessionExpired($timeout)) {
            $_SESSION['user']['session_start'] = time(); // Mise à jour du temps de session
        }
    }
}
