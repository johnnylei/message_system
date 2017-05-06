<?php
namespace johnnyLei\message_system;
use Yii;
use yii\base\Event;
use yii\base\Exception;

/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 17-1-4
 * Time: 上午11:47
 */
class MessageUserMap extends BaseRecord
{
    const Checked = 1;
    const UnChecked = 0;

    const BeforeDeleteMessage = 'BeforeDeleteMessage';
    const AfterDeleteMessage = 'AfterDeleteMessage';
    const BeforeInsertMessage = 'BeforeInsertMessage';
    const AfterInsertMessage = 'AfterInsertMessage';
    const BeforeCheckMessage = 'BeforeCheckMessage';
    const AfterCheckMessage = 'AfterCheckMessage';

    public function behaviors()
    {
        return [

        ];
    }

    public static function tableName()
    {
        return $this->messageUserMap;
    }

    public function attributes()
    {
        return [
            'id','message_id','checked','checked_time','user_id',
        ];
    }

    public function insertRecord($formData) {
        $this->loadData($formData, $this);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $event = new MessageEvent($formData);
            $this->trigger(self::BeforeInsertMessage, $event);
            if(!$event->isValidate) {
                throw new Exception('before insert message failed');
            }
            $this->insert();
            $this->trigger(self::AfterInsertMessage, $event);
            if(!$event->isValidate) {
                throw new Exception('after insert message failed');
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
        return true;
    }

    public function checkMessage($message_id, $user_id) {
        $record = self::find()->andWhere([
            'message_id'=>$message_id,
            'user_id'=>$user_id
        ])->one();
        if(empty($record)) {
            throw new Exception('without this message');
        }

        if( $record->checked == self::Checked) {
            return true;
        }

        $record->checked = self::Checked;
        $record->checked_time = time();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $event = new MessageEvent([
                'message_id'=>$message_id,
                'user_id'=>$user_id
            ]);
            $this->trigger(self::BeforeCheckMessage, $event);
            if(!$event->isValidate) {
                throw new Exception('invalid before check message');
            }
            $record->update();
            $this->trigger(self::AfterCheckMessage, $event);
            if(!$event->isValidate) {
                throw new Exception('invalid after check message');
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
        return true;
    }

    public function deleteMessage($message_id, $user_id = null) {
        $user_id = empty($user_id)?Yii::$app->getUser()->getId():$user_id;

        $record = self::find()->andWhere([
            'user_id'=>$user_id,
            'message_id'=>$message_id
        ])->one();
        if (empty($record)) {
            return true;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $event = new MessageEvent([
                'message_id'=>$message_id,
                'user_id'=>$user_id,
            ]);
            $this->trigger(self::BeforeDeleteMessage, $event);
            $record->delete();
            $this->trigger(self::AfterDeleteMessage, $event);
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
        return true;
    }
}