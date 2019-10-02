<?php

namespace App\Controller;

use App\Entity\Infos;
use App\Entity\News;
use App\Entity\Rights;
use App\Entity\User;
use App\Entity\UserProfil;
use App\Form\InfosType;
use App\Form\NewsType;
use App\Form\RightsType;
use App\Form\UserProfilType;
use App\Form\UserType;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CrudController extends AbstractController
{
    const users = [User::class, UserType::class];
    const news = [News::class, NewsType::class];
    const infos = [Infos::class, InfosType::class];
    const rights = [Rights::class, RightsType::class];
    const user_profil = [UserProfil::class, UserProfilType::class];

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $userPasswordEncoder;

    public function __construct(EntityManagerInterface $entityManager, ObjectManager $objectManager, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->objectManager = $objectManager;
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * @Security("is_granted('R', ['SITE','CMS'])")
     * @Route("/admin/{page}/index", name="crud.show")
     * @param $page
     * @return array|object[]
     */
    public function index($page) {
        $sliced_array = array();
        $getDataTransform = array();
        $getUuid = null;

        $all = $this->entityManager->getRepository($this->whatConst($page)[0]);

        $getData = $all->findAll();

        for($i = 0; $i < count($getData); $i++) {
            $getDataTransform[] = array_values((array)$getData[$i]);
        }

        for ($i = 0; $i < count($getDataTransform); $i++) {
            for($j = 0; $j < count($getDataTransform[$i]); $j++) {
                if (is_string($getDataTransform[$i][$j])) {
                    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $getDataTransform[$i][$j]) == 1) {
                        $getUuid = $getDataTransform[$i][$j];
                        unset($getDataTransform[$i][$j]);
                        break;
                    }
                }
            }
            $getId = $getDataTransform[$i][0];
            unset($getDataTransform[$i][0]);
            $sliced_array[] = array_values($getDataTransform[$i]);
            $sliced_array[$i]['id'] = $getId;
            $sliced_array[$i]['uuid'] = $getUuid;
        }

        return $this->render('admin/crud/index.html.twig', [
            'data' => $sliced_array
        ]);
    }

    /**
     * @Route("/admin/{page}/new/", name="crud.new")
     * @param Request $request
     * @param $page
     * @return Response
     * @throws Exception
     */
    public function new(Request $request, $page) {
        $getInstance = $this->whatConst($page)[0];
        $instance = new $getInstance;
        $form = $this->createForm($this->whatConst($page)[1], $instance, array(
            'page' => 'new'));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$instance->setUuid(Uuid::uuid4());
            if (array_key_exists('password', $form->all()) && $form['password']->getData()) {
                $instance->setPassword($this->userPasswordEncoder->encodePassword($instance, $form['password']->getData()));
            }
            $this->objectManager->persist($instance);
            $this->objectManager->flush();
            $this->addFlash("success", "Créé avec succès");
            return $this->redirectToRoute('admin');
        }
        return $this->render("admin/crud/new.html.twig", [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/{page}/edit/{id}", name="crud.edit", methods="GET|POST")
     * @param $page
     * @param $id
     * @param Request $request
     * @return array|object[]
     */
    public function edit($page, $id, Request $request) {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository($this->whatConst($page)[0])->find($id);

        /* Requête avec UUID */
        //$entity = $entityManager->getRepository($this->whatConst($page)[0])->findOne($id);

        $form = $this->createForm($this->whatConst($page)[1], $entity, [
            'page' => 'edit'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (array_key_exists('password', $form->all()) && $form['password']->getData()){
                $entity->setPassword($this->userPasswordEncoder->encodePassword($entity, $form['password']->getData()));
            }
            $this->objectManager->flush();
            $this->addFlash("success", "Créé avec succès");
            return $this->redirectToRoute('admin');
        }

        return $this->render('admin/crud/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/{page}/delete/{id}", name="crud.delete", methods="DELETE")
     * @param $page
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function delete($page, $id, Request $request) {
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository($this->whatConst($page)[0])->find($id);

        //$entity = $entityManager->getRepository($this->whatConst($page)[0])->findOne($id);

        if ($this->isCsrfTokenValid('delete' . $entity->getId(), $request->get("_token"))) {
            $this->objectManager->remove($entity);
            $this->objectManager->flush();
        }
        return $this->redirectToRoute('admin');
    }

    private function whatConst($page) {
        switch($page) {
            case 'users':
                return self::users;
                break;
            case 'news':
                return self::news;
                break;
            case 'infos':
                return self::infos;
                break;
            case 'rights':
                return self::rights;
                break;
            case 'user_profil':
                return self::user_profil;
                break;
        }
        return null;
    }

}
