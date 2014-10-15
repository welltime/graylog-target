<?php
namespace welltime\graylog;

use Gelf\Message;
use Yii;

class GelfMessage extends Message
{
    const LOGGER_ID_FIELD = 'LoggerId';
    const USER_ID_FIELD = 'UserId';
    const CATEGORY_FIELD = 'category';

    public static function create()
    {
        return new self();
    }

    public function __construct()
    {
        parent::__construct();
        $this->version = '1.1';
    }

    public function setSource($source)
    {
        if ($source) {
            $this->host = $source;
        }
        return $this;
    }

    public function setCategory($category)
    {
        $this->setAdditional(self::CATEGORY_FIELD, $category);
        return $this;
    }

    public function setLoggerId($loggerId = null)
    {
        $this->setAdditional(
            self::LOGGER_ID_FIELD,
            $loggerId !== null ? $loggerId : $this->getLoggerId()
        );
        return $this;
    }

    public function setUserId($userId = null)
    {
        if ($userId === null && Yii::$app instanceof \yii\web\Application && Yii::$app->user->getIdentity(false)) {
            $userId = Yii::$app->user->getIdentity(false)->getId();
        }
        if ($userId) {
            $this->setAdditional(self::USER_ID_FIELD, $userId);
        }
        return $this;
    }

    public function toArray()
    {
        $message = array(
            'host'          => $this->getHost(),
            'short_message' => $this->getShortMessage(),
            'full_message'  => $this->getFullMessage(),
            'level'         => $this->getSyslogLevel(),
            'timestamp'     => $this->getTimestamp(),
            '_facility'      => $this->getFacility(),
            '_file'          => $this->getFile(),
        );

        // add additionals
        foreach ($this->additionals as $key => $value) {
            $message["_" . $key] = $value;
        }

        // return after filtering false, null and empty strings
        return array_filter($message, 'strlen');
    }

    private function getLoggerId()
    {
        static $loggerId;
        if (!$loggerId) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $loggerId = '';
            for ($i = 0; $i < 8; $i++) {
                $loggerId .= $characters[rand(0, strlen($characters) - 1)];
            }
        }
        return $loggerId;
    }

}