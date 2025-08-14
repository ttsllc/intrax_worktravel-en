<?php
//ini_set('display_errors', '1');
/*return $_FILES['uploadFile'];
exit();*/
// リクエストヘッダーを取得
$headers = getallheaders();
if (!isset($headers['X-Requested-With']) or $headers['X-Requested-With'] != 'XMLHttpRequest') {
    die;
}

//declare(strict_types = 1);
require_once __DIR__ . '/SMTPMailSenderSample.php';

use util\SMTPMailSenderSample;

//メールの件名
$mailSubject = "[Intrax/WORK AND TRAVEL IN THE USA] Inquiry Confirmation";

//$fromHeader = ['intrax@intraxjp.com'];
$fromHeader = ['intrax@intraxjp.com', 'Intrax/WORK AND TRAVEL IN THE USA'];

//管理者 宛先メールアドレス
$adminEmail = "intrax@intraxjp.com";
//$adminEmail = "eseventhjp@gmail.com";


// 保存先のディレクトリ
$save_dir = './uploads/';


// メール送信部品の呼び出し側コードのサンプルです。

// 必要な値は設定ファイル、DB、他の処理などから取得し、部品クラスを生成します。
$host = "sv2223.xserver.jp";
$port = 587;
$user = "intrax@intraxjp.com";
$pass = "Intrax246";
$mailSender = new SMTPMailSenderSample($host, $port, $user, $pass);





$file = null;
//添付ファイルがあるか？

if (isset($_FILES['uploadFile']) && !empty($_FILES['uploadFile']['name'])) {
    // ファイルがアップロードされている
    $file = $_FILES['uploadFile'];
    // ファイル名を取得
    $filename = $file['name'];

    // ファイル名をユニークな文字列に変換
    $unique_filename = uniqid() . '_' . $filename;


    // 拡張子を取得
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $unique_filename . '.' . $extension;

    // ファイルを保存
    move_uploaded_file($file['tmp_name'], $save_dir . $unique_filename);

    $filePath = $save_dir . $unique_filename;

    // ファイル添付メソッド
    //$mailSender->AddAttachment($filePath, $unique_filename);
    $mailSender->addAttachment($filePath, $unique_filename);
} else {
    // ファイルがアップロードされていない
    // ...
}



// フォームから送信されたファイルを取得











// 必要な値は設定ファイル、DB、他の処理などから取得し、メール１件の情報を組み立てます。

// エスケープ処理




// ヘッダーにはFROMアドレスと送信者を指定 以下どちらでも動きます

// 通知メールのタイトルを組み立て
//$mailSubject = "(作画とキャラデザが)";
// 通知メールの本文を組み立て

//$mailBody = 'dear ' . $first_name ;

if (isset($_POST['kind']) && $_POST['kind'] == 'footerForm') {

    $name = htmlspecialchars($_POST['txtName']);
    $email = htmlspecialchars($_POST['txtEmail']);
    $phone = htmlspecialchars($_POST['txtPhone']);
    $message = strip_tags($_POST['message']);



    $mailBody = <<<EOD
  Dear . {$name}

Thank you for your inquiry to Intrax/Ayusa AlumniNetwork.

This is an automated email to confirm that your message has been sent successfully.


  Thank you for your inquiry.

  Here is the information you have entered:

  * Name：{$name}
  * Email: {$email}
  * Phone: {$phone}
  * Message: 
  {$message}
  
  We will contact you soon.

EOD;
} else {
    //$programSelect = htmlspecialchars($_POST['programSelect']);
    //$experienceSelect = htmlspecialchars($_POST['experienceSelect']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $first_name = htmlspecialchars($_POST['first_name']);
    $email = htmlspecialchars($_POST['email']);
	$message = strip_tags($_POST['message']);
    //$phone = htmlspecialchars($_POST['phone']);
    //$address = strip_tags($_POST['address']);
	
	
//セールスフォースへ渡す用	
$keys = ["last_name", "first_name", "email","message"];
$values = [$last_name, $first_name, $email,$message];
$salesforce_data = array_map(function ($key, $value) {
  return [$key => $value];
}, $keys, $values);

$salesforce_data = array_merge(...$salesforce_data);
	

    $mailBody = <<<EOD
  Dear . {$first_name} {$last_name}

Thank you for your inquiry to Intrax/WORK AND TRAVEL IN THE USA.

This is an automated email to confirm that your message has been sent successfully.


  Thank you for your inquiry.

  Here is the information you have entered:


  * Name：{$first_name} {$last_name}
  * Email: {$email}

 
  
  We will contact you soon.

EOD;
}


// メール送信を行います。
$sendResult = false;
try {
    $sendResult = $mailSender->send($adminEmail, $fromHeader, $mailSubject, $mailBody);
    if (!$sendResult) {
        // TODO: ここに来れば呼び出し時の引数がおかしい場合です。呼び出し側の処理から早期リターンで抜けるなど。
        //入力者に自動送信

        echo "error!";
    } else {
        /*		$mailSender->clearAttachments();
		$mailSender->send($email, $fromHeader, $mailSubject, $mailBody);*/
    }
} catch (\Exception $e) {
    // TODO: エラー処理。ログ記録などを行ってください。
    var_dump($e->getMessage());

    // ホストが違う,ポート番号が不正: "SMTP Error: Could not connect to SMTP host. Failed to connect to server"
    // 認証に使うユーザー、パスワードが不正："SMTP Error: Could not authenticate."
    // Fromアドレスのメアドが正しくない： "Invalid address:  (From): (ここにメアドの値)"
    // Fromアドレスのメアドが、認証に使うユーザーの保有するものではない： "Sender address rejected: not owned by user (ここにユーザー名)"
    // メール本文が空： "Message body empty"が出る前に部品側でfalseで終了するようにしています
}
if ($sendResult) {
    // TODO: ここまで来ればメール送信が成功しています。成功時のみの処理など。
//ユーザー宛メールはオミット　有効にする場合下記2行を有効化すること
//    $mailSender = new SMTPMailSenderSample($host, $port, $user, $pass);
//    $sendResult = $mailSender->send($email, $fromHeader, $mailSubject, $mailBody);
	
	//成功したのでセールスフォースへ送信
	include 'send_to_salesforce_inc.php';
	send_contact_data_to_salesforce2019($salesforce_data);

    echo 'Your message has been sent successfully.';
    return true;
} else {
    // 送信失敗時は例外に落ちるはずですが、例外が発生せず送信失敗した場合はこちらに来ます。
    return false;
}
//}