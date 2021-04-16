<?php

use BandwidthLib\Messaging\Models\BandwidthCallbackMessage;
use BandwidthLib\Messaging\Models\BandwidthMessage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$BW_ACCOUNT_ID = getenv("BW_ACCOUNT_ID");
$BW_USERNAME = getenv("BW_USERNAME");
$BW_PASSWORD = getenv("BW_PASSWORD");
$BW_MESSAGING_APPLICATION_ID = getenv("BW_MESSAGING_APPLICATION_ID");

$config = new BandwidthLib\Configuration(
    array(
        "messagingBasicAuthUserName" => $BW_USERNAME,
        "messagingBasicAuthPassword" => $BW_PASSWORD
    )
);
$client = new BandwidthLib\BandwidthClient($config);

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

$messagingClient = $client->getMessaging()->getClient();

$app->post('/outboundMessage', function (Request $request, Response $response) {
  global $messagingClient, $BW_ACCOUNT_ID, $BW_MESSAGING_APPLICATION_ID;

  $data = $request->getParsedBody();
  $body = new BandwidthLib\Messaging\Models\MessageRequest();
  $body->from = $data['from'];
  $body->to = array($data['to']);
  $body->applicationId = $BW_MESSAGING_APPLICATION_ID;
  $body->text = $data['message'];

  try {
      $msgResponse = $messagingClient->createMessage($BW_ACCOUNT_ID, $body);
      $response->getBody()->write('{"Success":"Message sent successfully"}');
      return $response->withStatus(201)
        ->withHeader('Content-Type', 'application/json');
  } catch (Exception $e) {
      $response->getBody()->write('{"Error":"Message Failed"}');
      return $response->withStatus(400)
        ->withHeader('Content-Type', 'application/json');
  }
});

$app->post('/messageCallback', function (Request $request, Response $response) {
  $data = $request->getBody()->getContents();
  $messagingCallbacks = \BandwidthLib\APIHelper::deserialize($data, BandwidthCallbackMessage::class, true );
  $messageCallback = array_pop($messagingCallbacks);

  // Grab callback message variables
  $type = $messageCallback->type;
  $time = $messageCallback->time;
  $description = $messageCallback->description;
  $to = $messageCallback->to;
  $message = $messageCallback->message;    // an object
  $messageId = $messageCallback->message->id;
  $messageTime = $messageCallback->message->time;
  $messageTo = $messageCallback->message->to;    // an array
  $messageFrom = $messageCallback->message->from;
  $messageText = $messageCallback->message->text;
  $messageApplicationId = $messageCallback->message->applicationId;
  $messageOwner = $messageCallback->message->owner;
  $messageDirection = $messageCallback->message->direction;
  $messageSegmentCount = $messageCallback->message->segmentCount;

  if ($messageDirection == "in"){
    // write callback to file
    $myfile = fopen("inbound_message.txt", "w") or die("Unable to open file!");
    $txt = "Type: ".$type."\nDescription: ".$description."\nText: ".$messageText;
    fwrite($myfile, $txt);
    fclose($myfile);
  } else {
    $myfile = fopen("outbound_message.txt", "w") or die("Unable to open file!");
    $txt = "ID:".$messageId."\nType: ".$type."\nDescription: ".$description;
    fwrite($myfile, $txt);
    fclose($myfile);
  }

  return $response->withStatus(200);
});

$app->run();
