# fsapi
File system api

Api is using Phalcon framework. It can be found here: https://phalconphp.com/en/download/windows/

Api is only reacting to three situations:
- GET /fs - this request will show all files.
- POST /fs - this request will upload file to server. To replace file you need to set 'replace' parameter at 1.
- GET /fs/file - this requst will show file as a HEX represantation. 'filename' parameter needs to be specified. To show file's meta you need to ser 'meta' parameter at 1.

Any other request will not be proceeded. Any response will be in JSON format: 'status' field will be set to 'OK' or 'Error' (GET /fs request doesn't have a 'status' field). Message of the error will be set in 'message'. 'OK' response going with 200 status code, 'Error' - with 400 or 500, depends on the problem (request or server problem).

All files will be saved in the directory specified in the FILES_FOLDER const. Maximum size of the file specified in the FILE_MAX_SIZE const.

File '.htaccess' specifies all requests to go to index.php. File 'composer.json' requires Phalcon framework.
