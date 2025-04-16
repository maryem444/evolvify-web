<?php

namespace App\Form;

use App\Entity\StatutProjet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjetFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du projet',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher par nom',
                    'class' => 'form-control'
                ]
            ])
            ->add('abbreviation', TextType::class, [
                'label' => 'Abréviation',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher par abréviation',
                    'class' => 'form-control'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'choices' => [
                    'Tous' => null,
                    'En cours' => StatutProjet::IN_PROGRESS,
                    'Terminé' => StatutProjet::COMPLETED,
                   
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Filtrer',
                'attr' => [
                    'class' => 'btn btn-primary mt-2'
                ]
            ]);
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}