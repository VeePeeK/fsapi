<?php
/**
 * Created by PhpStorm.
 * User: veepe
 * Date: 30.03.2017
 * Time: 12:34
 */
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Http\Response;

//folder for storing files
const FILES_FOLDER = "/files/";
const FILE_MAX_SIZE = 10000000;

try {
    //$di = new FactoryDefault();
    //$di->set('url', function () {
   //    $url = new UrlProvider();
    //    $url->setBaseUri('/testApi/');
    //    return $url;
   // });

    $app = new Micro();

    //Get all files
    $app->get(
        "/fs",
        function () {

            $fileArray = array_diff(scandir(FILES_FOLDER), array('..', '.'));
            $data = [];
            foreach ($fileArray as $file)
            {
                $data[] = [
                    "name" => $file
                ];
            }

            return json_encode($data);
        }
    );

    //Creating and replacing file
    $app->post(
        "/fs",
        function() {
            $response = new Response();
            $filesCount = count($_FILES);
            if ($filesCount==1)
            {
                foreach ($_FILES as $file) {
                    //Only one file came throw POST
                    if ($file['error']==UPLOAD_ERR_OK) {
                        //if there is no error
                        if ($file['size']<FILE_MAX_SIZE) {
                            //if size is alright

                            if (!is_dir(FILES_FOLDER)) {
                                mkdir(FILES_FOLDER);
                            }

                            $filename = basename($file['name']);
                            if (preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9\.-]*))$/" , $filename)!=NULL) {

                                $uploadFilename = FILES_FOLDER . basename($file['name']);
                                if (!file_exists($uploadFilename)) {
                                    //if file not exist
                                    if (move_uploaded_file($file['tmp_name'], $uploadFilename)) {
                                        //file was successfully moved
                                        $response->setStatusCode(200);
                                        $response->setJsonContent(
                                            [
                                                "status" => "OK",
                                                "message" => "Created"
                                            ]
                                        );
                                    } else {
                                        //error during moving file
                                        $response->setStatusCode(500);
                                        $response->setJsonContent(
                                            [
                                                "status" => "Error",
                                                "message" => "Can't move file. "
                                            ]
                                        );
                                    }
                                } else {
                                    //file already exist
                                    if (move_uploaded_file($file['tmp_name'], $uploadFilename)) {
                                        //file was successfully moved
                                        $response->setStatusCode(200);
                                        $response->setJsonContent(
                                            [
                                                "status" => "OK",
                                                "message" => "Replaced"
                                            ]
                                        );
                                    } else {
                                        //error during moving file
                                        $response->setStatusCode(500);
                                        $response->setJsonContent(
                                            [
                                                "status" => "Error",
                                                "message" => "Can't move file. "
                                            ]
                                        );
                                    }
                                }
                            }
                            else
                            {
                                //filename is not safe
                                $response->setStatusCode(400);
                                $response->setJsonContent(
                                    [
                                        "status" => "Error",
                                        "message" => "Filename has incorrect symbols."
                                    ]
                                );
                            }
                        }
                        else
                        {
                            //file too big
                            $response->setStatusCode(400);
                            $response->setJsonContent(
                                [
                                    "status" => "Error",
                                    "message" => "File too big. File size: " . $file['size'] . ". Max size: " . FILE_MAX_SIZE
                                ]
                            );

                        }
                    }
                    else
                    {
                        //if file received with error
                        $response->setStatusCode(500);
                        $response->setJsonContent(
                            [
                                "status" => "Error",
                                "message" => "Error during receiving file. Error code: " . $file['error']
                            ]
                        );
                    }
                }
            }
            else {
                if ($filesCount == 0) {
                    //Zero files came
                    $response->setStatusCode(400);
                    $response->setJsonContent(
                        [
                            "status" => "Error",
                            "message" => "Where is no file to post"
                        ]
                    );
                } else {
                    //Two or more files came
                    $response->setStatusCode(400);
                    $response->setJsonContent(
                        [
                            "status" => "Error",
                            "message" => "Only one file uploading allowed"
                        ]
                    );
                }
            }
            return $response;
        }
    );
    //Show file
    $app->get(
        "/fs/file",
        function () {
            $response = new Response();
            $params = array_diff($_GET,array('_url' => "/fs/file"));
            $parameterCount = count($params);
            if ($parameterCount>0)
            {
                if (array_key_exists('filename',$params)) {
                    $filename = $params['filename'];
                    if (preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9\.-]*))$/" , $filename)!=NULL) {
                        $uploadFilename = FILES_FOLDER . $filename;
                        if (file_exists($uploadFilename)) {
                            //if filename correct and file exist
                            if (array_key_exists('meta', $params)) {
                                $meta = $params['meta'];
                            } else {
                                $meta = 0;
                            }

                            if ($meta == 0 || $meta == 1) {
                                //if meta parameter not set or set correctly

                                if ($meta == 0) {
                                    //output file content
                                    $file = fopen($uploadFilename, "rb");
                                    //$data = file_get_contents($uploadFilename);
                                    $data = unpack("H*",fread($file, filesize($uploadFilename)));
                                    $response->setStatusCode(200);
                                    $response->setJsonContent(
                                        [
                                            "status" => "OK",
                                            "file-content" => $data[1]
                                        ]
                                    );
                                    fclose($file);
                                    //echo json_encode($data);

                                } else {
                                    //output file meta
                                    $metaArray = stat($uploadFilename);

                                    $info = finfo_file(finfo_open(FILEINFO_NONE),$uploadFilename);
                                    $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE),$uploadFilename);
                                    $mimeEnc = finfo_file(finfo_open(FILEINFO_MIME_ENCODING),$uploadFilename);
                                   // echo  $info;
                                    $data = array();
                                    $data["filename"] = basename($uploadFilename);
                                    $data["file-size"] = filesize($uploadFilename);
                                    $data["last-change-time"] = date("d F Y H:i:s.", filemtime($uploadFilename));
                                    $data["create-time"] = date("d F Y H:i:s.", filectime($uploadFilename));
                                    $data["file-type"] = filetype($uploadFilename);
                                    $data["info"]=$info;
                                    $data["mime-type"] = $mimeType;
                                    $data["mime-encoding"] = $mimeEnc;
                                    $response->setStatusCode(200);
                                    $response->setJsonContent(
                                        [
                                            "status" => "OK",
                                            "meta" => $data
                                        ]
                                    );
                                }
                            } else {
                                //wrong meta parameter
                                $response->setStatusCode(400);
                                $response->setJsonContent(
                                    [
                                        "status" => "Error",
                                        "message" => "META parameter should be equal 1 or 0 (default 0)."
                                    ]
                                );
                            }
                        }
                        else
                        {
                            //file not exist
                            $response->setStatusCode(500);
                            $response->setJsonContent(
                                [
                                    "status" => "Error",
                                    "message" => "File not exist."
                                ]
                            );
                        }
                    }
                    else
                    {
                        //bad filename
                        $response->setStatusCode(400);
                        $response->setJsonContent(
                            [
                                "status" => "Error",
                                "message" => "Filename has incorrect symbols."
                            ]
                        );
                    }
                }
                else
                {
                    //filename not specified
                    $response->setStatusCode(400);
                    $response->setJsonContent(
                        [
                            "status" => "Error",
                            "message" => "You need to specify filename"
                        ]
                    );
                }
            }
            else
            {
                //if file not specified
                $response->setStatusCode(400);
                $response->setJsonContent(
                    [
                        "status" => "Error",
                        "message" => "You need to request some file"
                    ]
                );
            }
            return $response;
        }
    );
    $app->get(
        "/",
        function () {
            echo "main";
        }
    );
    $app->handle();
}
catch (Exception $e) {
    // Handling error
    $app->response->setStatusCode(400);
    $app->response->setJsonContent(
        [
            "status" => "Error",
            "message" => "There is no such api function"
        ]
    );
    $app->response->send();
}
