<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\ApiResource\CustomFunctions;

/**
 * Description of ExceptionListener
 *
 * @author Dev
 */
class ExceptionListener {

    //put your code here

    private $customFunctions;
    
    public function __construct(CustomFunctions $customFunctions) {
        $this->customFunctions = $customFunctions;
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

        $this->customFunctions->sendEmail("tml@tpl17.fr ", "TML: Erreur systéme ", $email_admin_content, []);

    }
}
