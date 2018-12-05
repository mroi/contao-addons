<?php

namespace Addons;

/* send newsletter mails to multiple comma-separated recipients */
class NewsletterMultimail extends \Contao\Newsletter {

	public function loadDataContainerHook($strName) {
		if ($strName == 'tl_member') {
			// change validation to allow multiple email addresses
			$GLOBALS['TL_DCA']['tl_member']['fields']['email']['eval']['rgxp'] = 'emails';
			// email is not mandatory nor required unique
			$GLOBALS['TL_DCA']['tl_member']['fields']['email']['eval']['mandatory'] = false;
			$GLOBALS['TL_DCA']['tl_member']['fields']['email']['eval']['unique'] = false;
			// change the newsletter load callback
			$GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] = array_diff($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'], array(array('Newsletter', 'updateAccount')));
			$GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'][] = array('NewsletterMultimail', 'updateAccount');
			// change the newsletter save callback
			$GLOBALS['TL_DCA']['tl_member']['fields']['newsletter']['save_callback'] = array(array('NewsletterMultimail', 'synchronize'));
		}
	}

	/* The following two functions synchronize() and updateAccount() overwrite the originals
	 * in the Newsletter class. You can compare with newsletter/classes/Newsletter.php to
	 * compare the control flow. It should be relatively similar except for the places
	 * where handling multiple addresses needs to differ.
	 */
	// TODO: We may want to extract common functionality for handling multiple mail addresses
	// into functions. For example:
	// * removing an individual address only when it is not needed for another user
	// * adding an individual address only when it is not listed already

	public function synchronize($varValue, $objUser, $objModule = null) {
		if (!($objUser !== null && $objUser instanceof \DataContainer))
			return parent::synchronize($varValue, $objUser, $objModule);

		$objUser = $this->Database->prepare("SELECT * FROM tl_member WHERE id=?")
								  ->limit(1)
								  ->execute($objUser->id);
		if ($objUser->numRows < 1)
			// no such member
			return $varValue;
		if ($varValue == $objUser->newsletter || $objUser->email == '')
			// no need to change anything
			return $varValue;

		$time = time();
		$varValue = deserialize($varValue, true);
		$arrChannels = $this->Database->query("SELECT id FROM tl_newsletter_channel")->fetchEach('id');
		$arrDelete = array_values(array_diff($arrChannels, $varValue));

		// delete from unsubscribed newsletters
		if (!empty($arrDelete) && is_array($arrDelete)) {
			// TODO: do not remove, when still needed due to another member’s entry
			foreach (trimsplit(',', $objUser->email) as $strEmail) {
				$this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE pid IN(" . implode(',', array_map('intval', $arrDelete)) . ") AND email=?")
							   ->execute($strEmail);
			}
		}

		// add to newly subscribed newsletters
		foreach ($varValue as $varId) {
			$intId = intval($varId);
			if ($intId < 1) continue;

			foreach (trimsplit(',', $objUser->email) as $strEmail) {
				$objRecipient = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_newsletter_recipients WHERE pid=? AND email=?")
											->execute($intId, $strEmail);

				if ($objRecipient->count < 1) {
					$this->Database->prepare("INSERT INTO tl_newsletter_recipients SET pid=?, tstamp=$time, email=?, active=?, addedOn=?, ip=?")
								   ->execute($intId, $strEmail, ($objUser->disable ? '' : 1), '', '');
				}
			}
		}

		return serialize($varValue);
	}

	public function updateAccount() {
		$intUser = \Input::get('id');
		if (TL_MODE == 'FE') {
			$this->import('FrontendUser', 'User');
			$intUser = $this->User->id;
		}
		if (!$intUser) return;

		if (TL_MODE == 'FE' || \Input::get('act') == 'edit') {
			$objUser = $this->Database->prepare("SELECT email, disable FROM tl_member WHERE id=?")
									  ->limit(1)
									  ->execute($intUser);
			if ($objUser->numRows) {
				$strEmail = \Input::post('email', true);
				if (!empty($_POST) && $strEmail != '' && $strEmail != $objUser->email) {
					// email address has changed
					// TODO: split into individial addresses and update
					$this->Database->prepare("UPDATE tl_newsletter_recipients SET email=? WHERE email=?")
												   ->execute($strEmail, $objUser->email);
					$objUser->email = $strEmail;
				}

				// the user is subscribed to a newsletter when all email addresses are listed
				$arrSubscriptions = $this->Database->query("SELECT id FROM tl_newsletter_channel")->fetchEach('id');
				foreach (trimsplit(',', $objUser->email) as $strEmail) {
					$objSubscriptions = $this->Database->prepare("SELECT pid FROM tl_newsletter_recipients WHERE email=?")
													   ->execute($strEmail);

					if ($objSubscriptions->numRows)
						$arrSubscriptions = array_intersect($arrSubscriptions, $objSubscriptions->fetchEach('pid'));
					else
						$arrSubscriptions = array();
				}
				if (empty($arrSubscriptions) || empty($objUser->email))
					$strNewsletters = '';
				else
					$strNewsletters = serialize($arrSubscriptions);

				$this->Database->prepare("UPDATE tl_member SET newsletter=? WHERE id=?")
							   ->execute($strNewsletters, $intUser);

				if (TL_MODE == 'FE') {
					$this->User->newsletter = $strNewsletters;
				} elseif (!empty($_POST) && \Input::post('disable') != $objUser->disable) {
					// user activation status has changed
					// TODO: update mail addresses individually
					$this->Database->prepare("UPDATE tl_newsletter_recipients SET active=? WHERE email=?")
								   ->execute((\Input::post('disable') ? '' : 1), $objUser->email);

					$objUser->disable = \Input::post('disable');
				}
			}
		} elseif (\Input::get('act') == 'delete') {
			$objUser = $this->Database->prepare("SELECT email FROM tl_member WHERE id=?")
									  ->limit(1)
									  ->execute($intUser);

			// TODO: delete mail addresses unless needed due to another member’s entry
			if ($objUser->numRows) {
				$this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE email=?")
							   ->execute($objUser->email);
			}
		}
	}
}
