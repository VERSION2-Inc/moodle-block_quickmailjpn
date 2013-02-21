<?php
/**
 *  定数
 */

// カスタムフィールド名
interface QuickMailJPN_FieldName
{
	const EMAIL  = 'quickmailJPNmobileemail';
	const STATUS = 'quickmailJPNmobilestatus';
}

// 携帯メール登録状況
interface QuickMailJPN_State
{
	const NOT_SET   = 'notyet';
	const CHECKING  = 'checking';
	const CONFIRMED = 'confirmed';
}

?>