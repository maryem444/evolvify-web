<?php

namespace App\Form;

use App\Entity\Offre;
use App\Entity\Status;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('titre', TextType::class) 
        ->add('description', TextareaType::class)  
        ->add('datePublication', DateType::class, [
            'widget' => 'single_text',  
        ])
        ->add('dateExpiration', DateType::class, [
            'widget' => 'single_text',  
        ])
        ->add('status', ChoiceType::class, [
            'choices' => Status::cases(), // Utilise directement l'énumération
            'choice_label' => fn(Status $status) => $status->value, // Afficher les valeurs de l'enum
            'choice_value' => fn(?Status $status) => $status?->value, // Stocker les valeurs correctement
            'placeholder' => 'Choisir un statut',
            'label' => 'Statut'
        ])
        
        ->add("Add", SubmitType::class)
    ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offre::class,
        ]);
    }
}
