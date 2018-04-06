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
                'label' => 'FORM.USER.USERNAME',
                'attr' => [
                    'placeholder' => 'FORM.USER.PLACEHOLDER.USERNAME',
                    'class' => 'registrationForm'
                ]
                
            ]
            )
            ->add(
                'firstname',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'FORM.USER.FIRSTNAME',
                    'attr' => [
                        'placeholder' => 'FORM.USER.PLACEHOLDER.FIRSTNAME',
                        'class' => 'registrationForm'
                    ]
                    
                ]
                )
            ->add(                    
                'lastname',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'FORM.USER.LASTNAME',
                    'attr' => [
                        'placeholder' => 'FORM.USER.PLACEHOLDER.LASTNAME',
                        'class' => 'registrationForm'
                    ]
                    
                ]
                )
            ->add(                    
                'email',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'FORM.USER.MAIL',
                    'attr' => [
                        'placeholder' => 'FORM.USER.PLACEHOLDER.MAIL',
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
                    'first_options'  => array('label' => 'FORM.USER.PASSWORD1', 'attr' => array('placeholder' => 'FORM.USER.PLACEHOLDER.PASSWORD1')),
                    'second_options' => array('label' => 'FORM.USER.PASSWORD2', 'attr' => array('placeholder' => 'FORM.USER.PLACEHOLDER.PASSWORD2')),
                    'required' => true,
                    'label' => 'FORM.USER.PASSWORD',
                    
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
                        ->setContentType('text/html')
                        ->setBody(
                            $twig->render('mail/account_creation.html.twig',
                                ['user' => $user]
                                )
                          )->addPart(
                            $twig->render('mail/account_creation.txt.twig',
                              ['user' => $user]
                              ), 'text/plain'
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

