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
    new \SendGrid\Mail\TemplateId(getenv('SEND_GRID_TEMPLATE_ID'))
  );
  $sendgrid = new \SendGrid(getenv('SEND_GRID_API_ID'));
  try {
      $response = $sendgrid->send($email);
      print $response->statusCode() . "\n";
      print_r($response->headers());
      print $response->body() . "\n";
  } catch (Exception $e) {
      echo 'Caught exception: '. $e->getMessage() ."\n";
  }
?>