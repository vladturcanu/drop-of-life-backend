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

    /**
     * @Route("/available_donations", name="all_donations")
     */
    public function all_donations()
    {
        /* Get all donations from database */
        $donation_repo = $this->getDoctrine()->getRepository(Donation::class);
        $donations = $donation_repo->findAll();

        return $this->jsonify_donations_array($donations);
    }

    /**
     * @Route("/available_donations/blood_type/{blood_type}", name="blood_type_donations")
     */
    public function blood_type_donations(string $blood_type)
    {
        /* Get donations with the specified blood type from database */
        $donation_repo = $this->getDoctrine()->getRepository(Donation::class);
        $donations = $donation_repo->findBy([
            'blood_type' => $blood_type
        ]);

        return $this->jsonify_donations_array($donations);
    }

    /**
     * @Route("/available_donations/hospital", name="hospital_donations")
     */
    public function hospital_donations()
    {
        /* Get hospital name from request body */
        $request = Request::createFromGlobals();
        $hospital = $request->request->get('hospital');

        if (!$hospital) {
            return $this->json([
                'error' => 'Please specify hospital name'
            ]);
        }

        /* Get donations for the specified hospital from database */
        $donation_repo = $this->getDoctrine()->getRepository(Donation::class);
        $donations = $donation_repo->findBy([
            'hospital' => $hospital
        ]);

        return $this->jsonify_donations_array($donations);
    }

    /**
     * @Route("/available_donations/name", name="name_donations")
     */
    public function name_donations()
    {
        /* Get donation name from request body */
        $request = Request::createFromGlobals();
        $name = $request->request->get('name');

        if (!$name) {
            return $this->json([
                'error' => 'Please specify donation name'
            ]);
        }

        /* Get donations for the specified name from database */
        $donation_repo = $this->getDoctrine()->getRepository(Donation::class);
        $donations = $donation_repo->findBy([
            'name' => $name
        ]);

        return $this->jsonify_donations_array($donations);
    }

    /**
     * Create JSON with donations
     *
     * @param Array $donations_array
     * @return string JSON of the specified donations
     */
    protected function jsonify_donations_array($donations_array) {
        $return_json = [];
        foreach ($donations_array as $donation) {
            array_push($return_json, [
                'id' => $donation->getId(),
                'name' => $donation->getName(),
                'requested_quantity' => $donation->getRequestedQuantity(),
                'existing_quantity' => $donation->getExistingQuantity(),
                'donations_count' => $donation->getDonationsCount(),
                'hospital' => $donation->getHospital(),
                'blood_type' => $donation->getBloodType(),
                'creation_date' => $donation->getCreationDate(),
                'last_donation_date' => $donation->getLastDonationDate()
            ]);
        }

        return $this->json($return_json);
    }
}
