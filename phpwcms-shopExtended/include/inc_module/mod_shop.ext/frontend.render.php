<?php
/**
 * phpwcms content management system
 *
 * @author Oliver Georgi <oliver@phpwcms.de>
 * @copyright Copyright (c) 2002-2013, Oliver Georgi
 * @license http://opensource.org/licenses/GPL-2.0 GNU GPL-2
 * @link http://www.phpwcms.de
 *
 **/

// ----------------------------------------------------------------
// obligate check for phpwcms constants
if (!defined('PHPWCMS_ROOT')) {
   die("You Cannot Access This Script Directly, Have a Nice Day.");
}
// ----------------------------------------------------------------

// Module/Plug-in Shop & Products

$_shop_load_cat  		= strpos($content['all'], '{SHOP_CATEGOR');
$_shop_load_list 		= strpos($content['all'], '{SHOP_PRODUCTLIST}');
$_shop_load_cart_small	= strpos($content['all'], '{CART_SMALL}');
$_shop_load_order		= strpos($content['all'], '{SHOP_ORDER_PROCESS}');

// set preferences
$_shopPref				= array();

if(_getConfig( 'shop_pref_felang' )) {
	define('SHOP_FELANG_SUPPORT', true);
	define('SHOP_FELANG_SQL', " AND (shopprod_lang='' OR shopprod_lang="._dbEscape($phpwcms['default_lang']).')');
	define('CART_KEY', 'shopping_cart_'.$phpwcms['default_lang']);
} else {
	define('SHOP_FELANG_SUPPORT', false);
	define('SHOP_FELANG_SQL', '');
	define('CART_KEY', 'shopping_cart');
}

// set CART session value
if(!isset($_SESSION[CART_KEY])) {
	$_SESSION[CART_KEY] = array();
}
// reset cart session error var to allow cart listing
if(isset($_getVar['shop_cart']) && $_getVar['shop_cart'] == 'show') {
	unset($_SESSION[CART_KEY]['error'], $_getVar['cart'], $_GET['cart']);
}

