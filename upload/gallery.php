<!DOCTYPE html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>Presenter Uploads</title>
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css">
<link rel="stylesheet" href="css/bootstrap-image-gallery.min.css">
</head>
<body>
<div class="container-fluid">
  <h1>Presenter Uploads</h1>
  <div id="gallery" data-toggle="modal-gallery" data-target="#modal-gallery">
<?php
$thumbs = 'files/thumbnail';
$files = 'files';
$allowed_types = array('jpg','jpeg','gif','png');

$dir_handle = @opendir($thumbs) or die("There is an error with your image directory!");

$i=0;
while ($file = readdir($dir_handle)) {
	if ($file=='.' || $file == '..') continue;

	$file_parts = explode('.', $file);
	$ext = strtolower(array_pop($file_parts));

	$title = implode('.', $file_parts);
	$title = htmlspecialchars($title);

	if (in_array($ext,$allowed_types)) {
?>
      <a href="<?php echo $files.'/'.$file; ?>" title="<?php echo $title; ?>" data-gallery="gallery">
        <img src="<?php echo $thumbs.'/'.$file; ?>">
      </a>
<?php
		$i++;
	}
}

closedir($dir_handle);
?>
  </div>
</div>

<!-- modal-gallery is the modal dialog used for the image gallery -->
<div id="modal-gallery" class="modal modal-gallery hide fade" tabindex="-1">
    <div class="modal-header">
        <a class="close" data-dismiss="modal">&times;</a>
        <h3 class="modal-title"></h3>
    </div>
    <div class="modal-body"><div class="modal-image"></div></div>
    <div class="modal-footer">
        <a class="btn modal-download" target="_blank">
            <i class="icon-download"></i>
            <span>Download</span>
        </a>
        <a class="btn btn-success modal-play modal-slideshow" data-slideshow="5000">
            <i class="icon-play icon-white"></i>
            <span>Slideshow</span>
        </a>
        <a class="btn btn-info modal-prev">
            <i class="icon-arrow-left icon-white"></i>
            <span>Previous</span>
        </a>
        <a class="btn btn-primary modal-next">
            <span>Next</span>
            <i class="icon-arrow-right icon-white"></i>
        </a>
    </div>
</div>

<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/js/bootstrap.min.js"></script>
<script src="http://blueimp.github.com/JavaScript-Load-Image/load-image.min.js"></script>
<script src="js/bootstrap-image-gallery.min.js"></script>
<script src="js/main.js"></script>

</body>
</html>