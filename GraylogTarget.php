<?php
namespace welltime\graylog;

use Gelf;
use Psr\Log\LogLevel;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\log\Target;
use yii\log\Logger;

class GraylogTarget extends Target
{
    public $short_message_length = 150;

    public $host = '127.0.0.1';

    public $port = 12201;

    public $source;

    public $exportInterval = 1;

    public $logVars = [];

    public $addCategory = true;

    public $addUserId = true;

    public $addLoggerId = true;

    public $addFile = true;

    private $_levels = [
        Logger::LEVEL_TRACE => LogLevel::DEBUG,
        Logger::LEVEL_PROFILE_BEGIN => LogLevel::DEBUG,
        Logger::LEVEL_PROFILE_END => LogLevel::DEBUG,
        Logger::LEVEL_INFO => LogLevel::INFO,
        Logger::LEVEL_WARNING => LogLevel::WARNING,
        Logger::LEVEL_ERROR => LogLevel::ERROR,
    ];

    public function export()
    {
        $publisher = $this->spawnPublisher();

        foreach ($this->messages as $message) {
            $publisher->publish(
                $this->spawnGelfMessage($message)
            );
        }
    }

    private function spawnPublisher()
    {
        return new Gelf\Publisher(
            new Gelf\Transport\UdpTransport(
                $this->host,
                $this->port,
                Gelf\Transport\UdpTransport::CHUNK_SIZE_LAN
            )
        );
    }

    private function spawnGelfMessage(array $yiiMessage)
    {
        if ($yiiMessage[0] instanceof GelfMessage) {
            $message = $yiiMessage[0];
        } else {

            list($shortMessage, $fullMessage) = $this->parseYiiMessageExt($yiiMessage);

            $message =
                GelfMessage::create()
                    ->setShortMessage($shortMessage)
                    ->setFullMessage($fullMessage);
        }

        list($timeStamp, $level, $category, $file) = $this->parseYiiMessageBase($yiiMessage);

        $message
            ->setSource($this->source)
            ->setTimestamp($timeStamp)
            ->setLevel($level)
        ;

        if ($this->addCategory) {
            $message->setCategory($category);
        }
        if ($this->addLoggerId) {
            $message->setLoggerId();
        }
        if ($this->addUserId) {
            $message->setUserId();
        }
        if ($this->addFile) {
            $message->setFile($file);
        }

        return $message;
    }

    private function parseYiiMessageBase(array $yiiMessage)
    {
        $timeStamp = $yiiMessage[3];
        $level = ArrayHelper::getValue($this->_levels, $yiiMessage[1], LogLevel::INFO);
        $category = $yiiMessage[2];

        if (isset($yiiMessage[4][0]['file'])) {
            $file = $yiiMessage[4][0]['file'] . ($yiiMessage[4][0]['line'] ? ' [' . $yiiMessage[4][0]['line'] . ']' : '');
        } else {
            $file = null;
        }



        return [$timeStamp, $level, $category, $file];
    }

    private function parseYiiMessageExt(array $yiiMessage)
    {
        $fullMessage = is_string($yiiMessage[0]) ? $yiiMessage[0] : VarDumper::dumpAsString($yiiMessage[0]);
        if (mb_strlen($fullMessage) > $this->short_message_length) {
            $shortMessage = mb_substr($fullMessage, 0, $this->short_message_length);
        } else {
            $shortMessage = $fullMessage;
            $fullMessage = null;
        }
        return [$shortMessage, $fullMessage];
    }

}