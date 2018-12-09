<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\JsonRequestService;

class UserController extends AbstractController
{
    /**
     * @Route("/signup", name="signup")
     * @Route("/signup/", name="signup")
     */
    public function signup(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $username = $jsr->getArrayKey('username', $parameters);
        $email = $jsr->getArrayKey('email', $parameters);
        $plain_pw = $jsr->getArrayKey('password', $parameters);
        $type = $jsr->getArrayKey('type', $parameters);
        $blood_type = $jsr->getArrayKey('blood_type', $parameters);
        $hospital = $jsr->getArrayKey('hospital', $parameters);

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

        if ($blood_type)
            $user->setBloodType($blood_type);

        if ($hospital)
            $user->setHospital($hospital);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User added successfully!'
        ]);
    }

    /**
     * @Route("/login", name="login")
     * @Route("/login/", name="login")
     */
    public function login(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $username = $jsr->getArrayKey('username', $parameters);
        $password = $jsr->getArrayKey('password', $parameters);

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
                'token' => $token,
                'username' => $user->getUsername(),
                'user_type' => $user->getUserTypeInt(),
                'is_valid' => $user->getIsValid()
            ]);
        } else {
            return $this->json([
                'error' => 'Wrong username or password. Try again!'
            ]);
        }
    }

    /**
     * @Route("/logout_all", name="logout_all")
     * @Route("/logout_all/", name="logout_all")
     */
    public function logout_all(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $token = $jsr->getBearerToken($request);

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

    /**
     * @Route("/get_users", name="get_users")
     * @Route("/get_users/", name="get_users")
     */
    public function get_users_by_type()
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request, TRUE);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getBearerToken($request);
        $type = $jsr->getArrayKey('type', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }

        /* Find username in database by token, to make sure the token is real (he's authorized) */
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy([
            'jwt' => $token
        ]);

        if (!$user || $user->getType() != "admin") {
            return $this->json([
                'error' => 'Only administrators are authorized to view users.'
            ]);
        }

        if ($type) {
            $user_list = $user_repo->findBy([
                'type' => $type
            ]);
        } else {
            $user_list = $user_repo->findAll();
        }

        $users = [];
        foreach ($user_list as $user) {
            array_push($users, [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'type' => $user->getUserTypeInt(),
                'email' => $user->getEmail(),
                'blood_type' => $user->getBloodType(),
                'hospital' => $user->getHospital(),
                'is_valid' => $user->getIsValid(),
                'last_donation_date' => $user->getLastDonationDate()
            ]);
        }

        return $this->json($users);
    }


    /**
     * @Route("/get_user_data", name="get_user_data")
     * @Route("/get_user_data/", name="get_user_data")
     */
    public function get_user_data()
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getBearerToken($request);
        $username = $jsr->getArrayKey('username', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }

        /* Find username in database by token, to make sure the token is real (he's authorized) */
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy([
            'jwt' => $token
        ]);

        if (!$user || $user->getType() != "admin") {
            return $this->json([
                'error' => 'Only administrators are authorized to view users.'
            ]);
        }

        $selected_user = $user_repo->findOneBy([
            'username' => $username
        ]);

        if ($selected_user) {
            return $this->json([
                'id' => $selected_user->getId(),
                'username' => $selected_user->getUsername(),
                'type' => $selected_user->getUserTypeInt(),
                'email' => $selected_user->getEmail(),
                'blood_type' => $selected_user->getBloodType(),
                'hospital' => $selected_user->getHospital(),
                'is_valid' => $selected_user->getIsValid(),
                'last_donation_date' => $selected_user->getLastDonationDate()
            ]);
        } else {
            return $this->json([
                'error' => 'Username not found in database.'
            ]);
        }
    }

    /**
     * @Route("/validate_user", name="validate_user")
     * @Route("/validate_user/", name="validate_user")
     */
    public function validate_user(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getBearerToken($request);
        $username = $jsr->getArrayKey('username', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }

        /* Find logged user in database by token, to make sure the token is real (he's authorized) */
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy([
            'jwt' => $token
        ]);

        if (!$user || $user->getType() != "admin") {
            return $this->json([
                'error' => 'Only administrators are authorized to view users.'
            ]);
        }
        
        $validated_user = $user_repo->findOneBy([
            'username' => $username
        ]);

        if (!$validated_user) {
            return $this->json([
                'error' => 'User not found in database.'
            ]);
        }

        if ($validated_user->getIsValid()) {
            return $this->json([
                'error' => 'User has already been validated.'
            ]);
        }

        $validated_user->setIsValid(TRUE);
        $em->persist($validated_user);
        $em->flush();

        return $this->json([
            "message" => "User validated successfully"
        ]);
    }

    /**
     * @Route("/invalidate_user", name="invalidate_user")
     * @Route("/invalidate_user/", name="invalidate_user")
     */
    public function invalidate_user(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getBearerToken($request);
        $username = $jsr->getArrayKey('username', $parameters);

        /* Test token not empty */
        if (!$token) {
            return $this->json([
                'error' => 'No token supplied'
            ]);
        }
        if (!$username) {
            return $this->json([
                'error' => 'No username supplied'
            ]);
        }

        /* Find logged user in database by token, to make sure the token is real (he's authorized) */
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy([
            'jwt' => $token
        ]);

        if (!$user || $user->getType() != "admin") {
            return $this->json([
                'error' => 'Only administrators are authorized to view users.'
            ]);
        }
        
        $validated_user = $user_repo->findOneBy([
            'username' => $username
        ]);

        if (!$validated_user) {
            return $this->json([
                'error' => 'User not found in database.'
            ]);
        }

        if (!$validated_user->getIsValid()) {
            return $this->json([
                'error' => 'User has already been validated.'
            ]);
        }

        $validated_user->setIsValid(FALSE);
        $em->persist($validated_user);
        $em->flush();

        return $this->json([
            "message" => "User invalidated successfully"
        ]);
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
