<?php
/*------------------------------------------------------------
  ZeroMail core zeromail.php

  @version   1.4.7
  @copyright Copyright (c) Tenderfeel
  @link      http://zeromail.webtecnote.com/
  @license   GPL v3 License
--------------------------------------------------------------*/

mb_internal_encoding('UTF-8');
mb_http_input("auto");
mb_http_output('UTF-8');
ini_set("default_charset", 'UTF-8');
define('PHPVER',phpversion());
define( "ZEROMAIL_DIR", dirname( $_SERVER["SCRIPT_FILENAME"] ). DIRECTORY_SEPARATOR);
if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
	date_default_timezone_set('Asia/Tokyo');
}

session_start();

if(isset($_POST['id']) xor isset($_SESSION['id'])){
	if($_POST['id'] && preg_match("/^[\d\w\-_]+$/i", $_POST['id'])){
		$_SESSION['id']=$_POST['id'];
	}
	
	if(is_readable('init'.$_SESSION['id'].'.php'))
		require(ZEROMAIL_DIR.'init'.$_SESSION['id'].'.php');
	
}else{
	if(is_readable('init.php'))
		require(ZEROMAIL_DIR.'init.php');
}

mb_language(MAILCODE);

//Ajax使用状況（noscript=falseで使用とみなす）
if((isset($_POST['noscript']) && $_POST["noscript"]=="true") 
	|| (isset($_SESSION['noscript']) && $_SESSION["noscript"]=="true")
		||!array_key_exists("noscript",$_POST)){
	
	define("NOSCRIPT",true);
	
}elseif($_POST["noscript"]=="false"){
	define("NOSCRIPT",false);
}

if(defined("ZM_ROOTOUT") && ZM_ROOTOUT === true){
	define("ZM_LOGFILE",LOGFILEPASS);
}elseif(defined("ZM_ROOTOUT") && ZM_ROOTOUT === false){
	define("ZM_LOGFILE",ZEROMAIL_DIR.ZM_ADMINDIR.LOGFILEPASS);
}

if(isset($_POST)&&count($_POST)>0){

	if(isset($_POST["mode"]) && $_POST["mode"]==="Send"){
		zeromailAjax_send($_SESSION);
	}else{
		
		session_unset();
		
		//spam killer
		ip_check_destroy();
		ref_check_destroy();
	
		$formitem = value_check($_POST);
		
		if(NOSCRIPT===true){
			$_SESSION = array_merge($_SESSION,$formitem);
			header('Location: '.CHECKPAGE);
			
		}elseif( (NOSCRIPT===false && $formitem['Err'] > 1) || (NOSCRIPT===false && $formitem["confirm"]=="true")){
			AjaxConfDisp($formitem);
		}else{
			zeromailAjax_send($formitem);
		}
	}

}else{
	die('不正なアクセスです。');
}


/************************************************************
 *
 * 送信された値のチェック
 *
 ************************************************************/
