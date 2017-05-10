<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Login;
use AppBundle\Middleware\AuthenticationMiddleware;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LoginController extends Controller
{
    /**
     * @Route("/disconnect", name="disconnect")
     */
    public function disconnectAction(Request $request)
    {
        session_destroy();
        return $this->redirectToRoute('login');
    }

    /**
     * @Route("/", name="login")
     */
    public function loginAction(Request $request)
    {
        // Redirect if already logged in
        if(AuthenticationMiddleware::isAuthenticated()) {
            return $this->redirectToRoute('csv_controller');
        }

        // create a task and give it some dummy data for this example
        $login = new Login();
        $login->setEmail('E-mail');
        $login->setPassword('Password');

        $form = $this->createFormBuilder($login)
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('save', SubmitType::class, array('label' => 'Submit'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $login = $form->getData();
            $email = $login->getEmail();
            $password = $login->getPassword();
            $client = new Client();
            $response = $client->request('GET', "http://www.benebox.org/offres/gestion/login/controle_login.php?login=$email&mot_de_passe=$password");
            $xmlResponse = (string) $response->getBody();

            if ($this->hasLoginSucceeded($xmlResponse)) {
                $equipeRepository = $this->getDoctrine()->getManager()->getRepository('AppBundle:User');
                if ($equipeRepository->existsByEmail($email)) {
                    AuthenticationMiddleware::authenticate($email);

                    return $this->redirectToRoute('csv_controller');
                }

                return $this->redirectToRoute('login');
            }

            return $this->redirectToRoute('login');
        }

        return $this->render('login.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function hasLoginSucceeded($xml)
    {
        $decodedXml = new \SimpleXMLElement($xml);
        return (string) $decodedXml->result['val'] === 'OK';
    }
}