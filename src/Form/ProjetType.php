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

class ProjetType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('name', TextType::class, [
        'label' => 'Nom du projet',
        'empty_data' => '',
        'attr' => ['class' => 'form-control'],
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Description',
        'empty_data' => '',
        'attr' => ['class' => 'form-control'],
      ])
      ->add('status', ChoiceType::class, [
        'choices' => [
          'En cours' => StatutProjet::IN_PROGRESS,
          'Terminé' => StatutProjet::COMPLETED,
        ],
        'label' => 'Statut du projet',
      ])
      ->add('endDate', DateType::class, [
        'label' => 'Date de fin',
        'widget' => 'single_text',
      ])
      ->add('starterAt', DateType::class, [
        'label' => 'Date de début',
        'widget' => 'single_text',
      ])
      ->add('abbreviation', TextType::class, [
        'label' => 'Abréviation',
        'empty_data' => '',
        'attr' => ['class' => 'form-control'],
      ])
      ->add('uploaded_files', FileType::class, [
        'label' => 'Fichier (PDF, image, etc.)',
        'mapped' => false, // Ne lie pas directement ce champ à l'entité
        'required' => false,
      ])
      ->add('save', SubmitType::class, [
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
