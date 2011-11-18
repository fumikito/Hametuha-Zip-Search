jQuery(document).ready(function($){
	$('.hametuha-zip-search').click(function(e){
		e.preventDefault();
		var target = $(this);
		var originalVal = target.val();
		target.val('読み込み中...');
		target.addClass('hametuha-zip-search-loading');
		var f = target.parents('form');
		HametuhaZipSearch.zip = f.find('input[name=zipcode]').val().replace(/[^0-9]/g, "");
		var fillForm = function(address){
			f.find('input[name=zipcode]').val(address.zip);
			f.find('input[name=prefecture]').val(address.prefecture);
			f.find('select[name=prefecture] option').each(function(index, elt){
				if($(elt).text() == address.prefecture){
					$(elt).attr('selected', true);
				}
			});
			f.find('input[name=city]').val(address.city);
			var town = address.town;
			if(town.match(/（/)){
				switch(true){
					case town.match(/地階/):
						break;
					case town.match(/階）$/):
						break;
					default:
						town = town.replace(/（.*?）/, '');
						break;
				}
			}
			town = town.replace(/以下に掲載がない場合/, '');
			f.find('input[name=street]').val(town + address.street);
			f.find('input[name=office]').val(address.office);
		};
		$.post(
			HametuhaZipSearch.endpoint,
			HametuhaZipSearch,
			function(results){
				if(results.length > 0){
					if(results.length == 1){
						//Single Result
						fillForm(results[0]);
					}else{
						//Multiple Results
						//Create DOM Function
						var makeLine = function(address, key){
							var li = document.createElement('li');
							var btn = document.createElement('input');
							$(btn).attr({type: "button", value: "決定"}).addClass("regular-button");
							var hidden = document.createElement('input');
							$(hidden).attr({type:"hidden", name: 'resultKey', value: key});
							var str = "〒" + address.zip + " " + address.prefecture + address.city + address.town;
							if(address.street)
								str += address.street;
							if(address.office)
								str = "【" + address.office + "】" + str;
							$(li).html(str).prepend(btn).prepend(hidden);
							return li;
						}
						//Create display Container
						var container = document.createElement("div");
						$(container).attr('id', 'zipcode-container').css("display", "none");
						$(container).html('<div class="zipcode-inner"><h3>住所</h3><ul class="notice"></ul><h3>事業所</h3><ul class="notice"></ul></div>');
						//Insert to view port
						f.append(container);
						$(container).find('div').css({width: "auto", height: "350px", overflow: 'auto'});
						//住所の場合
						for(i = 0, l = results.length; i < l; i++){
							if(results[i].office != ''){
								$("#zipcode-container ul:last").append(makeLine(results[i], i));
							}else{
								$("#zipcode-container ul:first").append(makeLine(results[i], i));
							}
						}
						//コールバック登録
						$.fn.prettyPhoto({
							changepicturecallback: function(){ //開いたときに発生するイベント
								$('#zipcode-container').remove();
								$(".zipcode-inner input[type=button]").click(function(e){
									var hidden = $(this).prev('input');
									fillForm(results[hidden.val()]);
									$.prettyPhoto.close();
								});
							},
							callback: function(){ //閉じたときに呼び出されるコールバック
								$.fn.prettyPhoto({
									changepicturecallback: function(){},
									callback: function(){
									}
								});
							}
						});
						//prettyPhoto開く
						$.prettyPhoto.open('#zipcode-container', "複数の候補が見つかりました", "ボタンを押して、候補から選択してください");
					}
				}else{
					alert('該当する住所は見つかりませんでした');
				}
				target.removeClass('hametuha-zip-search-loading');
				target.val(originalVal);
			}
		);
	});
});