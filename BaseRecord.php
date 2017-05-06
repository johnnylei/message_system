<?php
namespace johnnyLei\message_system;
use yii\db\ActiveRecord;

/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 17-1-4
 * Time: 上午11:42
 */
class BaseRecord extends ActiveRecord
{
    public $messageTable = 'message';
    public $messageQueueSubscription =  'message_queue_subscription';
    public $messageUserMap = 'message_user_map';

    public function loadData($formData, $record) {
        $attributes = $this->attributes();
        foreach ($attributes as $attribute) {
            if (!isset($formData[$attribute])) {
                continue;
            }

            $record->$attribute = $formData[$attribute];
        }
    }
}