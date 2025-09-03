<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\ApiResource;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Description of CustomFunctions
 *
 * @author Dev
 */
class CustomFunctions {

    private $globalParams;

    public function __construct( ParameterBagInterface $globalParams) {
        $this->globalParams = $globalParams;
    }

    public function checkAllInArray(array $inputs, array $waited_inputs) {
        foreach ($waited_inputs as $waited_input_item) {
            if (!array_key_exists($waited_input_item, $inputs)) {
                return $waited_input_item;
            }
        }
        return true;
    }

    /**
     * 
     * @param type $email_to
     * @param type $email_content
     * @return bool
     */
    public function sendEmail($email_to, $email_subject, $email_content, $file_attachment = []) {
        try {
            $sendgrid = new \SendGrid($this->globalParams->get('send_grid_key'));

            $email_content = '<p>' . $email_content . '</p>'.
                    'Cordialement,<br/>'.
                    'L\'équipe TrocMonLead</br>';
                    
            $email = new \SendGrid\Mail\Mail();
            $email->setFrom("contact@tpl17.fr", "TrocMonLead (Env Dev)");
            $email->setSubject($email_subject);
            $email->addTo($email_to, $email_to);
            $email->addContent(
                    'text/html',
                    $email_content
            );

            // Attach a file
            if (!empty($file_attachment)) {
                foreach ($file_attachment as $file_attach_item) {
                    $file_type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file_attach_item);
                    $file_encoded = base64_encode(file_get_contents($file_attach_item));
                    $email->addAttachment(
                            $file_encoded,
                            $file_type,
                            basename($file_attach_item),
                            "attachment"
                    );
                }
            }


            $response = $sendgrid->send($email);
            if (substr($response->statusCode(), 0, 2) === "20") {

                return true;
            } else {
                //$data = $response->body();
                return false;
            }
        } catch (\Throwable $e) {
            /*throw $e;*/
            //dd( 'Caught exception: '. $e->getMessage() );
            return false;
        }
    }
    
    
    /**
     * 
     * @param type $urlInvoice
     * @param type $targetFile
     * @return bool
     * 
     * download and save stripe invoice
     */
    public function downloadAndSaveFile($urlInvoice, $targetFile) {

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
    
    
}
