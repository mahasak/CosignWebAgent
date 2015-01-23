
CoSign Signature Web Agent Quick Start php
====================

This example web app, written in PHP, demonstrates a simple integration with the CoSign Signature Web Agent service. When the user initiates the web app, it:
1. Programmatically creates a PDF file using the [TCPDF](http://www.tcpdf.org/) PHP library.
2. Submits the PDF file as a signing request to the CoSign Signature Web Agent via the *Sign/UploadFileToSign* service point.
3. Redirects the browser to the CoSign Signature Web Agent. The user then reviews and signs the PDF file. The Web Agent then redirects back to our example web app.
4. The web app then retrieves the signed file and other metadata about the signature from the Web Agent. The web app provides a final status page to the user and enables the signed file to be downloaded.

Installation
---------------------
The example web app can be downloaded or forked from the Github CoSign repository. It can be installed on a Linux or IIS web server. It is configured to use the developer's CoSign Signature Web Agent server or can be configured to use your own locally installed instance of the CoSign Signature Web Agent.

Demonstration
---------------------
The example web app demonstration is available at XXXX

Code Discussion
====================

index.htm
---------------------
The entry point for the web app is index.htm in the example's root directory. It is a simple Twitter Bootstrap html file. The user presses the "Sign!" button which starts a standard GET operation on url sign_start.php

sign_start.php
---------------------
sign_start is compact, using functions from other files:

```php
$xml =  make_file_upload_request(make_pdf(), 'pdf', 'Hello world.pdf', '123');

try {
  $response = Unirest::post(UPLOAD_DOC, array("Content-Type" => "application/x-www-form-urlencoded"),
    "inputXML=" . urlencode($xml));
  $redirect = handle_file_upload_response($response);
  send_redirect($redirect);
} catch (Exception $e) {
    echo '<h2>Problem: ',  $e->getMessage(), '</h2>';
}
```

Line 1: programmatically create a pdf file (*function make_pdf,* defined in file lib/pdf.php). Send the pdf file as a parameter to *make_file_upload_request,* defined in file lib/xml.php. Function make_pdf uses the [TCPDF library](http://www.tcpdf.org/) to create a simple PDF file for signing. Using various pdf libraries, the function could also fill in a previously created PDF form. [More information](http://goo.gl/D8XUh)

Function make_file_upload_request uses the php [XMLWriter](http://php.net/manual/en/book.xmlwriter.php) library to create the XML SignRequest document as defined in the CoSign Signature Web Agent manual.

Function make_file_upload_request creates an XML document that includes a base64 encoded version of the PDF file to be signed.

On line 3 we *try* a block of code which first uses the [Unirest](http://unirest.io/) library to POST the XML request to the Web Agent's sign request entry point. If an exception is raised, the *catch* block will display it to the user. In a completed app, an error reporting template or page should be used.

Line 6: *Function handle_file_upload_response,* defined in lib/xml.php parses the response from the Web Agent service. It uses the standard PHP [SimpleXMLElement library](http://php.net/manual/en/book.simplexml.php) to parse the XML document returned by the Signing Agent:

```php
	$xml = new SimpleXMLElement($payload);
    $return_code = (string)$xml->Error->returnCode;
	$sessionID = (string)$xml->Session->sessionId;
```

The return_code and sessionID are pulled from the XML. The browser is then redirected to the same sign request entry point previously used, but as a GET operation with the sessionID included as a query parameter. 

At this point, the user and their browser is directly interacting with the Web Agent service.

After the file is signed
====================

After the file is signed, or after the user cancels their operation, the Web Agent redirects the browser to our app's sign_finish_html file. This is set in the app's initial XML file on line 37 of xml.php.

sign_finish_html.php
---------------------

This url / file is invoked by the Web Agent after the document has been signed or the operation cancelled. Query parameters supply the sessionID, the docID and a return_code. If the document was signed, then the signed document and signature status information are retrieved:

```php
  $info = fetch_signed_file($sessionID);
```

fetch_signed_file is defined in file lib/sign_finish.php. It uses the Unirest library to retrieve the XML results from the Web Agent and SimpleXMLElement to parse the response, which includes the signed file.

The signed document is stored on the server's local file system. The *$info* associative array returned by fetch_signed_file includes status information and the url for downloading the signed file.

Javascript at the end of the sign_finish_html.php file determines which html should be visible to the user: either success information about the signed document, or cancellation notice and the chance to sign again, or error notice of a problem.

