<?php
/**
 * Virtuemart Card Gate Plus payment extension
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category    VMPayment
 * @package     cgpdirectdebit
 * @author      CardGate B.V., <info@cardgate.com>
 * @copyright   Copyright (c) 2022 CardGate B.V. - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined('_JEXEC') or die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

if (!class_exists('plgVMPaymentCgpgeneric')) {
    require(JPATH_PLUGINS . DS . 'vmpayment' . DS . 'cgpgeneric' . DS . 'cgpgeneric.php');
}

class plgVMPaymentCgpdirectdebit extends plgVMPaymentCgpgeneric {
    
    protected $_plugin_name = "Cgpdirectdebit";
    
}