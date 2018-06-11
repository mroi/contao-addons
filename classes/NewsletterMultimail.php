<?php

namespace Addons;

/* send newsletter mails to multiple comma-separated recipients */
class NewsletterMultimail extends \Contao\Newsletter {

	public function loadDataContainerHook($strName) {
		if ($strName == 'tl_newsletter_recipients') {
			// recipients can contain multiple email addresses
			$GLOBALS['TL_DCA']['tl_newsletter_recipients']['fields']['email']['eval']['rgxp'] = 'emails';
			$GLOBALS['TL_DCA']['tl_newsletter_recipients']['fields']['email']['eval']['maxlength'] = 255;
		}
	}

	protected function sendNewsletter(\Email $mail, \Database\Result $newsletter, $recipient, $text, $html, $css = null) {
		// split comma-separated recipients and call original function multiple times
		$addresses = trimsplit(',', $recipient['email']);
		foreach ($addresses as $address) {
			$recipient['email'] = $address;
			parent::sendNewsletter($mail, $newsletter, $recipient, $text, $html, $css);
		}
	}
}
