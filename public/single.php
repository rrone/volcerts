<?php
/* upload one file */
$upload_dir = '../var/uploads';
$name = basename($_FILES["myfile"]["name"]);
$target_file = "$upload_dir/$name";
if ($_FILES["myfile"]["size"] > 610000) { // limit size of 600KB
    echo "error: {$name} is too large.";
    exit();
}
if (!move_uploaded_file($_FILES["myfile"]["tmp_name"], $target_file))
    echo 'error: '.$_FILES["myfile"]["error"].' see error.log';
else {
    if (isset($_POST['data'])) print_r($_POST['data']);
    echo "\n filename : {$name}";
}
