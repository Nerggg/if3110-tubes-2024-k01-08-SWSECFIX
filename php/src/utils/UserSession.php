<?php

namespace src\utils;

use src\dao\{UserDao, UserRole};

/**
 * Class abstraction to access the user session
 */
class UserSession
{
    private static $encryptionKey = 'x4p9q2w7e3r8t5y1u6i0o2k4j8h5g3f9'; // better to store the key in env

    /**
     * Start the session if it is not already started
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Encrypt data before storing in session
     */
    private static function encrypt($data): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            serialize($data),
            'AES-256-CBC',
            self::$encryptionKey,
            0,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data from session
     */
    private static function decrypt($encryptedData)
    {
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            self::$encryptionKey,
            0,
            $iv
        );
        return unserialize($decrypted);
    }

    /**
     * Set the user session
     * @param user: UserDao object
     */
    public static function setUser(UserDao $user): void
    {
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
        ];
        $_SESSION['user'] = self::encrypt($userData);
    }

    /**
     * Get current user id
     */
    public static function getUserId(): int | null
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }
        $userData = self::decrypt($_SESSION['user']);
        return $userData['id'];
    }

    /**
     * Get current user email
     */
    public static function getUserEmail(): string | null
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }
        $userData = self::decrypt($_SESSION['user']);
        return $userData['email'];
    }

    /**
     * Get current user role
     */
    public static function getUserRole(): UserRole | null
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }
        $userData = self::decrypt($_SESSION['user']);
        return $userData['role'];
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Destroy the session
     */
    public static function destroy(): void
    {
        // Unset all of the session variables.
        $_SESSION = array();

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Finally, destroy the session.
        session_destroy();
    }
}