if( $_shop_load_cat !== false || $_shop_load_list !== false || $_shop_load_order !== false || $_shop_load_cart_small !== false) {

	// load template
	$_tmpl = array( 'config' => array(), 'source' => '', 'lang' => $phpwcms['default_lang'] );

	// Check against language specific shop template
	if(is_file($phpwcms['modules']['shop']['path'].'template/'.$phpwcms['default_lang'].'.html')) {
		$_tmpl['source'] = @file_get_contents($phpwcms['modules']['shop']['path'].'template/'.$phpwcms['default_lang'].'.html');
	} else {
		$_tmpl['source'] = @file_get_contents($phpwcms['modules']['shop']['path'].'template/default.html');
	}

	if($_tmpl['source']) {

		$_tmpl['config'] = parse_ini_str(get_tmpl_section('CONFIG', $_tmpl['source']), false);
		
		// Uncomment if CMS is released before 2013-10-01
		/*
		if(!function_exists('phpwcms_boolval')) {
			function phpwcms_boolval(&$BOOL, &$STRICT=false) {
				return boolval($BOOL, $STRICT);
			}
		}
		*/

		$_tmpl['config']['cat_list_products']		= empty($_tmpl['config']['cat_list_products']) ? false : phpwcms_boolval($_tmpl['config']['cat_list_products']);
		$_tmpl['config']['image_list_lightbox']		= empty($_tmpl['config']['image_list_lightbox']) ? false : phpwcms_boolval($_tmpl['config']['image_list_lightbox']);
		$_tmpl['config']['image_detail_lightbox']	= empty($_tmpl['config']['image_detail_lightbox']) ? false : phpwcms_boolval($_tmpl['config']['image_detail_lightbox']);
		$_tmpl['config']['image_detail_crop']		= empty($_tmpl['config']['image_detail_crop']) ? false : phpwcms_boolval($_tmpl['config']['image_detail_crop']);
		$_tmpl['config']['image_list_crop']			= empty($_tmpl['config']['image_list_crop']) ? false : phpwcms_boolval($_tmpl['config']['image_list_crop']);

		// handle custom fields
		$_tmpl['config']['shop_field'] = array();
		$custom_field_number = 1;
		while( !empty( $_tmpl['config']['shop_field_' . $custom_field_number] ) ) {

			$custom_field_type = explode('_', trim($_tmpl['config']['shop_field_' . $custom_field_number]) );
			if($custom_field_type[0] === 'STRING' || $custom_field_type[0] === 'TEXTAREA' || $custom_field_type[0] === 'CHECK') {
				$_tmpl['config']['shop_field'][ $custom_field_number ]['type'] = $custom_field_type[0];
				if(isset($custom_field_type[1]) && $custom_field_type[1] == 'REQ') {
					$_tmpl['config']['shop_field'][ $custom_field_number ]['required'] = true;
					if(empty($custom_field_type[2])) {
						$_tmpl['config']['shop_field'][ $custom_field_number ]['label'] = 'Custom '.$custom_field_number;
					} else {
						$_tmpl['config']['shop_field'][ $custom_field_number ]['label'] = trim($custom_field_type[2]);
					}
				} elseif(empty($custom_field_type[1])) {
					$_tmpl['config']['shop_field'][ $custom_field_number ]['required'] = false;
					$_tmpl['config']['shop_field'][ $custom_field_number ]['label'] = 'Custom '.$custom_field_number;
				} else {
					$_tmpl['config']['shop_field'][ $custom_field_number ]['required'] = false;
					$_tmpl['config']['shop_field'][ $custom_field_number ]['label'] = trim($custom_field_type[1]);
				}
				if($custom_field_type[0] === 'CHECK') {
					if($_tmpl['config']['shop_field'][ $custom_field_number ]['required']) {
						$_tmpl['config']['shop_field'][ $custom_field_number ]['value'] = empty($custom_field_type[3]) ? 1 : trim($custom_field_type[3]);
					} else {
						$_tmpl['config']['shop_field'][ $custom_field_number ]['value'] = empty($custom_field_type[2]) ? 1 : trim($custom_field_type[2]);
					}
				}
			}
			$custom_field_number++;
		}

		if($_shop_load_list) {
			$_tmpl['list_header']	= get_tmpl_section('LIST_HEADER',	$_tmpl['source']);
			$_tmpl['list_entry']	= get_tmpl_section('LIST_ENTRY',	$_tmpl['source']);
			$_tmpl['list_space']	= get_tmpl_section('LIST_SPACE',	$_tmpl['source']);
			$_tmpl['list_none']		= get_tmpl_section('LIST_NONE',		$_tmpl['source']);
			$_tmpl['list_footer']	= get_tmpl_section('LIST_FOOTER',	$_tmpl['source']);
			$_tmpl['detail']		= get_tmpl_section('DETAIL',		$_tmpl['source']);
			$_tmpl['image_space']	= get_tmpl_section('IMAGE_SPACE',	$_tmpl['source']);
		}

		if($_shop_load_cart_small) {
			$_tmpl['cart_small']	= get_tmpl_section('CART_SMALL',	$_tmpl['source']);
		}

		if($_shop_load_order) {
			$_tmpl['cart_header']	= get_tmpl_section('CART_HEADER',			$_tmpl['source']);
			$_tmpl['cart_entry']	= get_tmpl_section('CART_ENTRY',			$_tmpl['source']);
			$_tmpl['cart_space']	= get_tmpl_section('CART_SPACE',			$_tmpl['source']);
			$_tmpl['cart_footer']	= get_tmpl_section('CART_FOOTER',			$_tmpl['source']);
			$_tmpl['cart_none']		= get_tmpl_section('CART_NONE',				$_tmpl['source']);
			$_tmpl['inv_address']	= get_tmpl_section('ORDER_INV_ADDRESS',		$_tmpl['source']);
			$_tmpl['order_terms']	= get_tmpl_section('ORDER_TERMS',			$_tmpl['source']);
			$_tmpl['term_entry']	= get_tmpl_section('ORDER_TERMS_ITEM',		$_tmpl['source']);
			$_tmpl['term_space']	= get_tmpl_section('ORDER_TERMS_ITEMSPACE',	$_tmpl['source']);
			$_tmpl['mail_customer']	= get_tmpl_section('MAIL_CUSTOMER',			$_tmpl['source']);
			$_tmpl['mail_neworder']	= get_tmpl_section('MAIL_NEWORDER',			$_tmpl['source']);
			$_tmpl['order_success']	= get_tmpl_section('ORDER_DONE',			$_tmpl['source']);
			$_tmpl['order_failed']	= get_tmpl_section('ORDER_NOT_DONE',		$_tmpl['source']);
			$_tmpl['mail_item']		= get_tmpl_section('MAIL_ITEM',				$_tmpl['source']);
		}
	}

	// merge config settings like translations and so on
	$_tmpl['config'] = array_merge(	array(
							'cat_all'					=> '@@All products@@',
							'cat_list_products'			=> false,
							'cat_subcat_spacer'			=> ' / ',
							'price_decimals'			=> 2,
							'vat_decimals'				=> 0,
							'weight_decimals'			=> 0,
							'dec_point'					=> ".",
							'thousands_sep'				=> ",",
							'image_list_width'			=> 200,
							'image_list_height'			=> 200,
							'image_detail_width'		=> 200,
							'image_detail_height'		=> 200,
							'image_zoom_width'			=> 750,
							'image_zoom_height'			=> 500,
							'image_list_lightbox'		=> false,
							'image_detail_lightbox'		=> true,
							'image_detail_crop'			=> false,
							'image_list_crop'			=> false,
							'mail_customer_subject'		=> "[#{ORDER}] Your order at MyShop",
							'mail_neworder_subject'		=> "[#{ORDER}] New order",
							'label_payby_prepay'		=> "@@Cash with order@@",
							'label_payby_pod'			=> "@@Cash on delivery@@",
							'label_payby_onbill'		=> "@@On account@@",
							'order_number_style'		=> 'RANDOM',
							'cat_list_sort_by'			=> 'shopprod_name1 ASC',
							'shop_css'					=> '',
							'shop_wrap'					=> '',
							'image_detail_more_width'	=> 50,
							'image_detail_more_height'	=> 50,
							'image_detail_more_crop'	=> false,
							'image_detail_more_start'	=> 1,
							'image_detail_more_lightbox'=> false,
							'files_direct_download'		=> false,
							'files_template'			=> '', // default
							'on_request_trigger'		=> -999
						),	$_tmpl['config'] );

	foreach( array( 'shop_pref_currency', 'shop_pref_unit_weight', 'shop_pref_vat', 'shop_pref_email_to',
					'shop_pref_email_from', 'shop_pref_email_paypal', 'shop_pref_shipping', 'shop_pref_shipping_calc',
					'shop_pref_payment', 'shop_pref_discount', 'shop_pref_loworder' ) as $value ) {
		_getConfig( $value, '_shopPref' );
	}

	if(!isset($_tmpl['config']['shop_url'])) {
		$_tmpl['config']['shop_url'] = _getConfig( 'shop_pref_id_shop', '_shopPref' );
	}
	if(!isset($_tmpl['config']['cart_url'])) {
		$_tmpl['config']['cart_url'] = _getConfig( 'shop_pref_id_cart', '_shopPref' );
	}

	if(!is_intval($_tmpl['config']['shop_url']) && is_string($_tmpl['config']['shop_url'])) {
		$_tmpl['config']['shop_url']	= trim($_tmpl['config']['shop_url']);
	} elseif(is_intval($_tmpl['config']['shop_url']) && intval($_tmpl['config']['shop_url'])) {
		$_tmpl['config']['shop_url']	= 'aid='.intval($_tmpl['config']['shop_url']);
	} else {
		$_tmpl['config']['shop_url']	= $aktion[1] ? 'aid='.$aktion[1] : 'id='.$aktion[0];
	}

	if(!is_intval($_tmpl['config']['cart_url']) && is_string($_tmpl['config']['cart_url'])) {
		$_tmpl['config']['cart_url']	= trim($_tmpl['config']['cart_url']);
	} elseif(is_intval($_tmpl['config']['cart_url']) && intval($_tmpl['config']['cart_url'])) {
		$_tmpl['config']['cart_url']	= 'aid='.intval($_tmpl['config']['cart_url']);
	} else {
		$_tmpl['config']['cart_url']	= $aktion[1] ? 'aid='.$aktion[1] : 'id='.$aktion[0];
	}

	if($_tmpl['config']['shop_wrap']) {
		$_tmpl['config']['shop_wrap'] = explode('|', $_tmpl['config']['shop_wrap']);
		$_tmpl['config']['shop_wrap'] = array(
			'prefix' => trim($_tmpl['config']['shop_wrap'][0]) . LF,
			'suffix' => empty($_tmpl['config']['shop_wrap'][1]) ? '' : LF . trim($_tmpl['config']['shop_wrap'][1])
		);
	} else {
		$_tmpl['config']['shop_wrap'] = array('prefix'=>'', 'suffix'=>'');
	}

	if($_tmpl['config']['shop_css']) {
		renderHeadCSS($_tmpl['config']['shop_css']);
	}

	// OK get cart post data
	if( isset($_POST['shop_action']) && $_POST['shop_action'] == 'add') {

		$shop_prod_id		= abs(intval($_POST['shop_prod_id']));
		$shop_prod_amount	= abs(intval($_POST['shop_prod_amount']));
		$shop_prod_cartadd	= false;

		if(!empty($shop_prod_id) && !empty($shop_prod_amount)) {

			//wr begin changed 29.06.12
			// check for selections in $_POST
			// the session var is now prod id|opt1 id|opt2 id
			// addings with no options result in: prod id|0|0
			$opt_1 = isset($_POST['prod_opt1']) ? intval($_POST['prod_opt1']) : 0;
			$opt_2 = isset($_POST['prod_opt2']) ? intval($_POST['prod_opt2']) : 0;

			// Test against product options
			if(!isset($_POST['prod_opt1']) && !isset($_POST['prod_opt2'])) {

				$shop_prod_cartadd = true;

			} elseif(isset($_POST['prod_opt1']) && isset($_POST['prod_opt2']) && $opt_1 && $opt_2) {

				$shop_prod_cartadd = true;

			} elseif(isset($_POST['prod_opt1']) && !isset($_POST['prod_opt2']) && $opt_1) {

				$shop_prod_cartadd = true;

			} elseif(isset($_POST['prod_opt2']) && !isset($_POST['prod_opt1']) && $opt_2) {

				$shop_prod_cartadd = true;

			} else {

				$data = _dbGet('phpwcms_shop_products', 'shopprod_size,shopprod_color', 'shopprod_status=1 AND shopprod_id='.$shop_prod_id);
				
				if(isset($data[0]['shopprod_size'])) {
					$data[0]['shopprod_size']	= trim($data[0]['shopprod_size']);
					$data[0]['shopprod_color']	= trim($data[0]['shopprod_color']);

					if($data[0]['shopprod_size'] === '' && $data[0]['shopprod_color'] === '') {
						$shop_prod_cartadd = true;
					}
				}
			}

			if($shop_prod_cartadd) {

				// add product to shopping
				if(isset($_SESSION[CART_KEY]['products'][$shop_prod_id][$opt_1][$opt_2])) {
					$_SESSION[CART_KEY]['products'][$shop_prod_id][$opt_1][$opt_2] += $shop_prod_amount;
					$_SESSION[CART_KEY]['options1'][$shop_prod_id][$opt_1][$opt_2] = $opt_1;
					$_SESSION[CART_KEY]['options2'][$shop_prod_id][$opt_1][$opt_2] = $opt_2;
				} else {
					$_SESSION[CART_KEY]['products'][$shop_prod_id][$opt_1][$opt_2] = $shop_prod_amount;
					$_SESSION[CART_KEY]['options1'][$shop_prod_id][$opt_1][$opt_2] = $opt_1;
					$_SESSION[CART_KEY]['options2'][$shop_prod_id][$opt_1][$opt_2] = $opt_2;
				}
				//this sessionvar holds the products for the small cart
				if(isset($_SESSION[CART_KEY]['total'][$shop_prod_id.$opt_1.$opt_2])) {
					$_SESSION[CART_KEY]['total'][$shop_prod_id.$opt_1.$opt_2] += $shop_prod_amount;
				} else {
					$_SESSION[CART_KEY]['total'][$shop_prod_id.$opt_1.$opt_2]  = $shop_prod_amount;
				}

			} else {
				
				// Set Cart error
				
			}

		}

	} elseif( isset($_POST['shop_prod_amount']) && is_array($_POST['shop_prod_amount']) ) {

		//wr begin changed 29.06.12
		// loop through options to get the amount

		foreach($_POST['shop_prod_amount'] as $prod_id => $value_opt1) {
			foreach($value_opt1 as $opt_1 => $value_opt2) {
				foreach($value_opt2 as $opt_2 => $prod_qty) {
					$prod_id	= intval($prod_id);
					$prod_qty	= intval($prod_qty);
					$opt_1		= intval($opt_1);
					$opt_2		= intval($opt_2);
					if(isset($_SESSION[CART_KEY]['products'][$prod_id][$opt_1][$opt_2])) {
						if($prod_qty) {
							$_SESSION[CART_KEY]['products'][$prod_id][$opt_1][$opt_2] = $prod_qty;
						} else {
							unset($_SESSION[CART_KEY]['products'][$prod_id][$opt_1][$opt_2]);
							unset($_SESSION[CART_KEY]['total'][$prod_id.$opt_1.$opt_2]);
						}
					}
				}
			}
		}
		//wr end changed 29.06.12

	} elseif( isset($_POST['shop_order_step1']) ) {

		// handle invoice address -> checkout

		$_SESSION[CART_KEY]['step1'] = array(

			'INV_FIRSTNAME'	=> isset($_POST['shop_inv_firstname']) ? clean_slweg($_POST['shop_inv_firstname']) : '',
			'INV_NAME'		=> isset($_POST['shop_inv_name']) ? clean_slweg($_POST['shop_inv_name']) : '',
			'INV_ADDRESS'	=> isset($_POST['shop_inv_address']) ? clean_slweg($_POST['shop_inv_address']) : '',
			'INV_ZIP'		=> isset($_POST['shop_inv_zip']) ? clean_slweg($_POST['shop_inv_zip']) : '',
			'INV_CITY'		=> isset($_POST['shop_inv_city']) ? clean_slweg($_POST['shop_inv_city']) : '',
			'INV_REGION'	=> isset($_POST['shop_inv_region']) ? clean_slweg($_POST['shop_inv_region']) : '',
			'INV_COUNTRY'	=> isset($_POST['shop_inv_country']) ? clean_slweg($_POST['shop_inv_country']) : '',
			'EMAIL'			=> isset($_POST['shop_email']) ? clean_slweg($_POST['shop_email']) : '',
			'PHONE'			=> isset($_POST['shop_phone']) ? clean_slweg($_POST['shop_phone']) : ''

					);

		// retrieve all custom field POST data
		foreach($_tmpl['config']['shop_field'] as $key => $row) {

			$_SESSION[CART_KEY]['step1']['shop_field_'.$key] = empty($_POST['shop_field_'.$key]) ? '' : clean_slweg($_POST['shop_field_'.$key]);
			if($row['required'] && $_SESSION[CART_KEY]['step1']['shop_field_'.$key] === '') {
				$ERROR['inv_address']['shop_field_'.$key] = $row['required'] . ' must be filled';
			}
		}

		$payment_options = get_payment_options();
		if(!empty($_POST['shopping_payment']) && isset($payment_options[$_POST['shopping_payment']])) {
			$_SESSION[CART_KEY]['payby'] = $_POST['shopping_payment'];
		} else {
			$ERROR['inv_address']['payment'] = true;
		}

		if(empty($_SESSION[CART_KEY]['step1']['INV_FIRSTNAME'])) {
			$ERROR['inv_address']['INV_FIRSTNAME'] = '@@First name must be filled@@';
		}
		if(empty($_SESSION[CART_KEY]['step1']['INV_NAME'])) {
			$ERROR['inv_address']['INV_NAME'] = '@@Name must be filled@@';
		}
		if(empty($_SESSION[CART_KEY]['step1']['INV_ADDRESS'])) {
			$ERROR['inv_address']['INV_ADDRESS'] = '@@Address must be filled@@';
		}
		if(empty($_SESSION[CART_KEY]['step1']['INV_ZIP'])) {
			$ERROR['inv_address']['INV_ZIP'] = '@@ZIP must be filled@@';
		}
		if(empty($_SESSION[CART_KEY]['step1']['INV_CITY'])) {
			$ERROR['inv_address']['INV_CITY'] = '@@City must be filled@@';
		}
		if(empty($_SESSION[CART_KEY]['step1']['EMAIL']) || !is_valid_email($_SESSION[CART_KEY]['step1']['EMAIL'])) {
			$ERROR['inv_address']['EMAIL'] = '@@Email must be filled or is invalid@@';
		}
		if(empty($_SESSION[CART_KEY]['step1']['PHONE'])) {
			$ERROR['inv_address']['PHONE'] = '@@Phone must be filled@@';
		}
		if(isset($ERROR['inv_address']) && count($ERROR['inv_address'])) {
			$_SESSION[CART_KEY]['error']['step1'] = true;
		} elseif(isset($_SESSION[CART_KEY]['error']['step1'])) {
			unset($_SESSION[CART_KEY]['error']['step1']);
		}


	} elseif( isset($_POST['shop_order_submit']) ) {

		if(empty($_POST['shop_terms_agree'])) {
			$_SESSION[CART_KEY]['error']['step2'] = true;
		} elseif(isset($_SESSION[CART_KEY]['error']['step2'])) {
			unset($_SESSION[CART_KEY]['error']['step2']);
		}

	} elseif( isset($_SESSION[CART_KEY]['error']['step2']) && !isset($_POST['shop_order_submit'])) {

		unset($_SESSION[CART_KEY]['error']['step2']);

	}

}


