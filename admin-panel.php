<?php
/* @var $this Hametuha_Zip_Search */
/* @var $wpdb wpdb */
?>
<h3>ステータス</h3>
<p>
	現在<strong><?php echo number_format($this->count_rows()); ?></strong>件が登録されています。
</p>

<?php if($this->ajaxable()): ?>
<form id="hametuha-zip-regsiter">
	<div id="indicator-bg" style="border:1px solid #ccc; background:#EEE; height:30px;">
		<div id="indicator" style="width:0;height:30px;background:#0f0;"></div>
	</div>
	<p>
		<strong id="current_number">0</strong> /
		<strong id="total_number"><?php echo $this->current_row(); ?></strong>件を処理しました
	</p>
</form>
<?php endif; ?>

<form method="post">
	<?php wp_nonce_field('hametuha_zip_search'); ?>
	<table class="form-table">
		<tbody>
			<tr>
				<th>ファイル</th>
				<td>
					<?php foreach($this->get_csv() as $file): ?>
					<label>
						<input type="radio" name="csv" value="<?php echo $file->ID; ?>" />
						<?php echo $file->post_title; ?>
					</label>
					<?php endforeach; ?>
					<p class="description">
						<a href="http://www.post.japanpost.jp/zipcode/download.html" target="_blank">日本郵便のWebサイト</a>からダウンロード＆解凍したCSVを<a href="<?php echo admin_url('media-new.php'); ?>">メディアページ</a>からアップロードしてください。
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="action">アクション</label></th>
				<td>
					<select name="action" iad="action">
						<option value="add">追加</option>
						<option value="delete">削除</option>
					</select>
				</td>
			</tr>
		</tbody>
	</table>
	<?php submit_button('送信'); ?>
</form>