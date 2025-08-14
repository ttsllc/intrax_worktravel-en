//カスタムvalidateの作成
//ここでは英大文字のみを正規表現で判定
/*jQuery.validator.addMethod("uppercase", function(value, element) {
    return this.optional(element) || /^[A-Z]+$/.test(value);
}, "Please input uppercase");*/
$.validator.addMethod("selectRequired", function(value, element) {
  return $(element).val() !== "";
});
//validateのoption作成




$(function(){
const maxFileSize=1048576
let fileErrMessage="The maximum file size for uploads is 1 MB. <br>Please try again with a smaller file.";		
			//アップロードできる最大サイズを指定(1048576=1MB)
$("input[type=file]").change(function(){ 		//ファイルがアップロードされたら
    $(".error_msg").remove()					//エラーメッセージ削除
    let uploaded_file=$(this).prop('files')[0]; //アップロードファイル取得
    if(maxFileSize < uploaded_file.size){ 		//もし上限値を超えた場合
        $(this).val("")							//画像を空にする
        $(this).after('<p class="error_msg">'+fileErrMessage+'</p>') //エラーメッセージ表示
    }
})	
	
	
	
	var formValid = {
    //入力欄別にルールを作成
    rules:{
		programSelect:{
			selectRequired:true
		},		
        first_name:{
            required:true
        },
        last_name:{
            required:true
        },
        email:{
            required:true
        },
	message:{
            required:true
        },
/*       phone:{
            required:true
        },*/

/*        address:{
            required:true
        },*/
/*		uploadFile:{
			extension: "jpg|jpeg|png|gif|pdf"
			
		},*/
/*        cityCode:{
            required:true,
            minlength:3,
            uppercase:true
        },
        grade:{
            required:true
        },
        facility:{
            required:true
        },
        imagePath:{
            required:true,
            url:true
        },*/
    },
    //messageを自分好みに設定
/*    messages:{
        countryCode:{
            minlength:"Country code must be 2 characters."
        },
        cityCode:{
            minlength:"City code must be 3 characters."
        }
    }*/
	
	errorPlacement: function(error, element){
      var errorKey = $(element).attr('id') + 'Error';
      $('#error_' + errorKey).remove();
      element.addClass('is-invalid');
      const errorP = $('<p>').text(error[0].innerText);
      const errorDiv = $('<div class="invalid-feedback" id="error_' + errorKey + '">').append(errorP);
      element.parent().append(errorDiv);
    },
    success: function(error, element) {
      var errorKey = $(element).attr('id') + 'Error';
      $('#error_' + errorKey).remove();
      $(error).remove();
      $(element).removeClass('is-invalid');
      $(element).removeClass('error');
    },
	
	
}

/*let $form = $('#applyingForm');
let fd = new FormData($form.get(0));*/
	
	
    //ボタンクリックで発火
    $("#applyFormSubmit").on('click',function(){
        //validate実行（作成したvalidateのoptionを指定）
        $("#applyingForm").validate(formValid);
        //失敗で戻る
        if (!$("#applyingForm").valid()) {
            return false;
        };
let $form = $('#applyingForm');	
let fd = new FormData($form.get(0));
        //Ajaxでform入力内容送信
        $.ajax( {
            type: "POST",
            url: "./ajax_mail_sender.php",
           // dataType: "json",
			data:fd,
	processData: false,
    contentType: false,
/*            data: {
                "programSelect" : $('#programSelect').val(),
				"first_name" : $('#first_name').val(),
				"last_name" : $('#last_name').val(),
                "email" : $('#email').val(),
				"address" : $('#address').val(),
				"phone" : $('#phone').val(),
                "experienceSelect" : $('#experienceSelect').val(),
                "uploadFile" : $('#uploadFile').val()
            }*/
        //成功で作成したホテルIdをアラートで表示
        }).done(function( response ){
		//	alert(response);
        alert( "Thank you for your message. We have received your message and will respond shortly." );
			
	$("#applyingForm").find(":input").each(function() {
      switch (this.type) {
        case "checkbox":
        case "radio":
          this.checked = false;
          break;
        case "select-one":
        case "select-multiple":
          this.selectedIndex = 0;
          break;
        default:
          this.value = "";
      }
    });
			
			
        //失敗もアラートで通知
        }).fail(function( data ){
            alert( "faild" );
        });
    });
})
