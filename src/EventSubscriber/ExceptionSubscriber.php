<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use App\ApiResource\CustomFunctions;

/**
 * EXEMPLE CLASS
 * 
 * Description of ExceptionSubscriber
 *
 * this is an exemple of excpetion subscriber class that handle exceptions and send mails, like expcetion listener but implemented in another way 
 * 
 * @author Dev
 */
class ExceptionSubscriber implements EventSubscriberInterface {

    private $customFunctions;

    public function __construct(CustomFunctions $customFunctions) {
        $this->customFunctions = $customFunctions;
    }

    //put your code here
    public static function getSubscribedEvents() {
        return [
           // 'kernel.exception' => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event) {
        // Prepare and send the email
        $email_admin_content = 'Bonjour, <br/> '
                . 'Une Erreur systéme s\'est produite.<br/>'
                . 'Details erreur:<br/>'
                . '<b>- Date : </b>' . date('Y-m-d H:i:s') . ' <br/> '
                . '<b>- Fichier : </b>' . $event->getThrowable()->getFile() . ' <br/> '
                . '<b>- Ligne : </b>' . $event->getThrowable()->getLine() . ' <br/> '
                . '<b>- Message : </b>' . $event->getThrowable()->getMessage() . ' <br/> ';

        //$this->customFunctions->sendEmail("mehdi.khenissi@tpl17.fr", "TML: Erreur systéme ", $email_admin_content, []);
    }
}
