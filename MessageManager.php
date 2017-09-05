<?php
/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 17-1-4
 * Time: 下午12:01
 */

namespace johnnylei\message_system;


use yii\data\Pagination;
use johnnylei\message_system\Message;
use yii\base\Component;
use yii\base\Exception;
use Yii;

class MessageManager extends Component
{
    public $userInformation;

    const CreateTimeDesc = 1;
    const CreateTimeAsc = 2;

    const BeforeSendMessage = 'BeforeSendMessage';
    const AfterSendMessage = 'afterSendMessage';

    const SystemMessageQueue = 'SystemMessageQueue';  // 系统消息队列

    public function beforeSendMessage($event) {
        $this->trigger(self::BeforeSendMessage, $event);
        return $event->isValidate;
    }

    public function afterSendMessage($event) {
        $this->trigger(self::AfterSendMessage, $event);
        return $event->isValidate;
    }

    /**
     * @param $message ['title'=>'xxxx', 'body'=>'ssss', 'type'=>xxx, '']
     * @param $queue
     * @throws Exception
     */
    public function send($message, $queue, $enableAddMessageNumber = true) {
        if(!is_array($message)) {
            throw new Exception('$message should be array');
        }

        if (!isset($message['title']) || empty($message['title'])) {
            throw new Exception('$message title be set');
        }

        if (!isset($message['body']) || empty($message['body'])) {
            throw new Exception('$message body be set');
        }

        if (!is_array($queue)) {
            $queue = [$queue];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $event = new MessageEvent($message);
            if(!$this->beforeSendMessage($event)) {
                throw new Exception('before send message failed');
            }

            $formData = $message + [
                    'create_time'=>time(),
                ];
            // 生成消息体
            $messageRecord = new Message();
            if(!$messageRecord->insertRecord($formData)) {
                throw new Exception('insert message failed');
            }

            // 获取订阅这个消息队列的用户
            $subscription = new MessageQueueSubscription();
            $subscribers = [];
            foreach ($queue as $item) {
                $subscribers = array_merge($subscribers, $subscription->getSubscriber($item));
            }

            // 分发消息
            $messageUserMap = new MessageUserMap();
            if ($enableAddMessageNumber) {
                $messageUserMap->on(MessageUserMap::AfterInsertMessage, [$this, 'addMessageNumber']);
            }

            foreach ($subscribers as $subscriber) {
                if(!$messageUserMap->insertRecord([
                    'user_id'=>$subscriber,
                    'message_id'=>$messageRecord->id,
                ])) {
                    throw new Exception('send message failed');
                }
            }

            if(!$this->afterSendMessage($event)) {
                throw new Exception('after send message failed');
            }
        }catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
        return true;
    }

    public function myMessageList($order_by = null, $page_size = 10) {
        $query = Message::find()->select([
            't1.*',
            't2.checked',
            't2.checked_time',
            't2.id primary_id'
        ])->alias('t1')
            ->leftJoin(BaseRecord::MessageUserMap. ' t2', 't1.id = t2.message_id')
            ->andWhere(['t2.user_id'=>Yii::$app->getUser()->getId()]);

        if(!empty($this->pagination)) {
            $pagination = Yii::createObject($this->pagination);
            $pagination->totalCount = $query->count();
            $pagination->pageSize = $page_size;
        } else {
            $pagination = new Pagination([
                'totalCount'=>$query->count(),
                'pageSize'=>$page_size,
            ]);
        }

        $map = [
            self::CreateTimeAsc => [
                't1.create_time'=>SORT_ASC,
            ],
            self::CreateTimeDesc => [
                't1.create_time'=>SORT_DESC
            ]
        ];
        $_order_by = empty($order_by)?[
            't2.id'=>SORT_DESC
        ]:$map[$order_by];
        $list = $query->orderBy($_order_by)->offset($pagination->offset)->limit($pagination->limit)->asArray()->all();
        return [
            'list'=>$list,
            'pagination'=>$pagination,
        ];
    }

    public function checkMessage($message_id) {
        $messageUserMap = new MessageUserMap();
        $messageUserMap->on(MessageUserMap::AfterCheckMessage, [$this, 'minusMessageNumber']);
        return $messageUserMap->checkMessage($message_id, Yii::$app->getUser()->getId());
    }

    public function deleteMessage($message_id) {
        $messageUserMap = new MessageUserMap();
        $messageUserMap->on(MessageUserMap::AfterDeleteMessage, [$this, 'checkMessageValid']);
        $messageUserMap->on(MessageUserMap::AfterDeleteMessage, [$this, 'minusMessageNumber']);
        return $messageUserMap->deleteMessage($message_id, Yii::$app->getUser()->getId());
    }

    /**
     * 查看一下这个消息体不是否有效，无效的消息是指所有人都删除的消息
     * @param $e
     */
    public function checkMessageValid($e) {
        $message_id = $e->message['message_id'];
        $record = MessageUserMap::find()->andWhere(['message_id'=>$message_id])->one();
        if(!empty($record)) {
            return true;
        }

        $message = new Message();
        $message->deleteRecord($message_id);
    }

    public function addMessageNumber($e) {
        if(empty($this->userInformation)) {
            return ;
        }

        $userInformation = Yii::createObject($this->userInformation);
        if(!$userInformation instanceof UserInformationInterface) {
            $e->isValidate = false;
            throw new Exception('invalid user information');
        }

        if(!$userInformation->addMessageNumber($e->message['user_id'])) {
            $e->isValidate = false;
        }
    }

    public function minusMessageNumber($e) {
        if(empty($this->userInformation)) {
            return ;
        }

        $userInformation = Yii::createObject($this->userInformation);
        if(!$userInformation instanceof UserInformationInterface) {
            $e->isValidate = false;
            throw new Exception('invalid user information');
        }

        if(!$userInformation->minusMessageNumber($e->message['user_id'])) {
            $e->isValidate = false;
        }
    }

    public function receive() {
        $query = Message::find()->select([
            't1.*',
            't2.checked',
            't2.checked_time',
            't2.id primary_id'
        ])->alias('t1')
            ->leftJoin(BaseRecord::MessageUserMap . ' t2', 't1.id = t2.message_id')
            ->andWhere([
                'and',
                ['t2.user_id'=>Yii::$app->getUser()->getId()],
                ['t2.checked'=>MessageUserMap::UnChecked]
            ]);
        return $query->orderBy(['t1.id'=>SORT_DESC])->asArray()->one();
    }
}