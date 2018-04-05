<?php
namespace App\Controller;


use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController
{
    public function registerUser(Environment $twig, FormFactoryInterface $factory, Request $request, ObjectManager $manager,
        SessionInterface $session, UrlGeneratorInterface $urlGenerator, \Swift_Mailer $mailer)
    {
        $user = new User();
        $builder = $factory->createBuilder(FormType::class, $user);
        $builder->add( 
            'username',
            TextType::class,
            [
                'required' => true,
                'label' => 'User name',
                'attr' => [
                    'placeholder' => 'Please enter your username',
                    'class' => 'registrationForm'
                ]
                
            ]
            )
            ->add(
                'firstname',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'First name',
                    'attr' => [
                        'placeholder' => 'Please enter your firstname',
                        'class' => 'registrationForm'
                    ]
                    
                ]
                )
            ->add(                    
                'lastname',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'Last name',
                    'attr' => [
                        'placeholder' => 'Please enter your lastname',
                        'class' => 'registrationForm'
                    ]
                    
                ]
                )
            ->add(                    
                'email',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'E-Mail Address',
                    'attr' => [
                        'placeholder' => 'Please enter your email address',
                        'class' => 'registrationForm'
                    ]
                    
                ]
                )
            ->add(
                'password',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'first_options'  => array('label' => 'Password'),
                    'second_options' => array('label' => 'Repeat Password', 'attr' => array('placeholder' => 'Please repeat your password')),
                    'required' => true,
                    'label' => 'Password',
                    
                    'options' => array('attr' => array('class' => 'password-field registrationForm', 'placeholder' => 'Please enter a password')),
             
                ]
                )
                 
             ->add(
                 'submit', 
                 SubmitType::class
              );
                         
                    
            $form = $builder->getForm();
            $form->handleRequest($request);
            
            if($form->isSubmitted() && $form->isValid())
            {
                $manager->persist($user);
                $manager->flush();
                
                
                $message = new \Swift_Message();
                $message->setFrom('frank@datateam.lu')
                        ->setTo($user->getEmail())
                        ->setSubject('Validate your account')
                        ->setBody(
                            $twig->render('mail/account_creation.html.twig',
                                ['user' => $user]
                                )
                            );
                
                
                $mailer->send($message);
                
                $session->getFlashBag()->add('info', 'The user was created successfully, please check your mails');
                return new RedirectResponse($urlGenerator->generate('homepage'));
            }
            
            
            return new Response(
                $twig->render(
                    'User/registerUser.html.twig',
                    [
                        'registrationFormular'=>  $form->createView()
                             
                    ]
                    )
                );
    }
    
    public function activateUser($token, ObjectManager $manager, SessionInterface $session, UrlGeneratorInterface $urlGenerator)
    {
        $userRepository = $manager->getRepository(User::class);
        $user = $userRepository->findOneByEmailToken($token);
        
        if (!$user) {
            throw new NotFoundHttpException('User not found for given token');
        }
        
        $user->setActive(true);
        $user->SetEmailToken(null);
        $userName = $user->getUsername();
        $manager->flush();
        $session->getFlashBag()->add('info', "Hi $userName. Your account has been activated");
        
        return new RedirectResponse($urlGenerator->generate('homepage'));
    }
}

