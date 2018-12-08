<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Donation;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use App\Service\JsonRequestService;

class DonationController extends AbstractController
{
    /**
     * @Route("/add_donation", name="add_donation")
     */
    public function add_donation(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);
        $name = $jsr->getArrayKey('name', $parameters);
        $requested_quantity = $jsr->getArrayKey('requested_quantity', $parameters);
        $blood_type = $jsr->getArrayKey('blood_type', $parameters);

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
     * @Route("/edit_donation", name="edit_donation")
     */
    public function edit_donation(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);
        $donation_id = $jsr->getArrayKey('donation_id', $parameters);
        $name = $jsr->getArrayKey('name', $parameters);
        $requested_quantity = $jsr->getArrayKey('requested_quantity', $parameters);
        $hospital = $jsr->getArrayKey('hospital', $parameters);
        $blood_type = $jsr->getArrayKey('blood_type', $parameters);

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

        if (!$hospital) {
            return $this->json([
                'error' => 'No hospital name supplied'
            ]);
        }

        /* Get the user associated to the token */
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy([
            'jwt' => $token
        ]);

        /* No user with this token was found. Probably a forged request, or database malfunction */
        if (!$user || !in_array($user->getType(), ["doctor", "admin", "center"])) {
            return $this->json([
                'error' => 'You are not authorized to edit a donation request.'
            ]);
        }

        /* Get donation from database */
        $donation_repo = $this->getDoctrine()->getRepository(Donation::class);
        $donation = $donation_repo->find($donation_id);

        if (!$donation) {
            return $this->json([
                'error' => 'Donation not found in database.'
            ]);
        }

        /* Modify donation */
        $donation->setName($name);
        $donation->setHospital($hospital);
        $donation->setRequestedQuantity(floatval($requested_quantity));
        $donation->setBloodType($blood_type);

        /* Write modifications to database */
        $em->persist($donation);
        $em->flush();

        return $this->json([
            'message' => 'Donation edited successfully!'
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
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $hospital = $jsr->getArrayKey('hospital', $parameters);

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
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $name = $jsr->getArrayKey('name', $parameters);

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
     * @Route("/donate", name="donate")
     */
    public function donate(EntityManagerInterface $em)
    {
        $request = Request::createFromGlobals();
        $jsr = new JsonRequestService();

        $parameters = $jsr->getRequestBody($request);
        if ($parameters === FALSE) {
            return $this->json([
                'error' => 'Empty or invalid request body.'
            ]);
        }

        $token = $jsr->getArrayKey('token', $parameters);
        $donation_id = $jsr->getArrayKey('donation_id', $parameters);
        $quantity = $jsr->getArrayKey('quantity', $parameters);

        /* Can specify a donor_id in post, if we are submitting a donation for another user */
        $donor_id = $jsr->getArrayKey('donor_id', $parameters);

        if (!$token) {
            return $this->json([
                'error' => 'You must be logged in, in order to register a donation.'
            ]);
        }
        if (!$donation_id) {
            return $this->json([
                'error' => 'Donation not specified.'
            ]);
        }
        if (!$quantity || floatval($quantity) < 0) {
            return $this->json([
                'error' => 'Quantity must be greater than 0.'
            ]);
        }

        /* Get donation from database */
        $donation_repo = $this->getDoctrine()->getRepository(Donation::class);
        $donation = $donation_repo->find($donation_id);

        if (!$donation) {
            return $this->json([
                'error' => 'Donation not found.'
            ]);
        }

        return $this->json([
            'error' => $donation->getName()
        ]);

        /* Get the user associated to the token */
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repo->findOneBy([
            'jwt' => $token
        ]);

        /* No user with this token was found. Probably a forged request, or database malfunction */
        if (!$user) {
            return $this->json([
                'error' => 'Could not verify your login credentials. Please log in again.'
            ]);
        }

        /* If the donor_id was specified, submit donation for the specified user. */
        if ($donor_id) {
            $specified_user = $user_repo->find($donor_id);
            if ($specified_user) {
                $user = $specified_user;
            } else {
                return $this->json([
                    'error' => 'Could not find donor in database.'
                ]);
            }

            /* Check that the specified user has the required blood type (case-insensitive) */
            if ($specified_user->getBloodType() != $donation->getBloodType()) {
                return $this->json([
                    'error' => 'Specified user does not have the required blood type.'
                ]);
            }
        }

        /* Add blood to donation */
        $existing_quantity = $donation->getExistingQuantity();
        $donation->setExistingQuantity($existing_quantity + floatval($quantity));

        /* Set last donation date to today for both the donation and the user */
        $date = new \DateTime('now');
        $donation->setLastDonationDate($date);
        $user->setLastDonationDate($date);

        /* Increment the number of donations for this request */
        $donations_count = $donation->getDonationsCount();
        $donation->setDonationsCount($donations_count + 1);

        /* Write modifications to database */
        $em->persist($donation);
        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'You have donated successfully!'
        ]);
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
