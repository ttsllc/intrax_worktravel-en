<?php require_once('confirm.php');//confim.phpへのパス。無いと動かない。?>
<?php print('<?xml version="1.0" encoding="utf-8"?>'."\n");?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>[zeromail]フォームメール確認</title>
<meta http-equiv="Content-Language" content="ja" />
<meta http-equiv="pragma" content="no-cache" />
<meta name="robots" content="noindex,nofollow" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="zeromail.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="container">
	<h1>ZeroMail ContactForm</h1>
	<h2>フォームメール確認</h2>
	<form action="zeromail.php" method="post" class="zeromail">
		<p class="message"><?php Message();//メッセージ?></p>
		<fieldset>
			<legend>Contact details</legend>
			<table summary="送信内容確認" id="confirm">
			<?php ConfDisp();//確認表示。行しか出ないのでtableタグ内に書く?>
			</table>
			<div class="button">
			<?php Button();//ボタン表示。form内に置くこと。 ?>
			</div>
		</fieldset>
	</form>
</div>
</body>
</html>