// first we take categories
if( $_shop_load_cat !== false ) {

	preg_match('/\{SHOP_CATEGORY:(\d+)\}/', $content['all'], $catmatch);
	if(!empty($catmatch[1])) {
		$shop_limited_cat = true;
		$shop_limited_catid = intval($catmatch[1]);
		if(empty($GLOBALS['_getVar']['shop_cat'])) {
			$GLOBALS['_getVar']['shop_cat'] = $shop_limited_catid;
		}
	} else {
		$shop_limited_cat = false;
	}


	$sql  = 'SELECT * FROM '.DB_PREPEND.'phpwcms_categories WHERE ';
	$sql .= "cat_type='module_shop' AND cat_status=1 AND cat_pid=0 ";
	if($shop_limited_cat) {
		$sql .= 'AND cat_id = ' . $shop_limited_catid . ' ';
	}
	$sql .= 'ORDER BY cat_sort DESC, cat_name ASC';
	$data = _dbQuery($sql);

	$shop_cat = array();

	$shop_cat_selected	= isset($GLOBALS['_getVar']['shop_cat']) ? $GLOBALS['_getVar']['shop_cat'] : 'all';
	if(strpos($shop_cat_selected, '_')) {
		$shop_cat_selected = explode('_', $shop_cat_selected, 2);
		if(isset($shop_cat_selected[1])) {
			$shop_subcat_selected	= intval($shop_cat_selected[1]);
		}
		$shop_cat_selected = intval($shop_cat_selected[0]);
		if(!$shop_cat_selected) {
			$shop_cat_selected		= 'all';
			$shop_subcat_selected	= 0;
		}
	} else {
		$shop_subcat_selected = 0;
	}

	$shop_detail_id = isset($GLOBALS['_getVar']['shop_detail']) ? intval($GLOBALS['_getVar']['shop_detail']) : 0;
	unset($GLOBALS['_getVar']['shop_cat'], $GLOBALS['_getVar']['shop_detail']);

	if($shop_detail_id) {
		$GLOBALS['_getVar']['shop_detail'] = $shop_detail_id;
	}

	if(is_array($data) && count($data)) {

		$x = 0;

		foreach($data as $row) {

			if($shop_limited_cat && $row['cat_id'] != $shop_limited_catid) {
				continue;
			}

			$shop_cat_prods = '';
			$shop_cat[$x]   = '<li id="shopcat-'.$row['cat_id'].'"';
			if($row['cat_id'] == $shop_cat_selected) {
				$shop_cat[$x] .= ' class="active"';

				// now try to retrieve sub categories for active category
				$sql  = 'SELECT * FROM '.DB_PREPEND.'phpwcms_categories WHERE ';
				$sql .= "cat_type='module_shop' AND cat_status=1 AND cat_pid=" . $shop_cat_selected ;
				$sql .= ' ORDER BY cat_sort DESC, cat_name ASC';
				$sdata = _dbQuery($sql);

				$subcat_count = count($sdata);

				$selected_product_cat = $subcat_count && $shop_subcat_selected ? $shop_subcat_selected : $shop_cat_selected;

				if($subcat_count) {

					$shop_subcat = array();
					$z = 0;
					foreach($sdata as $srow) {

						$shop_subcat[$z]   = '<li id="shopsubcat-'.$row['cat_id'].'"';
						if($srow['cat_id'] == $shop_subcat_selected) {
							$shop_subcat[$z] .= ' class="active"';
						}

						$shop_subcat[$z] .= '><a href="' . rel_url(array('shop_cat' => $srow['cat_pid'] . '_' . $srow['cat_id']), array('shop_detail', 'shop_cart'), $_tmpl['config']['shop_url']) . '">@@';
						$shop_subcat[$z] .= html_specialchars($srow['cat_name']);
						$shop_subcat[$z] .= '@@</a>';
						if($srow['cat_id'] == $shop_subcat_selected && $_tmpl['config']['cat_list_products']) {
							$shop_subcat[$z] .= get_category_products($srow['cat_id'], $shop_detail_id, $shop_cat_selected, $shop_subcat_selected, $_tmpl['config']['shop_url']);
						}
						$shop_subcat[$z] .= '</li>';

						$z++;
					}

					if(count($shop_subcat)) {
						$shop_cat_prods = LF . '		<ul>' . LF.'			' . implode(LF.'			', $shop_subcat) . LF .'		</ul>' . LF.'	';
					}

				}

				if($_tmpl['config']['cat_list_products']) {
					 $shop_cat_prods .= get_category_products($shop_cat_selected, $shop_detail_id, $shop_cat_selected, $shop_subcat_selected, $_tmpl['config']['shop_url']);
				}

			}
			$shop_cat[$x] .= '><a href="' . rel_url(array('shop_cat' => $row['cat_id']), array('shop_detail', 'shop_cart'), $_tmpl['config']['shop_url']) . '">@@';
			$shop_cat[$x] .= html_specialchars($row['cat_name']);
			$shop_cat[$x] .= '@@</a>' . $shop_cat_prods;
			$shop_cat[$x] .= '</li>';

			$x++;
		}

	}

	if( count($shop_cat) ) {

		if( ! $shop_limited_cat ) {
			$shop_cat[$x]  = '<li id="shopcat-all"';
			if($shop_cat_selected == 'all') {
				$shop_cat[$x] .= ' class="active"';
			}
			$shop_cat[$x] .= '><a href="' . rel_url(array('shop_cat' => 'all'), array('shop_detail', 'shop_cart'), $_tmpl['config']['shop_url']) . '">@@';
			$shop_cat[$x] .= html_specialchars($_tmpl['config']['cat_all']);
			$shop_cat[$x] .= '@@</a>';
			$shop_cat[$x] .= '</li>';
		}
		$shop_cat = '<ul class="'.$template_default['classes']['shop-category-menu'].'">' . LF.'	' . implode(LF.'	', $shop_cat) . LF . '</ul>';

	} else {

		$shop_cat = '';

	}

	$content['all'] = str_replace('{SHOP_CATEGORIES}', $shop_cat, $content['all']);
	$content['all'] = preg_replace('/\{SHOP_CATEGORY:\d+\}/', $shop_cat, $content["all"]);

	if($shop_cat_selected) {
		$GLOBALS['_getVar']['shop_cat'] = $shop_cat_selected;
		if($shop_subcat_selected) {
			$GLOBALS['_getVar']['shop_cat'] .= '_' . $shop_subcat_selected;
		}
	}

}


