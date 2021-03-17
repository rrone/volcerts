<?php
/* upload multiple files in one request */
$upload_dir = '../var/uploads';
$nameList = array();

foreach ($_FILES["myfile"]["error"] as $key => $error) {
    if ($error == UPLOAD_ERR_OK) {
        $name = basename($_FILES["myfile"]["name"][$key]);
        $target_file = "$upload_dir/$name";
        if ($_FILES["myfile"]["size"][$key] > 610000) { // limit size of 600KB
            echo "error: {$name} is too large. \n";
            continue;
        }
        if (!move_uploaded_file($_FILES["myfile"]["tmp_name"][$key], $target_file))
            echo 'error:' . $_FILES["myfile"]["error"][$key] . ' see error.log';
        else
            $nameList[] = $name;
    }
}

if (isset($_POST['data'])) {
    print_r($_POST['data']);
}

echo "\n success: \n";

print_r($nameList);
