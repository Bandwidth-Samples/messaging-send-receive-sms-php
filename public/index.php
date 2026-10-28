<?php

use Bandwidth\Api\MediaApi;
use Bandwidth\Api\MessagesApi;
use Bandwidth\Configuration;
use Bandwidth\Model\MessageRequest;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$BW_ACCOUNT_ID = getenv('BW_ACCOUNT_ID');
$BW_CLIENT_ID = getenv('BW_CLIENT_ID');
$BW_CLIENT_SECRET = getenv('BW_CLIENT_SECRET');
$BW_MESSAGING_APPLICATION_ID = getenv('BW_MESSAGING_APPLICATION_ID');
$BW_NUMBER = getenv('BW_NUMBER');

$config = Configuration::getDefaultConfiguration()
    ->setClientId($BW_CLIENT_ID)
    ->setClientSecret($BW_CLIENT_SECRET);
$messagesApi = new MessagesApi(new Client(), $config);
$mediaApi = new MediaApi(new Client(), $config);

const STATUS_MESSAGES = [
    'message-sending' => 'message-sending type is only for MMS.',
    'message-delivered' => "Your message has been handed off to the Bandwidth's MMSC network, but has not been confirmed at the downstream carrier.",
    'message-failed' => 'For MMS and Group Messages, you will only receive this callback if you have enabled delivery receipts on MMS.',
];

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

function parseBody(Request $request): ?array
{
    $data = json_decode($request->getBody()->getContents(), true);
    return is_array($data) ? $data : null;
}

function parseCallbacks(Request $request): ?array
{
    $callbacks = parseBody($request);
    if ($callbacks === null || !array_is_list($callbacks)) {
        return null;
    }
    foreach ($callbacks as $callback) {
        if (!is_array($callback)) {
            return null;
        }
    }
    return $callbacks;
}

function saveMedia(MediaApi $mediaApi, string $accountId, string $mediaUrl): void
{
    $mediaId = substr($mediaUrl, strpos($mediaUrl, 'media/') + strlen('media/'));
    $mediaName = basename($mediaId);
    if (str_ends_with($mediaName, '.xml')) {
        return;
    }

    try {
        $media = $mediaApi->getMedia($accountId, $mediaId);
        if (!is_dir('media')) {
            mkdir('media');
        }
        copy($media->getPathname(), "media/{$mediaName}");
        error_log("Saved media to media/{$mediaName}");
    } catch (Throwable $e) {
        error_log("Failed to download {$mediaUrl}: " . $e->getMessage());
    }
}

$app->post('/messages', function (Request $request, Response $response) use ($messagesApi, $BW_ACCOUNT_ID, $BW_MESSAGING_APPLICATION_ID, $BW_NUMBER) {
    $data = parseBody($request);
    $media = $data['media'] ?? [];
    if (!is_string($data['to'] ?? null) || !is_string($data['text'] ?? null)
        || !is_array($media) || !array_is_list($media) || count(array_filter($media, 'is_string')) !== count($media)) {
        return $response->withStatus(400);
    }

    $messageRequest = new MessageRequest([
        'application_id' => $BW_MESSAGING_APPLICATION_ID,
        'to' => [$data['to']],
        'from' => $BW_NUMBER,
        'text' => $data['text'],
        'media' => $media ?: null,
    ]);

    try {
        $message = $messagesApi->createMessage($BW_ACCOUNT_ID, $messageRequest);
        error_log("Sent message {$message->getId()}");
    } catch (Throwable $e) {
        error_log('Failed to send message: ' . $e->getMessage());
        return $response->withStatus(500);
    }

    return $response->withStatus(204);
});

$app->post('/callbacks/outbound/messaging/status', function (Request $request, Response $response) {
    $callbacks = parseCallbacks($request);
    if ($callbacks === null) {
        return $response->withStatus(400);
    }
    foreach ($callbacks as $callback) {
        if (!isset(STATUS_MESSAGES[$callback['type'] ?? ''])) {
            return $response->withStatus(400);
        }
    }

    foreach ($callbacks as $callback) {
        error_log(STATUS_MESSAGES[$callback['type']]);
    }
    return $response->withStatus(204);
});

$app->post('/callbacks/inbound/messaging', function (Request $request, Response $response) use ($mediaApi, $BW_ACCOUNT_ID) {
    $callbacks = parseCallbacks($request);
    if ($callbacks === null) {
        return $response->withStatus(400);
    }
    foreach ($callbacks as $callback) {
        if (($callback['type'] ?? null) !== 'message-received') {
            return $response->withStatus(400);
        }
    }

    foreach ($callbacks as $callback) {
        $message = $callback['message'];
        error_log("{$callback['description']}\nTo: {$message['to'][0]}\nFrom: {$message['from']}\nText: {$message['text']}");
        foreach ($message['media'] ?? [] as $mediaUrl) {
            saveMedia($mediaApi, $BW_ACCOUNT_ID, $mediaUrl);
        }
    }
    return $response->withStatus(204);
});

$app->run();
