<?php
namespace Mroi\ContaoAddons\Utilities;


/* add global operation to download tables as CSV files */
class CSVDownloader {

	/* add a CSV download operation to these DCAs,
	 * the given fields will be exported in the CSV */
	private $arrDCAs = array(
		'tl_member' => array('lastname', 'firstname', 'groups', 'street', 'postal', 'city', 'phone', 'mobile', 'fax', 'email')
	);

	/* URL routing key for the CSV download operation */
	const KEY = 'csv';

	/* delimiter for compound values */
	const DELIM = ', ';

	/* cache for foreign key resolution */
	private $arrForeignCache = array();

	public function onLoadDataContainer($strName) {
		if (array_key_exists($strName, $this->arrDCAs)) {
			// create the CSV download operation
			$arrOperation = array(
				'label' => &$GLOBALS['TL_LANG']['MSC']['csv_export'],
				'href'  => 'key=' . self::KEY,
				'class' => 'header_icon',
				'attributes' => 'style="background-image:url(system/themes/flexible/icons/db.svg)"'
			);
			// add the operation to the DCA
			$GLOBALS['TL_DCA'][$strName]['list']['global_operations'] = array_merge(
				array('csv' => $arrOperation),
				$GLOBALS['TL_DCA'][$strName]['list']['global_operations']
			);
			// register callback key with all modules using this table
			foreach ($GLOBALS['BE_MOD'] as &$arrGroup) {
				foreach ($arrGroup as $strModuleName => &$arrModule) {
					if (isset($arrModule['tables']) && in_array($strName, $arrModule['tables'])) {
						if (!isset($arrModule[self::KEY]))
							$arrModule[self::KEY] = array(get_class(), 'exportCSV');
						else
							\Contao\System::log('Module "' . $strModuleName . '" already contains key  "' . self::KEY . '"', __METHOD__, TL_ERROR);
					}
				}
			}
		}
	}

	public function onLoadLanguageFile($strName, $strLanguage) {
		if (array_key_exists($strName, $this->arrDCAs)) {
			switch (true) {
			case substr($strLanguage, 0, 2) == 'de':
				$GLOBALS['TL_LANG']['MSC']['csv_export'][0] = 'CSV-Export';
				$GLOBALS['TL_LANG']['MSC']['csv_export'][1] = 'Daten als CSV-Datei herunterladen';
				break;
			default:
				$GLOBALS['TL_LANG']['MSC']['csv_export'][0] = 'CSV export';
				$GLOBALS['TL_LANG']['MSC']['csv_export'][1] = 'Download data as a CSV file';
				break;
			}
		}
	}

	public function exportCSV(\Contao\DataContainer $dc) {
		// make sure we are operating on a simple table (i.e. not a tree)
		if (!is_a($dc, \Contao\DC_Table::class) || !empty($dc->rootIds)) {
			\Contao\System::log('Data container "' . $dc->table . '" not suitable for CSV export', __METHOD__, TL_ERROR);
			exit;
		}

		// try to use localized module name as file name
		if (is_array($GLOBALS['TL_LANG']['MOD'][\Contao\Input::get('do')]))
			$strFileName = $GLOBALS['TL_LANG']['MOD'][\Contao\Input::get('do')][0];
		else
			$strFileName = $dc->table;
		$strFileName .= '.csv';

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $strFileName);
		$resOutput = fopen('php://output', 'w');

		// localized table headers
		$arrHeaders = array();
		foreach ($this->arrDCAs[$dc->table] as $field) {
			if (is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['label']))
				$arrHeaders[] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['label'][0];
			else
				$arrHeaders[] = $field;
		}
		fputcsv($resOutput, $arrHeaders);

		$strQuery  = 'SELECT ' . implode(', ', $this->arrDCAs[$dc->table]);
		$strQuery .= ' FROM ' . $dc->table;
		$strQuery .= ' ORDER BY ' . implode(', ', $this->arrDCAs[$dc->table]);

		$objRow = $dc->Database->prepare($strQuery)->execute();
		while ($objRow->next()) {
			$arrRow = $objRow->row();
			foreach ($arrRow as $column => &$value) {
				$value = $this->prettyPrint($dc, $column, $value);
			}
			fputcsv($resOutput, $arrRow);
		}

		fclose($resOutput);
		exit;
	}

	/* format one element of a result row */
	private function prettyPrint(\Contao\DataContainer $dc, $column, $value) {
		// deserialize compound value
		$arrValue = deserialize($value);
		if (!is_array($arrValue)) {
			// also handle non-compound value as array
			$arrValue = array($value);
		}

		// resolve foreign key references
		if (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$column]['foreignKey'])) {
			$foreignKey = explode('.', $GLOBALS['TL_DCA'][$dc->table]['fields'][$column]['foreignKey'], 2);
			foreach ($arrValue as &$sub) {
				$cacheKey = "{$foreignKey[0]}.{$foreignKey[1]}.{$sub}";
				if (!isset($this->arrForeignCache[$cacheKey])) {
					$objForeign = $dc->Database
						->prepare('SELECT ' . $foreignKey[1] . ' AS value FROM ' . $foreignKey[0] . ' WHERE id=?')
						->limit(1)
						->execute($sub);
					if ($objForeign->numRows)
						$this->arrForeignCache[$cacheKey] = $objForeign->value;
					else
						$this->arrForeignCache[$cacheKey] = '(?)';
					// size-constrain the cache
					if (count($this->arrForeignCache) > 128)
						array_shift($this->arrForeignCache);
				}
				$sub = $this->arrForeignCache[$cacheKey];
			}
		}

		return implode(self::DELIM, $arrValue);
	}
}
