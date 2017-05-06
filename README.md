# message_system
这是一个基于yii2的消息系统

## install
```
composer require --prefer-dist johnnylei/message-system
```

## usage
### imgrate
打开imgrate/文件夹，建好相关的表
### 配置文件
```
'messageManager'=>[
    'class'=>'johnnylei\message_system\MessageManager',
    // userInformation 是一个用户表的activeRecord,主要作用当你发送消息的时候，将用户表里面的消息总数＋１，当你阅读完消息的时候，将用户表里面的消息数－１，
    // 通过实现UserInformationInterface,里面的两个方法
    'userInformation'=>[
        'class'=>'xxx',
    ]
]
```
### 监听一个队列
```
// 原理就是往message_queue_subscription表里面插入一条数据，表示某个用户监听了某个队列
$subscription = new MessageQueueSubscription();
$subscription->subscription($userId, $queueId);
```
### 发送消息
```
// 发送消息就是往某个消息队列里面写数据，谁监听了这个消息队列，谁就会收到一条数据
// 原理，１.生产一条消息记录，２.在message_queue_subscription表里面查看谁监听了这个队列 3.根据获取到的user_id,将第一步生成消息id,和user_id写入到message_user_map表里面，这样就表示该用户收到这条消息了
Yii::$app->messageManager->send([
                'title'=> 'this is title',
                'body'=> 'this is body',
                // 后面的参数不重要，但是可以配置
                // type就是你自定义一个消息type
                'type'=>'this is message type',
                // 这个参数也是你自定的显示风格，我们用到的弹窗和打开新的页面，具体怎么用，自己写规则
                'show_style'=>'this is show style',
            ], $queue_id);
```
### 接受消息
```
// 获取所有消息的列表
$messageList = Yii::$app->messageManager->myMessageList();

// 获取一个消息
$messageList = Yii::$app->messageManager->receive();
```
