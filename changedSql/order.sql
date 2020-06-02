ALTER TABLE	`order`
	ADD COLUMN `refund_remark` VARCHAR(500) NULL DEFAULT NULL COMMENT '退款说明' COLLATE 'utf8_general_ci',
	ADD COLUMN `cancel_remark` VARCHAR(500) NULL DEFAULT NULL COMMENT '取消说明' COLLATE 'utf8_general_ci';

ALTER TABLE	`order`
	ADD COLUMN `cancel_status` TINYINT(4) UNSIGNED NULL DEFAULT '0' COMMENT '关闭状态:0是未关闭,1是已关闭' AFTER `refund_status`;

ALTER TABLE `order`
	ADD COLUMN `audit_status`  TINYINT(4) UNSIGNED NULL DEFAULT '0' COMMENT '审核状态:0是未审核,1已审核';