<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Sell;
use App\Entity\Lead;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\ApiResource\CustomFunctions;

/**
 * Description of AutoValidLeadsCommand
 *
 * @author Dev
 */
class AutoLeadsValidationCommand extends Command {

    protected static $defaultName = 'app:auto-leads-validation';
    private $logger;
    private $stripe;
    private $globalParams;

    public function __construct(LoggerInterface $logger, private EntityManagerInterface $entityManager, ParameterBagInterface $globalParams, CustomFunctions $customFunctions) {
        parent::__construct();
        $this->logger = $logger;
        $this->stripe = new \Stripe\StripeClient($globalParams->get('stripe_key'));
        $this->customFunctions = $customFunctions;
        $this->globalParams = $globalParams;
    }

    protected function configure(): void {
        $this
                ->setDescription('Description of the cron job')
                ->setHelp('This command allows you to run a specific task periodically...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        // admins list
        $admins_list = $this->entityManager->getRepository(User::class)->findBy(['enterprise' => null]);

        try {
            $customFucntion = new CustomFunctions();

            $leads_should_be_validated = $this->entityManager->getRepository(Lead::class)->findLeadsReservedTwoDaysAgo();
            //dd($leads_should_be_validated);

            if (!empty($leads_should_be_validated)) {

                foreach ($leads_should_be_validated as $leadshouldbevalidated_item) {
                    //echo $leadshouldbevalidated_item->getSell()->getStripePaymentId() . '<br/>';
                    /* get payment intent infos  */
                    $paymentIntentToCapture = $this->stripe->paymentIntents->retrieve($leadshouldbevalidated_item->getSell()->getStripePaymentId());
                    /* capturer le payement */
                    $paymentIntentToCapture->capture(['amount_to_capture' => $paymentIntentToCapture->amount]);

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
                        'description' => "Prospect #" . $leadshouldbevalidated_item->getId(),
                        'invoice' => $invoiceCreate->id
                    ]);

                    $paidInvoice = $this->stripe->invoices->pay($invoiceCreate->id, ['paid_out_of_band' => true]); // mark invoice as payed
                    /* End Create invoice */

                    // save invoice 
                    $this->customFunctions->downloadAndSaveFile(
                            $paidInvoice->invoice_pdf,
                            $this->globalParams->get('enterprise_files_directory') . 'enterprise_' . $leadshouldbevalidated_item->getEnterprise()->getId() . '/invoices/' . $paidInvoice->number . '.pdf');

                    /* update lead row */
                    $lead_row_to_update = $this->entityManager->getRepository(Lead::class)->find($leadshouldbevalidated_item->getId());
                    $lead_row_to_update->setStatus("valid");
                    $lead_row_to_update->setValidatedAt((new \DateTimeImmutable()));
                    $this->entityManager->merge($lead_row_to_update);
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    /* END update lead row */

                    /* update sell row */
                    $sell_row_to_update = $this->entityManager->getRepository(Sell::class)->find($leadshouldbevalidated_item->getSell()->getId());
                    $sell_row_to_update->setStatut("paid");
                    $sell_row_to_update->setInvoiceNum($paidInvoice->number);
                    $sell_row_to_update->setInvoiceLink($paidInvoice->invoice_pdf);
                    $sell_row_to_update->setInvoiceId($paidInvoice->id);
                    $this->entityManager->merge($sell_row_to_update);
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    /* END update sell row */

                    foreach ($admins_list as $admin_item) {
                        //send email of litige
                        $this->customFunctions->sendEmail(
                                $admin_item->getEmail(),
                                'TML: validation automatique du prospect #'.$leads_should_be_validated->getId().'#',
                                'La validation automatique du prospect #'.$leads_should_be_validated->getId().'# à été effectuer avec succès.<br/><br/><br/> Cordialement,'
                        );
                    }
                }

                $output->writeln('');
                $output->writeln('------------------------------------------');
                $output->writeln('<info>Fin des validations automatiques des prospects.</info>');
                $output->writeln('------------------------------------------');
            } else {
                $output->writeln('');
                $output->writeln('------------------------------------------');
                $output->writeln('<info>Aucun prospect trouvé pour la valdiation automatique.</info>');
                $output->writeln('------------------------------------------');
            }
        } catch (Exception $ex) {

            foreach ($admins_list as $admin_item) {
                //send email of litige
                $this->customFunctions->sendEmail(
                        $admin_item->getEmail(),
                        'TML: Erreur validation automatique des prospects',
                        'Une erreur c\'est produite lors de la validation automatique des prospects, ci-dessous la description de l\'erreur:<br/>' . $ex->getMessage() . '<br/><br/> Cordialement,'
                );
            }

            $output->writeln('');
            $output->writeln('------------------------------------------');
            $output->writeln('<error>' . $ex->getMessage() . '</error>');
            $output->writeln('------------------------------------------');
        }


        // Indicate that the command executed successfully
        return Command::SUCCESS;
    }
}