// Ok lets search for product listing
if( $_shop_load_list !== false ) {

	// check selected category
	$shop_cat_selected	= isset($GLOBALS['_getVar']['shop_cat']) ? $GLOBALS['_getVar']['shop_cat'] : 0;
	if(strpos($shop_cat_selected, '_')) {
		$shop_cat_selected = explode('_', $shop_cat_selected, 2);
		if(isset($shop_cat_selected[1])) {
			$shop_subcat_selected = intval($shop_cat_selected[1]);
		}
		$shop_cat_selected = intval($shop_cat_selected[0]);
		if(!$shop_cat_selected) {
			//$shop_cat_selected		= 'all';
			$shop_subcat_selected	= 0;
		}
	} else {
		$shop_cat_selected		= intval($shop_cat_selected);
		$shop_subcat_selected	= 0;
	}
	$selected_product_cat = $shop_subcat_selected ? $shop_subcat_selected : $shop_cat_selected;

	$shop_detail_id		= isset($GLOBALS['_getVar']['shop_detail']) ? intval($GLOBALS['_getVar']['shop_detail']) : 0;

	$shop_cat_name = get_shop_category_name($shop_cat_selected, $shop_subcat_selected);

	if(empty($shop_cat_name)) {
		$shop_cat_name		= $_tmpl['config']['cat_all'];
		$shop_cat_selected	= 0;
	}

	$shop_pagetitle = '';

	$sql  = "SELECT * FROM ".DB_PREPEND.'phpwcms_shop_products WHERE ';
	$sql .= "shopprod_status=1";

	if($selected_product_cat && !$shop_detail_id) {

		$sql .= ' AND (';
		$sql .= "shopprod_category = '" . $selected_product_cat . "' OR ";
		$sql .= "shopprod_category LIKE '%," . $selected_product_cat . ",%' OR ";
		$sql .= "shopprod_category LIKE '" . $selected_product_cat . ",%' OR ";
		$sql .= "shopprod_category LIKE '%," . $selected_product_cat . "'";
		$sql .= ')';

	} elseif($shop_detail_id) {

		$sql .= ' AND shopprod_id=' . $shop_detail_id;

	} else {

		$sql .= ' AND shopprod_listall=1';

	}

	// FE language
	$sql .= SHOP_FELANG_SQL;

	$_tmpl['config']['cat_list_sort_by'] = trim($_tmpl['config']['cat_list_sort_by']);
	if($_tmpl['config']['cat_list_sort_by'] !== '') {
		$sql .= ' ORDER BY '.aporeplace($_tmpl['config']['cat_list_sort_by']);
	}

	$data = _dbQuery($sql);

	if( isset($data[0]) ) {

		$x = 0;
		$entry = array();

		$shop_prod_detail = rel_url(array(), array('shop_detail'));

		$_tmpl['config']['init_lightbox'] = false;

		foreach($data as $row) {

			$_price['vat'] = $row['shopprod_vat'];
			if($row['shopprod_netgross'] == 1) {
				// price given is GROSS price, including VAT
				$_price['net']		= $row['shopprod_price'] / (1 + $_price['vat'] / 100);
				$_price['gross']	= $row['shopprod_price'];
			} else {
				// price given is NET price, excluding VAT
				$_price['net']		= $row['shopprod_price'];
				$_price['gross']	= $row['shopprod_price'] * (1 + $_price['vat'] / 100);
			}

			$_price['vat']		= number_format($_price['vat'],   $_tmpl['config']['vat_decimals'],   $_tmpl['config']['dec_point'], $_tmpl['config']['thousands_sep']);
			$_price['net']		= number_format($_price['net'],   $_tmpl['config']['price_decimals'], $_tmpl['config']['dec_point'], $_tmpl['config']['thousands_sep']);
			$_price['gross']	= number_format($_price['gross'], $_tmpl['config']['price_decimals'], $_tmpl['config']['dec_point'], $_tmpl['config']['thousands_sep']);
			$_price['weight']	= $row['shopprod_weight'] > 0 ? number_format($row['shopprod_weight'], $_tmpl['config']['weight_decimals'], $_tmpl['config']['dec_point'], $_tmpl['config']['thousands_sep']) : '';

			$row['shopprod_var'] = @unserialize($row['shopprod_var']);

			// check custom product URL
			if(empty($row['shopprod_var']['url'])) {
				$row['prod_url'] = array('link'=>'', 'target'=>'');
			} else {
				$row['prod_url'] = get_redirect_link($row['shopprod_var']['url'], ' ', '');
				$row['prod_url']['link'] = html_specialchars($row['prod_url']['link']);
			}

			// select template based on listing or detail view
			$entry[$x] = $shop_detail_id ? $_tmpl['detail'] : $_tmpl['list_entry'];


			//wr begin changed 29.06.12, fixed OG
			// get the value from the textarea for options, prepare the data, write select drop down

			//order options 1
			$_cart_opt_1 = array();
			$_cart_opt_1['data'] = explode(LF, $row['shopprod_size']);
			$_cart_prod_opt1 = '';
			$k = 0;
			foreach($_cart_opt_1['data'] as $key => $value){
				//title - first row in textarea - string
				if(!$k) {
					$_cart_opt_1['title'] = clean_slweg($value);
					if($_cart_opt_1['title']) {
						$_cart_prod_opt1 .= '<option value="0">'.html_specialchars($_cart_opt_1['title']).'</option>' . LF;
					}
					$k++;
					continue;
				}

				//values - followin rows
				$_cart_opt_1['value'] = explode('|', trim($value));
				// following is default for the exploded $caption
				// [0] string: description
				// [1] float: price to add
				// [2] string:# to add to prod#
				$_cart_opt_1['values'][$k] = $_cart_opt_1['value'];
				$value_opt1_float = "";

				if(isset($_cart_opt_1['value'][1])) {

					$value_opt1_float = preg_replace("/[^-0-9\.\,]/","",$_cart_opt_1['value'][1]);
					$value_opt1_float = floatval(preg_replace("/\,/",".",$value_opt1_float));
					$_cart_opt_1['values'][$k][1] = $value_opt1_float;
					$value_opt1_float = number_format($value_opt1_float, 2, $_tmpl['config']['dec_point'], $_tmpl['config']['thousands_sep']);
					if($value_opt1_float >= 0) {
						$value_opt1_float = "+".$value_opt1_float; //+ (wieder) hinzufügen
					}
				}
				if(isset($_cart_opt_1['value'][0]) || isset($_cart_opt_1['value'][1])){
					$_cart_prod_opt1 .= '<option value="'.$k.'">'.html_specialchars($_cart_opt_1['value'][0])." ".$value_opt1_float.'</option>' . LF;
				}

				$k++;
			}
			if($_cart_prod_opt1) {
				$_cart_prod_opt1 = '<select name="prod_opt1" id="prod_opt1" class="prod_opt1">'.$_cart_prod_opt1.'</select>'.LF;
			}

			//order options 2
			$_cart_opt_2 = array();
			$_cart_opt_2['data'] = explode(LF, $row['shopprod_color']);
			$_cart_prod_opt2 = '';
			$k = 0;

			foreach($_cart_opt_2['data'] as $key => $value){
				//title - first row in textarea - string
				if(!$k) {
					$_cart_opt_2['title'] = clean_slweg($value);
					if($_cart_opt_2['title']) {
						$_cart_prod_opt2 .= '<option value="0">'.html_specialchars($_cart_opt_2['title']).'</option>' . LF;
					}
					$k++;
					continue;
				}

				//values - followin rows
				$_cart_opt_2['value'] = explode('|', trim($value));
				// following is default for the exploded $caption
				// [0] string: description
				// [1] float: price to add
				// [2] string:# to add to prod#
				$_cart_opt_2['values'][$k] = $_cart_opt_2['value'];
				$value_opt2_float = '';

				if(isset($_cart_opt_2['value'][1])) {
					$value_opt2_float = preg_replace("/[^-0-9\.\,]/", "", $_cart_opt_2['value'][1]);
					$value_opt2_float = floatval(preg_replace("/\,/", ".", $value_opt2_float));
					$_cart_opt_2['values'][$k][1] = $value_opt2_float;
					$value_opt2_float = number_format($value_opt2_float, 2, $_tmpl['config']['dec_point'], $_tmpl['config']['thousands_sep']);
					if ($value_opt2_float >= 0) {
						$value_opt2_float = "+".$value_opt2_float; //+ (wieder) hinzufügen
					}
				}

				if(isset($_cart_opt_2['value'][0]) || isset($_cart_opt_2['value'][1])){
					$_cart_prod_opt2 .= '<option value="'.$k.'">'.html_specialchars($_cart_opt_2['value'][0])." ".$value_opt2_float."</option>" . LF;
				}

				$k++;
			}
			if($_cart_prod_opt2) {
				$_cart_prod_opt2 = '<select name="prod_opt2" id="prod_opt2" class="prod_opt2">'.$_cart_prod_opt2.'</select>' . LF;
			}

			//wr end changed 29.06.12

			if($_tmpl['config']['on_request_trigger'] == $_price['net']) {

				$_cart = '';
				$_cart_add = '';
				$_cart_on_request = TRUE;

			} else {

			$_cart = preg_match("/\[CART_ADD\](.*?)\[\/CART_ADD\]/is", $entry[$x], $g) ? $g[1] : '';

			$_cart_add  = '<form action="' . $shop_prod_detail . '" method="post">';
			$_cart_add .= '<input type="hidden" name="shop_prod_id" value="' . $row['shopprod_id'] . '" />';
			$_cart_add .= '<input type="hidden" name="shop_action" value="add" />';
			if(strpos($_cart, '<!-- SHOW-AMOUNT -->') !== false) {
				// user has set amount manually
				$_cart_add .= '<input type="text" name="shop_prod_amount" class="shop-list-amount" value="1" size="2" />';
				$_cart = str_replace('<!-- SHOW-AMOUNT -->', '', $_cart);
			} else {
				$_cart_add .= '<input type="hidden" name="shop_prod_amount" value="1" />';
			}

			//wr start changed 29.06.12, extended OG

			if(strpos($_cart, '{PRODUCT_OPT1}') !== false) {
				$_cart_add .= $_cart_prod_opt1;
				$_cart = str_replace('{PRODUCT_OPT1}', '', $_cart);
			}
			if(strpos($_cart, '{PRODUCT_OPT2}') !== false) {
				$_cart_add .= $_cart_prod_opt2;
				$_cart = str_replace('{PRODUCT_OPT2}', '', $_cart);
			}

			//wr end changed 29.06.12

			if(strpos($_cart, 'input ') !== false) {
				// user has set input button
				$_cart_add .= $_cart;
			} else {
				$_cart_add .= '<input type="submit" name="shop_cart_add" value="' . html_specialchars($_cart) . '" class="list-add-button" />';
			}

			$_cart_add .= '</form>';

				$_cart_on_request = FALSE;
			}

			$entry[$x] = preg_replace('/\[CART_ADD\](.*?)\[\/CART_ADD\]/is', $_cart_add , $entry[$x]);

			// product name
			$entry[$x] = str_replace('{CURRENCY_SYMBOL}', html_specialchars($_shopPref['shop_pref_currency']), $entry[$x]);
			$entry[$x] = render_cnt_template($entry[$x], 'ON_REQUEST', $_cart_on_request);
			$entry[$x] = render_cnt_template($entry[$x], 'PRODUCT_TITLE', html_specialchars($row['shopprod_name1']));
			$entry[$x] = render_cnt_template($entry[$x], 'PRODUCT_ADD', html_specialchars($row['shopprod_name2']));
			$entry[$x] = render_cnt_template($entry[$x], 'PRODUCT_SHORT', $row['shopprod_description0']);
			$entry[$x] = render_cnt_template($entry[$x], 'PRODUCT_LONG', $row['shopprod_description1']);
			$entry[$x] = render_cnt_template($entry[$x], 'PRODUCT_WEIGHT', $_price['weight']);
			$entry[$x] = render_cnt_template($entry[$x], 'PRODUCT_NET_PRICE', $_price['net']);
			$entry[$x] = render_cnt_template($entry[$x], 'PRODUCT_GROSS_PRICE', $_price['gross']);
			$entry[$x] = render_cnt_template($entry[$x], 'PRODUCT_VAT', $_price['vat']);
			$entry[$x] = render_cnt_template($entry[$x], 'PRODUCT_URL', $row['prod_url']['link']);

			if(empty($_shopPref['shop_pref_discount']['discount']) || empty($_shopPref['shop_pref_discount']['percent'])) {
				$row['discount'] = '';
			} else {
				$row['discount'] = round($_shopPref['shop_pref_discount']['percent'], 2);
				if($row['discount'] - floor($row['discount']) == 0) {
					$row['discount'] = number_format($row['discount'], 0, $_tmpl['config']['dec_point'], $_tmpl['config']['thousands_sep']);
				} else {
					$row['discount'] = number_format($row['discount'], 1, $_tmpl['config']['dec_point'], $_tmpl['config']['thousands_sep']);
				}
			}
			$entry[$x] = render_cnt_template($entry[$x], 'DISCOUNT', $row['discount']);
			$entry[$x] = str_replace('{PRODUCT_URL_TARGET}', $row['prod_url']['target'], $entry[$x]);
			$entry[$x] = render_cnt_template($entry[$x], 'ORDER_NUM', html_specialchars($row['shopprod_ordernumber']));
			$entry[$x] = render_cnt_template($entry[$x], 'MODEL', html_specialchars($row['shopprod_model']));
			$entry[$x] = render_cnt_template($entry[$x], 'VIEWED', number_format($row['shopprod_track_view'], 0, $_tmpl['config']['dec_point'], $_tmpl['config']['thousands_sep']));

			if($shop_detail_id) {

				$_tmpl['config']['mode']		= 'detail';
				$_tmpl['config']['lightbox_id']	= '[product_'.$x.'_'.$shop_detail_id.']';
				$shop_pagetitle					= $row['shopprod_name1'];
				/*
				if(!empty($row['shopprod_model'])) {
					$shop_pagetitle .= ' / ' . $row['shopprod_model'];
				}
				*/

				// product detail
				$entry[$x] = str_replace('{PRODUCT_DETAIL_LINK}', $shop_prod_detail, $entry[$x]);

				// Images
				$_prod_list_img = array();

				if(count($row['shopprod_var']['images'])) {

					$row['shopprod_var']['img_count'] = 1;
					foreach($row['shopprod_var']['images'] as $img_key => $img_vars) {
						$img_vars['count'] = $row['shopprod_var']['img_count'];
						if($_tmpl['config']['image_detail_more_start'] <= $row['shopprod_var']['img_count']) {
							$_tmpl['config']['mode'] = 'detail_more';
						}
						if($img_vars = shop_image_tag($row['shopprod_var']['images'][$img_key], $img_vars['count'], $row['shopprod_name1'])) {
							$_prod_list_img[] = $img_vars;
							$row['shopprod_var']['img_count']++;
						}
					}
				}
				$_prod_list_img = implode($_tmpl['image_space'], $_prod_list_img);

				// Files
				$_prod_list_files = isset($row['shopprod_var']['files'][0]['f_id']) ? shop_files($row['shopprod_var']['files']) : '';

				if(!empty($row['shopprod_overwrite_meta'])) {
					if($row['shopprod_name1']) {
						$content["pagetitle"] = setPageTitle($content["pagetitle"], $article['cat'], $row['shopprod_name1']);
						set_meta('og:title', $row['shopprod_name1'], 'property');
					}
					if($row['shopprod_description0']) {
						$row['meta_description'] = $row['shopprod_description0'];
					} elseif($row['shopprod_description1']) {
						$row['meta_description'] = $row['shopprod_description1'];
					} else {
						$row['meta_description'] = '';
					}
					if($row['meta_description']) {
						$row['meta_description'] = trim( strip_tags( strip_bbcode($row['meta_description']) ) );
						$row['meta_description'] = getCleanSubString($row['meta_description'], 40, '', 'word');
						set_meta('description', $row['meta_description']);
						set_meta('og:description', $row['meta_description'], 'property');
					}

					set_meta('og:type', 'og:product', 'property');
					set_meta('og:url', abs_url(array('shop_detail'=>$shop_detail_id), array('shop_cat', 'shop_cart')),'property');

					if(count($_prod_list_img)) {
						set_meta('og:image', PHPWCMS_URL.'img/cmsimage.php/600x600x1/'.$row['shopprod_var']['images'][0]['f_hash'] . '.' . $row['shopprod_var']['images'][0]['f_ext'], 'property');
						$block['custom_htmlhead']['image_src'] = '  <link rel="image_src" href="'.PHPWCMS_URL.'img/cmsimage.php/600x600x1/'.$row['shopprod_var']['images'][0]['f_hash'] . '.' . $row['shopprod_var']['images'][0]['f_ext'].'" />';
					}

				}

				// Update product view count
				// ToDo: Maybe use cookie or session to avoid tracking in case showed once
				$sql = 'UPDATE LOW_PRIORITY '.DB_PREPEND.'phpwcms_shop_products SET shopprod_track_view=shopprod_track_view+1 WHERE shopprod_id='.$shop_detail_id;
				_dbQuery($sql, 'UPDATE');

			} else {

				$_tmpl['config']['mode']		= 'list';
				$_tmpl['config']['lightbox_id']	= '';

				if(count($row['shopprod_var']['images'])) {
					$_prod_list_img = shop_image_tag($row['shopprod_var']['images'][0], 0, $row['shopprod_name1']);
				} else {
					$_prod_list_img = '';
				}

				// product listing
				$entry[$x] = str_replace('{PRODUCT_DETAIL_LINK}', $shop_prod_detail.'&amp;shop_detail='.$row['shopprod_id'], $entry[$x]);

				// no files in list mode
				$_prod_list_files = '';

			}

			if(!$_tmpl['config']['init_lightbox'] && $_tmpl['config']['image_'.$_tmpl['config']['mode'].'_lightbox'] && $_prod_list_img) {
				$_tmpl['config']['init_lightbox'] = true;
			}

			$entry[$x] = render_cnt_template($entry[$x], 'IMAGE', $_prod_list_img);


			// Render Files
			$entry[$x] = render_cnt_template($entry[$x], 'FILES', $_prod_list_files);


			$x++;
		}

		// initialize Lightbox effect
		if($_tmpl['config']['init_lightbox']) {
			initSlimbox();
		}

		$entries = implode($_tmpl['list_space'], $entry);

	} else {

		$entries = $_tmpl['list_none'];

	}

	if($shop_detail_id) {
		$entries = $_tmpl['config']['shop_wrap']['prefix'] . $entries . $_tmpl['config']['shop_wrap']['suffix'];
	} else {
		$entries = $_tmpl['config']['shop_wrap']['prefix'] . $_tmpl['list_header'] . LF . $entries . LF . $_tmpl['list_footer'] . $_tmpl['config']['shop_wrap']['suffix'];
	}

	$entries = str_replace('{CATEGORY}', html_specialchars($shop_cat_name), $entries);
	$entries = render_cnt_template($entries, 'CART_LINK', is_cart_filled() ? rel_url(array('shop_cart' => 'show'), array('shop_detail'), $_tmpl['config']['cart_url']) : '');
	$entries = parse_cnt_urlencode($entries);

	$content['all'] = str_replace('{SHOP_PRODUCTLIST}', $entries, $content['all']);

	if(preg_match('/<!--\s{0,}RENDER_SHOP_PAGETITLE:(BEFORE|AFTER)\s{0,}-->/', $content['all'], $match)) {

		if(empty($GLOBALS['pagelayout']['layout_title_spacer'])) {
			$title_spacer = ' | ';
			$GLOBALS['pagelayout']['layout_title_spacer'] = $title_spacer;
		} else {
			$title_spacer = $GLOBALS['pagelayout']['layout_title_spacer'];
		}

		if($shop_pagetitle) {
			$shop_pagetitle .= $title_spacer;
		}

		$shop_pagetitle .= $shop_cat_name;

		if(empty($content['pagetitle'])) {
			$content['pagetitle'] = html_specialchars($shop_pagetitle);
		} elseif($match[1] == 'BEFORE') {
			$content['pagetitle'] = html_specialchars($shop_pagetitle . $title_spacer) . $content['pagetitle'];
		} else {
			$content['pagetitle'] .= html_specialchars($title_spacer . $shop_pagetitle);
		}

		$content['all'] = str_replace($match[0], '', $content['all']);

	}
}

