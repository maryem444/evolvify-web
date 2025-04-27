<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Twilio\Rest\Client as TwilioClient;

class ForgetPasswordController extends AbstractController
{
    private $twilioAccountSid;
    private $twilioAuthToken;
    private $twilioPhoneNumber;
    private $recaptchaSecretKey;

    public function __construct()
    {
        // Load from environment variables
        $this->twilioAccountSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? '';
        $this->twilioAuthToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
        $this->twilioPhoneNumber = $_ENV['TWILIO_PHONE_NUMBER'] ?? '';
        $this->recaptchaSecretKey = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
    }

    /**
     * Forgot password request form
     */
    #[Route("/forgot-password", name: "app_forgot_password")]
    public function forgotPassword(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();

        // If password was already reset in this session, redirect to login
        if ($session->has('password_reset_completed')) {
            $this->addFlash('info', 'Votre mot de passe a déjà été réinitialisé. Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        // If code was already verified, redirect to reset password (would need token)
        if ($session->has('code_verified')) {
            $this->addFlash('info', 'Veuillez terminer la réinitialisation de votre mot de passe.');
            return $this->redirectToRoute('app_login');
        }

        // If form is submitted
        if ($request->isMethod('POST')) {
            $phoneNumber = $request->request->get('phone');

            // Validate reCAPTCHA
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            if (!$this->validateReCaptcha($recaptchaResponse)) {
                $this->addFlash('error', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');
                return $this->render('security/forgot_password.html.twig', [
                    'recaptcha_site_key' => '6LdcsSYrAAAAAKsqF0nscSHvY_Ky_BaUf39GLx7N'
                ]);
            }

            // Find user by phone number
            $user = $entityManager->getRepository(User::class)->findOneBy(['num_tel' => $phoneNumber]);

            if (!$user) {
                $this->addFlash('error', 'Aucun compte trouvé avec ce numéro de téléphone.');
                return $this->render('security/forgot_password.html.twig', [
                    'recaptcha_site_key' => '6LdcsSYrAAAAAKsqF0nscSHvY_Ky_BaUf39GLx7N'
                ]);
            }

            // Generate verification code (6 digits)
            $code = random_int(100000, 999999);

            // Store in session for verification
            $session = $request->getSession();
            $session->set('reset_code', $code);
            $session->set('reset_phone', $phoneNumber);
            $session->set('reset_user_id', $user->getId());
            $session->set('forgot_password_initiated', true);

            // Send SMS with code
            $smsResult = $this->sendSMSConfirmationCode($phoneNumber, $code);

            if (!$smsResult) {
                $this->addFlash('error', 'Erreur lors de l\'envoi du SMS. Veuillez réessayer plus tard.');
                return $this->render('security/forgot_password.html.twig', [
                    'recaptcha_site_key' => '6LdcsSYrAAAAAKsqF0nscSHvY_Ky_BaUf39GLx7N'
                ]);
            }

            // Redirect with proper cache control headers
            $response = new RedirectResponse($this->generateUrl('app_verify_code'));
            $response->setPrivate();
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
            return $response;
        }

        // Set cache control headers
        $response = $this->render('security/forgot_password.html.twig', [
            'user' => null,
            'recaptcha_site_key' => '6LdcsSYrAAAAAKsqF0nscSHvY_Ky_BaUf39GLx7N'
        ]);

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }

    /**
     * Verify SMS code
     */
    #[Route("/verify-code", name: "app_verify_code")]
    public function verifyCode(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();

        // Check if we have session data and if the forgot password process was initiated
        if (!$session->has('reset_code') || !$session->has('reset_user_id') || !$session->has('forgot_password_initiated')) {
            $this->addFlash('error', 'Vous devez d\'abord demander une réinitialisation de mot de passe.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // If password was already reset in this session, redirect to login
        if ($session->has('password_reset_completed')) {
            $this->addFlash('info', 'Votre mot de passe a déjà été réinitialisé. Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        // If form is submitted
        if ($request->isMethod('POST')) {
            $enteredCode = $request->request->get('code');
            $correctCode = $session->get('reset_code');

            if ($enteredCode == $correctCode) {
                // Code is correct, generate reset token
                $user = $entityManager->getRepository(User::class)->find($session->get('reset_user_id'));

                if (!$user) {
                    $this->addFlash('error', 'Utilisateur non trouvé.');
                    return $this->redirectToRoute('app_forgot_password');
                }

                // Generate unique token
                $token = bin2hex(random_bytes(32));
                $expirationDate = new \DateTime('+1 hour');

                // Save token to user
                $user->setResetToken($token);
                $user->setResetTokenExpiration($expirationDate);
                $entityManager->flush();

                // Clear session data related to code verification
                $session->remove('reset_code');
                $session->remove('reset_phone');

                // Set a flag that code verification is completed
                $session->set('code_verified', true);

                // Redirect to password reset form with proper headers
                $response = new RedirectResponse($this->generateUrl('app_reset_password', ['token' => $token]));
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
                return $response;
            } else {
                $this->addFlash('error', 'Code de vérification incorrect.');
            }
        }

        // Render the verify code template with correct headers
        $response = $this->render('security/verify_code.html.twig');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }

    /**
     * Reset password with token
     */
    #[Route("/reset-password/{token}", name: "app_reset_password")]
    public function resetPassword(Request $request, string $token, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $session = $request->getSession();

        // If password was already reset in this session, redirect to login
        if ($session->has('password_reset_completed')) {
            $this->addFlash('info', 'Votre mot de passe a déjà été réinitialisé. Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        // Check if code verification step was completed
        if (!$session->has('code_verified') && $session->has('forgot_password_initiated')) {
            $this->addFlash('error', 'Veuillez compléter la vérification du code avant de réinitialiser votre mot de passe.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // Find user by token
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        // Check if token is valid
        if (!$user || $user->getResetTokenExpiration() < new \DateTime()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Process form
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            // Check if passwords match
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('security/resetPwd.html.twig', [
                    'token' => $token,
                    'error' => ['messageKey' => 'Les mots de passe ne correspondent pas.']
                ]);
            }

            // Hash and set password
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Clear reset token
            $user->setResetToken(null);
            $user->setResetTokenExpiration(null);

            // Save changes
            $entityManager->flush();

            // Mark password as reset in session
            $session->set('password_reset_completed', true);

            // Clear other session flags related to reset process
            $session->remove('code_verified');
            $session->remove('reset_user_id');
            $session->remove('forgot_password_initiated');

            // Force session regeneration for security
            $session->migrate(true);

            $this->addFlash('success', 'Votre mot de passe a été créé avec succès. Vous pouvez maintenant vous connecter.');

            // Redirect with proper headers
            $response = new RedirectResponse($this->generateUrl('app_login'));
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
            return $response;
        }

        $response = $this->render('security/resetPwd.html.twig', [
            'token' => $token
        ]);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }

    /**
     * Send SMS with Twilio
     * Improved error handling and logging
     */
    private function sendSMSConfirmationCode(string $phoneNumber, int $code): bool
    {
        try {
            // Check if Twilio config is set
            if (empty($this->twilioAccountSid) || empty($this->twilioAuthToken) || empty($this->twilioPhoneNumber)) {
                error_log('Twilio configuration missing: TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, or TWILIO_PHONE_NUMBER not set');
                return false;
            }

            // Add Tunisia country code if needed
            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+216' . $phoneNumber;
            }

            // Initialize Twilio client properly
            $twilioClient = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);

            // Log the attempt (without showing sensitive data)
            error_log('Attempting to send SMS to: ' . substr($phoneNumber, 0, 4) . '****' . substr($phoneNumber, -4));

            // Send message
            $message = $twilioClient->messages->create(
                $phoneNumber,
                [
                    "from" => $this->twilioPhoneNumber,
                    "body" => "Votre code de vérification est : " . $code
                ]
            );

            // Log success
            error_log('SMS sent successfully, SID: ' . $message->sid);
            return true;
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Error sending SMS: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate reCAPTCHA
     * Improved implementation with more robust error handling and logging
     */
    private function validateReCaptcha($recaptchaResponse): bool
    {
        try {
            // Debug information
            error_log('Validating reCAPTCHA response: ' . substr($recaptchaResponse, 0, 20) . '...');
            
            // Check if key is configured - using environment variable
            $secretKey = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
            if (empty($secretKey)) {
                error_log('reCAPTCHA secret key is not set, skipping validation');
                return false; // Changed to false to enforce validation
            }
            
            // Check for dev environment skip
            if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev' && 
                isset($_ENV['SKIP_RECAPTCHA_IN_DEV']) && $_ENV['SKIP_RECAPTCHA_IN_DEV'] === 'true') {
                error_log('Skipping reCAPTCHA validation in dev environment');
                return true;
            }
            
            // Check if response is provided
            if (empty($recaptchaResponse)) {
                error_log('reCAPTCHA validation failed: No response provided');
                return false;
            }
            
            // Make the verification request
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = [
                'secret' => $secretKey, // Use the environment variable
                'response' => $recaptchaResponse,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
            ];
            
            // Use cURL for the request
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                error_log('reCAPTCHA verification cURL error: ' . $curlError);
                curl_close($ch);
                return false;
            }
            
            if ($httpCode !== 200) {
                error_log('reCAPTCHA verification failed with HTTP code: ' . $httpCode);
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
            
            // Parse the JSON response
            $result = json_decode($response, true);
            error_log('reCAPTCHA API response: ' . print_r($result, true));
            
            if ($result === null) {
                error_log('reCAPTCHA verification failed: Invalid JSON response');
                return false;
            }
            
            // Check for success
            if (isset($result['success']) && $result['success'] === true) {
                error_log('reCAPTCHA verification successful');
                return true;
            } else {
                error_log('reCAPTCHA verification failed: ' . json_encode($result));
                return false;
            }
        } catch (\Exception $e) {
            error_log('Exception during reCAPTCHA validation: ' . $e->getMessage());
            return false;
        }
    }
}