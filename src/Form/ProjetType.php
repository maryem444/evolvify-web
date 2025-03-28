<?php

namespace App\Form;

use App\Entity\Projet;
use App\Entity\StatutProjet;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProjetType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('name', TextType::class, [
        'label' => 'Nom du projet',
        'constraints' => [
          new Length([
            'min' => 3,
            'max' => 255,
            'minMessage' => 'Le nom du projet doit contenir au moins {{ limit }} caractères',
            'maxMessage' => 'Le nom du projet ne peut pas dépasser {{ limit }} caractères'
          ])
        ]
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Description',
        'required' => true,
        'constraints' => [
          new Length([
            'max' => 1000,
            'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
          ])
        ]
      ])
      ->add('status', ChoiceType::class, [
        'choices' => [
          'En cours' => StatutProjet::IN_PROGRESS,
          'Terminé' => StatutProjet::COMPLETED,
        ],
        'label' => 'Statut du projet',
        'required' => true
      ])
      ->add('endDate', DateType::class, [
        'label' => 'Date de fin',
        'widget' => 'single_text',
        'required' => true
      ])
      ->add('starterAt', DateType::class, [
        'label' => 'Date de début',
        'widget' => 'single_text',
        'required' => true
      ])
      ->add('abbreviation', TextType::class, [
        'label' => 'Abréviation',
        'constraints' => [
          new Length([
            'min' => 2,
            'max' => 10,
            'minMessage' => 'L\'abréviation doit contenir au moins {{ limit }} caractères',
            'maxMessage' => 'L\'abréviation ne peut pas dépasser {{ limit }} caractères'
          ])
        ]
      ])
      ->add('uploaded_files', FileType::class, [
        'label' => 'Fichier (PDF, image, etc.)',
        'mapped' => false, // Ne lie pas directement ce champ à l'entité
        'required' => false,
        'constraints' => [
          new File([
            'maxSize' => '5M',
            'mimeTypes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'mimeTypesMessage' => 'Veuillez uploader un fichier PDF, JPEG ou PNG',
          ]),
        ],
      ]);

    // Ajouter un champ pour afficher le fichier existant


    $builder->add('save', SubmitType::class, [
      'label' => 'Enregistrer le projet',
      'attr' => [
        'class' => 'btn btn-primary'
      ]
    ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Projet::class,
    ]);
  }
}