if( $_shop_load_order ) {

	$cart_data = get_cart_data();

	if(empty($cart_data)) {

		// cart is empty
		$order_process = $_tmpl['cart_none'];

	} elseif(isset($_POST['shop_cart_checkout']) || isset($ERROR['inv_address']) || isset($_SESSION[CART_KEY]['error']['step1']) || isset($_POST['shop_edit_address'])) {

		// order Step 1 -> get address

		// checkout step 1 -> insert invoice address
		$order_process = $_tmpl['inv_address'];

		$_step1 = array(
					'INV_FIRSTNAME' => '',
					'INV_NAME' => '',
					'INV_ADDRESS' => '',
					'INV_ZIP' => '',
					'INV_CITY' => '',
					'INV_REGION' => '',
					'INV_COUNTRY' => '',
					'EMAIL' => '',
					'PHONE' => ''
						);

		// handle custom fields
		foreach($_tmpl['config']['shop_field'] as $item_key => $row) {
			if($row['type'] === 'CHECK') {
				$_step1['shop_field_'.$item_key] = $row['value'];
				if($_SESSION[CART_KEY]['step1']['shop_field_'.$item_key] && $_SESSION[CART_KEY]['step1']['shop_field_'.$item_key] == $row['value']) {
					$order_process	= render_cnt_template($order_process, 'shop_field_'.$item_key, html_specialchars($row['value']).'" checked="checked');
				} else {
					$order_process	= render_cnt_template($order_process, 'shop_field_'.$item_key, html_specialchars($row['value']));
				}
			} else {
				$_step1['shop_field_'.$item_key] = '';
			}
		}

		if(isset($_SESSION[CART_KEY]['step1'])) {
			$_step1 = array_merge($_step1, $_SESSION[CART_KEY]['step1']);
		}

		foreach($_step1 as $item_key => $row) {
			$field_error	= empty($ERROR['inv_address'][$item_key]) ? '' : $ERROR['inv_address'][$item_key];
			/*
			$row_checked	= '';
			if($field_error == '' && $row != '' && preg_match('/^shop_field_(\d+)$/', $item_key, $row_match)) {
				$row_match = intval($row_match[1]);
				if(isset($_tmpl['config']['shop_field'][$row_match]['type']) && $_tmpl['config']['shop_field'][$row_match]['type'] === 'CHECK') {
					if(!empty($_POST[$item_key] && ))
					$row_checked = ' checked="checked';
				}
			}*/
			$row = html_specialchars($row);
			$order_process	= render_cnt_template($order_process, $item_key, $row); //.$row_checked
			$order_process	= render_cnt_template($order_process, 'ERROR_'.$item_key, $field_error);
		}

		$payment_options = get_payment_options();

		if(count($payment_options)) {

			$payment_fields = array();
			$payment_selected = isset($_SESSION[CART_KEY]['payby']) && isset($payment_options[ $_SESSION[CART_KEY]['payby'] ]) ? $_SESSION[CART_KEY]['payby'] : '';
			foreach($payment_options as $item_key => $row) {

				$payment_fields[$item_key]  = '<div><label>';
				$payment_fields[$item_key] .= '<input type="radio" name="shopping_payment" id="shopping_payment_'.$item_key.'" ';
				$payment_fields[$item_key] .= 'value="'.$item_key.'" ';
				if($payment_selected == $item_key) {
					$payment_fields[$item_key] .= ' checked="checked"';
				}
				$payment_fields[$item_key] .= ' />';
				$payment_fields[$item_key] .= '<span>' . html_specialchars($_tmpl['config']['label_payby_'.$item_key]) . '</span>';
				$payment_fields[$item_key] .= '</label></div>';
			}
			$order_process = render_cnt_template($order_process, 'PAYMENT', implode(LF, $payment_fields));
		} else {
			$order_process = render_cnt_template($order_process, 'PAYMENT', '');
		}

		// some error handling
		$order_process = render_cnt_template($order_process, 'ERROR_PAYMENT', isset($ERROR['inv_address']['payment']) ? ' ' : '');
		$order_process = render_cnt_template($order_process, 'IF_ERROR', isset($ERROR['inv_address']) ? ' ' : '');

		$order_process = '<form action="' . rel_url(array('shop_cart' => 'show'), array('shop_detail'), $_tmpl['config']['cart_url']) . '" method="post">' . LF . trim($order_process) . LF . '</form>';


	} elseif( isset($_POST['shop_order_step1']) || isset($ERROR['terms']) || isset($_SESSION[CART_KEY]['error']['step2']) ) {

		// Order step 2 -> Proof and [X] terms of business
		$order_process = $_tmpl['order_terms'];

		$order_process = str_replace('{SHOP_LINK}', rel_url(array(), array('shop_cat', 'shop_cart', 'shop_detail'), $_tmpl['config']['shop_url']), $order_process);
		$order_process = str_replace('{CART_LINK}', rel_url(array('shop_cart' => 'show'), array('shop_detail'), $_tmpl['config']['cart_url']), $order_process);

		foreach($_SESSION[CART_KEY]['step1'] as $item_key => $row) {
			$order_process = render_cnt_template($order_process, $item_key, nl2br(html_specialchars($row)));
		}

		$order_process = render_cnt_template($order_process, 'IF_ERROR', isset($_SESSION[CART_KEY]['error']['step2']) ? ' ' : '');

		if(isset($_SESSION[CART_KEY]['payby'])) {
			$order_process = render_cnt_template($order_process, 'PAYMENT', html_specialchars($_tmpl['config']['label_payby_'.$_SESSION[CART_KEY]['payby']]));
		} else {
			$order_process = render_cnt_template($order_process, 'PAYMENT', '');
		}

		$cart_mode = 'terms';
		include($phpwcms['modules']['shop']['path'].'inc/cart.items.inc.php');
		$order_process = str_replace('{ITEMS}', implode($_tmpl['term_space'], $cart_items), $order_process);

		$terms_text		= _getConfig( 'shop_pref_terms', '_shopPref' );
		$terms_format	= _getConfig( 'shop_pref_terms_format', '_shopPref' );
		$order_process = str_replace('{TERMS}', $terms_format ? $terms_text : nl2br(html_specialchars($terms_text)), $order_process);

		include($phpwcms['modules']['shop']['path'].'inc/cart.parse.inc.php');


	} elseif( isset($_POST['shop_order_submit']) && !isset($_SESSION[CART_KEY]['error']['step2']) ) {

		// OK agreed - now send order

		if($_tmpl['config']['order_number_style'] == 'RANDOM') {
			$order_num = generic_string(8, 2);
		} else {
			// count all current orders
			$order_num = _dbCount('SELECT COUNT(*) FROM '.DB_PREPEND.'phpwcms_shop_orders') + 1;
			if(strpos($_tmpl['config']['order_number_style'], '%') !== FALSE) {
				$order_num = sprintf($_tmpl['config']['order_number_style'], $order_num);
			}
		}

		// prepare customer mail
		$order_process = $_tmpl['mail_customer'];

		foreach($_SESSION[CART_KEY]['step1'] as $item_key => $row) {
			$order_process = render_cnt_template($order_process, $item_key, html_specialchars($row));
		}

		$cart_mode = 'mail1';
		include($phpwcms['modules']['shop']['path'].'inc/cart.items.inc.php');
		$order_process = str_replace('{ITEMS}', implode(LF.LF, $cart_items), $order_process);

		include($phpwcms['modules']['shop']['path'].'inc/cart.parse.inc.php');

		$order_process = str_replace('{ORDER}', $order_num, $order_process);
		$order_process = render_cnt_date($order_process, time());

		$mail_customer = @html_entity_decode($order_process);

		// prepare new order mail
		$order_process = $_tmpl['mail_neworder'];

		foreach($_SESSION[CART_KEY]['step1'] as $item_key => $row) {
			$order_process = render_cnt_template($order_process, $item_key, html_specialchars($row));
		}

		$cart_mode = 'mail1';
		include($phpwcms['modules']['shop']['path'].'inc/cart.items.inc.php');
		$order_process = str_replace('{ITEMS}', implode(LF.LF, $cart_items), $order_process);

		include($phpwcms['modules']['shop']['path'].'inc/cart.parse.inc.php');

		$order_process = str_replace('{ORDER}', $order_num, $order_process);
		$order_process = render_cnt_date($order_process, time());

		$mail_neworder = @html_entity_decode($order_process);

		if(!empty($_SESSION[CART_KEY]['payby'])) {
			$payment = $_SESSION[CART_KEY]['payby'];
			$mail_customer = render_cnt_template($mail_customer, 'PAYBY_'.strtoupper($payment), $_tmpl['config']['label_payby_'.$payment]);
			$mail_neworder = render_cnt_template($mail_neworder, 'PAYMENT', $_tmpl['config']['label_payby_'.$payment]);
		} else {
			$mail_customer = render_cnt_template($mail_customer, 'PAYBY_'.strtoupper($payment), 'n.a.');
			$mail_neworder = render_cnt_template($mail_neworder, 'PAYMENT', 'n.a.');
			$payment = 'n.a.';
		}

		$payment_options = get_payment_options();
		foreach($payment_options  as $item_key => $row) {
			$mail_customer = render_cnt_template($mail_customer, 'PAYBY_'.strtoupper($item_key), '');
		}

		// store order in database
		$order_data = array(
			'order_number'		=> $order_num,
			'order_date'		=> gmdate('Y-m-d H:i'),
			'order_name'		=> $_SESSION[CART_KEY]['step1']['INV_NAME'],
			'order_firstname'	=> $_SESSION[CART_KEY]['step1']['INV_FIRSTNAME'],
			'order_email'		=> $_SESSION[CART_KEY]['step1']['EMAIL'],
			'order_net'			=> $subtotal['float_total_net'],
			'order_gross'		=> $subtotal['float_total_gross'],
			'order_payment'		=> $payment,
			'order_data'		=> @serialize( array(
												'cart' => $cart_data,
												'address' => $_SESSION[CART_KEY]['step1'],
												'mail_customer' => $mail_customer,
												'mail_self' => $mail_neworder,
												'subtotal' => array(
														'subtotal_net' => $subtotal['float_net'],
														'subtotal_gross' => $subtotal['float_gross']
																	),
												'shipping' => array(
														'shipping_net' => $subtotal['float_shipping_net'],
														'shipping_gross' => $subtotal['float_shipping_gross']
																	),
												'discount' => array(
														'discount_net' => $subtotal['float_discount_net'],
														'discount_gross' => $subtotal['float_discount_gross']
																	),
												'loworder' => array(
														'loworder_net' => $subtotal['float_loworder_net'],
														'loworder_gross' => $subtotal['float_loworder_gross']
																	),
												'weight' => $subtotal['float_weight'],
												'lang' => $phpwcms['default_lang']
												) ),
			'order_status'		=> 'NEW-ORDER'
		);

		// receive order db ID
		$order_data = _dbInsert('phpwcms_shop_orders', $order_data);

		// send mail to customer
		$email_from = _getConfig( 'shop_pref_email_from', '_shopPref' );
		if(!is_valid_email($email_from)) $email_from = $phpwcms['SMTP_FROM_EMAIL'];

		$order_mail_customer = array(
			'recipient'	=> $_SESSION[CART_KEY]['step1']['EMAIL'],
			'toName'	=> $_SESSION[CART_KEY]['step1']['INV_FIRSTNAME'] . ' ' . $_SESSION[CART_KEY]['step1']['INV_NAME'],
			'subject'	=> str_replace('{ORDER}', $order_num, $_tmpl['config']['mail_customer_subject']),
			'text'		=> $mail_customer,
			'from'		=> $email_from,
			'sender'	=> $email_from
		);

		$order_data_mail_customer = sendEmail($order_mail_customer);

		// send mail to shop
		$send_order_to = convertStringToArray( _getConfig( 'shop_pref_email_to', '_shopPref' ), ';' );
		if(empty($send_order_to[0]) || !is_valid_email($send_order_to[0])) {
			$email_to = $phpwcms['SMTP_FROM_EMAIL'];
		} else {
			$email_to = $send_order_to[0];
			unset($send_order_to[0]);
		}

		$order_mail_self = array(
			'from'		=> $_SESSION[CART_KEY]['step1']['EMAIL'],
			'fromName'	=> $_SESSION[CART_KEY]['step1']['INV_FIRSTNAME'] . ' ' . $_SESSION[CART_KEY]['step1']['INV_NAME'],
			'subject'	=> str_replace('{ORDER}', $order_num, $_tmpl['config']['mail_neworder_subject']),
			'text'		=> $mail_neworder,
			'recipient'	=> $email_to,
			'sender'	=> $_SESSION[CART_KEY]['step1']['EMAIL']
		);

		$order_data_mail_self = sendEmail($order_mail_self);

		// are there additional recipients for orders?
		if(count($send_order_to)) {
			foreach($send_order_to as $value) {
				$order_mail_self['recipient'] = $value;
				@sendEmail($order_mail_self);
			}
		}


		// success
		if(!empty($order_data['INSERT_ID']) || !empty($order_data_mail_customer[0])) {

			$order_process = $_tmpl['order_success'];

			foreach($_SESSION[CART_KEY]['step1'] as $item_key => $row) {
				$order_process = render_cnt_template($order_process, $item_key, html_specialchars($row));
			}
			unset($_SESSION[CART_KEY]);

		// NO success
		} else {

			$order_process = $_tmpl['order_failed'];

			$order_process = str_replace('{SUBJECT}', rawurlencode($_tmpl['config']['mail_neworder_subject']), $order_process);
			$order_process = str_replace('{MSG}', rawurlencode('---- FALLBACK MESSAGE ---' . LF . LF . $mail_customer), $order_process);

			foreach($_SESSION[CART_KEY]['step1'] as $item_key => $row) {
				$order_process = render_cnt_template($order_process, $item_key, html_specialchars($row));
			}

		}

		$order_process = str_replace('{ORDER}', $order_num, $order_process);


	} else {

		// show cart

		$cart_mode = 'cart';
		include($phpwcms['modules']['shop']['path'].'inc/cart.items.inc.php');

		$order_process  = $_tmpl['cart_header'];
		$order_process .= implode($_tmpl['cart_space'], $cart_items);
		$order_process .= $_tmpl['cart_footer'];

		include($phpwcms['modules']['shop']['path'].'inc/cart.parse.inc.php');

		// Update Cart Button
		$_cart_button = preg_match("/\[UPDATE\](.*?)\[\/UPDATE\]/is", $order_process, $g) ? $g[1] : '';
		if(strpos($_cart_button, 'input ') === false) {
			$_cart_button = '<input type="submit" name="shop_cart_update" value="' . html_specialchars($_cart_button) . '" class="cart_update_button" />';
		}
		$order_process  = preg_replace('/\[UPDATE\](.*?)\[\/UPDATE\]/is', $_cart_button , $order_process);

		// Checkout Button
		$_cart_button = preg_match("/\[CHECKOUT\](.*?)\[\/CHECKOUT\]/is", $order_process, $g) ? $g[1] : '';
		if(strpos($_cart_button, 'input ') === false) {
			$_cart_button = '<input type="submit" name="shop_cart_checkout" value="' . html_specialchars($_cart_button) . '" class="cart_checkout_button" />';
		}
		$order_process  = preg_replace('/\[CHECKOUT\](.*?)\[\/CHECKOUT\]/is', $_cart_button , $order_process);

		// Is Shipping?
		$order_process = render_cnt_template($order_process, 'SHIPPING', $subtotal['float_shipping_net'] > 0 ? 1 : '');
		$order_process = '<form action="' . rel_url(array('shop_cart' => 'show'), array('shop_detail'), $_tmpl['config']['cart_url']) . '" method="post">' . LF . trim($order_process) . LF . '</form>';

	}

	$order_process = str_replace('{SHOP_LINK}', rel_url(array(), array('shop_cart', 'shop_detail'), $_tmpl['config']['shop_url']), $order_process);

	$content['all'] = str_replace('{SHOP_ORDER_PROCESS}', $_tmpl['config']['shop_wrap']['prefix'] . $order_process . $_tmpl['config']['shop_wrap']['suffix'], $content['all']);
}

