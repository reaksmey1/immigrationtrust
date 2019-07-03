<?php
  require __DIR__ . '/../vendor/autoload.php';

  $payment_amount = $_POST['payment_amount'];
  $full_name = $_POST['full_name'];
  $invoice_number = $_POST['invoice_number'];
  $receive_email = $_POST['receive_email'];
  $transaction_number = $_POST['transaction_number'];

  $email = new \SendGrid\Mail\Mail(); 
  $email->setFrom("account@immigrationtrust.co.nz", "Immigration Trust");
  $email->setSubject("Confirmation of Payment");
  $email->addTo($receive_email, $full_name);
  $email->addCc("info@immigrationtrust.co.nz", "Ivy");
  $email->addBcc("simon@immigrationtrust.co.nz", "Simon");
  $email->addDynamicTemplateData(
    new \SendGrid\Mail\Substitution("name", $full_name)
  );
  $email->addDynamicTemplateData(
    new \SendGrid\Mail\Substitution("amount", $payment_amount)
  );
  $email->addDynamicTemplateData(
    new \SendGrid\Mail\Substitution("invoice", $invoice_number)
  );
  $email->addDynamicTemplateData(
    new \SendGrid\Mail\Substitution("transaction", $transaction_number)
  );
  $email->setTemplateId(
    new \SendGrid\Mail\TemplateId("d-213e0757d26948b9aee817d77476c0e4")
  );
  $sendgrid = new \SendGrid("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");
  try {
      $response = $sendgrid->send($email);
      print $response->statusCode() . "\n";
      print_r($response->headers());
      print $response->body() . "\n";
  } catch (Exception $e) {
      echo 'Caught exception: '. $e->getMessage() ."\n";
  }
   // data sent in header are in JSON format
//    header('Content-Type: application/json');
//    // takes the value from variables and Post the data
//    $name = $_POST['name'];
//    $email = $_POST['email'];
//    $contact = $_POST['contact'];
//    $postmessage = $_POST['message'];  
//    $to = "chea.reaksmey@gmail.com";
//    $subject = "Contact Us";
//    // Email Template
//    $message = "<b>Name : </b>". $name ."<br>";
//    $message .= "<b>Contact Number : </b>".$contact."<br>";
//    $message .= "<b>Email Address : </b>".$email."<br>";
//    $message .= "<b>Message : </b>".$postmessage."<br>";

//    $header = "From:"+$email+" \r\n";
//    $header .= "MIME-Version: 1.0\r\n";
//    $header .= "Content-type: text/html\r\n";
//    $retval = mail ($to,$subject,$message,$header);
//    // message Notification
//    if( $retval == true ) {
//       echo json_encode(array(
//          'success'=> true,
//          'message' => 'Message sent successfully'
//       ));
//    }else {
//       echo json_encode(array(
//          'error'=> true,
//          'message' => 'Error sending message'
//       ));
//    }
?>