<?php
// src/Security/CustomAuthenticator.php
namespace App\Security;

use App\Repository\UserBiometricDataRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class CustomAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;
    private ?UserBiometricDataRepository $userFaceRepository;

    public function __construct(UrlGeneratorInterface $urlGenerator, UserBiometricDataRepository $userFaceRepository = null)
    {
        $this->urlGenerator = $urlGenerator;
        $this->userFaceRepository = $userFaceRepository;
    }

    public function authenticate(Request $request): Passport
    {
        // Check if this is a facial recognition authentication request
        if ($request->request->has('facial_auth') && $request->request->get('facial_auth') === 'true') {
            // Extract the face descriptor data
            $faceDescriptor = $request->request->get('face_descriptor');
            $username = $request->request->get('_username', '');
            
            $request->getSession()->set(Security::LAST_USERNAME, $username);
            
            // Use custom credentials for facial authentication
            return new Passport(
                new UserBadge($username),
                new CustomCredentials(
                    function ($credentials, UserInterface $user) {
                        // The validation logic is in this callback
                        // $credentials will be the face descriptor
                        return $this->validateFacialRecognition($credentials, $user);
                    },
                    $faceDescriptor
                ),
                [
                    new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                ]
            );
        }
        
        // Regular password authentication
        $username = $request->request->get('_username', '');
        $request->getSession()->set(Security::LAST_USERNAME, $username);
        
        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($request->request->get('_password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    /**
     * Validate face descriptor against stored face data
     */
    private function validateFacialRecognition(string $faceDescriptor, UserInterface $user): bool
    {
        if (!$this->userFaceRepository) {
            return false;
        }
        
        // Convert the JSON face descriptor string to an array
        $descriptorData = json_decode($faceDescriptor, true);
        if (!$descriptorData) {
            return false;
        }
        
        // Fetch the user's stored face data
        $userFace = $this->userFaceRepository->findOneBy(['user' => $user]);
        if (!$userFace) {
            return false;
        }
        
        // Get the stored descriptor
        $storedDescriptor = json_decode($userFace->getDescriptor(), true);
        if (!$storedDescriptor) {
            return false;
        }
        
        // Calculate Euclidean distance between face descriptors
        // Lower distance means higher similarity
        $distance = $this->calculateEuclideanDistance($descriptorData, $storedDescriptor);
        
        // Threshold for facial recognition (typically between 0.5 and 0.6)
        // Lower value = stricter matching
        $threshold = 0.5;
        
        return $distance <= $threshold;
    }
    
    /**
     * Calculate Euclidean distance between two face descriptors
     */
    private function calculateEuclideanDistance(array $descriptor1, array $descriptor2): float
    {
        if (count($descriptor1) !== count($descriptor2)) {
            return PHP_FLOAT_MAX;
        }
        
        $sum = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $diff = $descriptor1[$i] - $descriptor2[$i];
            $sum += $diff * $diff;
        }
        
        return sqrt($sum);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Handle API responses for facial recognition
        if ($request->request->has('facial_auth') && $request->request->get('facial_auth') === 'true' && 
            $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse(['success' => true, 'redirect' => $this->urlGenerator->generate('app_home')]);
        }
        
        // Store authenticated user info in session, NOT localStorage
        $user = $token->getUser();
        $session = $request->getSession();
        
        // Store user identifier (usually email or username) in session
        $session->set('user_identifier', $user->getUserIdentifier());
        
        // Store user roles in session
        if (method_exists($user, 'getRoles')) {
            $roles = $user->getRoles();
            if (!empty($roles)) {
                $session->set('user_role', $roles[0]); // Assuming first role is primary
            }
        }
        
        // Check if there's a target path the user was trying to access
        if ($targetPath = $this->getTargetPath($session, $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // If no target path, redirect to home page for role-based redirection
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
    
    /**
     * Override to handle AJAX responses for facial recognition
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->request->has('facial_auth') && $request->request->get('facial_auth') === 'true' && 
            $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse(
                ['success' => false, 'message' => $exception->getMessage()],
                Response::HTTP_UNAUTHORIZED
            );
        }
        
        // Default behavior for regular form login
        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}