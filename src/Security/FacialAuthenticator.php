<?php 
namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Psr\Log\LoggerInterface;

class FacialAuthenticator extends AbstractAuthenticator {
    private $entityManager;
    private $logger;
    
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }
    
    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/facial-login/authenticate' && $request->isMethod('POST');
    }
    
    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);
        $faceDescriptor = $data['faceDescriptor'] ?? null;
        $faceImage = $data['faceImage'] ?? null;
        
        if (!$faceDescriptor) {
            throw new AuthenticationException('Descripteur facial manquant');
        }
        
        // Trouver l'utilisateur par les données faciales
        $userRepository = $this->entityManager->getRepository(User::class);
        
        // Rechercher tous les utilisateurs ayant l'authentification faciale activée
        $users = $userRepository->findBy(['facialAuthEnabled' => true]);
        
        if (empty($users)) {
            $this->logger->warning('Aucun utilisateur avec authentification faciale activée');
            throw new AuthenticationException('Aucun utilisateur avec authentification faciale activée');
        }
        
        // Trouver le meilleur match en comparant les descripteurs faciaux
        $bestMatch = $this->findBestMatch($users, $faceDescriptor);
        
        if (!$bestMatch) {
            throw new AuthenticationException('Aucune correspondance faciale trouvée');
        }
        
        // S'assurer que l'utilisateur a les méthodes requises
        if (!method_exists($bestMatch, 'getEmail')) {
            throw new AuthenticationException('L\'utilisateur n\'a pas de méthode getEmail');
        }
        
        return new SelfValidatingPassport(new UserBadge($bestMatch->getEmail()));
    }
    
    /**
     * Trouver le meilleur match parmi les utilisateurs
     */
    private function findBestMatch(array $users, array $faceDescriptor): ?User
    {
        $bestMatch = null;
        $bestDistance = INF;
        
        foreach ($users as $user) {
            try {
                if (!$user->getFacialData()) {
                    continue;
                }
                
                $storedData = json_decode($user->getFacialData(), true);
                
                if (!$storedData || !isset($storedData['descriptor'])) {
                    continue;
                }
                
                $storedDescriptor = $storedData['descriptor'];
                
                if (!is_array($storedDescriptor) || count($storedDescriptor) != count($faceDescriptor)) {
                    continue;
                }
                
                // Calculer la distance euclidienne entre les descripteurs
                $distance = $this->calculateEuclideanDistance($faceDescriptor, $storedDescriptor);
                
                $this->logger->debug('Distance avec ' . $user->getEmail() . ': ' . $distance);
                
                if ($distance < $bestDistance) {
                    $bestMatch = $user;
                    $bestDistance = $distance;
                }
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la comparaison pour ' . $user->getId() . ': ' . $e->getMessage());
                continue;
            }
        }
        
        // Appliquer un seuil de confiance (0.6 est une valeur courante pour face-api.js)
        if ($bestMatch && $bestDistance < 0.6) {
            return $bestMatch;
        }
        
        return null;
    }
    
    /**
     * Calculer la distance euclidienne entre deux descripteurs
     */
    private function calculateEuclideanDistance(array $descriptor1, array $descriptor2): float
    {
        if (count($descriptor1) !== count($descriptor2)) {
            return INF;
        }
        
        $sum = 0;
        foreach ($descriptor1 as $i => $val) {
            $sum += pow($val - $descriptor2[$i], 2);
        }
        
        return sqrt($sum);
    }
    
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Retourner null pour laisser la requête continuer
        return null;
    }
    
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'success' => false,
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }
}