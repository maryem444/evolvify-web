<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PasswordResetSessionManager
{
    public function invalidatePasswordResetSession(SessionInterface $session): void
    {
        // Mark that password reset is completed
        $session->set('password_reset_completed', true);
        
        // Clear all reset-related session variables
        $session->remove('reset_code');
        $session->remove('reset_phone');
        $session->remove('reset_user_id');
        $session->remove('code_verified');
        $session->remove('forgot_password_initiated');
        
        // Regenerate session ID for security
        $session->migrate(true);
    }
    
    public function isPasswordResetCompleted(SessionInterface $session): bool
    {
        return $session->has('password_reset_completed');
    }
    
    public function isCodeVerified(SessionInterface $session): bool
    {
        return $session->has('code_verified');
    }
    
    public function isPasswordResetInitiated(SessionInterface $session): bool
    {
        return $session->has('forgot_password_initiated');
    }
}