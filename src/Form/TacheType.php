<?php
// src/Form/TacheType.php

namespace App\Form;

use App\Entity\Tache;
use App\Entity\Priorite;
use App\Entity\StatutTache;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TacheType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $idProjet = $options['idProjet'] ?? '96'; // Valeur par défaut si non fournie
    $idEmploye = $options['idEmploye'] ?? '87'; // ID employé statique temporaire

    $builder
      ->add('description', TextType::class, [
        'label' => 'Description',
        'required' => true,
        'attr' => [
          'placeholder' => 'Entrez une description'
        ]
      ])

      ->add('status', ChoiceType::class, [
        'label' => 'Statut',
        'choices' => [
          'À faire' => StatutTache::TO_DO,
          'En cours' => StatutTache::IN_PROGRESS,
          'Terminé' => StatutTache::DONE,
          'Annulé' => StatutTache::CANCELED
        ],
        'required' => true
      ])
      ->add('priority', ChoiceType::class, [
        'label' => 'Priorité',
        'choices' => [
          'Faible' => Priorite::LOW,
          'Moyenne' => Priorite::MEDIUM,
          'Haute' => Priorite::HIGH
        ],
        'required' => true
      ])
      ->add('location', TextType::class, [
        'label' => 'Localisation',
        'required' => true,
        'attr' => [
          'placeholder' => 'Entrez une localisation'
        ]
      ])
      ->add('createdAt', DateType::class, [
        'label' => 'Date de création',
        'widget' => 'single_text',
        'data' => new \DateTime(),
        'required' => true
      ])
      ->add('idProjet', HiddenType::class, [
        'data' => $idProjet,
        'mapped' => false
      ])
      ->add('idEmploye', HiddenType::class, [
        'data' => $idEmploye, // Assure-toi que l'ID est bien injecté
        'mapped' => false
      ])
      ->add('submit', SubmitType::class, [
        'label' => 'Enregistrer'
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Tache::class,
      'idProjet' => null,
      'idEmploye' => null, // Ajout de la nouvelle option
    ]);
  }
}
