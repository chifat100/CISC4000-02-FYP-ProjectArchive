<?php
    $check = mail("edusync12567@gmail.com","Testing Email", "abcasdasdasd", "From:calvin62813340@gmail.com");

    if($check){
        echo "email sent successfully";

    }
    else{
        echo "email not sent successfully";
    }
?>