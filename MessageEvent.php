<?php
/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 17-1-4
 * Time: 下午12:04
 */

namespace johnnylei\message_system;


use yii\base\Event;

class MessageEvent extends Event
{
    public $message;
    public $isValidate = true;

    public function __construct($message, array $config = [])
    {
        $this->message = $message;
        parent::__construct($config);
    }
}