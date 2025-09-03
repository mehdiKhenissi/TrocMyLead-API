<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Serializer;

use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use \Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Security\Core\Security;
use App\Entity\UserOwnedInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

/**
 * Description of UserOwnedDenormalizer
 *
 * @author Dev
 */
class UserOwnedDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface {

    //put your code here

    use DenormalizerAwareTrait;

    private const ALREADY_CALLED_DENORMALIZER = 'UserOwnedDenormalizerCalled'; // key to check if denormalizer already called, if dont use this all denormalizer will be called and create infinite loop in denormalize fucntion when  denromalize called itself

    public function __construct(private Security $security, private EntityManagerInterface $entityManager) {
        
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool {
        $reflectionClass = new \ReflectionClass($type);
        //$alreadyCalled = $context[$this->getAlreadyCallerKey($type)] ?? false;
        $alreadyCalled = $data[self::ALREADY_CALLED_DENORMALIZER] ?? false;

        return $reflectionClass->implementsInterface(UserOwnedInterface::class) && $alreadyCalled === false;
    }

    public function denormalize(mixed $data, string $type, mixed $format = null, array $context = []): mixed {
        //$context[$this->getAlreadyCallerKey($type)] = true;
        $data[self::ALREADY_CALLED_DENORMALIZER] = true;
        $obj = $this->denormalizer->denormalize($data, $type, $format, $context);

//        if ($this->security->getUser()) {
//            $current_user_infos = $this->entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
//            
//            if(!is_null($current_user_infos->getEnterprise())){
//                $obj->setEnterprise($current_user_infos->getEnterprise());
//            }
//            
//        }


        return $obj;
    }

    private function getAlreadyCallerKey(string $type) {

        return self::ALREADY_CALLED_DENORMALIZER . $type;
    }
}
