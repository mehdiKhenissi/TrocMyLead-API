<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Sell;
use App\Entity\Enterprise;
use App\Entity\Leads;
use App\Entity\Litige;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\ApiResource\CustomFunctions;

/**
 * Description of EnterpriseController
 *
 * @author Dev
 */
class SellController extends AbstractController {

    private $customFunctions;

    public function __construct(private Security $security, TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager, CustomFunctions $customFunctions) {
        $this->customFunctions = $customFunctions;
    }

    #[Route(path: '/api/sells/lead/{id}', name: 'sells_by_lead', methods: ['GET'])]
    public function getByLead(Request $request, EntityManagerInterface $entityManager, $id) {
        //$file = $request->files->get('file');
        $response_data = [];

        if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
            $sells_list_by_lead = $entityManager->getRepository(Sell::class)->findOneBy(['lead' => $id]);
            if (!empty($sells_list_by_lead)) {
                $sell_informations = [
                    "id" => $sells_list_by_lead->getId(),
                    "pricing" => $sells_list_by_lead->getPricing(),
                    "id_stripe" => $sells_list_by_lead->getIdStripe(),
                    "id_charge" => $sells_list_by_lead->getIdCharge(),
                    "statut" => $sells_list_by_lead->getStatut(),
                    "invoice_num" => $sells_list_by_lead->getInvoiceNum(),
                    "invoice_link" => $sells_list_by_lead->getInvoiceLink(),
                    "stripe_payment_id" => $sells_list_by_lead->getStripePaymentId(),
                    "buyer_enterprise" => [
                        "id" => $sells_list_by_lead->getBuyerEnterprise()->getId(),
                    ],
                    "lead" => [
                        "id" => $sells_list_by_lead->getLead()->getId(),
                    ]
                ];
                $response_data = ['code' => 200, 'sell_data' => $sell_informations];
            } else {
                $response_data = ['code' => 400, 'status' => 'error', 'message' => "Pas des données trouvées"];
            }

            /* array_filter(
              array_map(function ($e)use ($sells_list_by_lead) {
              if (str_starts_with($e, "get"))
              return [strtolower(str_replace("get", "", $e)) => $sells_list_by_lead->{$e}()];
              return null;
              },
              get_class_methods($sells_list_by_lead)
              )
              ); */
        } else {
            $response_data = ['code' => 400, 'status' => 'error', 'message' => "Token not found"];
        }


