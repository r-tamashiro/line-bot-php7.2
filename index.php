<?php
DEFINE("ACCESS_TOKEN", getenv('ACCESS_TOKEN'));
DEFINE("SECRET_TOKEN", getenv('SECRET_TOKEN'));

use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\Constant\HTTPHeader;

//LINESDKの読み込み
require_once(__DIR__."/vendor/autoload.php");

//LINEから送られてきたらtrueになる
if(isset($_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE])){

    //LINEBOTにPOSTで送られてきた生データの取得
    $InputData = file_get_contents("php://input");
    $json = json_decode($InputData);

    //LINEBOTSDKの設定
    $HttpClient = new CurlHTTPClient(ACCESS_TOKEN);
    $Bot = new LINEBot($HttpClient, ['channelSecret' => SECRET_TOKEN]);
    $Signature = $_SERVER["HTTP_".HTTPHeader::LINE_SIGNATURE]; 
    $Events = $Bot->parseEventRequest($InputData, $Signature);

    $event = $json->events[0];
    $response = $Bot->getProfile($event->source->userId);
    if ($response->isSucceeded()) {
        error_log("profile");
        $profile = $response->getJSONDecodedBody();
        error_log(print_r($profile, true));
    }

    //大量にメッセージが送られると複数分のデータが同時に送られてくるため、foreachをしている。
    foreach($Events as $event){
        if (!($event instanceof MessageEvent)) {
            error_log('Non message event has come');
            continue;
        }
        if (!($event instanceof TextMessage)) {
            error_log('Non text message has come');
            continue;
        }
        $SendMessage = new MultiMessageBuilder();
        $TextMessageBuilder = new TextMessageBuilder($event->getText());
        $SendMessage->add($TextMessageBuilder);
        $Bot->replyMessage($event->getReplyToken(), $SendMessage);
    }
}
