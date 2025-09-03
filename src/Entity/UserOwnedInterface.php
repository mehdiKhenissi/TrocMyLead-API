<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Entity;

/**
 * Description of UserOwnedInterface
 *
 * @author Dev
 */
Interface UserOwnedInterface {
    //put your code here, this should be referenced to enterprise because all is related to enterprise not user is main classe
    
     public function getEnterprise(): ?enterprise;

    public function setEnterprise(?enterprise $enterprise): static;
    
}
