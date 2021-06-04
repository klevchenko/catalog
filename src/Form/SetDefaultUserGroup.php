<?php

namespace App\Form;

use App\Entity\UserGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SetDefaultUserGroup extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('default_group', EntityType::class, array(
                'label' => 'Група за замовчуванням',
                'class' => UserGroup::class,
                'choice_label' => function ($UserGroup) {
                    return $UserGroup->getname() . ' (' . $UserGroup->getExtraCharge() . ')';
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserGroup::class,
        ]);
    }
}
