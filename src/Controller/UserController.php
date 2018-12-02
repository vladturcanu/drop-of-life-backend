<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    /**
     * @Route("/signup", name="signup")
     */
    public function signup(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();

        $username = $request->request->get('username');
        $email = $request->request->get('email');
        $plain_pw = $request->request->get('password');
        $type = $request->request->get('type');
        $blood_type = $request->request->get('blood_type');
        $hospital = $request->request->get('hospital');

        /* Test username, email, password, type not empty */
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }
        if (!$plain_pw) {
            return $this->json([
                'error' => 'No password supplied'
            ]);
        }
        if (!$email) {
            return $this->json([
                'error' => 'No email supplied'
            ]);
        }
        if (!$type) {
            return $this->json([
                'error' => 'No type supplied'
            ]);
        }

        /* Test if the user exists */
        $user_repo = $this->getDoctrine()->getRepository(User::class);

        /* Test unique username */
        $existing_user = $user_repo->findOneBy(['username' => $username]);
        if ($existing_user) {
            return $this->json([
                'error' => 'Username already exists'
            ]);
        }

        /* Test unique email */
        $existing_user = $user_repo->findOneBy(['email' => $email]);
        if ($existing_user) {
            return $this->json([
                'error' => 'Email already exists'
            ]);
        }

        /* Create user with supplied data */
        $user = new User();
        $password = password_hash($plain_pw, PASSWORD_BCRYPT);
        if (!ctype_alnum($username)) {
            return $this->json([
                'error' => 'Invalid username: only alphanumeric characters allowed'
            ]);
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE) {
            return $this->json([
                'error' => 'Invalid email'
            ]);
        }

        $user->setUsername($username);
        $user->setPassword($password);
        $user->setEmail($email);
        $user->setType($type);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User added successfully!'
        ]);
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();

        $username = $request->request->get('username');
        $password = $request->request->get('password');

        /* Test username, email, password, type not empty */
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }
        if (!$password) {
            return $this->json([
                'error' => 'No password supplied'
            ]);
        }

        /* Find username + password combination in database */
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy([
            'username' => $username
        ]);

        if ($user) {
            /* Test matching password */
            if (!password_verify($password, $user->getPassword())) {
                return $this->json([
                    'error' => 'Wrong username or password. Try again!'
                ]);
            }

            /* Generate token for the user */
            $token = $this->generateRandomString();

            /* Insert token into the database */
            $user->setJwt($token);
            $em->persist($user);
            $em->flush();

            return $this->json([
                'token' => $token
            ]);
        } else {
            return $this->json([
                'error' => 'Wrong username or password. Try again!'
            ]);
        }
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();

        $token = $request->request->get('token');

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }

        /* Find username in database by token */
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy([
            'jwt' => $token
        ]);

        if ($user) {
            /* Remove user token from database */
            $user->setJwt("");
            $em->persist($user);
            $em->flush();

            return $this->json([
                'message' => 'Logout successful!'
            ]);
        } else {
            return $this->json([
                'error' => 'Error while logging out'
            ]);
        }
    }

    protected function generateRandomString($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
