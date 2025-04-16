<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController extends AbstractController
{

/**
 * Page de création de mot de passe pour les nouveaux utilisateurs
 */
#[Route("/reset-password/{token}", name: "app_reset_password")]
public function resetPassword(Request $request, string $token, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
{
    // Trouver l'utilisateur par le token
    $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
    
    // Vérifier si le token est valide
    if (!$user || $user->getResetTokenExpiration() < new \DateTime()) {
        $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
        return $this->redirectToRoute('app_login');
    }
    
    // Traitement du formulaire
    if ($request->isMethod('POST')) {
        $password = $request->request->get('password');
        $confirmPassword = $request->request->get('confirm_password');
        
        // Vérifier que les mots de passe correspondent
        if ($password !== $confirmPassword) {
            $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            return $this->render('security/resetPwd.html.twig', [
                'token' => $token,
                'error' => ['messageKey' => 'Les mots de passe ne correspondent pas.']
            ]);
        }
        
        // Encoder et définir le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Effacer le token de réinitialisation
        $user->setResetToken(null);
        $user->setResetTokenExpiration(null);
        
        // Sauvegarder les changements
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre mot de passe a été créé avec succès. Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }
    
    // Afficher le formulaire
    return $this->render('security/resetPwd.html.twig', [
        'token' => $token
    ]);
}
}