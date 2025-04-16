<?php
// src/Security/CustomAuthenticator.php
namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class CustomAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function authenticate(Request $request): Passport
    {
        // Extract username from request
        $username = $request->request->get('_username', '');
        
        // Store username in session (not localStorage) for failure case
        $request->getSession()->set(Security::LAST_USERNAME, $username);
        
        // Create authentication passport with credentials and CSRF protection
        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($request->request->get('_password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
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

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}