// small cart
if($_shop_load_cart_small) {

	$_cart_count = 0;
	//wr begin changed 29.06.12
	// counter for small cart has own session var
	if(isset($_SESSION[CART_KEY]['total']) && is_array($_SESSION[CART_KEY]['total']) && count($_SESSION[CART_KEY]['total'])) {
		foreach($_SESSION[CART_KEY]['total'] as $cartval) {
			$_cart_count += $cartval;
		}
	}

	if(!$_cart_count) {
		$_cart_count = '';
	}

	if(strpos($_tmpl['cart_small'], '{CART_LINK}')) {

		$shop_cat_selected	= isset($GLOBALS['_getVar']['shop_cat']) ? $GLOBALS['_getVar']['shop_cat'] : 0;
		$shop_detail_id		= isset($GLOBALS['_getVar']['shop_detail']) ? intval($GLOBALS['_getVar']['shop_detail']) : 0;
		unset($GLOBALS['_getVar']['shop_cat'], $GLOBALS['_getVar']['shop_detail']);
		$_tmpl['cart_small'] = str_replace('{CART_LINK}', rel_url(array('shop_cart' => 'show'), array(), $_tmpl['config']['cart_url']), $_tmpl['cart_small']);
		if($shop_cat_selected) {
			$GLOBALS['_getVar']['shop_cat'] = $shop_cat_selected;
		}
		if($shop_detail_id) {
			$GLOBALS['_getVar']['shop_detail'] = $shop_detail_id;
		}
	}

	$_tmpl['cart_small'] = render_cnt_template($_tmpl['cart_small'], 'COUNT', $_cart_count);
	$content['all'] = str_replace('{CART_SMALL}', $_tmpl['cart_small'], $content['all']);
}


