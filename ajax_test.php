<?php
// リクエストヘッダーを取得
$headers = getallheaders();

// "X-Requested-With" ヘッダーが存在し、値が "XMLHttpRequest" であることを確認
if (isset($headers['X-Requested-With']) && $headers['X-Requested-With'] === 'XMLHttpRequest') {
  // AJAXリクエストの場合の処理
  // 例：メール送信処理
} else {
	var_dump($headers);
  // AJAXリクエスト以外の場合の処理
  // 例：何も実行しない
}
?>