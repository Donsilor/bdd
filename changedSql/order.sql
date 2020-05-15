ALTER TABLE	`order`
	ADD COLUMN `refund_remark` VARCHAR(500) NULL DEFAULT NULL COMMENT '退款说明' COLLATE 'utf8_general_ci',
	ADD COLUMN `cancel_remark` VARCHAR(500) NULL DEFAULT NULL COMMENT '取消说明' COLLATE 'utf8_general_ci';