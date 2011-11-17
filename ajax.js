jQuery(document).ready(function($){
	if($('#hametuha-zip-regsiter').length > 0){
		var sendData = function(){
			$.post(
				HametuhaZipSearch.endpoint,
				HametuhaZipSearch,
				function(response){
					HametuhaZipSearch.offset += response.done;
					$('#current_number').text(HametuhaZipSearch.offset);
					$('#indicator').css('width', Math.floor((HametuhaZipSearch.offset / parseInt($('#total_number').text(), 10)) * 100) + '%' );
					$('#current_number').val(HametuhaZipSearch.offset);
					if(response.complete){
						$('#indicator-bg').before('<div class="update">終了しました。</div>');
					}else{
						sendData();
					}
				}
			);
		}
		HametuhaZipSearch.offset = 0;
		sendData();
	}
});