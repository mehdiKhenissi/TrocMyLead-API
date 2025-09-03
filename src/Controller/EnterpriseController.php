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

/**
 * Description of EnterpriseController
 *
 * @author Dev
 */
class EnterpriseController extends AbstractController {

    public function __construct(private Security $security) {
        
    }

//    public function __invoke(Request $request) {
//        $file = $request->files->get('file');
//
//        foreach ($request->files as $file_item){
//            
//                echo($file_item);
//            
//        }
//        
////        if (move_uploaded_file($_FILES["file"]["tmp_name"], $uploadFile)) {
////            echo "File is valid, and was successfully uploaded.\n";
////        } 
////        else {
////            echo "Error uploading file.\n";
////        }
//
//        dd($file);
//    }

    #[Route(path: '/api/enterprises/{id}/upload/files', name: 'upload_enterprises_files', methods: ['POST'])]
    public function uploadFiles(Request $request, $id) {
        //$file = $request->files->get('file');
        $upload_dir = $this->getParameter('enterprise_files_directory') . 'enterprise_' . $id . '/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }


        foreach ($_FILES as $key_file_item => $file_item) {
            
            // check if file already exist and remove it
            foreach (glob($upload_dir . $key_file_item . ".*") as $file_already_exist) {
                unlink($file_already_exist);
            }
            
            $extension = strrchr($file_item["name"], '.');
            if (in_array($extension, [".jpg", ".png", ".PNG", ".JPEG", ".JPG", ".gif", ".svg", ".jpg", ".apng", ".avif", ".pjp", ".pjpeg", ".jfif", ".bmp", ".ico", ".cur", ".tif", ".tiff"])) {
                $extension = ".webp";
            }
            
            /*$response = new JsonResponse($extension);
            return $response;*/

            if (move_uploaded_file($file_item["tmp_name"], $upload_dir . $key_file_item . $extension)) {
                $data = [
                    'code' => 200,
                    'message' => 'Success',
                ];
            } else {
                $data = [
                    'code' => 500,
                    'message' => 'Problem upload file',
                ];
            }
        }

        $response = new JsonResponse($data);
        return $response;
    }

    #[Route(path: '/api/enterprises/{id}/get/files', name: 'get_enterprises_files', methods: ['GET'])]
    public function readFiles(Request $request, $id) {
        $enterprise_files_dir = $this->getParameter('enterprise_files_directory') . 'enterprise_' . $id . '/';

        dd($enterprise_files_dir);

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES as $key_file_item => $file_item) {
            $extension = strrchr($file_item["name"], '.');

            if (move_uploaded_file($file_item["tmp_name"], $upload_dir . $key_file_item . $extension)) {
                $data = [
                    'code' => 200,
                    'message' => 'Success',
                ];
            } else {
                $data = [
                    'code' => 500,
                    'message' => 'Problem upload file',
                ];
            }
        }

        $response = new JsonResponse($data);
        return $response;
    }
}
