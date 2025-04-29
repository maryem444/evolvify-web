<?php

namespace App\Form;

use App\Entity\Conge;
use App\Entity\CongeStatus;
use App\Entity\CongeType;
use App\Entity\CongeReason;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CongeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('leaveStart', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('leaveEnd', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de demande',
                'choices' => [
                    'Congé' => CongeType::CONGE,
                    'Télétravail' => CongeType::TT,
                ],
                'required' => true,
            ])
            ->add('reason', ChoiceType::class, [
                'label' => 'Motif',
                'choices' => [
                    'Congé payé' => CongeReason::CONGE_PAYE,
                    'Congé sans solde' => CongeReason::SANS_SOLDE,
                    'Maladie' => CongeReason::MALADIE,
                    
                ],
                'required' => true,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Conge::class,
        ]);
    }
}