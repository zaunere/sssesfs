<?php

// first step is to run:    composer require asyncaws
require 'vendor/autoload.php';

use AsyncAws\Ses\SesClient;
use AsyncAws\Ses\Input\SendEmailRequest;
use AsyncAws\Ses\ValueObject\Body;
use AsyncAws\Ses\ValueObject\Content;
use AsyncAws\Ses\ValueObject\Destination;
use AsyncAws\Ses\ValueObject\EmailContent;
use AsyncAws\Ses\ValueObject\Message;

// ensure the identities are correctly set in AWS SES
$to_address = 'someone@somehwere.com';
$from_address = 'your@place.com';

// set your region, key and secret
$SES_CREDS = ['region' => 'us-east-2',
         'accessKeyId' => '12345678912345678900',
     'accessKeySecret' => '1234567891234567890012345678912345678900'];

// define various subjects, commonly from a drop-down
define('SUBJECT_QUESTION',1);
define('SUBJECT_PRODUCT',2);
define('SUBJECT_BILLING',3);
define('SUBJECT_OTHER',4);

$SUBJECTS = [SUBJECT_QUESTION=>"I have a general question",
             SUBJECT_JPRODUCT=>"I have a question about a product",
             SUBJECT_BILLING=>"I have a billing question",
             SUBJECT_OTHER=>"Other"];



if( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' )
{
    header('HTTP/1.1 204 No Content');
    header('Access-Control-Allow-Origin',$_SERVER['HTTP_ORIGIN']??'*');
    header('Access-Control-Allow-Methods','GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers','Content-Type, Authorization, Set-Cookie');
    header('Access-Control-Allow-Credentials','true');
    header('Vary','Origin');
    exit;
}

$request = json_decode(file_get_contents('php://input'));

if( empty($request) )
    exit('not available');

if( !empty($request->checkbox) )
    $request->checkbox = 'YES';
else
    $request->checkbox = 'NO';

$body = <<< __EOH__

     From: {$request->name}  -  {$request->email}
 Checkbox: {$request->checkbox}
  Subject: {$SUBJECTS[$request->subject]}
  Message:

{$request->message}

__EOH__;


$ses = new SesClient($SES_CREDS);


$result = $ses->sendEmail(new SendEmailRequest([
    'FromEmailAddress' => $from_address,
    'Content' => new EmailContent([
        'Simple' => new Message([
            'Subject' => new Content(['Data' => 'NEW CONTACT FORM SUBMIT']),
            'Body' => new Body([
                'Text' => new Content(['Data' => $body]),
            ]),
        ]),
    ]),
    'Destination' => new Destination([
        'ToAddresses' => [$to_address]
    ]),
]));


if( $result )
{
    echo 'ok';
    error_log('sent contact message: '.$result->getMessageId());
}
else
    echo 'error';



