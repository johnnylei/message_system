<?php
/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 17-1-4
 * Time: 上午11:54
 */

namespace johnnyLei\message_system;

use Yii;
use yii\base\Exception;

class MessageQueueSubscription extends BaseRecord
{
    public static function tableName()
    {
        return self::MessageQueueSubscription;
    }

    public function attributes()
    {
        return [
            'id','queue_id','user_id',
        ];
    }

    public function subscription($queue_id, $user_id = null) {
        if(empty($queue_id)) {
            throw  new Exception('queue could not be null');
        }
        return $this->insertRecord([
            'user_id'=>empty($user_id)?Yii::$app->getUser()->getId():$user_id,
            'queue_id'=>$queue_id,
        ]);
    }

    public function insertRecord($formData) {
        $this->loadData($formData, $this);
        $this->setOldAttribute('queue_id', null);
        $this->setOldAttribute('user_id', null);
        return $this->insert();
    }

    public function unSubscription($queue_id, $user_id = null) {
        if(empty($queue_id)) {
            throw  new Exception('queue could not be null');
        }

        return $this->deleteRecord([
            'queue_id'=>$queue_id,
            'user_id'=>empty($user_id)?Yii::$app->getUser()->getId():$user_id,
        ]);
    }

    public function deleteRecord($formData) {
        $record = self::find()->andWhere([
            'user_id'=>$formData['user_id'],
            'queue_id'=>$formData['queue_id'],
        ])->one();
        if(empty($record)) {
            return true;
        }

        return $record->delete();
    }

    public function getSubscriber($queue_id) {
        $users = self::find()->select(['user_id'])->andWhere(['queue_id'=>$queue_id])->asArray()->all();
        $_users = null;
        foreach ($users as $user) {
            $_users[] = $user['user_id'];
        }

        return $_users;
    }

    /**
     * 监听多个队列
     * @param array $formData
     * @return bool
     * @throws Exception
     */
    public function subscribeMultiQueue($formData = [
        'user_id'=>null,
        'queues'=>null,
    ]) {
        if (!isset($formData['queues']) || empty($formData['queues'])) {
            throw new Exception('subscribeMultiQueue failed, queues empty');
        }

        $user_id = empty($formData['user_id'])?Yii::$app->getUser()->getId():$formData['user_id'];
        $queues = $formData['queues'];
        foreach ($queues as $queue) {
            if(!$this->subscription($queue, $user_id)) {
                throw new Exception('subscribe ' . $queue . ' failed');
            }
        }

        return true;
    }
}