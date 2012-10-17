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
			f.find('input[name=' + HametuhaZipSearch.zipName + ']').val(address.zip);
			f.find('input[name=' + HametuhaZipSearch.prefName + ']').val(address.prefecture);
			f.find('select[name=' + HametuhaZipSearch.prefName + '] option').each(function(index, elt){
				if($(elt).text() == address.prefecture){
					$(elt).attr('selected', true);
				}
			});
			f.find('input[name=' + HametuhaZipSearch.cityName + ']').val(address.city);
			var town = address.town;
			town = town.replace(/以下に掲載がない場合/, '');
			f.find('input[name=' + HametuhaZipSearch.streetName + ']').val(town + address.street);
			f.find('input[name=' + HametuhaZipSearch.officeName + ']').val(address.office);
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
						var container = $('<div title="複数の候補が見つかりました"></div>');
						var curPage = 1;
						var perPage = 5;
						var totalPage = Math.ceil(results.length / perPage);
						//住所を追加
						for(i = 0, l = results.length; i < l; i++){
							if(i % perPage == 0){
								container.append('<ol start="' + (i + 1) + '"></ol>');
								if(i > 0){
									container.find('ol:last').css('display', 'none');
								}
							}
							container.find("ol:last").append(makeLine(results[i], i));
						}
						//ページネーション追加
						if(totalPage > 1){
							var pagenator = $('<div>' +
								'<a href="#next" class="ui-state-default ui-corner-all" style="float:right;"><span class="ui-icon ui-icon-circle-triangle-e next"></span></a>' +
								'<a href="#prev" class="ui-state-default ui-corner-all" style="float:right; margin-right: 1em;"><span class="ui-icon ui-icon-circle-triangle-w next"></span></a>' +
								'<span class="current">' + curPage + '</span> / ' +
								'<span class="total">' + totalPage + '</span>' +
								'</div>');
							pagenator.find('a').click(function(e){
								e.preventDefault();
								if($(this).attr('href').match(/next/)){
									curPage = Math.min(curPage + 1, totalPage);
								}else{
									curPage = Math.max(1, curPage - 1);
								}
								container.find('ol').each(function(index, ol){
									if(index + 1 == curPage){
										$(ol).css('display', 'block');
									}else{
										$(ol).css('display', 'none');
									}
									pagenator.find('.current').text(curPage);
								});
							});
							container.prepend(pagenator);
						}
						//コールバック登録
						container.find('input[type=button]').click(function(e){
							e.preventDefault();
							var hidden = $(this).prev('input');
							fillForm(results[hidden.val()]);
							container.dialog( "close" );
						});
						/*
						$.fn.prettyPhoto({
							changepicturecallback: function(){ //開いたときに発生するイベント
								$('#zipcode-container').remove();
								$(".zipcode-inner input[type=button]").click(function(e){
									$.prettyPhoto.close();
								});
							},
							callback: function(){ //閉じたときに呼び出されるコールバック
								$.fn.prettyPhoto({
									changepicturecallback: function(){},
									callback: function(){
									}
								});
							},
							social_tools: ''
						});
						*/
						//prettyPhoto開く
						container.dialog({
							modal: true
						});
					}
				}else{
					var tag = '<div title="Not Found">' +
						'<p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 50px 0;"></span>' +
						'該当する住所は見つかりませんでした。別の郵便番号でお試しください。</p></div>';
					$(tag).dialog({
					   modal: true,
					   buttons: {
						   Ok: function() {
							   $( this ).dialog( "close" );
						   }
					   }
				   });
				}
				target.removeClass('hametuha-zip-search-loading');
				target.val(originalVal);
			}
		);
	});
});