<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Faker\Generator;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Enterprise;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\ApiResource\CustomFunctions;

/**
 * Description of UserController
 *
 * @author Dev
 */
class UserController extends AbstractController {

    private Generator $faker;
    private $passwordHasher;
    private $entityManager;
    private $jwtManager;
    private $customFunctions;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, private Security $security, JWTTokenManagerInterface $jwtManager, CustomFunctions $customFunctions) {
        $this->faker = Factory::create('fr_FR');
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
        $this->customFunctions = $customFunctions;
    }

    #[Route(path: '/api/users/{id}/update/password', name: 'user_update_password', methods: ['PATCH'])]
    public function updateUserPassword(Request $request, EntityManagerInterface $entityManager, $id) {
        //get user infos
        $user_infos = $entityManager->getRepository(User::class)->find($id);

        if (!empty($user_infos)) {
            $old_password = json_decode($request->getContent(), true)['old_password'];
            $new_password = json_decode($request->getContent(), true)['new_password'];

            if ($this->passwordHasher->isPasswordValid($user_infos, $old_password)) {

                $user_infos->setPassword(
                        $this->passwordHasher->hashPassword(
                                $user_infos,
                                $new_password
                ));
                $entityManager->persist($user_infos);
                $entityManager->flush();

                $data = ["message" => "Mot de passe modifié avec succès", "code" => 200];
            } else {
                $data = ["message" => "Erreur, mot de passe actuel incorrecte", "code" => 500];
            }
        } else {
            $data = ["message" => "Erreur informations utilisateur", "code" => 500];
        }



        $response = new JsonResponse($data);
        return $response;
    }

    #[Route(path: '/api/users/reinit/password', name: 'user_reinit_password', methods: ['PATCH'])]
    public function reinitUserPassword(Request $request, EntityManagerInterface $entityManager) {

        if (isset(json_decode(trim($request->getContent()), true)['email'])) {
            $faker = new Generator();
            $user_email = json_decode(trim($request->getContent()), true)['email'];
            $new_password = $this->faker->bothify('???##??');

            //get user infos
            $user_infos = $entityManager->getRepository(User::class)->findOneBy(['email' => $user_email]);
            //dd($user_infos;)

            if (!empty($user_infos)) {
                $user_infos->setPassword(
                        $this->passwordHasher->hashPassword(
                                $user_infos,
                                $new_password
                ));

                /* send email */
                $email = new \SendGrid\Mail\Mail();
                $email->setFrom("contact@tpl17.fr", "Example User");
                $email->setSubject('🔏 Réinitialisation de votre mot de passe "Troc my lead"');
                $email->addTo($user_email, "Example User");
                $email->addContent(
                        'text/html',
                        '<p>Bonjour,</p>' .
                        '<p>Vous avez fait une demande de changement de mot de passe sur l\'application "Troc my lead".<br> Vous trouverez si-dessous votre nouveau mot de passe:</p>' .
                        '<strong>' . $new_password . '</strong>'
                );
                $sendgrid = new \SendGrid('');
                try {
                    $response = $sendgrid->send($email);
                    if (substr($response->statusCode(), 0, 2) === "20") {

                        $entityManager->persist($user_infos);
                        $entityManager->flush();

                        $data = ["message" => "Mot de passe modifié avec succès", "code" => 200];
                    } else {
                        $data = $response->body();
                    }
                } catch (Exception $e) {
                    //dd( 'Caught exception: '. $e->getMessage() );
                    $data = ["message" => $e->getMessage(), "code" => 500];
                }
            } else {
                $data = ["message" => "Aucun compte trouvait avec cette adresse email", "code" => 500];
            }
        }

        $response_json = new JsonResponse($data);

        return $response_json;
    }

    #[Route(path: '/api/users/enterprise/{id}', name: 'get_enterprise_users_list', methods: ['GET'])]
    public function getEnterpriseUsersList(Request $request, SerializerInterface $serializer, $id) {
        $response_data = [];

        try {
            if (($this->security->getToken() !== null) && ( $this->security->isGranted("ROLE_ADMIN") || $this->security->isGranted("ROLE_USER"))) {
                if (is_numeric($id)) {
                    if ($this->security->isGranted("ROLE_ADMIN")) {
                        $list_users = $this->entityManager->getRepository(User::class)->FindBy(["enterprise" => $id]);
                        $jsonContent = $serializer->serialize($list_users, 'json');
                        $response_data = ['code' => 200, 'list_users' => json_decode($jsonContent)];
                    } else {
                        $current_user_infos = $this->entityManager->getRepository(User::class)->Find($this->security->getUser()->getId());
                        if (!empty($current_user_infos) && ($current_user_infos->getEnterprise()->getId() == $id)) {
                            $list_users = $this->entityManager->getRepository(User::class)->FindBy(["enterprise" => $id]);
//                            $jsonContent = $serializer->serialize($list_users, 'json', [
//                                'groups' => ['group1'], // Optional: configure serialization groups if needed
//                                'circular_reference_handler' => function ($object) {
//                                    return $object->getId();
//                                }
//                            ]);
                            $jsonContent = $serializer->serialize($list_users, 'json');
                            $response_data = ['code' => 200, 'list_users' => json_decode($jsonContent)];
                        } else {
                            $response_data = ['code' => 400, 'status' => 'error', 'message' => "Accès non autorisé aux informations de cette entreprise"];
                        }
                    }
                } else {
                    $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur enterprise ID"];
                }
            } else {
                $response_data = ['code' => 400, 'status' => 'error', 'message' => "Token not found"];
            }
        } catch (\Throwable $ex) {
            $response_data = ['code' => 400, "message" => $ex->getMessage()];
        }


        return new JsonResponse($response_data);
    }

    #[Route(path: '/api/enable/account', name: 'user_enable_account', methods: ['PATCH'])]
    public function enableUserAccount(Request $request, EntityManagerInterface $entityManager) {
        $response_data = [];
        $waited_inputs = ['code_validation', 'email'];
        $post_data = json_decode($request->getContent(), true);
        $check_post_data = $this->customFunctions->checkAllInArray($post_data, $waited_inputs);

        if ($check_post_data === true) {
            $get_user_by_email = $this->entityManager->getRepository(User::class)->FindOneBy(['email' => $post_data['email']]);

            if (!empty($get_user_by_email)) {
                if ($get_user_by_email->getCodeValidation() == $post_data['code_validation']) {

                    //enable compte utilisateur, enabled_at = datenow
                    $get_user_by_email->setEnabledAt(\DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d H:i:s', date("Y-m-d H:i:s"))));
                    //$this->entityManager->merge($get_user_by_email);
                    $this->entityManager->flush();

                    $response_data = ['code' => 200, 'message' => "Compte utilisateur activé avec succes"];
                } else {
                    $response_data = ['code' => 204, 'status' => 'error', 'message' => "Erreur code validation"];
                }
            } else {
                $response_data = ['code' => 404, 'status' => 'error', 'message' => "Aucun compte utilisateur n'est asssocié à l'adresse email " . $post_data['email']];
            }
        } else {
            $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur post data, variable manquante (" . $check_post_data . ")"];
        }

        $response_json = new JsonResponse($response_data);
        return $response_json;
    }

    #[Route(path: '/api/new/validation/code', name: 'new_validation_code', methods: ['GET'])]
    public function getNewValidationCode(Request $request, EntityManagerInterface $entityManager) {
        $response_data = [];
        
        //check if email sent in url query params
        if (!is_null($request->query->get('email'))) {
            $email = $request->query->get('email');
            $get_user_by_email = $this->entityManager->getRepository(User::class)->FindOneBy(['email' => $email]);

            if (!empty($get_user_by_email)) {
                $new_code_validation = $this->faker->safeColorName() . $this->faker->randomNumber(3) . $this->faker->safeColorName();
                //insert new code activation
                $get_user_by_email->setCodeValidation($new_code_validation);
                //$this->entityManager->merge($get_user_by_email);
                $this->entityManager->flush();

                //send email with new code validation to adresse email
                $email_new_validation_code_content = 'Bonjour, <br/><br/> '
                        . 'Votre nouveau code d\'activation est : <b>' . $new_code_validation . '</b> <br/>'
                        . 'Si vous n\'êtes pas à l\'origine de cette demande, veuillez ignorer cet e-mail. Il est possible qu\'une autre personne se soit trompée lors de la saisie de ses identifiants.<br/>';
                $this->customFunctions->sendEmail($email, "TrocMonLead : Nouveau code activation", $email_new_validation_code_content);

                $response_data = ['code' => 200, 'message' => "Code validation envoyait avec succès à l'adresse email suivante " . $email ];
            } else {
                $response_data = ['code' => 404, 'status' => 'error', 'message' => "Aucun compte utilisateur n'est asssocié à l'adresse email " . $email];
            }
        } else {
            $response_data = ['code' => 404, 'status' => 'error', 'message' => "Valeur adresse email manquante"];
        }

        $response_json = new JsonResponse($response_data);
        return $response_json;
    }
}
