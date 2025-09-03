<?php

namespace App\DataFixtures;

use App\Entity\Enterprise;
use App\Entity\User;
use App\Entity\Leads;
use App\Entity\Litige;
use App\Entity\LitigeStep;
use App\Entity\Sell;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture {

    private Generator $faker;
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher) {
        $this->faker = Factory::create('fr_FR');
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void {

        /* Enterprise */
        $enterprise_four = null;
        for ($i = 0; $i < 5; $i++) {
            $enterprise = new Enterprise();
            $enterprise->setSiren($this->faker->randomNumber(9));
            $enterprise->setName($this->faker->words(2, true));
            $enterprise->setAddress($this->faker->address());
            $enterprise->setCity($this->faker->city());
            //$enterprise->setCountry($this->faker->country());
            $enterprise->setCountry('France');
            $enterprise->setPostalCode($this->faker->randomNumber(5));
            $enterprise->setEnabledAt(\DateTimeImmutable::createFromMutable(new \DateTime('now', new \DateTimeZone('UTC'))));
            $manager->persist($enterprise);
        }
        $manager->flush();

        $enterprise_four = $manager->getRepository(Enterprise::class)->find($enterprise->getId() - 1);

        /* USER */
        /* create admin user */
        $user = new User();
        $user->setFirstname($this->faker->firstName())
                ->setName($this->faker->lastName())
                ->setEmail("mehdi.khenissi@tpl17.fr")
                ->setPhone('07' . $this->faker->randomNumber(8))
                //->setCodeValidation($this->faker->word() .$this->faker->randomNumber(3).$this->faker->word())
                ->setCodeValidation($this->faker->safeColorName() . $this->faker->randomNumber(3) . $this->faker->safeColorName())
                ->setRoles(['ROLE_ADMIN'])
                ->setPassword(
                        $this->passwordHasher->hashPassword(
                                $user,
                                '1234'
                        )
                )
                ->setEnabledAt(\DateTimeImmutable::createFromMutable(new \DateTime('now', new \DateTimeZone('UTC'))));
        $manager->persist($user);
        for ($iu = 0; $iu < 2; $iu++) {
            $user = new User();
            $user->setFirstname($this->faker->firstName())
                    ->setName($this->faker->lastName())
                    ->setEmail($this->faker->email())
                    ->setPhone('07' . $this->faker->randomNumber(8))
                    //->setCodeValidation($this->faker->word() .$this->faker->randomNumber(3).$this->faker->word())
                    ->setCodeValidation($this->faker->safeColorName() . $this->faker->randomNumber(3) . $this->faker->safeColorName())
                    ->setRoles(['ROLE_USER'])
                    ->setPassword(
                            $this->passwordHasher->hashPassword(
                                    $user,
                                    '1234'
                            )
                    )
                    ->setMain(true)
                    ->setEnabledAt(\DateTimeImmutable::createFromMutable(new \DateTime('now', new \DateTimeZone('UTC'))));
            if ($iu % 2 == 0) {
                $user->setEmail("mehdi.khenissi@hotmail.fr")
                        ->setEnterprise($enterprise);
            } else {
                $user->setEmail("mehdi.khenisi@gmail.com")
                        ->setEnterprise($enterprise_four);
            }
            $manager->persist($user);
        }

        /* Leads */
        $activity = ["Art", "Tunnel", "Réseaux", "Électricité", "Maritime", "Génie Civil", "Démolition", "Terrassement", "Forage", "Électricité", "Plomberie",
            "Thermique", "Isolation", "Plâtrerie", "Sol", "Menuiserie", "Serrurerie", "Agencement", "Revêtement", "Peinture", "Charpente", "Couverture", "Étanchéité", "Structure", "Maçonnerie", "Automobile", "Elagage", "Paysage"];

        for ($il = 0; $il < 100; $il++) {
            $lead = new Leads();
            $pricing_to_seller = $this->faker->randomFloat(2, 10, 100);

            $lead->setFirstname($this->faker->firstName())
                    ->setName($this->faker->name())
                    ->setAddress($this->faker->address())
                    ->setPostalCode($this->faker->postcode)
                    ->setCity($this->faker->city())
                    ->setCountry($this->faker->country())
                    ->setPhone('07' . $this->faker->randomNumber(8))
                    ->setEmail($this->faker->email())
                    ->setStatus("to_sell")
                    ->setActivity($activity[array_rand($activity)])
                    ->setCommentary($this->faker->words(10, true))
                    ->setPricingToSeller($pricing_to_seller)
                    ->setPricingToTpl(($pricing_to_seller * 0.25))
                    ->setPricingToIncrease(($pricing_to_seller + (($pricing_to_seller * 0.25) * 2)))
                    ->setMinDate(\DateTimeImmutable::createFromMutable($this->faker->datetime()))
                    ->setMaxDate(\DateTimeImmutable::createFromMutable($this->faker->datetime()));

            if ($il % 2 == 0) {
                $lead->setEnterprise($enterprise_four);
            } else {
                $lead->setEnterprise($enterprise);
            }



            $manager->persist($lead);
        }


        /* Litige
          for ($ili = 0; $ili < 1; $ili++) {
          $litige = new Litige();

          $litige->setLead($lead)
          ->setStatus("waiting")
          ->setCommentary($this->faker->words(10, true));

          $manager->persist($litige);
          } */


        /* LitigeStep 
          for ($ilit = 0; $ilit < 1; $ilit++) {
          $litige_step = new LitigeStep();

          $litige_step->setLitige($litige)
          ->setStep(0)
          ->setCommentary($this->faker->words(10, true));

          $manager->persist($litige_step);
          } */


        /* Sell 
          for ($isell = 0; $isell < 1; $isell++) {
          $sell_entity = new Sell();

          $sell_entity->setLead($lead)
          ->setBuyerEnterprise($enterprise)
          ->setPricing($this->faker->randomFloat(2, 10, 100))
          ->setIdStripe($this->faker->word())
          ->setIdCharge($this->faker->word())
          ->setStatut($this->faker->word())
          ->setInvoiceNum($this->faker->word())
          ->setInvoiceLink($this->faker->url())
          ->setCreatedAt(\DateTimeImmutable::createFromMutable($this->faker->datetime()));

          $manager->persist($sell_entity);
          } */

        $manager->flush();
    }
}
