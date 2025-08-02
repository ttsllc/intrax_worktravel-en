<?php

/*
このファイルを、何かしらのPHPファイルからincludeなりrequireして、


send_contact_data_to_salesforce2019($_POST)
↑こんな感じで使えばよくね？サニタイズは必要と思う

*/


//Sales force用データ生成
function set_data_to_salesforce2019 ($data) {

	
	$programTxt='Intrax/Work and Travel in the USA';
	
	
	
/*$message = htmlspecialchars($data['message']);
$programSelect = htmlspecialchars($data['programSelect']);
$experienceSelect = htmlspecialchars($data['experienceSelect']);*/
	
/* 本番用 */
	$array = array(
		'oid' => '00D30000000p3II',
		'00N300000068fwe' => $data['birth_month'].'/'.$data['birth_day'].'/'.$data['birth_year'], //birthOfDate
		'Primary Activity' => '',
		'00N30000008QnVM' => $data['affiliation'], // educationLevel
		'00N300000068JwE' => 'Participant', // unknown
		'00N300000068Glm' => $programTxt, // IntraxPrograms(ex.Internship->Internship)
		'00N3000000692oE' => 'Japan', // IntraxRegion
		'lead_source' => 'Web Form',
		'00N300000069Noq' => 'Web Form', // LeadSourceTag
		'00N3000000692rD' => 'English', // WebFormLanguage
		/*'00N30000007BGW0' => $inprogOp, // IntraxProgramOptions*/
		'00N30000007BGW0' => '', // IntraxProgramOptions
		'first_name' => $data['first_name'],
		'last_name' => $data['last_name'],
		'first_name_local' =>  $data['first_name'],
		'last_name_local' => $data['last_name'],
		'00N30000008QJrm' => $data['furi2'],
		'00N30000008QJrw' => $data['furi1'],
		'year' => '',
		'month' => '',
		'day' => '',
		'email' => $data['email'],
		'zip' => $data['zipcode'],
		'street' => $data['pref'].' '.$data['address1'].' '.$data['address2'],
		'phone' => $data['tel'],
		'mobile' => '',
		'yusoflg' => $hope,
		'00N300000068Glc' => $data['gender'], // gender
		'reference' => $refce,
		'00N400000021x8T' => '', // unknown
	  '00N300000068ZCr' => '', // How Heard
	  
	  '00N30000007ClGQ'=>'',
		'submit1' => 'submit',
	);

	return $array;
}

// curlダメそうなので、file_put_contentで
function send_post2019 ($data) {
	$url = 'https://webto.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8';
//	$url = 'https://test.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8';
	$content = http_build_query($data);
	$options = array('http' => array('method' => 'POST','content' => $content));
	$contents = file_get_contents($url,false,stream_context_create($options));
}



//Sales forceとの連携

//お問い合わせテスト
function send_contact_data_to_salesforce2019 ($data) {
	//$data = $data->gets();

//$content.=' [郵送希望]'.$data['hope']; // content
//$test_date= $data['test_year'].'年'.$data['test_month'].'月'.$data['test_day'].'日';
//$dohan=$data['care1'].$data['care2'];
//$mob_tel=$data['mob_tel'];
//$school=$data['school'].'（'.$data['school_fur'].'）';
//$parent=$data['care3'].$data['care4'].'（'.$data['care_furi3'].$data['care_furi4'].'/'.$data['relationship'].'）';
//$parent_tel=$data['care_tel'];
//$parent_email=$data['care_email'];
	$array = set_data_to_salesforce2019($data);


	send_post2019($array);

}

/*
本番のとき下記を有効
これはWP用なので無視
*/

//add_action('mwform_after_send_mw-wp-form-4397','send_contact_data_to_salesforce2019');
