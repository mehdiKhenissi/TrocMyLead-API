<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\User;
use App\Entity\Sell;
use App\Entity\Leads;
use App\Entity\Litige;
use App\ApiResource\CustomFunctions;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StripePaymentController extends AbstractController {

    private $stripe;
    private $customFunctions;
    private $globalParams;

    public function __construct(private Security $security, private UrlGeneratorInterface $router, TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager, ParameterBagInterface $globalParams, CustomFunctions $customFunctions) {
        $this->jwtManager = $jwtManager;
        $this->tokenStorageInterface = $tokenStorageInterface;
        $this->stripe = new \Stripe\StripeClient($globalParams->get('stripe_key'));
        $this->customFunctions = $customFunctions;
    }

    #[Route(path: '/api/stripe/customer/create', name: 'stripe_customer_create', methods: ['POST'])]
    public function stripeCreateCustomer(Request $request, EntityManagerInterface $entityManager) {
        $response_data = [];
        //dd($request->getHttpHost().$this->router->generate('stripe_payment')); // referer url
        // check if token is good, referer ( request come from external path) is good, and not data in url passed ( only reponse from stripe payment send data in path )
        if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
            $user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());

            // check if user infos good
            if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {
                $waited_inputs = ['postal_code', 'name', 'email', 'phone', 'country', 'city', 'description'];
                $post_data = json_decode($request->getContent(), true);
                $check_post_data = $this->customFunctions->checkAllInArray($post_data, $waited_inputs);

                if ($check_post_data === true) {

                    $create_customer = $this->stripe->customers->create([
                        'address' => [
                            'line1' => $post_data['address'],
                            'city' => $post_data['city'],
                            'postal_code' => $post_data['postal_code'],
                            'country' => 'FR',
                            'state' => $post_data['country'],
                        ],
                        'name' => $post_data['name'],
                        'email' => $post_data['email'],
                        'phone' => $post_data['phone'],
                        'description' => $post_data['description'],
                        'preferred_locales' => ['fr'],
                    ]);

                    $response_data = ['code' => 200, 'status' => 'success', 'stripe_customer_id' => $create_customer->id];
                } else {
                    $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur post data, variable manquante (" . $check_post_data . ")"];
                }
            } else {
                $response_data = ['code' => 400, 'status' => 'error', 'message' => "Utilisateur invalide"];
            }
        } else {
            $response_data = ['code' => 400, 'status' => 'error', 'message' => "Token not found"];
        }


        return new JsonResponse($response_data);
    }

    /**
     *  Stripe retreive customer informations
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param type $id
     * @return JsonResponse
     */
    #[Route(path: '/api/stripe/customer/retrieve/{id}', name: 'stripe_customer_retrieve', methods: ['GET'])]
    public function stripeCustomerRetreive(Request $request, EntityManagerInterface $entityManager, $id) {
        $response_data = [];
        try {
            if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
                $user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {
                    /* retreive customer */
                    $retreive_customer = $this->stripe->customers->retrieve($id);
                    //dd($retreive_customer);

                    $response_data = ['code' => 200, 'stripe_customer_data' => $retreive_customer];
                } else {
                    $response_data = ['code' => 401, 'status' => 'error', 'message' => "Utilisateur invalid"];
                }
            } else {
                $response_data = ['code' => 401, 'status' => 'error', 'message' => "Invalid token"];
            }
        } catch (\Throwable $e) {
            $response_data = ['code' => 401, 'status' => 'error', 'message' => $e->getMessage()];
            //echo json_encode(['error' => $e->getMessage()]);
        }

        return new JsonResponse($response_data);
    }

    #[Route(path: '/api/stripe/checkout', name: 'stripe_checkout', methods: ['POST'])]
    public function stripeCheckout(Request $request, EntityManagerInterface $entityManager) {
        $response_data = [];
        //dd($request->getHttpHost().$this->router->generate('stripe_payment')); // referer url
        // check if token is good, referer ( request come from external path) is good, and not data in url passed ( only reponse from stripe payment send data in path )
        if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
            $user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
            // check if user infos good
            if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {

                // check if referer ( request come from external path) is good, and not data in url passed
                if (($request->headers->get('referer') !== null) && ( empty($request->query->all()) )) {
                    $waited_inputs = ['lead_id', 'amount', 'success_url', 'cancel_url'];
                    $post_data = json_decode($request->getContent(), true);
                    $check_post_data = $this->customFunctions->checkAllInArray($post_data, $waited_inputs);

                    if ($check_post_data === true) {
                        $protocol = $request->isSecure() ? 'https://' : 'http://';
                        $token = str_replace("Bearer ", "", $request->headers->get('authorization'));

                        // Stripe session checkout data
                        $stripe_session_checkout_data = [
                            'line_items' => [[
                            'price_data' => [
                                'currency' => 'EUR',
                                'product_data' => [
                                    'name' => 'Prospect #' . $post_data['lead_id'],
                                ],
                                'unit_amount' => (int) ($post_data['amount'] * 100),
                            ],
                            'quantity' => 1,
                                ]],
                            'mode' => 'payment',
                            /* 'invoice_creation' => [
                              'enabled' => 'true',
                              'invoice_data' => [
                              'description' => 'Facture payer avec succès'
                              ]
                              ], */
                            'payment_intent_data' => [
                                'capture_method' => 'manual', // Configure le paiement pour une capture manuelle
                                'setup_future_usage' => 'off_session', // Important for saving the card
                            ],
                            // 'success_url' => $protocol . $request->getHttpHost() . $this->router->generate('stripe_payment') . '?success=true',
                            //'success_url' => $post_data['success'].'?'. str_replace("ey", "", explode('.', $token)[1]),
                            'success_url' => $post_data['success_url'] . '?session_id={CHECKOUT_SESSION_ID}&lid=' . $post_data['lead_id'],
                            //'cancel_url' => $protocol . $request->getHttpHost() . $this->router->generate('stripe_payment') . '?canceled=true',
                            'cancel_url' => $post_data['cancel_url'],
                        ];

                        if (isset($post_data['customer_id'])) {
                            $stripe_session_checkout_data['customer'] = $post_data['customer_id'];
                        } else {
                            $stripe_session_checkout_data['customer_creation'] = "always";
                        }


                        $checkout_session = $this->stripe->checkout->sessions->create($stripe_session_checkout_data);

                        $response_data = ['code' => 200, 'status' => 'success', 'checkout_url' => $checkout_session->url];
                    } else {
                        $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur post data, variable manquante (" . $check_post_data . ")"];
                    }
                } else {
                    $response_data = ['code' => 401, 'status' => 'error', 'message' => "Erreur security, unathorized access"];
                }
            } else {
                $response_data = ['code' => 400, 'status' => 'error', 'message' => "Utilisateur invalide"];
            }
        } else {
            $response_data = ['code' => 400, 'status' => 'error', 'message' => "Invalid token"];
        }


        return new JsonResponse($response_data);
    }

    #[Route(path: '/api/stripe/session/retrieve/{id}', name: 'stripe_session_retreive', methods: ['GET'])]
    public function stripeRetreiveSession(Request $request, EntityManagerInterface $entityManager, $id) {


        try {
            if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
                $user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {
                    $session = $this->stripe->checkout->sessions->retrieve($id);
                    //dd(json_encode($session));

                    $response_data = ['code' => 200, 'session_data' => $session];
                } else {
                    $response_data = ['code' => 401, 'status' => 'error', 'message' => "Utilisateur invalid"];
                }
            } else {
                $response_data = ['code' => 401, 'status' => 'error', 'message' => "Invalid token"];
            }
        } catch (\Throwable $e) {
            $response_data = ['code' => 401, 'status' => 'error', 'message' => $e->getMessage()];
            //echo json_encode(['error' => $e->getMessage()]);
        }

        return new JsonResponse($response_data);
    }

    #[Route(path: '/api/stripe/invoice/retrieve/{id}', name: 'stripe_invoice_retrieve', methods: ['GET'])]
    public function stripeInvoiceRetreive(Request $request, EntityManagerInterface $entityManager, $id) {
        try {
            if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
                $user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {
                    /* retreive invoice */
                    $retreive_invoice = $this->stripe->invoices->retrieve($id);

                    $response_data = ['code' => 200, 'invoice_data' => $retreive_invoice];
                } else {
                    $response_data = ['code' => 401, 'status' => 'error', 'message' => "Utilisateur invalid"];
                }
            } else {
                $response_data = ['code' => 401, 'status' => 'error', 'message' => "Invalid token"];
            }
        } catch (\Throwable $e) {
            $response_data = ['code' => 401, 'status' => 'error', 'message' => $e->getMessage()];
            //echo json_encode(['error' => $e->getMessage()]);
        }

        return new JsonResponse($response_data);
    }

    #[Route(path: '/api/stripe/captur/payment/{id}', name: 'stripe_captur_payment', methods: ['GET'])]
    public function stripeCapturePayment(Request $request, EntityManagerInterface $entityManager, $id) {
        $response_data = [];

        try {
            if (($this->security->getToken() !== null) && ( $this->security->isGranted("ROLE_USER") || $this->security->isGranted("ROLE_ADMIN") )) {
                $user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {

                    /* get sell lead infos */
                    $lead_sell_infos = $entityManager->getRepository(Sell::class)->findOneBy(['stripe_payment_id' => $id]);
                    if (!empty($lead_sell_infos)) {
                        //get litige informations if exist
                        $lead_selled_litiges = $entityManager->getRepository(Litige::class)->findOneBy(['lead' => $lead_sell_infos->getLead()->getId()]);
                        //dd($lead_selled_litiges);

                        /* get payment intent infos  */
                        $paymentIntentToCapture = $this->stripe->paymentIntents->retrieve($id);
                        /* capturer le payement */

                        /* vérifier si on doit juste prélever le montant de pricingToSeller et rombourse le reste au acheteur */
                        $amount_to_pay = null;
                        if (($lead_sell_infos->getCreatedAt()->modify('+2 days'))->format('Y-m-d H:i:s') > date('Y-m-d H:i:s')) {
                            //Rembourser
                            $lead_infos = $entityManager->getRepository(Leads::class)->findOneBy(['id' => $lead_sell_infos->getLead()->getId()]);
                            $amount_to_pay = $paymentIntentToCapture->amount - ($lead_infos->getPricingToTpl() * 100);

                            // EDIT pricing column in SELL DB Table 
                            $lead_sell_infos->setPricing($amount_to_pay/100);
                            $entityManager->flush();
                            $entityManager->clear();
                        }
                        else if(!empty($lead_selled_litiges)){
                            // verifie si y a pas un litige ouvert c'est pour ca la validation de lead a depasser  les 48h
                            
                            //Rembourser
                            $lead_infos = $entityManager->getRepository(Leads::class)->findOneBy(['id' => $lead_sell_infos->getLead()->getId()]);
                            $amount_to_pay = $paymentIntentToCapture->amount - ($lead_infos->getPricingToTpl() * 100);

                            // EDIT pricing column in SELL DB Table 
                            $lead_sell_infos->setPricing($amount_to_pay/100);
                            $entityManager->flush();
                            $entityManager->clear();
                            
                        }
                        else {
                            //Prelever la totalité du montant
                            $amount_to_pay = $paymentIntentToCapture->amount;
                        }

                        $paymentIntentToCapture->capture(['amount_to_capture' => $amount_to_pay]);

                        /* vérifier si on doit juste prélever le montant de pricingToSeller et rombourse le reste au acheteur */

                        /* Create invoice */
                        // create invoice
                        $invoiceCreate = $this->stripe->invoices->create([
                            'customer' => $paymentIntentToCapture->customer,
                            'description' => "Facture acquittée",
                            'auto_advance' => true, // Automatically finalize and attempt to pay the invoice
                        ]);

                        // add item on invoice created
                        $invoiceItem = $this->stripe->invoiceItems->create([
                            'customer' => $paymentIntentToCapture->customer,
                            'amount' => $paymentIntentToCapture->amount, // Amount in cents
                            'currency' => 'eur',
                            'description' => "Prospect #" . $lead_sell_infos->getLead()->getId(),
                            'invoice' => $invoiceCreate->id
                        ]);

                        $paidInvoice = $this->stripe->invoices->pay($invoiceCreate->id, ['paid_out_of_band' => true]); // mark invoice as payed
                        /* End Create invoice */

                        // save invoice 
                        self::saveStripeInvoice(
                                $paidInvoice->invoice_pdf,
                                $this->getParameter('enterprise_files_directory') . 'enterprise_' . $lead_sell_infos->getEnterprise()->getId() . '/invoices/' . $paidInvoice->number . '.pdf');

                        $response_data = ['code' => 200, 'status' => 'success', 'infos' => [
                                "invoice" => [
                                    "id" => $paidInvoice->id,
                                    "invoice_num" => $paidInvoice->number,
                                    "invoice_pdf" => $paidInvoice->invoice_pdf
                                ]
                        ]];
                    } else {
                        $response_data = ['code' => 200, 'status' => 'error', 'message' => "Informations payement invalide"];
                    }
                } else {
                    $response_data = ['code' => 400, 'status' => 'error', 'message' => "Utilisateur invalide"];
                }
            } else {
                $response_data = ['code' => 401, 'status' => 'error', 'message' => "Invalid token"];
            }
        } catch (\Throwable $ex) {
            $response_data = ['code' => 401, 'status' => 'error', 'message' => $ex->getMessage()];
        }

        return new JsonResponse($response_data);
    }

    #[Route(path: '/api/stripe/refund/payment/{id}', name: 'stripe_refund_payment', methods: ['GET'])]
    public function stripeRefundPayment(Request $request, EntityManagerInterface $entityManager, $id) {
        $response_data = [];

        try {
            if (($this->security->getToken() !== null) && ( $this->security->isGranted("ROLE_USER") || $this->security->isGranted("ROLE_ADMIN") )) {
                $user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {

                    /* get sell lead infos */
                    $lead_sell_infos = $entityManager->getRepository(Sell::class)->findOneBy(['stripe_payment_id' => $id]);
                    //dd($lead_sell_infos);

                    if (!empty($lead_sell_infos)) {
                        /* GEt LEAD INFOS */
                        $lead_infos = $entityManager->getRepository(Leads::class)->findOneBy(['id' => $lead_sell_infos->getLead()->getId()]);

                        /* get payment intent infos  */
                        $paymentIntentToCapture = $this->stripe->paymentIntents->retrieve($id);

                        /* capturer le payement */
                        $paymentIntentToCapture->capture(['amount_to_capture' => intval($lead_infos->getPricingToTpl() * 100)]);

                        /* CREATE INVOICE  */
                        // create invoice
                        $invoiceCreate = $this->stripe->invoices->create([
                            'customer' => $paymentIntentToCapture->customer,
                            'description' => "Facture acquittée",
                            'auto_advance' => true, // Automatically finalize and attempt to pay the invoice
                        ]);

                        // add item on invoice created
                        $invoiceItem = $this->stripe->invoiceItems->create([
                            'customer' => $paymentIntentToCapture->customer,
                            'amount' => intval($lead_infos->getPricingToTpl() * 100), // Amount in cents
                            'currency' => 'eur',
                            'description' => "Frais de service vérification informations prospect #" . $lead_sell_infos->getLead()->getId(),
                            'invoice' => $invoiceCreate->id
                        ]);

                        $paidInvoice = $this->stripe->invoices->pay($invoiceCreate->id, ['paid_out_of_band' => true]); // mark invoice as payed
                        /* END CREATE INVOICE  */

                        // save invoice 
                        self::saveStripeInvoice(
                                $paidInvoice->invoice_pdf,
                                $this->getParameter('enterprise_files_directory') . 'enterprise_' . $lead_sell_infos->getEnterprise()->getId() . '/invoices/' . $paidInvoice->number . '.pdf');

                        // EDIT pricing column in SELL DB Table 
                        $lead_sell_infos->setPricing($lead_infos->getPricingToTpl());
                        $entityManager->flush();

                        $response_data = ['code' => 200, 'status' => 'success', 'infos' => [
                                "invoice" => [
                                    "id" => $paidInvoice->id,
                                    "invoice_num" => $paidInvoice->number,
                                    "invoice_pdf" => $paidInvoice->invoice_pdf
                                ]
                        ]];
                    } else {
                        $response_data = ['code' => 200, 'status' => 'error', 'message' => "Informations payement invalide"];
                    }
                } else {
                    $response_data = ['code' => 400, 'status' => 'error', 'message' => "Utilisateur invalide"];
                }
            } else {
                $response_data = ['code' => 401, 'status' => 'error', 'message' => "Invalid token"];
            }
        } catch (\Throwable $ex) {
            $response_data = ['code' => 401, 'status' => 'error', 'message' => $ex->getMessage()];
        }

        return new JsonResponse($response_data);
    }

    #[Route(path: '/api/stripe/cancel/payment/{id}', name: 'stripe_cancel_payment', methods: ['GET'])]
    public function stripeCancelPayment(Request $request, EntityManagerInterface $entityManager, $id) {
        $response_data = [];

        try {
            if (($this->security->getToken() !== null) && ( $this->security->isGranted("ROLE_USER") || $this->security->isGranted("ROLE_ADMIN") )) {
                $user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {

                    /* get sell lead infos */
                    $lead_sell_infos = $entityManager->getRepository(Sell::class)->findOneBy(['stripe_payment_id' => $id]);

                    if (!empty($lead_sell_infos)) {
                        $this->stripe->paymentIntents->cancel($id);
                        $response_data = ['code' => 200, 'status' => 'success', 'message' => 'Paiement annulé'];
                    } else {
                        $response_data = ['code' => 400, 'status' => 'error', 'message' => "Informations payement invalide"];
                    }
                } else {
                    $response_data = ['code' => 400, 'status' => 'error', 'message' => "Utilisateur invalide"];
                }
            } else {
                $response_data = ['code' => 400, 'status' => 'error', 'message' => "Invalid token"];
            }
        } catch (\Throwable $ex) {
            $response_data = ['code' => 400, 'status' => 'error', 'message' => $ex->getMessage()];
        }

        return new JsonResponse($response_data);
    }

    /**
     * 
     * @param type $urlInvoice
     * @param type $targetFile
     * @return bool
     * 
     * download and save stripe invoice
     */
    public function saveStripeInvoice($urlInvoice, $targetFile) {

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlInvoice); // URL to fetch
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // Return the transfer as a string
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);     // Follow redirects
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                //echo 'cURL error: ' . curl_error($ch);
                return false;
            } else {
                // Check if the directory exists; if not, create it
                $directory = dirname($targetFile);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Save the response data to a file
                $result = file_put_contents($targetFile, $response);

                // Check if the file was saved successfully
                if ($result !== false) {
                    //echo "Stock data saved successfully to {$targetFile}";
                    return true;
                } else {
                    //echo "Failed to save the stock data.";
                    return false;
                }
            }
            curl_close($ch);
        } catch (\Throwable $ex) {
            //return $ex->getMessage();
            return false;
        }
    }

    #[Route(path: '/api/stripe/session/retrieve/{id}/old', name: 'old_stripe_retreive_session', methods: ['GET'])]
    public function oldstripeRetreiveSession(Request $request, EntityManagerInterface $entityManager) {
        try {
            $session = $this->stripe->checkout->sessions->retrieve('cs_test_a1Su7TbFNiIPRCZ7cc3zF0luWFJxfYZKeRnak6H7ld2GaQt1JjUvBHgpcS');
            dd($session);

            $retreive_payment = $this->stripe->paymentIntents->retrieve($session->payment_intent);
            dd($retreive_payment);

            /* retreive payment method  */
            $retreive_payment_method = $this->stripe->paymentMethods->retrieve('pm_1ObPxLDePNMea6FpQkSliPTP', []);
            //dd($retreive_payment_method->card);

            /* create paument methods 
              $create_payment_method = $this->stripe->paymentMethods->attach("pm_1ObPxLDePNMea6FpQkSliPTP", [
              'customer'=> "cus_PQagxKyDgTImGk"
              ]);
              dd($create_payment_method); */

            /* update payment intent 
              $update_payment_method = $this->stripe->paymentIntents->update($session->payment_intent, [
              "customer" => "cus_PQWJJi8Q4s09jq"
              ]);
              dd($update_payment_method); */


            /* charge create 
              $create_charge = $this->stripe->charges->create([
              'amount' => 1099,
              'currency' => 'EUR',
              'source' => 'card',
              'customer'=>"cus_PQWJJi8Q4s09jq"
              ]);
              dd($create_charge); */

            /* create user
              $create_customer = $this->stripe->customers->create([
              'name' => 'Jenny Rosen',
              'email' => 'jennyrosen@example.com',
              'description'=> "client de teste"
              ]);
              dd($create_customer); */

            /* attach payment method to customer */
            /* $customer_payment_method = $this->stripe->paymentMethods->attach('pm_1ObPxLDePNMea6FpQkSliPTP',[
              'customer' => 'cus_PQWJJi8Q4s09jq'
              ]); */
            /* $customer_payment_method = $this->stripe->customers->update('cus_PQWJJi8Q4s09jq',[
              'customer' => 'cus_PQWJJi8Q4s09jq'
              ]); */
            //dd($customer_payment_method);

            /* create invoice
              $create_invoice = $this->stripe->invoices->create([
              'auto_advance' => true,
              'customer' => 'cus_PQWJJi8Q4s09jq',
              'description'=>'facture de teste'
              ]); */


            /* create invoice item 
              $create_invoice_item = $this->stripe->invoiceItems->create([
              "customer"=>'cus_PQWJJi8Q4s09jq',
              "invoice"=> "in_1ObfclDePNMea6FpgQ725akL",
              "amount"=>"20000",
              "description"=>"lead to sell"
              ]);
              dd($create_invoice_item); */

            /* update invoice
              $update_invoice = $this->stripe->invoices->update("in_1ObfclDePNMea6FpgQ725akL", [
              "collection_method"=>"charge_automatically"
              ]);
              dd($update_invoice); */


            /* paid invoice
              $paid_invoice = $this->stripe->invoices->pay("in_1ObjrIDePNMea6Fpai9D3QZq", []);
              dd($paid_invoice); */

            /* retreive invoice */
            $retreive_invoice = $this->stripe->invoices->retrieve("in_1ObkxTDePNMea6FpDdLuLYF2");
            dd($retreive_invoice);

            //echo "<h1>Thanks for your order, $customer->name!</h1>";
        } catch (Error $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        return new JsonResponse([]);
    }

    #[Route(path: '/api/stripe/test/', name: 'stripe_test', methods: ['POST'])]
    public function testStripeFunctions(Request $request, EntityManagerInterface $entityManager) {
        /* $invoiceRetrive = $this->stripe->invoices->retrieve('in_1P9SXdDePNMea6Fp13oHJf6L');
          dd($invoiceRetrive); */

        //dd('capture the payment intent and create invoice and valid it');
        //$sessionRetreive = $this->stripe->checkout->sessions->retrieve('cs_test_a1ocQE3iGjH1OBjMuILW4uHQ2LLVXUvIsDy4V4aokiyiBwx3oBX2fARLdr');
        //dd($sessionRetreive);
        $paymentIntentToCapture = $this->stripe->paymentIntents->retrieve('pi_3PJapfDePNMea6Fp4lz7ZodJ');
        /* try{
          dd($this->stripe->paymentIntents->cancel('pi_3PHL5jDePNMea6Fp2B4Qg09Bpp'));
          dd($paymentIntentToCapture);
          } catch (\Throwable $ex) {

          dd($ex->getMessage());

          } */

        //$paymentIntentToCapture->capture(['amount_to_capture' => $sessionRetreive->amount_total]);
        // payment charge
        //$chargeRetreive = $this->stripe->charges->retrieve($paymentIntentToCapture->latest_charge, []);
        //dd($chargeRetreive);

        /* CREATE INVOICE  */
        // create invoice
        $invoiceCreate = $this->stripe->invoices->create([
            'customer' => $paymentIntentToCapture->customer,
            'description' => "Facture acquittée",
            'auto_advance' => true, // Automatically finalize and attempt to pay the invoice
        ]);

        // add item on invoice created
        $invoiceItem = $this->stripe->invoiceItems->create([
            'customer' => $paymentIntentToCapture->customer,
            'amount' => $paymentIntentToCapture->amount, // Amount in cents
            'currency' => 'eur',
            'description' => "Frais de service vérification des informations du prospect #100",
            'invoice' => $invoiceCreate->id
        ]);

        $paidInvoice = $this->stripe->invoices->pay($invoiceCreate->id, ['paid_out_of_band' => true]); // mark invoice as payed
        /* END CREATE INVOICE  */

        /* END Create an invoice and mark as payed */

        dd('ok');
    }

    public function oldtestStripeFunctions(Request $request, EntityManagerInterface $entityManager) {
        //dd($this->security->getUser()->getId());
        /* $intentcreate = $this->stripe->paymentIntents->create([
          'amount' => 1099,
          'currency' => 'eur',
          'payment_method_types' => ['card'],
          'capture_method' => 'manual',
          ]); */

        /* session retreive */
        $sessionRetreive = $this->stripe->checkout->sessions->retrieve('cs_test_a1QS41usXK6V8bbVDC4rvOduBPv8Luw7tCw5rFlXSuIUFYTVkMEz2rIiY5');
        dd($sessionRetreive);
        //1 capture payment
        $paymentIntentToCapture = $this->stripe->paymentIntents->retrieve($sessionRetreive->payment_intent);

        //2 atach payment method to customer
        $paymentMethodToAtach = $this->stripe->paymentMethods->retrieve($paymentIntentToCapture->payment_method, []);
        $paymentMethodToAtach->attach(['customer' => $sessionRetreive->customer]);
        dd($paymentMethodToAtach);

        //2 capture payment
        $paymentIntentToCapture = $this->stripe->paymentIntents->retrieve($sessionRetreive->payment_intent);
        //$paymentIntentToCapture->capture(['amount_to_capture' => 5000]);
        //dd($paymentIntentToCapture);
        //3 create invoice
        //$invoiceCreate = $this->stripe->invoices->create(['customer' => $sessionRetreive->customer, 'auto_advance' => true,]);
        //dd($invoiceCreate); 
        //4 create item for invoice (amount)
        /* $itemInvoice = $this->stripe->invoiceItems->create([
          'customer' => 'cus_PzMdvqSYLMW0IQ',
          'amount' => "1000", // Amount in cents
          'currency' => 'eur',
          'description' => 'Prospect',
          'invoice' => $invoiceCreate->id
          ]); */
        //dd($itemInvoice);
        //



        $paidInvoice = $this->stripe->invoices->pay('in_1P9RJqDePNMea6FpLCL6Nh9M', []);
        dd($paidInvoice);

        //6 mark invoice as payed
        $finalizedInvoice = $invoiceCreate->finalizeInvoice();
        if (!$finalizedInvoice->paid) {
            $paidInvoice = $finalizedInvoice->pay('in_1P9RJqDePNMea6FpLCL6Nh9M', [
                'paid_out_of_band' => true, // Indicates that the payment was made outside of Stripe
                'payment_intent' => $paymentIntentToCapture->id,
            ]);
        }
        dd($finalizedInvoice);

        $customerPymentMethod = $this->stripe->customers->allPaymentMethods(
                'cus_PygWTBKt0FSsKW',
                []
        );
        //dd($customerPymentMethod);
        // PAY INVOICE
        $invpoicePay = $this->stripe->invoices->pay('in_1P96QsDePNMea6Fptz1GlF1R', [
            'payment_method' => $intentCapturePaymentMethod
        ]);
        dd($invpoicePay);

        dd('ok');
    }
}
