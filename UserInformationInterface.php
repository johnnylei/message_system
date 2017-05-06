<?php
/**
 * Created by PhpStorm.
 * User: johnny
 * Date: 17-3-4
 * Time: 上午11:05
 */

namespace johnnylei\message_system;


interface UserInformationInterface
{
    public function minusMessageNumber($user_id);
    public function addMessageNumber($user_id);
}