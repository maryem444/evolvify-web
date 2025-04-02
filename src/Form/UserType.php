<?php
// src/Form/UserType.php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your first name.',
                    ])
                ]
            ])
            ->add('lastname', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your last name.',
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your email address.',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Your email address cannot be longer than {{ limit }} characters.',
                    ]),
                ]
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password.',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters long.',
                    ])
                ]
            ])
            ->add('profilePhoto', FileType::class, [
                'required' => false, // Not required (nullable)
                'constraints' => [
                    new Length([
                        'max' => 1024 * 1024, // 1MB max size for the uploaded file
                        'maxMessage' => 'The file is too large. Maximum size allowed is 1MB.',
                    ])
                ]
            ])
            ->add('birthdayDate', DateType::class, [
                'widget' => 'single_text', // Displays as a single date input field
                'required' => false, // Optional
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'Male' => 'male',
                    'Female' => 'female',
                    'Other' => 'other',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select your gender.',
                    ])
                ]
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'User' => 'ROLE_USER',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a role.',
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class, // The form is associated with the User entity
        ]);
    }
}
