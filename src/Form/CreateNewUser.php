<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\UserGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType as TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class CreateNewUser extends AbstractType
{

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $user = $this->security->getUser();

        $builder
            ->add('name', TextType::class,[
                'label' => 'І`мя',
                'required' => true,
            ])
            ->add('number', TextType::class,[
                'label' => 'Номер',
                'required' => false,
            ])
            ->add('email', TextType::class,[
                'label' => 'Email',
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Пароль',
                'required' => true,
            ])

        ;

        if(in_array('ROLE_ADMIN', $user->getRoles())){
            $builder
                ->add('u_group', EntityType::class, array(
                'label' => 'Група за замовчуванням',
                'class' => UserGroup::class,
                'choice_label' => function ($UserGroup) {
                    return $UserGroup->getname() . ' (' . $UserGroup->getExtraCharge() . ')';
                }
            ))
                ;
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