function is_cart_filled() {

	if(empty($_SESSION[CART_KEY]['products'])) {

		return false;

	} elseif(!is_array($_SESSION[CART_KEY]['products'])) {

		return false;

	} elseif(!count($_SESSION[CART_KEY]['products'])) {

		return false;

	} elseif(isset($_SESSION[CART_KEY]['total']) && empty($_SESSION[CART_KEY]['total'])) {

		return false;
	}

	return true;
}

function get_cart_data() {

	// retrieve all cart data
	if(!is_cart_filled()) {
		return array();
	}

	$in = array();
	foreach($_SESSION[CART_KEY]['products'] as $key => $value) {
		$key = intval($key);
		$in[$key] = $key;
	}

	$sql  = 'SELECT * FROM '.DB_PREPEND.'phpwcms_shop_products WHERE shopprod_status=1 AND ';
	$sql .= 'shopprod_id IN (' . implode(',', $in) . ')';

	$data = _dbQuery($sql);

	if(isset($data[0])) {

		foreach($data as $key => $value) {

			$data[$key]['shopprod_quantity'] = $_SESSION[CART_KEY]['products'][ $value['shopprod_id'] ];

		}

	}

	return $data;
}



function shop_image_tag($img=array(), $counter=0, $title='') {

	$config =& $GLOBALS['_tmpl']['config'];

	// set image values
	$width		= $config['image_'.$config['mode'].'_width'];
	$height		= $config['image_'.$config['mode'].'_height'];
	$crop		= $config['image_'.$config['mode'].'_crop'];
	$caption	= empty($img['caption']) ? '' : ' :: '.$img['caption'];
	$title		= empty($title) ? '' : ' title="'.html_specialchars($title.$caption).'"';

	$thumb_image = get_cached_image(
			array(	"target_ext"	=>	$img['f_ext'],
					"image_name"	=>	$img['f_hash'] . '.' . $img['f_ext'],
					"max_width"		=>	$width,
					"max_height"	=>	$height,
					"thumb_name"	=>	md5($img['f_hash'].$width.$height.$GLOBALS['phpwcms']["sharpen_level"].$crop),
					'crop_image'	=>	$crop
				  )
			);

	if($thumb_image) {

		// now try to build caption and if neccessary add alt to image or set external link for image
		$caption	= getImageCaption($img['caption']);
		// set caption and ALT Image Text for imagelist
		$capt_cur	= html_specialchars($caption[0]);
		$caption[3] = empty($caption[3]) ? '' : ' title="'.html_specialchars($caption[3]).'"'; //title
		$caption[1] = html_specialchars(empty($caption[1]) ? $img['f_name'] : $caption[1]);

		$list_img_temp  = '<img src="'.PHPWCMS_IMAGES.$thumb_image[0].'" ';
		$list_img_temp .= $thumb_image[3].' alt="'.$caption[1].'"'.$caption[3].$title.' border="0" />';

		// use lightbox effect
		if($config['image_'.$config['mode'].'_lightbox']) {

			$a  = '<a href="img/cmsimage.php/';
			$a .= $config['image_zoom_width'] . 'x' . $config['image_zoom_height'] . '/';
			$a .= $img['f_hash'] . '.' . $img['f_ext'] . '" ';
			$a .= 'target="_blank" rel="lightbox'.$config['lightbox_id'].'"' . $caption[3] . $title . '>';

			$list_img_temp = $a . $list_img_temp . '</a>';
		}

		$class = empty($counter) ? '' : ' img-num-'.$counter;

		return '<span class="shop-article-img'.$class.'">' . $list_img_temp . '</span>';

	}

	return '';
}

