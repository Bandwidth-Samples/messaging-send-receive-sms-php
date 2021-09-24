# Messaging-Send-Receive-SMS-PHP
<a href="http://dev.bandwidth.com"><img src="https://s3.amazonaws.com/bwdemos/BW-VMP.png"/></a>
</div>

 # Table of Contents

<!-- TOC -->

- [Messaging-Send-Receive-SMS-PHP](#Messaging-Send-Receive-SMS-PHP)
- [Description](#description)
- [Bandwidth](#bandwidth)
- [Environmental Variables](#environmental-variables)
- [Callback URLs](#callback-urls)
    - [Ngrok](#ngrok)

<!-- /TOC -->

# Description
A sample PHP application that demonstrates a server capable of sending and recieving SMS messages.
Using a tool capable of making POST requests (Postman), send a POST request to the app's endpoint `/callbacks/outbound/messaging` with a json body like:
```json
{
  "to": "+19199994444",
  "text":"Hello World!"
}
```
The application will text the number `+19199994444` the words `Hello World!`.

The other two endpoints are used for handling inbound and outbound webhooks from Bandwidth. In order to use the correct endpoints, you must check the "Use multiple callback URLs" box on the application page in Dashboard. Then in Dashboard, set the INBOUND CALLBACK to `/callbacks/inbound/messaging` and the STATUS CALLBACK to `/callbacks/outbound/messaging/status`. The same can be accomplished via the Dashboard API by setting InboundCallbackUrl and OutboundCallbackUrl respectively.

Inbound callbacks are sent notifying you of a received message on a Bandwidth number, this app prints the phone numbers invloved, as well as the text received to the text file `inbound_message.txt`. Outbound callbacks are status updates for messages sent from a Bandwidth number and are logged in the text file `outbound_status.txt`.
You can start the php server for this project using the command `php -S localhost:8000 -t public`.
# Bandwidth

In order to use the Bandwidth API users need to set up the appropriate application at the [Bandwidth Dashboard](https://dashboard.bandwidth.com/) and create API credentials.

To create an application log into the [Bandwidth Dashboard](https://dashboard.bandwidth.com/) and navigate to the `Applications` tab.  Fill out the **New Application** form selecting the service (Messaging or Voice) that the application will be used for.  All Bandwidth services require publicly accessible Callback URLs, for more information on how to set one up see [Callback URLs](#callback-urls).

For more information about API credentials see [here](https://dev.bandwidth.com/guides/accountCredentials.html#top)

# Environmental Variables
The sample app uses the below environmental variables.
```sh
BW_ACCOUNT_ID                 # Your Bandwidth Account Id
BW_USERNAME                   # Your Bandwidth API Username
BW_PASSWORD                   # Your Bandwidth API Password
BW_NUMBER                     # The Bandwidth phone number involved with this application
BW_MESSAGING_APPLICATION_ID   # Your Messaging Application Id created in the dashboard
```

# Callback URLs

For a detailed introduction to Bandwidth Callbacks see https://dev.bandwidth.com/guides/callbacks/callbacks.html

Below are the callback paths:
* `/callbacks/outbound/messaging` For Sending Text Messages
* `/callbacks/outbound/messaging/status` For Outbound Status Callbacks
* `/callbacks/inbound/messaging` For Inbound Message Callbacks

## Ngrok

A simple way to set up a local callback URL for testing is to use the free tool [ngrok](https://ngrok.com/).  
After you have downloaded and installed `ngrok` run the following command to open a public tunnel to your port (`$LOCAL_PORT`)
```cmd
ngrok http $LOCAL_PORT
```
You can view your public URL at `http://127.0.0.1:{LOCAL_PORT}` after ngrok is running.  You can also view the status of the tunnel and requests/responses here.
