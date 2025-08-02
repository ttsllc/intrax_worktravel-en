/*------------------------------------------------------------
  ZeroMail-Ajax.js
  @copyright:(c)Tenderfeel(http://webtecnote.com/)
  @license: MIT-style license.
--------------------------------------------------------------*/
// MooTools 1.3系の場合は
var $$ = document.id;

//フォーカスさせる要素のID
var firstFocus = 'features';

//フォームのID
var form = "applyingForm";

//結果を表示する要素のID
var result = "result";

//リセットボタンのID
var myreset = "myreset";

//閉じるボタンの文字
var buttonText = "閉じる";

//送信ボタンの文字
var submitText = "この内容で送信";

//確認画面の表示
var confirmDisp = "false";

/*--------------------------------------------------------------*/

//MooTools.lang.setLanguage("ja-JP");

window.addEvent("domready",function() {
	
	if(!$$(form)) return;
	if(firstFocus) $(firstFocus).focus();
	$$(form).addEvent("submit",function(){return false});
	
	var myValidator = new Form.Validator.Inline($(form),{errorPrefix: ""});
	
	var ss = window.retrieve('SmoothScroll');
	
	var req = new Form.Request.Append($(form),$(result),
					 {
						resetForm:false,
						extraData :{"noscript":"false",'confirm':confirmDisp},
						requestOptions: {
							'spinnerTarget':$(form)
						},
						revealOptions:{
							onHide:function(){
								this.element.destroy();
							}
						},
						onSuccess:function(el,html){
							
							req.disable();//フォームを使えないようにする
							
							if(ss) ss.toElement($(result).getParent());
							else window.scrollTo($(result).getScroll().x,$(result).getScroll().y-15);
							
							if(el.hasClass("exit")){ 
								$(form).reset();//入力内容のリセット
							}
							
							if(el.hasClass("confirmed")){//確認
								
								new Element("button",{"id":"submit","html":submitText,
											"events":{
												"click":function(){ 
												req.options.extraData['mode']='Send';//modeを追加
												req.send();//送信
												el.dissolve();//スライドアウト
												req.enable();//フォームを使えるようにする
												}
											}}).inject(new Element("p",{"class":"button"})).inject(el,"bottom");
							
							}else{//エラー・完了
								
								new Element("button",{"id":"close","html":buttonText,
											"events":{
												"click":function(){
													el.dissolve();//スライドアウト
													req.enable();//フォームを使えるようにする
													}
												}}).inject(new Element("p",{"class":"button"})).inject(el,"bottom");
							}
							
						}
					});
	
	$$(myreset).addEvent("click",function(){
        $$(form).reset();
        $$(form).getElements("[input|textarea]").removeClass("validation-failed");
        myValidator.reset();
    });

});