<?php
/**
 * Google Drive Library
 *
 * Custom library to do custom functions for google SDK
 *
 * This library requires PHP SDK for GOOGLE.
 *
 * It also requires a Service Account from your google developers account - see more: https://developers.google.com/identity/protocols/OAuth2ServiceAccount
 *
 * Usage:
 *
 * # Initialize the library.
 * $this->load->library("google_drive");
 *
 *
 * # Upload file, the return is either "" or the file name.
 * $GoogleDriveFileId = $this->google_drive->upload("./somewhere/image.jpg");
 *
 *
 * # There you go
 * echo "https://drive.google.com/uc?export=view&id=" . $GoogleDriveFileId ;
 *
 *
 * */

require_once APPPATH. 'third_party/Google/autoload.php';
require_once APPPATH. 'third_party/Google/Client.php';
require_once APPPATH. 'third_party/Google/Service/Drive.php';

class Google_drive
{
    public $google;
    public $file;

    public function __construct(  )
    {
        $client_email = 'yoursomethingsomethingserviceaccountfromgoogle';

        $private_key = file_get_contents( APPPATH . 'somewhere/credentials_file.p12' );

        $scopes = array('https://www.googleapis.com/auth/drive.file');

        $credentials = new Google_Auth_AssertionCredentials(
            $client_email,
            $scopes,
            $private_key
        );

        $google_config = new Google_Config();
        $google_config->setLoggerClass('Google_Logger_File');
        $google_config->setClassConfig('Google_IO_Curl', 'options',
            array(
                CURLOPT_VERBOSE => TRUE,
            )
        );

        $this->google = new Google_Client($google_config);
        $this->google->setAssertionCredentials($credentials);
        $this->google->setAccessType("offline");

        if ($this->google->getAuth()->isAccessTokenExpired()) {
            $this->google->getAuth()->refreshTokenWithAssertion();
        }
    }

    function insertPermission( $service, $fileId,  $type = 'anyone' , $role = 'reader') {

        $newPermission = new Google_Service_Drive_Permission();
        $newPermission->setType( $type );
        $newPermission->setRole( $role );
        try {
            $service->permissions->create( $fileId, $newPermission);
        } catch (Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }
        return NULL;
    }

    public function upload( $file = array() )
    {
        $this->file = $file;

        $service = new Google_Service_Drive($this->google);

        //Insert a file /*
        $file = new Google_Service_Drive_DriveFile();

        $file->setName( $this->file["file_name"] );
        $file->setDescription('Podcast file');
        $file->setMimeType('video/mpeg');

        $data = file_get_contents( $this->file["full_path"] );

        $createdFile = $service->files->create($file, array(
            'data' => $data,
            'mimeType' => 'video/mpeg',
            'uploadType' => 'multipart'
        ));

        $this->insertPermission( $service , $createdFile->id, 'anyone' , 'reader'  );

        return isset($createdFile->id) && $createdFile->id!="" ? $createdFile->id : "";
    }


}