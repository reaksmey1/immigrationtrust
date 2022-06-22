<!doctype html>
<html lang="en">
<head>
	<title>Hosted Payment Demo</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="css/paystation.css">
</head>
<body>
<div class="page">
	<div class="content">
		<div class="box center">
      <h2>Make a Payment Online</h2>
      <form method="post" action="checkout.php">
        <ul class="form-style-1">
            <li>
              <label>Full Name</label>
              <input type="text" name="full_name" id="full_name" class="field-long" />
            </li>
            <li>
					<label>Email Address <span class="required">*</span></label>
            <input type="email" name="emailTxt" id="email" class="field-long" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" required />
            </li>
            <li>
              <label>Invoice Number <span class="required">*</span></label>
              <input type="text" name="invoice" id="invoice_number" class="field-long" required />
            </li>
            <li>
              <label>Amount to Pay <span class="required">*</span></label>
              <input type="number" name="amount" id="payment_amount" class="field-long" required />
            </li>
			<?php
				require_once('recaptchalib.php');
				$publickey = getenv('RECAPTCHA_PUBLIC_KEY');;
				echo recaptcha_get_html($publickey);
			?>
            <li>
                <input type="submit" id="checkout_button" value="Proceed to Payment Page" />
            </li>
        </ul>
        </form>
		</div>
	</div>
	<div class="flex"></div>
	<!-- <div class="footer">
	</div> -->
</div>

<script>()=>{};let upToDateBrowserDetected=true;</script>
<script>try{upToDateBrowserDetected}catch(e){alert("Please update your browser.");}</script>
<script src="js/paystation.js"></script>
<script src="js/jquery.min.js"></script>
<script src="js/qrcode.min.js"></script>
<script>
	// Note: none of this javascript is necessary if you plan to have your checkout on a separate page.
	let _paymentFrame, _qrCode;
	const _paymentFrameWrapper = document.getElementById('payment_wrapper');
	const _paymentAmountInput = document.getElementById('payment_amount');
  const _invoiceNumberInput = document.getElementById('invoice_number');
	// const _paymentAmountButton = document.getElementById('payment_button');
	const _checkoutButton = document.getElementById('checkout_button');
	const _qrCodeWrapper = document.getElementById("payment-page-qr-code");
	let _transactionComplete = false;
	let _goingToCheckout = false;

	// This gets called whenever a new page is loaded in the iframe. This is only possible if you are using redirects.
	function onFrameLoaded(iframe) {
		// Browsers shouldn't allow access to the content of an iframe unless it is from your own domain.
		// So, if we can access the content, then the user has been redirected back to your site from paystation.
		if (Paystation.canAccessIFrame(iframe)) {
			// We have redirected back to our own site inside the iframe.
			// It is possible to grab some data from inside the frame, but it is better and often quicker to use polling to get this response as that data can be trusted.
			Paystation.closePaymentFrame(iframe);
		}
	}

	// This function will get a response every time we poll the website.
	// Most of these responses will get transaction details for an incomplete transaction while the user is still entering their details in the iframe.
	function onTransactionResponse(err, transaction) {
		if (err) {
			// have some error handling if you want
			displayResult(err);
			return;
		}

		// hasError is for all errors regardless if they come from paystation or us, which could happen before the transaction completes.
		// errorCode is a paystation response which is set after a transaction is complete. A negative error code means no error code has been returned.
		if (transaction && (transaction.errorCode > -1 || transaction.hasError)) {
			onTransactionComplete(transaction);
		}
	}

	// Remove the iframe and stop polling the transaction details. Show a response to the user.
	function onTransactionComplete(transaction) {
		if (_transactionComplete) {
			return;
		}
		_transactionComplete = true;

		window.localStorage.removeItem('txnId');
		window.localStorage.removeItem('txnUrl');
		Paystation.closePaymentFrame(_paymentFrame);
		Paystation.stopPolling();
		displayResult(transaction.errorMessage);
	}

	function displayResult(msg) {
		// Display the outcome to the user i.e. "Transaction successful" or "Insufficient funds"
		// You might want to handle these differently depending on the errorCode (transaction.errorCode)
		_paymentFrameWrapper.innerHTML = '<h1>' + msg + '</h1>';
		showQrCode(false);
    _paymentAmountInput.disabled = false;
    _invoiceNumberInput.disabled = false;
		// _paymentAmountButton.disabled = false;
		_checkoutButton.disabled = false;
	}

	function validateAmount(input, amount) {
		const valid = parseInt(amount * 100) > 0;
		if (!valid) {
			input.classList.add('invalid');
		}
		else {
			input.classList.remove('invalid');
		}
		return valid;
	}

	function openPaymentForm(txnId, paymentUrl) {
		window.localStorage.setItem('txnId', txnId);
		window.localStorage.setItem('txnUrl', paymentUrl);
		showQrCode(paymentUrl);

		_transactionComplete = false;
		_paymentFrameWrapper.innerHTML = '';
		_paymentFrame = Paystation.createPaymentFrame(_paymentFrameWrapper, paymentUrl, onFrameLoaded, true);
		_paymentFrame.classList.add('paystation-payment-frame');
		Paystation.pollTransactionDetails(txnId, onTransactionResponse);
	}

	function payHere(e) {
		const amount = parseFloat(_paymentAmountInput.value);
		if (!validateAmount(_paymentAmountInput, amount)) {
			e.preventDefault();
			return;
		}

		if (_goingToCheckout) {
			return;
		}

		e.preventDefault();
		_paymentAmountInput.disabled = true;
		// _paymentAmountButton.disabled = true;
		_checkoutButton.disabled = true;

		// create the transaction, get the URL for iframe, show it to the user, begin polling the transaction to see when they have finished paying.
		Paystation.createTransaction(amount, (err, transaction) => {
			if (err) {
				displayResult(err);
				return;
			}

			openPaymentForm(transaction.transactionId, transaction.digitalOrderUrl);
		});
	}

	function payAtCheckout() {
		_goingToCheckout = true;
	}

	function initQRCode() {
		const a = document.createElement('a');
		a.setAttribute('rel', 'nofollow');
		a.setAttribute('target', '_blank');
		_qrCode = new QRCode(a, {width: 275, height: 275});
		_qrCodeWrapper.appendChild(a);
	}

	function isMobile() {
		return /Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini/i.test(navigator.userAgent);
	}

	function showQrCode(url) {
		if (!url || isMobile()) {
			_qrCodeWrapper.style.display = 'none';
			return;
		}

		if (!_qrCode) initQRCode();

		_qrCode.makeCode(url);
		_qrCode._el.href = url;
		_qrCodeWrapper.style.display = '';
	}

	function loadPreviousTransaction() {
		const txnId = window.localStorage.getItem('txnId');
		const txnUrl = window.localStorage.getItem('txnUrl');
		if (txnId && txnUrl) {
			openPaymentForm(txnId, txnUrl);
		}
	}

	loadPreviousTransaction();
	// _paymentAmountButton.form.addEventListener('submit', payHere);
	_checkoutButton.addEventListener('click', payAtCheckout);
	_paymentAmountInput.addEventListener('blur', function () {
		validateAmount(this, parseFloat(this.value));
	});
</script>
</body>
</html>