function get_shop_category_name($id=0, $subid=0) {
	if(empty($id)) {
		return '';
	}
	$cat_name = '';

	$sql  = 'SELECT cat_name FROM '.DB_PREPEND.'phpwcms_categories WHERE ';
	$sql .= "cat_type='module_shop' AND cat_status=1 AND cat_id=" . $id . ' LIMIT 1';
	$data = _dbQuery($sql);

	if(isset($data[0]['cat_name'])) {
		$cat_name = $data[0]['cat_name'];
	}

	if($subid) {

		$sql  = 'SELECT cat_name FROM '.DB_PREPEND.'phpwcms_categories WHERE ';
		$sql .= "cat_type='module_shop' AND cat_status=1 AND cat_id=" . $subid . ' LIMIT 1';
		$data = _dbQuery($sql);

		if(isset($data[0]['cat_name'])) {
			if($cat_name) {
				$cat_name .= str_replace('_', ' ', $GLOBALS['_tmpl']['config']['cat_subcat_spacer']);
			}
			$cat_name .= $data[0]['cat_name'];
		}
	}

	return $cat_name;
}

function get_payment_options() {

	$payment_prefs = _getConfig( 'shop_pref_payment', '_shopPref' );
	$supported = array('prepay' => 0, 'pod' => 0, 'onbill' => 0);
	$available = array();
	foreach($supported as $key => $value) {
		if($payment_prefs[$key]) $available[$key] = $payment_prefs[$key];
	}
	return $available;
}


function get_category_products($selected_product_cat, $shop_detail_id, $shop_cat_selected, $shop_subcat_selected, $shop_alias) {

	$shop_cat_prods = '';

	$sql  = "SELECT * FROM ".DB_PREPEND.'phpwcms_shop_products WHERE ';
	$sql .= "shopprod_status=1";
	$sql .= ' AND (';
	$sql .= "shopprod_category = '" . $selected_product_cat . "' OR ";
	$sql .= "shopprod_category LIKE '%," . $selected_product_cat . ",%' OR ";
	$sql .= "shopprod_category LIKE '" . $selected_product_cat . ",%' OR ";
	$sql .= "shopprod_category LIKE '%," . $selected_product_cat . "'";
	$sql .= ')';
	// FE language
	$sql .= SHOP_FELANG_SQL;
	$pdata = _dbQuery($sql);

	if(is_array($pdata) && count($pdata)) {

		$z = 0;
		$shop_cat_prods = array();
		foreach($pdata as $prow) {

			$shop_cat_prods[$z] = '<li id="shopcat-product-'.$prow['shopprod_id'].'"';
			if($prow['shopprod_id'] == $shop_detail_id) {
				$shop_cat_prods[$z] .= ' class="active"';
			}
			$shop_cat_prods[$z] .= '>';

			$prow['get'] = array(
				'shop_cat' => $shop_cat_selected,
				'shop_detail' => $prow['shopprod_id']
			);
			if($shop_subcat_selected) {
				$prow['get']['shop_cat'] .= '_' . $shop_subcat_selected;
			}

			$shop_cat_prods[$z] .= '<a href="' . rel_url($prow['get'], array(), $shop_alias) . '">';
			$shop_cat_prods[$z] .= html_specialchars($prow['shopprod_name1']);
			$shop_cat_prods[$z] .= '</a>';
			$shop_cat_prods[$z] .= '</li>';
			$z++;
		}

		if(count($shop_cat_prods)) {
			$shop_cat_prods = LF . '		<ul class="'.$template_default['classes']['shop-products-menu'].'">' . LF.'			' . implode(LF.'			', $shop_cat_prods) . LF .'		</ul>' . LF.'	';
		}

	}

	return $shop_cat_prods;

}

function shop_files($data=array()) {

	global $phpwcms;

	$value = array(
		'cnt_object'			=> array('cnt_files' => array('id' => array(), 'caption' => array())), // id, caption
		'files_direct_download'	=> $GLOBALS['_tmpl']['config']['files_direct_download'],
		'files_template'		=> $GLOBALS['_tmpl']['config']['files_template']
	);

	foreach($data as $item) {
		$value['cnt_object']['cnt_files']['id'][]		= $item['f_id'];
		$value['cnt_object']['cnt_files']['caption'][]	= $item['caption'];
	}

	$IS_NEWS_CP	= true;
	$news		= array('files_result' => '');
	$crow		= array();

	// include content part files renderer
	include(PHPWCMS_ROOT.'/include/inc_front/content/cnt7.article.inc.php');

	return $news['files_result'];

}


?>