function value_check($POST){
	
	global $formURL,$blocktxt,$blockIP,$alink;
	
	if(FILEPOOL !== true) RemoveFiles(UPLOADPASS);//file delete
	
	$formitem = array();
	$error = 1;
	
	//hiddenの必須指定(checkbox,files&etc)
	$SENDS = array_merge($POST,$_FILES);
	
	if(isset($_POST["require"])){
		$reqnames = explode(",",$POST["require"]);
		foreach($reqnames as $reqname){
			if(!array_key_exists($reqname, $SENDS)||$SENDS[$reqname]["error"]==4){
				 $_SESSION[$reqname]=$formitem[$reqname]=convert_encode('<strong class="error">この項目は必須入力です。</strong>');
				 $error++;
			}
		}
	}
	
	//hiddenのrep[name]
	if(isset($POST["rep"]) && is_array($POST["rep"])){
		foreach($POST["rep"] as $name => $regs){
			$formitem["reps"][$name]=$regs;
		}
		unset($POST["rep"]);
	}
	
	foreach($POST as $key => $value){
		$name=explode("_", $key);
		
		if(is_array($value)){//値が配列
			$value=implode("\n", $value);
		}
		
		//必須
		if( (mb_ereg("_req",$key) && mb_strlen(preFilter($value)) < 1) 
				|| ($key == "name" && mb_strlen($value) < 1 && NAMECHECK == true)
				|| (($key == "email") && (mb_strlen($value) < 1) && (REPLY == true || $_POST["reply"] =="true")) 
					|| ( ($key == "email") && (mb_strlen($value) < 1) && EMAILCHECK === true )
				){
				 $formitem[$name[0]]=convert_encode('<strong class="error">この項目は必須入力です。</strong>');
				 $error++;
				 continue;
				 
		}elseif(is_empty_skip($value)){//check empty value skip
			
				continue;
			
		}else{
			$formitem[$name[0]]=preFilter($value);
		}

			
		for($i=0; $i<count($name); $i++){
		
			//全角変換
			if( $name[$i] == "jpz" && mb_strlen($value) > 0){
					$value=mb_convert_kana($value, "RNASKH", TEXTCODE); 
					$formitem[$name[0]]=$value;
			//全カタカナ
			}elseif($name[$i] == "jpk" && mb_strlen($value) > 0){
				$value=mb_convert_kana($value, "RNASKH", TEXTCODE); 
				if (preg_match("/^[ァ-ヾ 　]+$/u", mb_convert_encoding($value,'UTF-8',TEXTCODE))) {
					$formitem[$name[0]]=$value;
				}else{
					$formitem[$name[0]]=convert_encode('<strong class="error">カタカナで入力してください。</strong>');
					$error++;
				}
			//全ひらがな
			}elseif($name[$i] == "jph" && mb_strlen($value) > 0){
				$value=mb_convert_kana($value, "RNASKH", TEXTCODE); 
				if (preg_match("/^[ぁ-ゞ 　]+$/u", mb_convert_encoding($value,'UTF-8',TEXTCODE))) {
					$formitem[$name[0]]=$value;
				}else{
					$formitem[$name[0]]=convert_encode('<strong class="error">ひらがなで入力してください。</strong>');
					$error++;
				}
			
			//カタカナorひらがな
			}elseif($name[$i] == "jpa" && mb_strlen($value) > 0){
				$value=mb_convert_kana($value, "RNASKH", TEXTCODE); 
				if (preg_match("/^[ぁ-ゞァ-ヾ 　]+$/u", mb_convert_encoding($value,'UTF-8',TEXTCODE))) {
					$formitem[$name[0]]=$value;
				}else{
					$formitem[$name[0]]=convert_encode('<strong class="error">「ひらがな」または「カタカナ」で入力してください。</strong>');
					$error++;
				}
			//数字
			}elseif($name[$i] == "num" && mb_strlen($value) > 0){
				$value = mb_convert_kana($value, "n", TEXTCODE);
				if (preg_match("/^[0-9\s]+$/", $value)) {
					$formitem[$name[0]]=$value;
				}else{
					$formitem[$name[0]]=convert_encode('<strong class="error">半角の整数以外は入力出来ません。</strong>');
					$error++;
				}

			//英字
			}elseif( $name[$i] == "eng" && mb_strlen($value) > 0){
				$value =  mb_convert_kana($value,"as", TEXTCODE);
				if (!preg_match("/^[a-zA-Z0-9\s]+$/", $value)) {
					$formitem[$name[0]]=convert_encode('<strong class="error">半角英数以外は使用できません。</strong>');
					$error++;
					
				}else{
					$formitem[$name[0]]=$value;
				}
		
			//名前
			}elseif( stristr($name[0],"name")!==false ){
				if($name[0] == "name" && mb_strlen($value) < 1 && NAMECHECK == true){
					$formitem[$name[0]]=convert_encode('<strong class="error">この項目は必須入力です。</strong>');
					$error++;
				}elseif( (ALPHANAME === false) && (MAILCODE === 'ja') && preg_match("/^[\x01-\x7e]+$/",$value)){
					$formitem[$name[0]]=convert_encode('<strong class="error">全て半角英数の文字列は使用できません。</strong>');
					$error++;
				}else{
					$formitem[$name[0]]=htmlentities($value,ENT_QUOTES, TEXTCODE);
				}
			
			//メアド
			}elseif( stristr($name[0],"email")!==false){
				if((mb_strlen($value) < 1 && (REPLY == true || $_POST["reply"] =="true")) || (mb_strlen($value) < 1 &&EMAILCHECK === true )){
					$formitem[$name[0]]=convert_encode('<strong class="error">メールアドレスを入力してください。</strong>');
					$error++;
				}elseif(mb_strlen($value) > 0 && !preg_match("/^([a-z0-9_]|\-|\.|\+)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/i",$value)){
					$formitem[$name[0]]=convert_encode('<strong class="error">メールアドレスの書式に誤りがあります。</strong>');
					$error++;
				}
			
			//URL
			}elseif( stristr($name[0],"url")!==false && mb_strlen($value) > 0){
				if(!preg_match('/^(https|http)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $value)) {
				$formitem[$name[0]]=convert_encode("<strong class=\"error\">URLの書式に誤りがあります。</strong>");
				$error++;
				
				}
			
			//電話
			}elseif( stristr($name[0],"tel")!==false && mb_strlen($value) > 0){
				$value = mb_convert_kana($value, "n", TEXTCODE);
				if(strpos($value,"-")===false){
					if (!preg_match("/(^(?<!090|080|070)\d{10}$)|(^(090|080|070)\d{8}$)|(^0120\d{6}$)|(^0080\d{7}$)/", $value)) {
						$formitem[$name[0]]=convert_encode("<strong class=\"error\">電話番号の書式に誤りがあります。</strong>");
						$error++;
					}else{
						$formitem[$name[0]]=$value;
					}
				}else{
					if (!preg_match("/(^(?<!090|080|070)(^\d{2,5}?\-\d{1,4}?\-\d{4}$|^[\d\-]{12}$))|(^(090|080|070)(\-\d{4}\-\d{4}|[\\d-]{13})$)|(^0120(\-\d{2,3}\-\d{3,4}|[\d\-]{12})$)|(^0080\-\d{3}\-\d{4})/", $value)) {
						$formitem[$name[0]]=convert_encode("<strong class=\"error\">電話番号の書式に誤りがあります。</strong>");
						$error++;
					}else{
						$formitem[$name[0]]=$value;
					}
				}
				
			
			//郵便番号
			}elseif( stristr($name[0],"zipcode")!==false && mb_strlen($value) > 0){
				$value = mb_convert_kana($value, "n", TEXTCODE);
				if (!preg_match("/(^\d{3}\-\d{4}$)|(^\d{7}$)/", $value)) {
					$error++;
					$formitem[$name[0]]=convert_encode("<strong class=\"error\">郵便番号の書式に誤りがあります。</strong>");
				}
			//本文
			}elseif( stristr($name[0],"message")!==false && mb_strlen($value) > 0){
			
				if(preg_match("/^[\x01-\x7e]+$/",$value) && (MAILCODE === 'ja')){
					//マルチバイト特殊文字（アクセント付きとか）が入っているとスルーしてしまう
					$formitem[$name[0]]=convert_encode('<strong class="error">日本語で書いてください。</strong>');
					$error++;
				}elseif (substr_count(strtolower($value), 'http') > $alink){//リンク数チェック
					$formitem[$name[0]]=convert_encode('<strong class="error">リンクの記述は認められていません。</strong>');
					$error++;
				}elseif(is_array($blocktxt)&&count($blocktxt)>0){//禁止語句チェック
					foreach($blocktxt as $fuck){
						if (strstr($value,$fuck) === false){
							$formitem[$name[0]]=preFilter($value);
						}else{
							$formitem[$name[0]]=convert_encode('<strong class="error">禁止語句が含まれているようです。</strong>');
							$error++;
							break;
						}

					}
				}else{
					$formitem[$name[0]] = preFilter($value);
				}
				
			}//end:preg_match
		}//end:for
	}//end:foreach

	//hiddenのmatch="name+name2,hoge1+hoge2"
	if(isset($POST["match"]) && $POST["match"] !== ""){
		$matchArr = explode(',',$POST["match"]);
		if(is_array($matchArr)){
			foreach($matchArr as $name_str){
				$nameArr = explode('+', $name_str);
				if($formitem[$nameArr[0]] !== $POST[$nameArr[1]]){
					$formitem[$nameArr[0]]=convert_encode('<strong class="error">入力された値が確認項目と一致しません。</strong>');
					$error++;
				}
			}
		}
	}

	if(isset($_FILES)&&count($_FILES)!=0){
		$error += checkUploadData($_FILES);
	}
	
	$formitem['Err'] = $error;
	
	return $formitem;
	
}

/************************************************************
 *
 * 添付ファイルのチェック
 *
 ************************************************************/
function checkUploadData($FILES)
{
	$err = 0;
	
	foreach ($FILES as $name => $array) {
		
		$filename = $array["name"];
		
		if(FILETEMP === false){
			
			$_SESSION[$name] = convert_encode("<strong class=\"error\">ファイルのアップロードが許可されていません。</strong>");
			$err = 1;
	
		}elseif ($array["error"] == 0) {
			
			$tmp_name =$array["tmp_name"];
			$filesize = $array["size"];
			$filetype = $array['type'];
			preg_match("/^.+?((?:\.\w{3})*\.\w{2,4})$/i", $filename, $extension);//拡張子

			if(!check_minetype($filetype, strtolower($extension[1]))){
				$_SESSION[$name] = convert_encode('<strong class="error">'.$label."[".$filename."] のファイル形式が不適切です。</strong>");
				$err = 1;
			}
			
			if($filesize >= MAXSIZE*1000 ){ //ファイルサイズ
				$_SESSION[$name] = convert_encode('<strong class="error">'.$label."[".$filename."] のファイルサイズ(".($filesize/1000)."kb)が大きすぎます</strong>");
				$err = 1;
			}
			
			if(!$err){
				if((FILEPOOL === true) || ! preg_match("/^[\w\d_\-\.]+?\.\w{2,4}$/i", $filename)){//FILEPOOL=ON | 画像が日本語だったら
					
					$filename = substr(md5(microtime()), 0, rand(5, 8)).$extension[1];//適当に名前付ける
				}else{
					$filename = strtolower($filename);
				}
				
				$target = (strpos(IMG_CHECK_TARGET,"_")===0) ? ' target="'.IMG_CHECK_TARGET.'"':' rel="'.IMG_CHECK_TARGET.'"';
				
				$_SESSION[$name] = convert_encode($filename." (".($filesize/1000)."kb)".' <a href="'.UPLOADPASS.$filename.'"'.$target.' class="zmPreview">ファイルの確認</a>');
				$_SESSION["FILES"][] = array('filename'=>$filename, 'type' =>$filetype);
				$_SESSION["FILETEMP"] = true;
				
				//tmpファイルを移動
				move_uploaded_file($tmp_name,UPLOADPASS.$filename);
				
				
			}
			
		}elseif($array["error"] != 4){
		
			switch($array["error"]){
				case 1:
					$_SESSION[$name] = convert_encode("<strong class=\"error\">[".$filename."] のファイルサイズが大きすぎます</strong>");
					$err = 1;
				break;
				
				case 2:
					$_SESSION[$name] = convert_encode("<strong class=\"error\">[".$filename."] のファイルサイズが大きすぎます</strong>");
					$err = 1;
				break;
				
				case 6:
					$_SESSION[$name] = convert_encode("<strong class=\"error\">テンポラリフォルダがありません</strong>");
					$err = 1;
				break;
				
				default:
					$_SESSION[$name] = convert_encode("<strong class=\"error\">[".$filename."] はアップロードできませんでした</strong>");
					$err = 1;
			}
			
		}
		
	}
	if(isset($err)){
		return $err;
	}
	
}

/************************************************************
 *
 * Ajax用入力エラー表示
 *
 ************************************************************/
function AjaxConfDisp($formitem){
	
	global $inputs,$replycomment;
		
	if($formitem['Err']>1)
		print '<div id="error" class="zeromail"><p><span class="error">入力エラーを修正してください。</span></p>';
	else
		print '<div class="zeromail confirmed"><p><span class="confirm">入力内容に間違いが無ければ、送信ボタンを押してください。</span></p>';
		
		switch(VIEWSTYLE){
			case 'Table':
				echo '<table id="confirm">';
				foreach( $inputs as $key => $value){
					$formitem[$key] = zeromail_regtag_replace($formitem, $key);
					echo '<tr><th scope="row" class="label">'.$value.'</th><td class="value">';
					echo $formitem[$key];
					echo '</td></tr>';
				}
				echo '</table>';
			break;
			
			case'List':
				echo '<dl id="confirm">';
				foreach( $inputs as $key => $value){
					$formitem[$key] = zeromail_regtag_replace($formitem, $key);
					echo '<dt class="label">'.$value.'</dt><dd class="value">';
					echo $formitem[$key];
					echo '</dd>';
				}
				echo '</dl>';
			break;
			
			default:
				echo '<div id="confirm">';
				foreach( $inputs as $key => $value){
					$formitem[$key] = zeromail_regtag_replace($formitem, $key);
					echo '<p><em class="label">'.$value.'</em><span class="value">';
					echo $formitem[$key];
					echo '</span></p>';
				}
			echo '</div></div>';
		}
}

/************************************************************
 *
 * 送信関数
 *
 ************************************************************/
 
function zeromailAjax_send($formitem)
{
	global $inputs, $endMassage,$replyfoot,$replycomment;
	
	ip_check_destroy();
	
	//ユーザー情報
	$user_ip = $_SERVER['REMOTE_ADDR'];
	$user_host = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	
	$user ="DATE: ".date("Y/m/d - H:i:s")."\n";
	$user.="IP: ".$user_ip."\n";
	$user.="HOST: ".$user_host."\n";
	$user.= "USER AGENT: ".$user_agent."\n";
	$copy =SCRIPT."[http://zeromail.webtecnote.com/]";
	
	if(is_admin()) $csv = '"'.date("Y/m/d H:i:s").'"';
	
	$mailsubject = MAILSUBJECT;

	//本文スタイル
	$message="\n────────────────────────────────────";
	
	foreach($inputs as $key => $value){
	
		$formitem[$key] = mb_convert_encoding($formitem[$key], "UTF-8", TEXTCODE);
		
		if(strpos($formitem[$key], 'zmPreview') !== FALSE){
			$formitem[$key] = preg_replace('/\s*<a[^>]+ class="zmPreview">ファイルの確認<\/a>/i',"",$formitem[$key]);
		}
		
		if(NOSCRIPT===false) $formitem[$key] = zeromail_regtag_replace($formitem, $key);
		
		$mailsubject = convert_tag($key, $formitem[$key],$mailsubject);
		
		if(is_admin()) $csv .=',"'.str_replace(array("\n","\r","\r\n"),"",$formitem[$key]).'"';
		
		if(is_empty_skip($formitem[$key])) { continue; }//empty value skip
		

		$message .= "\n■$value\n";
		$message .= $formitem[$key]."\n";
	}

	$message.= "────────────────────────────────────\n";
	$message.= "□ユーザー情報\n";
	$message.= $user ."\n";
	$message.= "────────────────────────────────────\n";
	$message.= $copy;

	if(is_admin())
		$csv .= ',"'.$user_ip.'","'.$user_host.'","'.$user_agent.'"'."\n";
	
	//本文整形
	if(strpos(PHPVER,'5')===false){
		$message = unhtmlentities($message);
	}else{
		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
	}
	
	$message = $mailsubject.str_replace(array("\r","\r\n"),"",str_replace("<br />","",$message));
	
	$name = isset($formitem["name"])? $formitem["name"] : SCRIPT;
	
	//添付ファイルなし（POOLあり）
	if(FILETEMP === false || (FILETEMP === true && FILEPOOL === true) || (FILETEMP === true && $formitem["FILETEMP"] !== true)){
		
		$mailheader ="From: ".get_mailfrom($name, $formitem['email'])."\r\n";
		if(BCC != "") $mailheader.="Bcc: ".BCC."\r\n";
		$mailheader.="X-Mailer: ".SCRIPT."(Version ".VERSION.")\r\n";
		
		@mb_send_mail(MAILTO,$mailsubject,$message,$mailheader);
	
	//添付ファイルあり（POOLなし）
	}elseif(FILEPOOL === false && FILETEMP === true && $formitem["FILETEMP"]===true){
		$boundary = "zeromail".md5(uniqid(rand()));//バウンダリー
		$mailfrom2 = get_mailfrom($name, $formitem['email']);
		
		$mailheader = "From: ".$mailfrom2."\r\n";
		if(BCC != "") $mailheader.="Bcc: ".BCC."\r\n";
		$mailheader .= "X-Mailer: ".SCRIPT."(Version ".VERSION.")\r\n";
		$mailheader .= "MIME-version: 1.0\r\n";
		$mailheader .= "Content-Type: multipart/mixed;";
		$mailheader .= " boundary=".$boundary."\r\n";
		
		$msg = "--".$boundary."\r\n";
		
        $mailcode = (MAILCODE === 'ja') ? 'ISO-2022-JP' : 'UTF-8';
		$encbit = (MAILCODE === 'ja') ? '7bit' : 'base64';
        $msg .= "Content-type: text/plain; charset={$mailcode}\r\n";
		$msg .= "Content-transfer-encoding: {$encbit}\r\n";
		
		if(MAILCODE === 'ja'){
			$message = mb_convert_encoding($message, $mailcode, 'UTF-8');
		}
		
		$msg .=chunk_split(base64_encode($message));
		
		foreach($formitem["FILES"] as $i => $tmp){
			$fp = @fopen(ZEROMAIL_DIR.UPLOADPASS.$tmp["filename"], "r"); //ファイルの読み込み
			$contents = @fread($fp, @filesize(ZEROMAIL_DIR.UPLOADPASS.$tmp["filename"]));
			@fclose($fp);
			$encoded = chunk_split(base64_encode($contents)); //エンコード
			$msg .= "\r\n--".$boundary."\r\n";
			$msg .= "Content-Type: " . $tmp["type"] . ";\r\n";
			$msg .= "\tname=\"".$tmp["filename"]."\"\r\n";
			$msg .= "Content-Transfer-Encoding: base64\r\n";
			$msg .= "Content-Disposition: attachment;\r\n";
			$msg .= "\tfilename=\"".$tmp["filename"]."\"\r\n";
			$msg .= $encoded."\r\n";
		}
		
		RemoveFiles(ZEROMAIL_DIR.UPLOADPASS);//ファイル削除
		
		$msg .= "--".$boundary."--";
		
		$subject = zm_encode_mimeheader($mailsubject);
		
		@mail(MAILTO, $subject, $msg, $mailheader);
	}
	
	//メール自動返信
	if( ( $formitem['reply'] === "true" || REPLY === true ) && $formitem['email'] != "" ){
		
		$replyheader ="From: \"".zm_encode_mimeheader(FROMNAME)."\" <".MAILTO.">\r\n";
		if(BCC != "") $replyheader.="Bcc: ".BCC."\r\n";
		$replyheader.="X-Mailer: ".SCRIPT."(Version ".VERSION.")\r\n";
		
	//自動返信本文スタイル
	$replymessage='';
	foreach( $inputs as $key => $value){
		
		$replycomment =  convert_tag($key, $formitem[$key], $replycomment);
		$replyfoot = convert_tag($key, $formitem[$key], $replyfoot);
		
		if(is_empty_skip($formitem[$key])) { continue; }//empty value skip
		
		$replymessage .= "\n■$value\n";
		$replymessage .= $formitem[$key]."\n";
	
	}

	$replymessage = $replycomment.$replymessage;
	$replymessage .= $replyfoot;

	//自動返信本文整形
	if(strpos(PHPVER,'5') === false){
		$replymessage = unhtmlentities($replymessage);
	}else{
		$replymessage = html_entity_decode($replymessage, ENT_QUOTES, 'UTF-8');
	}
		$replymessage = str_replace("\r","",str_replace("<br />","", $replymessage));
		
		@mb_send_mail($formitem['email'], REPSUBJECT, $replymessage, $replyheader);
	}
	
	if(is_admin()) zeromail_data_put_csv($csv);//CSV保存
	
	session_destroy();
	
	if(NOSCRIPT === false){
		
		print $endMassage;
		
	}else{
		
		header('Location: '.SUCCESSPAGE);

	}
	
}

/*-----------------------------------------------------
  タグ変換
------------------------------------------------------*/
function convert_tag($key, $val, $str)
{
	return str_replace("{".$key."}", $val, $str);
}

/*-----------------------------------------------------
  管理画面機能の存在チェック
------------------------------------------------------*/
function is_admin()
{
	return (defined('ZM_ADMIN') && ZM_ADMIN === true);
}

/*-----------------------------------------------------
  csvに書き込み
------------------------------------------------------*/
function zeromail_data_put_csv($csv)
{
	
	$filename = ZM_LOGFILE;
	
	if (is_writable($filename)) {
		$data = @file_get_contents($filename);
		$data = str_replace("<?php exit;/*",'',$data);
		$data = ltrim($data);
	}else{
		touch($filename);
	}
	
	if(filesize($filename) == "0"){
		$empty = true;
	}
	
	$fp = fopen($filename, "w");
	fwrite($fp, mb_convert_encoding("<?php exit;/*\n".$csv,"UTF-8", TEXTCODE));
	
	if(isset($data) && is_numeric(ZM_ADMIN_LOGMAX)){
		$line = explode("\n",$data);
		for($i=0; $i < ZM_ADMIN_LOGMAX; $i++){
			fwrite($fp, mb_convert_encoding($line[$i]."\n","UTF-8", TEXTCODE));
		}
		
		
	}elseif(isset($data) && !is_numeric(ZM_ADMIN_LOGMAX)){
		fwrite($fp, mb_convert_encoding($data,"UTF-8", TEXTCODE));
	}
	
	if($empty===true || count($line)>ZM_ADMIN_LOGMAX) fwrite($fp,mb_convert_encoding("*/?>","UTF-8", TEXTCODE));
	
	fclose($fp);
	
}


/*-----------------------------------------------------
  MIMEヘッダエンコード
------------------------------------------------------*/
function zm_encode_mimeheader($str)
{
	
	$encode = (MAILCODE === 'ja') ? 'ISO-2022-JP' : 'UTF-8';
	
	if($encode && (MAILCODE === 'ja')){
		$str = mb_convert_encoding($str, $MAILCODE, 'UTF-8');
	}
	return mb_encode_mimeheader($str, $encode, "B");
}

/*-----------------------------------------------------
  差出人フォーマット
------------------------------------------------------*/
function get_mailfrom($name, $email)
{
	$name = zm_encode_mimeheader($name);
	
	if(!$email){
		return '"'.$name.'" <'.SCRIPT.'@Ver'.VERSION.'>';
	}else {
		return '"'.$name.'" <'.$email.'>';
	}
	
}

/*-----------------------------------------------------
  添付ファイル削除
------------------------------------------------------*/
function RemoveFiles($dir)
{
    if(!$dh = @opendir($dir)) return;
    while (false !== ($obj = readdir($dh))) {
        if($obj=='.' || $obj=='..') continue;
        @unlink($dir.'/'.$obj);
    }
    closedir($dh);
}

/*-----------------------------------------------------
  ファイルタイプのチェック
------------------------------------------------------*/
function check_minetype($filetype, $extension)
{
	$minetype = array("image/jpeg","image/pjpeg","image/x-png","image/png","image/gif","image/bmp","application/pdf","application/octet-stream","application/x-shockwave-flash","text/plain","application/x-zip","application/zip","application/x-zip-compressed","application/x-lha-compressed","application/mspowerpoint","application/x-compress","application/x-excel","application/excel","application/vnd.ms-excel","application/vnd.ms-powerpoint","application/x-msexcel","application/x-gzip");
	$ext  = array(".gif",".png",".jpg",".bmp",".pdf",".swf",".txt",".xls",".doc",".ppt",".zip",".lzh",".tar.gz");

	if(array_search($filetype,$minetype)===false){
		return false;
	}elseif(array_search($extension,$ext)===false){
		return false;
	}else{
		return true;
	}
}

/*-----------------------------------------------------
  IPチェック
------------------------------------------------------*/
function ip_check_destroy()
{
	global $blockIP;
	
	if(array_search($_SERVER['REMOTE_ADDR'],$blockIP)!==false){
		if(NOSCRIPT===true){
			ErrerDisp('送信が認められていません。');
		}else{
			print "<div id=\"error\"><p><span class=\"error\">送信が認められていません。</span></p></div>";
			exit;
		}
	}
}

/*-----------------------------------------------------
  リファラチェック
------------------------------------------------------*/
function ref_check_destroy()
{
	global $formURL;
	
	if(REFCHECK === true && $formURL!="" && $_SERVER["HTTP_REFERER"]!==$formURL){ //リファラーチェック
		
		if(NOSCRIPT===true){
			ErrerDisp('不正な送信元です。');
		}else{
			print "<div id=\"error\"><p><span class=\"error\">不正な送信元です。</span></p></div>";
			exit;
		}
	}
}

/*-----------------------------------------------------
  magic_quotes_gpc=ON対策
------------------------------------------------------*/
function preFilter($str)
{
	if (ini_get('magic_quotes_gpc')){
		$str = stripslashes_deep(nl2br(htmlentities($str, ENT_QUOTES,TEXTCODE)));
	}else{
		$str = nl2br(htmlentities($str, ENT_QUOTES,TEXTCODE));
	}
	return $str;
}

/*-----------------------------------------------------
  スラッシュ消す
------------------------------------------------------*/
function stripslashes_deep($str)
{
	$str = is_array($str) ?
		array_map('stripslashes_deep', $str) :
		stripslashes($str);
	return $str;
}

/*-----------------------------------------------------
  ver4用デコード
------------------------------------------------------*/
function unhtmlentities($string)
{
    // 数値エンティティの置換
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
    // 文字エンティティの置換
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}
?>