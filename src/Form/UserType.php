<?php 
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isNewUser = $options['is_new_user'] ?? false;
        
        // Champs obligatoires pour tous les utilisateurs
        $builder
            ->add('firstname', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir le prénom.']),
                ]
            ])
            ->add('lastname', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir le nom.']),
                ]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir l\'email.']),
                ]
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Responsable RH' => 'RESPONSABLE_RH',
                    'Chef de Projet' => 'CHEF_PROJET',
                    'Employé' => 'EMPLOYEE',
                    'Candidat' => 'CONDIDAT'
                ],
                'placeholder' => 'Sélectionner...',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un rôle.']),
                ]
            ]);

        // Champs facultatifs si modification
        if (!$isNewUser) {
            $builder
                ->add('profilePhoto', FileType::class, [
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [
                        new File([
                            'maxSize' => '1024k',
                            'mimeTypes' => ['image/jpeg', 'image/png'],
                            'mimeTypesMessage' => 'Veuillez télécharger une image JPG ou PNG valide.',
                        ])
                    ],
                    'label' => 'Photo de profil (JPG ou PNG)',
                ])
                ->add('birthdayDate', DateType::class, [
                    'widget' => 'single_text',
                    'required' => false,
                ])
                ->add('gender', ChoiceType::class, [
                    'choices' => [
                        'Homme' => 'HOMME',
                        'Femme' => 'FEMME'
                    ],
                    'required' => false,
                    'placeholder' => 'Genre (optionnel)'
                ])
                ->add('num_tel', TextType::class, [
                    'required' => false,
                ])
                ->add('tt_restants', TextType::class, [
                    'required' => false,
                    'label' => 'Télétravail restants'
                ])
                ->add('conge_restant', TextType::class, [
                    'required' => false,
                    'label' => 'Jours de congé restants'
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new_user' => false,
        ]);
    }
}