        $response = new JsonResponse($response_data);
        return $response;
    }

    #[Route(path: '/api/user-leads/buyed-selled/', name: 'user_leads_buyed_selled', methods: ['GET'])]
    public function userLeadsBuyedSelled(Request $request, EntityManagerInterface $entityManager) {
        $nbpage = $request->query->get('page');
        $nb_results = $request->query->get('nbresult');

        if (is_numeric($nb_results) && (strpos($nb_results, '.') === false)) {

            if (is_numeric($nbpage) && (strpos($nbpage, '.') === false)) {

                if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
                    $current_user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());

                    $user_leads_selled_query = $entityManager->getRepository(Sell::class)->findAllJoinLeadEnterprise($current_user_infos->getEnterprise()->getId(), $nbpage, $nb_results);
                    $entityManager->clear();
                    $user_leads_selled_list = [];
                    foreach ($user_leads_selled_query as $user_lead_selled_item) {
                        // buyer enteprrise infos
                        $enterprise_buyer_infos = $entityManager->getRepository(Enterprise::class)->find($user_lead_selled_item->getBuyerEnterprise()->getId());
                        $entityManager->clear();
                        // lead infos
                        $lead_infos = $entityManager->getRepository(Leads::class)->find($user_lead_selled_item->getLead()->getId());
                        $entityManager->clear();
                        // lead enterprise infos
                        $enterprise_lead_infos = $entityManager->getRepository(Enterprise::class)->find($lead_infos->getEnterprise()->getId());
                        $entityManager->clear();
                        // litiges infos
                        if (!empty($lead_infos->getLitige())) {
                            $litige_infos = $entityManager->getRepository(Litige::class)->find($lead_infos->getLitige()->getId());
                            $litige_infos = [
                                "id" => $litige_infos->getId(),
                                "commentary" => $litige_infos->getCommentary(),
                                "created_at" => $litige_infos->getCreatedAt()->format('Y-m-d H:i:s'),
                                "closed_at" => ($litige_infos->getClosedAt() != null) ? $litige_infos->getClosedAt()->format('Y-m-d H:i:s') : $litige_infos->getClosedAt(),
                                "status" => $litige_infos->getStatus(),
                                "litigeStep" => []
                            ];
                        } else {
                            $litige_infos = [];
                        }
                        $entityManager->clear();
                        array_push($user_leads_selled_list,
                                [
                                    "id" => $user_lead_selled_item->getId(),
                                    "pricing" => $user_lead_selled_item->getPricing(),
                                    "id_stripe" => $user_lead_selled_item->getIdStripe(),
                                    "id_charge" => $user_lead_selled_item->getIdCharge(),
                                    "statut" => $user_lead_selled_item->getStatut(),
                                    "invoice_num" => $user_lead_selled_item->getInvoiceNum(),
                                    "invoice_link" => $user_lead_selled_item->getInvoiceLink(),
                                    "stripe_payment_id" => $user_lead_selled_item->getStripePaymentId(),
                                    "created_at" => ($user_lead_selled_item->getCreatedAt() != null) ? $user_lead_selled_item->getCreatedAt()->format('Y-m-d H:i:s') : $user_lead_selled_item->getCreatedAt(),
                                    "buyer_enterprise" => [
                                        "id" => $enterprise_buyer_infos->getId(),
                                        "name" => $enterprise_buyer_infos->getName(),
                                    ],
                                    "lead" => [
                                        "id" => $lead_infos->getId(),
                                        "enterprise" => [
                                            "id" => $enterprise_lead_infos->getId(),
                                            "name" => $enterprise_lead_infos->getName(),
                                        ],
                                        "name" => $lead_infos->getName(),
                                        "firstname" => $lead_infos->getName(),
                                        "email" => $lead_infos->getName(),
                                        "postalCode" => $lead_infos->getName(),
                                        "city" => $lead_infos->getCity(),
                                        "address" => $lead_infos->getAddress(),
                                        "country" => $lead_infos->getcountry(),
                                        "phone" => $lead_infos->getphone(),
                                        "status" => $lead_infos->getStatus(),
                                        "pricingToSeller" => $lead_infos->getPricingToSeller(),
                                        "pricingToTpl" => $lead_infos->getPricingToTpl(),
                                        "pricingToIncrease" => $lead_infos->getPricingToIncrease(),
                                        "min_date" => ($lead_infos->getMinDate() != null) ? $lead_infos->getMinDate()->format('Y-m-d H:i:s') : $lead_infos->getMinDate(),
                                        "max_date" => ($lead_infos->getMaxDate() != null) ? $lead_infos->getMaxDate()->format('Y-m-d H:i:s') : $lead_infos->getMaxDate(),
                                        "created_at" => $lead_infos->getCreatedAt()->format('Y-m-d H:i:s'),
                                        "disabled_at" => ($lead_infos->getDisabledAt() != null) ? $lead_infos->getDisabledAt()->format('Y-m-d H:i:s') : $lead_infos->getDisabledAt(),
                                        "validated_at" => ($lead_infos->getValidatedAt() != null) ? $lead_infos->getValidatedAt()->format('Y-m-d H:i:s') : $lead_infos->getValidatedAt(),
                                        "activity" => $lead_infos->getActivity(),
                                        "commentary" => $lead_infos->getCommentary(),
                                        "litige" => $litige_infos
                                    ],
                        ]);
                    }

                    $response_data = ['code' => 200, 'leads_buyed_selled' => (array) $user_leads_selled_list];
                } else {
                    $response_data = ['code' => 400, 'status' => 'error', 'message' => "Token not found"];
                }
            } else {
                $response_data = ['code' => 400, 'status' => 'error', 'message' => " Le paramétre 'Page'  doit être un nombre"];
            }
        } else {
            $response_data = ['code' => 400, 'status' => 'error', 'message' => " Le paramétre 'Nbresult' paramétre  doit être un nombre"];
        }

        return new JsonResponse($response_data);
    }

    #[Route(path: '/api/sells/invoices/buyed-selled/from-to', name: 'sells_invoices_buyed_selled_from_to', methods: ['GET'])]
    public function sellsInvoicesBuyedSelledFromTo(Request $request, EntityManagerInterface $entityManager) {

        if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
            if (($request->query->get('date_from') != null) && ( ($request->query->get('date_to')) != null )) {
                $date_from = $request->query->get('date_from');
                $date_to = $request->query->get('date_to');
                if (( \DateTime::createFromFormat('Y-m-d H:i', ($date_from)) ) && ( \DateTime::createFromFormat('Y-m-d H:i', ($date_to)) )) {
                    $current_user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                    $user_leads_selled_query = $entityManager->getRepository(Sell::class)->findValidBuyedSelledFromToDate($current_user_infos->getEnterprise()->getId(), $date_from, $date_to, true);
                    //dd($user_leads_selled_query);
                    $response_data = ['code' => 200, 'sells_invoices' => $user_leads_selled_query];
                } else {
                    $response_data = [
                        'code' => 400,
                        'status' => 'error',
                        'message' => ((!(\DateTime::createFromFormat('Y-m-d H:i:s', ($date_from)) )) ? ' Erreur fromat "date_from" (YYYY-mm-dd H:i) ' : '') . ' ' . ((!(\DateTime::createFromFormat('Y-m-d H:i', ($date_to)) )) ? ' Erreur fromat "date_to" (YYYY-mm-dd H:i:s) ' : '')
                    ];
                }
            } else {
                $response_data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => ( ($request->query->get('date_from') == null) ? ' Variable "date_from" manquants ' : '') . ' ' . ( ($request->query->get('date_to') == null) ? ' Variable "date_to" manquants ' : '')
                ];
            }
        } else {
            $response_data = ['code' => 400, 'status' => 'error', 'message' => "Token not found"];
        }

        return new JsonResponse($response_data);
    }

    #[Route(path: '/api/sells/download/invoices', name: 'sells_invoices', methods: ['GET'])]
    public function downloadMultipleSellsInvoices(Request $request, EntityManagerInterface $entityManager) {

        dd(file_get_contents('https://pay.stripe.com/invoice/acct_1OI6r5DePNMea6Fp/test_YWNjdF8xT0k2cjVEZVBOTWVhNkZwLF9QVm9CM1dEUGd0bjRLUHZ4NGNvNVBUYThFZnVyeTJVLDk3NzU4NzQ30200SZ010fvF/pdf?s=ap'));

        dd("ok");

        if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
            if (($request->query->get('date_from') != null) && ( ($request->query->get('date_to')) != null )) {
                $date_from = $request->query->get('date_from');
                $date_to = $request->query->get('date_to');
                if (( \DateTime::createFromFormat('Y-m-d H:i', ($date_from)) ) && ( \DateTime::createFromFormat('Y-m-d H:i', ($date_to)) )) {
                    $current_user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                    $user_leads_selled_query = $entityManager->getRepository(Sell::class)->findValidBuyedSelledFromToDate($current_user_infos->getEnterprise()->getId(), $date_from, $date_to);
                    //dd($user_leads_selled_query);
                    $response_data = ['code' => 200, 'sells_invoices' => $user_leads_selled_query];
                } else {
                    $response_data = [
                        'code' => 400,
                        'status' => 'error',
                        'message' => ((!(\DateTime::createFromFormat('Y-m-d H:i:s', ($date_from)) )) ? ' Erreur fromat "date_from" (YYYY-mm-dd H:i) ' : '') . ' ' . ((!(\DateTime::createFromFormat('Y-m-d H:i', ($date_to)) )) ? ' Erreur fromat "date_to" (YYYY-mm-dd H:i:s) ' : '')
                    ];
                }
            } else {
                $response_data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => (!isset($post_data['date_from']) ? ' Variable "date_from" manquants ' : '') . ' ' . (!isset($post_data['date_to']) ? ' Variable "date_to" manquants ' : '')
                ];
            }
        } else {
            $response_data = ['code' => 400, 'status' => 'error', 'message' => "Token not found"];
        }

        return new JsonResponse($response_data);
    }

    /**
     * 
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * 
     * Custom create Sell ( prevent lead id insertion problem if user dont have access to it )
     * 
     */
    #[Route(path: '/api/sells', name: 'custom_add_new_sell', methods: ['POST'])]
    public function customAddNewSell(Request $request, EntityManagerInterface $entityManager) {
        $new_sell = new Sell();
        $response_data = [];
        $post_data = json_decode($request->getContent(), true);

        if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
            $user_infos = $entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
            if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {

                try {

                    /* check post data to insert */
                    $waited_inputs = ['lead', 'buyerEnterprise', 'pricing', 'statut', 'idStripe', 'stripePaymentId'];
                    $post_data = json_decode($request->getContent(), true);
                    $check_post_data = $this->customFunctions->checkAllInArray($post_data, $waited_inputs);

                    if ($check_post_data === true) {
                        $stripe = new \Stripe\StripeClient($this->getParameter('stripe_key'));
                        /* get the payment Intent infos */
                        $payment_intent_infos = $stripe->paymentIntents->retrieve($post_data['stripePaymentId'], []);
                        /* end get the payment Intent infos */
                        /* get the charge infos */
                        $charge_infos = $stripe->charges->retrieve($payment_intent_infos->latest_charge, []);
                        /* end get the charge infos */

                        //Enterprise
                        $buyer_enterprise = $entityManager->getRepository(Enterprise::class)->find($post_data['buyerEnterprise']);
                        //Lead
                        $lead_buyed = $entityManager->getRepository(Leads::class)->find($post_data['lead']);
                        if (!empty($buyer_enterprise) && !empty($lead_buyed)) {

                            /* add new sell */
                            $new_sell->setBuyerEnterprise($buyer_enterprise)
                                    ->setLead($lead_buyed)
                                    ->setPricing($post_data['pricing'])
                                    ->setIdStripe($post_data['idStripe'])
                                    ->setStatut($post_data['statut'])
                                    ->setStripePaymentId($post_data['stripePaymentId']);
                            $entityManager->persist($new_sell);
                            $entityManager->flush();

                            if ($new_sell->getId()) {
                                //send email for sell
                                $this->customFunctions->sendEmail(
                                        $user_infos->getEmail(),
                                        '📜 Reservation prospect effectuer avec succés',
                                        'Votre reservation du prospect a etait effectue avec succès, le montant a etait resrever mais pas encore prélever, il sera prélever sous 2 jours (validation atomatique du prospect) ou en cas de valiation manuele sur la palforme.<br/><br/> '
                                        . 'vous trouverez ci-dessous le lien du recus de payement.<br/> ' . $charge_infos->receipt_url
                                        . '<br/><br/> En vous remerciant, <br/><br/> Cordialement,');
                            }

                            $response_data = ['code' => 200, "message" => "success", "id_sell" => $new_sell->getId()];
                        } else {
                            $response_data = ['code' => 400, "statut" => "error", "message" =>
                                (empty($buyer_enterprise)) ? "Les informations entreprise de l'acheteur introuvables" : "Les informations sur le lead acheté introuvables"];
                        }
                    } else {
                        $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur post data, variable manquante (" . $check_post_data . ")"];
                    }
                } catch (\Throwable $e) {
                    $response_data = ['code' => 400, "message" => $e->getMessage()];
                }
            } else {
                $response_data = ['code' => 401, 'status' => 'error', 'message' => "Utilisateur invalid"];
            }
        } else {
            $response_data = ['code' => 400, 'status' => 'error', 'message' => "Token not found"];
        }

        return new JsonResponse($response_data);
    }

    
}
