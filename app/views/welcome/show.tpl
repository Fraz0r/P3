<?
	$this->content_for('sample', function(){
		return '<p>Views can write to buffers, which other views/layouts can render</p>';
	});
?>
<strong>Edit in: <?= $this->_path ?></strong>
<h1><?= $lol; ?></h1>


<?  $this->start_content_for('sample'); ?>
	<p>There are two ways to add to buffers, as you can see here</p>
<?  $this->end_content_for('sample'); ?>