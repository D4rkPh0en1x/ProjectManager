<?php
namespace App\Controller;


use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use App\Entity\Product;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Entity\CommentFile;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;




class ProductController extends Controller
{
    public function addProduct(Environment $twig, FormFactoryInterface $factory, Request $request, ObjectManager $manager, 
        SessionInterface $session, UrlGeneratorInterface $urlGenerator)
    {
        $product = new Product();
        $builder = $factory->createBuilder(FormType::class, $product);
        $builder->add(
            'name', 
            TextType::class,
                [
                    'required' => false,
                    'label' => 'FORM.PRODUCT.NAME',
                    'attr' => [
                        'placeholder' => 'FORM.PRODUCT.PLACEHOLDER.NAME',
                        'class' => 'classname'
                    ]
                ] 
            )        

            ->add(
                'description', 
                TextareaType::class,
                [
                    'required' => false,
                    'label' => 'FORM.PRODUCT.DESCRIPTION',
                    'attr' => [
                        'placeholder' => 'FORM.PRODUCT.PLACEHOLDER.DESCRIPTION',
                        'class' => 'classname'   
                    ]
                    
                ]
            )
            ->add(
                'version',
                TextareaType::class,
                [
                    'required' => false,
                    'label' => 'FORM.PRODUCT.VERSION',
                    'attr' => [
                        'placeholder' => 'FORM.PRODUCT.PLACEHOLDER.VERSION',
                        'class' => 'classname'
                    ]
                    
                ]
                )
            ->add('submit', SubmitType::class);
        
        $form = $builder->getForm();
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
            $manager->persist($product);
            $manager->flush();
            $session->getFlashBag()->add('info', 'Your product was inserted correctly');
            return new RedirectResponse($urlGenerator->generate('homepage'));
        }
       
        
        return new Response(
            $twig->render(
                'Product/addProduct.html.twig',
                [
                    'formular'=>  $form->createView(),
                    'isTrue'=> true
                    
                ]
                )
            );        
    }
    public function listProduct(Environment $twig, Request $request, ObjectManager $manager, SessionInterface $session, 
        UrlGeneratorInterface $urlGenerator)
    {
       
      $repository = $this->getDoctrine()
                         ->getRepository(Product::class);
      $products = $repository->findAll();
      return new Response(
            $twig->render(
                'Product/listProduct.html.twig',
                array('products' => $products)
               )
            );        
    }
    
    public function productDetails(Environment $twig, Request $request, FormFactoryInterface $formFactory, 
        TokenStorageInterface $tokenStorage, ObjectManager $manager, UrlGeneratorInterface $urlGenerator)
    {
        $id = $request->query->get('id');
        $repository = $this->getDoctrine()
        ->getRepository(Product::class);
        $product = $repository->find($id);
        
        $comment = new Comment();
        $form = $formFactory->create(
            CommentType::class,
            $comment,
            ['stateless' => true]
            );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
           $tmpCommentFile = [];
           foreach ($comment->getFiles() as $fileArray) {
               foreach ($fileArray as $file) {
                    $name = sprintf('%s.%s', Uuid::uuid1(), $file->getClientOriginalExtension());
                   
                    $commentFile = new CommentFile();
                    $commentFile->setComment($comment)
                                ->setMimeType($file->getMimeType())
                                ->setName($file->getClientOriginalName())
                                ->setFileUrl('/upload/'.$name);

                    
                    $tmpCommentFile[] = $commentFile;        
                    $file->move(__DIR__.'/../../public/upload', $name);
                    $manager->persist($commentFile);
               }
            }
            
            $token = $tokenStorage->getToken();
            if (!$token){
                throw new \Exception();
            }
            $user = $token->getUser();
            if (!$user){
                throw new \Exception();
            }
            
            $comment->setFiles($tmpCommentFile)
                    ->setAuthor($user)
                    ->setProduct($product);
            $manager->persist($comment);
            $manager->flush();
            return new RedirectResponse($urlGenerator->generate('product_details')."?id=$id");
        }
        
        return new Response(
            $twig->render(
                'Product/productDetails.html.twig',
             
                [
                    'product' => $product,
                    'routeAttr' => ['id' => $product->getId()],
                    'formComment' => $form->createView()
                ]
   
                )
     
            
            );
    }
}
