CREATE TABLE IF NOT EXISTS `message` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(155) NOT NULL,
  `body` TEXT NOT NULL,
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '系统消息等等',
  `priority` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '优先级',
  `queue_id` VARCHAR(45) NOT NULL,
  `show_style` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 弹窗\n２　打开新页面',
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = '消息表' charset utf8;

CREATE TABLE IF NOT EXISTS `message_user_map` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` INT UNSIGNED NOT NULL,
  `checked` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 　未读\n１　已读',
  `checked_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '阅读时间',
  `user_id` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `message_id_idx` (`message_id` ASC),
  CONSTRAINT `message_id`
    FOREIGN KEY (`message_id`)
    REFERENCES `message` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB charset utf8;

CREATE TABLE IF NOT EXISTS `message_queue_subscription` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue_id` VARCHAR(45) NOT NULL,
  `user_id` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB charset utf8;