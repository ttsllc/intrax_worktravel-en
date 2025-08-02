<?php print('<?xml version="1.0" encoding="utf-8"?>'."\n");?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Language" content="ja" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="pragma" content="no-cache" />
<meta name="robots" content="noindex,nofollow" />
<title>[zeromail]お問い合わせフォーム:エラー</title>
<link href="zeromail.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="container">
	<h1>ZeroMail ContactForm</h1>
	<h2>エラーが発生しました</h2>
	<div id="error" class="zeromail">
		<p class="message"><?php Message(); ?></p>
		<div class="button"><?php Button();//ボタン表示 ?></div>
	</div>
</div>
</body>
</html>