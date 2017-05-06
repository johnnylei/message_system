<?php
namespace johnnylei\message_system;

/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 17-1-4
 * Time: 上午11:30
 */
class Message extends BaseRecord
{
    public static function tableName()
    {
        return self::MessageTable;
    }

    public function attributes()
    {
        return [
            'id', 'title','body','create_time','type','priority','queue_id','show_style'
        ];
    }

    public function rules()
    {
        return [];
    }

    public function insertRecord($formData) {
        $this->loadData($formData, $this);
        return $this->insert();
    }

    public function deleteRecord($id) {
        return self::deleteAll(['id'=>$id]);
    }
}