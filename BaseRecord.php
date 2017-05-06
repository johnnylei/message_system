<?php
namespace johnnylei\message_system;
use yii\db\ActiveRecord;

/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 17-1-4
 * Time: 上午11:42
 */
class BaseRecord extends ActiveRecord
{
    const MessageTable = 'message';
    const MessageQueueSubscription =  'message_queue_subscription';
    const MessageUserMap = 'message_user_map';

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