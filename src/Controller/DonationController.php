<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Donation;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class DonationController extends AbstractController
{
    /**
     * @Route("/add_donation", name="add_donation")
     */
    public function add_donation(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();

        $token = $request->request->get('token');
        $name = $request->request->get('name');
        $requested_quantity = $request->request->get('requested_quantity');
        $blood_type = $request->request->get('blood_type');

        if (!$token) {
            return $this->json([
                'error' => 'You must be logged in to add a donation request.'
            ]);
        }

        if (!$name) {
            return $this->json([
                'error' => 'No donation name supplied'
            ]);
        }

        if (!$requested_quantity) {
            return $this->json([
                'error' => 'No requested quantity supplied'
            ]);
        }

        if (!$blood_type) {
            return $this->json([
                'error' => 'No blood type supplied'
            ]);
        }

        /* Get the user associated to the token */
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy([
            'jwt' => $token
        ]);

        /* No user with this token was found. Probably a forged request, or database malfunction */
        if (!$user || $user->getType() != "doctor") {
            return $this->json([
                'error' => 'You are not authorized to add a donation request.'
            ]);
        }

        /* Add donation to database */
        $donation = new Donation();
        $donation->setName($name);
        $donation->setRequestedQuantity(floatval($requested_quantity));
        $donation->setExistingQuantity(0.0);
        $donation->setBloodType($blood_type);
        $donation->setCreationDate(new \DateTime('now'));
        $donation->setHospital($user->getHospital());
        $donation->setUser($user);

        $em->persist($donation);
        $em->flush();

        return $this->json([
            'message' => 'Donation added successfully!'
        ]);
    }
}
