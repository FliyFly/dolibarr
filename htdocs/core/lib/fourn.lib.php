<?php
/* Copyright (C) 2005-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Marc Barilley		<marc@ocebo.com>
 * Copyright (C) 2011-2013  Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2022-2023  Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/fourn.lib.php
 *		\brief      Functions used by supplier invoice module
 *		\ingroup	supplier
 */

/**
 * Prepare array with list of tabs
 *
 * @param   FactureFournisseur	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function facturefourn_prepare_head(FactureFournisseur $object)
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('SupplierInvoice');
	$head[$h][2] = 'card';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/contact.php?facid='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	//if ($fac->mode_reglement_code == 'PRE')
	if (isModEnabled('paymentbybanktransfer')) {
		$nbStandingOrders = 0;
		$sql = "SELECT COUNT(pfd.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
		$sql .= " WHERE pfd.fk_facture_fourn = ".((int) $object->id);
		$sql .= " AND type = 'ban'";
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$nbStandingOrders = $obj->nb;
			}
		} else {
			dol_print_error($db);
		}
		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$object->id.'&type=bank-transfer';
		$head[$h][1] = $langs->trans('BankTransfer');
		if ($nbStandingOrders > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbStandingOrders.'</span>';
		}
		$head[$h][2] = 'standingorders';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_invoice', 'add', 'core');

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/note.php?facid='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/info.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_invoice', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_invoice', 'remove');

	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   CommandeFournisseur	$object		Object related to tabs
 * @return  array							Array of tabs to show
 */
function ordersupplier_prepare_head(CommandeFournisseur $object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("SupplierOrder");
	$head[$h][2] = 'card';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	if (isModEnabled('stock') && (!empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) || !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION) || !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE))) {
		$langs->load("stocks");
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/dispatch.php?id='.$object->id;
		$head[$h][1] = $langs->trans("OrderDispatch");

		//If dispach process running we add the number of item to dispatch into the head
		if (in_array($object->statut, array($object::STATUS_ORDERSENT, $object::STATUS_RECEIVED_PARTIALLY, $object::STATUS_RECEIVED_COMPLETELY))) {
			$sumQtyAllreadyDispatched = 0;
			$sumQtyOrdered = 0;

			if (empty($object->lines)) {
				$object->fetch_lines();
			}
			$nbLinesOrdered = count($object->lines);
			$dispachedLines = $object->getDispachedLines(1);
			$nbDispachedLines = count($dispachedLines);

			for ($line = 0 ; $line < $nbDispachedLines; $line++) {
				$sumQtyAllreadyDispatched = $sumQtyAllreadyDispatched + $dispachedLines[$line]['qty'];
			}
			for ($line = 0 ; $line < $nbLinesOrdered; $line++) {
				//If line is a product of conf to manage stocks for services
				if ($object->lines[$line]->product_type == 0 || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
					$sumQtyOrdered = $sumQtyOrdered + $object->lines[$line]->qty;
				}
			}
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.price2num($sumQtyAllreadyDispatched, 'MS').' / '.price2num($sumQtyOrdered, 'MS').'</span>';
		}

		$head[$h][2] = 'dispatch';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_order', 'add', 'core');

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Notes");
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->fournisseur->dir_output."/commande/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
	}
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_order', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'supplier_order', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	        head array with tabs
 */
function supplierorder_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('commande_fournisseur');
	$extrafields->fetch_name_optionals_label('commande_fournisseurdet');
	$extrafields->fetch_name_optionals_label('facture_fourn');
	$extrafields->fetch_name_optionals_label('facture_fourn_det');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/supplier_order.php";
	$head[$h][1] = $langs->trans("SupplierOrder");
	$head[$h][2] = 'order';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/supplier_invoice.php";
	$head[$h][1] = $langs->trans("SuppliersInvoice");
	$head[$h][2] = 'invoice';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/supplier_payment.php";
	$head[$h][1] = $langs->trans("SuppliersPayment");
	$head[$h][2] = 'supplierpayment';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'supplierorder_admin');

	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierorder_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierOrders");
	$nbExtrafields = $extrafields->attributes['commande_fournisseur']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'supplierorder';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierorderdet_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierOrdersLines");
	$nbExtrafields = $extrafields->attributes['commande_fournisseurdet']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'supplierorderdet';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierinvoice_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierInvoices");
	$nbExtrafields = $extrafields->attributes['facture_fourn']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'supplierinvoice';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/supplierinvoicedet_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSupplierInvoicesLines");
	$nbExtrafields = $extrafields->attributes['facture_fourn_det']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'supplierinvoicedet';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'supplierorder_admin', 'remove');

	return $head;
}
