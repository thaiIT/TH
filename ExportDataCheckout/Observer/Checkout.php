<?php 

namespace TH\ExportDataCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;

class Checkout implements ObserverInterface  
{
	protected $directory_list;
	protected $driverFile;
	protected $priceHelper;

	public function __construct(
		\Magento\Framework\Filesystem\DirectoryList $directoryList,
		\Magento\Framework\Filesystem\Driver\File $driverFile,
		\Magento\Framework\Pricing\Helper\Data $priceHelper
	) {  
		$this->directory_list = $directoryList;
		$this->driverFile = $driverFile;
		$this->priceHelper = $priceHelper;
	}

	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$order = $observer->getEvent()->getOrder();
		$shippingAddress = $order->getShippingAddress();
		$billingAddress = $order->getBillingAddress();

		$fileName = 'MAGENTO_' . $order->getIncrementId() . '_' . date('Y_m_d') . '.xml';
		$domTree = new \DOMDocument('1.0', 'UTF-8');
		$domTree->formatOutput = true;

		$root = $domTree->createElement('Order');
		$domTree->appendChild($root);

		$orderHead =  $domTree->createElement('OrderHead');
		$root->appendChild($orderHead);
		$magentoOrderNumber = $domTree->createElement('MagentoOrderNumber', $order->getIncrementId());
		$magentoWebsiteID = $domTree->createElement('MagentoWebsiteID', 'base');
		$orderType = $domTree->createElement('OrderType', 'Order');
		$currency = $domTree->createElement('Currency', $order->getOrderCurrencyCode());
		$orderDate = $domTree->createElement('OrderDate', date('Y-m-d', strtotime($order->getCreatedAt())));
		$orderHead->appendChild($magentoOrderNumber);
		$orderHead->appendChild($magentoWebsiteID);
		$orderHead->appendChild($orderType);
		$orderHead->appendChild($currency);
		$orderHead->appendChild($orderDate);

		$customerInfor = $domTree->createElement('CustomerInfo');
		$root->appendChild($customerInfor);
		$firstName = $domTree->createElement('FirstName', $order->getCustomerFirstname());
		$lastName = $domTree->createElement('LastName', $order->getCustomerLastname());
		$email = $domTree->createElement('Email', $order->getCustomerEmail());
		$customerInfor->appendChild($firstName);
		$customerInfor->appendChild($lastName);
		$customerInfor->appendChild($email);

		$delivery = $domTree->createElement('Delivery');
		$root->appendChild($delivery);

		$deliverTo = $domTree->createElement('DeliverTo');
		$DeliverMethod = $domTree->createElement('DeliverMethod');
		$BuyersPONumber = $domTree->createElement('BuyersPONumber', '');
		$OrderNotes = $domTree->createElement('OrderNotes', $order->getCustomerNote());
		$DeliverAsSoonAsPossible = $domTree->createElement('DeliverAsSoonAsPossible', 'No');
		$delivery->appendChild($deliverTo);
		$delivery->appendChild($DeliverMethod);
		$delivery->appendChild($BuyersPONumber);
		$delivery->appendChild($OrderNotes);
		$delivery->appendChild($DeliverAsSoonAsPossible);

		$ERPAddressCode = $domTree->createElement('ERPAddressCode', '');
		$AddressLine1 = $domTree->createElement('AddressLine1',isset($shippingAddress->getStreet()[0]) ? $shippingAddress->getStreet()[0] : '');
		$AddressLine2 = $domTree->createElement('AddressLine2',isset($shippingAddress->getStreet()[1]) ? $shippingAddress->getStreet()[1] : '');
		$AddressLine3 = $domTree->createElement('AddressLine3',isset($shippingAddress->getStreet()[2]) ? $shippingAddress->getStreet()[2] : '');
		$Country = $domTree->createElement('Country',$shippingAddress->getCountryId());
		$City = $domTree->createElement('City',$shippingAddress->getCity());
		$State = $domTree->createElement('State', $shippingAddress->getRegionId());
		$Zipcode = $domTree->createElement('Zipcode', $shippingAddress->getPostcode());
		$ShippingContactEmail = $domTree->createElement('ShippingContactEmail', $shippingAddress->getEmail());
		$PhoneNumber = $domTree->createElement('PhoneNumber', $shippingAddress->getTelephone());

		$deliverTo->appendChild($ERPAddressCode);
		$deliverTo->appendChild($AddressLine1);
		$deliverTo->appendChild($AddressLine2);
		$deliverTo->appendChild($AddressLine3);
		$deliverTo->appendChild($Country);
		$deliverTo->appendChild($City);
		$deliverTo->appendChild($State);
		$deliverTo->appendChild($Zipcode);
		$deliverTo->appendChild($ShippingContactEmail);
		$deliverTo->appendChild($PhoneNumber);

		$ShippingProvider = $domTree->createElement('ShippingProvider', $order->getShippingDescription());
		$ValueQuoted = $domTree->createElement('ValueQuoted', '');
		$DeliverMethod->appendChild($ShippingProvider);
		$DeliverMethod->appendChild($ValueQuoted);

		$InvoiceTo = $domTree->createElement('InvoiceTo');
		$root->appendChild($InvoiceTo);
		$ERPAddressCodeB = $domTree->createElement('ERPAddressCode', '');
		$AddressLine1B = $domTree->createElement('AddressLine1',isset($billingAddress->getStreet()[0]) ? $billingAddress->getStreet()[0] : '');
		$AddressLine2B = $domTree->createElement('AddressLine2',isset($billingAddress->getStreet()[1]) ? $billingAddress->getStreet()[1] : '');
		$AddressLine3B = $domTree->createElement('AddressLine3',isset($billingAddress->getStreet()[2]) ? $billingAddress->getStreet()[2] : '');
		$CountryB = $domTree->createElement('Country',$billingAddress->getCountryId());
		$CityB = $domTree->createElement('City',$billingAddress->getCity());
		$StateB = $domTree->createElement('State', $billingAddress->getRegionId());
		$ZipcodeB = $domTree->createElement('Zipcode', $billingAddress->getPostcode());
		$PhoneNumberB = $domTree->createElement('PhoneNumber', $billingAddress->getTelephone());

		$InvoiceTo->appendChild($ERPAddressCodeB);
		$InvoiceTo->appendChild($AddressLine1B);
		$InvoiceTo->appendChild($AddressLine2B);
		$InvoiceTo->appendChild($AddressLine3B);
		$InvoiceTo->appendChild($CountryB);
		$InvoiceTo->appendChild($CityB);
		$InvoiceTo->appendChild($StateB);
		$InvoiceTo->appendChild($ZipcodeB);
		$InvoiceTo->appendChild($PhoneNumberB);

		$OrderLines = $domTree->createElement('OrderLines');
		$root->appendChild($OrderLines);
		foreach ($order->getAllVisibleItems() as $item) {
			$OrderLine = $domTree->createElement('OrderLine');
			$OrderLines->appendChild($OrderLine);
			$SKU = $domTree->createElement('SKU', $item->getSku());
			$ProductName = $domTree->createElement('ProductName', $item->getName());
			$Quantity = $domTree->createElement('Quantity');
			$Taxes = $domTree->createElement('Taxes');
			$LineSubtotal = $domTree->createElement('LineSubtotal', $item->getRowTotal());
			$OrderLine->appendChild($SKU);
			$OrderLine->appendChild($ProductName);
			$OrderLine->appendChild($Quantity);
			$OrderLine->appendChild($Taxes);
			$OrderLine->appendChild($LineSubtotal);

			$Amount = $domTree->createElement('Amount', $item->getQtyOrdered());
			$UnitOfMeasure = $domTree->createElement('UnitOfMeasure','');
			$UnitPrice = $domTree->createElement('UnitPrice', $item->getPrice());
			$Quantity->appendChild($Amount);
			$Quantity->appendChild($UnitOfMeasure);
			$Quantity->appendChild($UnitPrice);

			$TaxLine = $domTree->createElement('TaxLine');
			$Taxes->appendChild($TaxLine);

			$Label = $domTree->createElement('Label', '');
			$Rate = $domTree->createElement('Rate', $item->getTaxPercent().'%');
			$Total = $domTree->createElement('Total', $this->priceHelper->currency($order->getAllItems()[0]->getTaxAmount(), true, false));
			$TaxLine->appendChild($Label);
			$TaxLine->appendChild($Rate);
			$TaxLine->appendChild($Total);
		}

		$OrderSummary = $domTree->createElement('OrderSummary');
		$root->appendChild($OrderSummary);

		$ShippingTotal = $domTree->createElement('ShippingTotal',$order->getShippingAmount());
		$TaxTotal = $domTree->createElement('TaxTotal', $order->getTaxAmount());
		$ItemsSubtotal = $domTree->createElement('ItemsSubtotal', $order->getSubtotal());
		$DiscountTotal = $domTree->createElement('DiscountTotal', $order->getDiscountAmount());
		$OrderTotal = $domTree->createElement('OrderTotal', $order->getGrandTotal());

		$OrderSummary->appendChild($ShippingTotal);
		$OrderSummary->appendChild($TaxTotal);
		$OrderSummary->appendChild($ItemsSubtotal);
		$OrderSummary->appendChild($DiscountTotal);
		$OrderSummary->appendChild($OrderTotal);

		$xmlContent = $domTree->saveXML();
		$xmlContent = preg_replace('/^[^\n\r]*[\n\r]+/m', '', $xmlContent, 1);
		$folderPath = $this->directory_list->getRoot() . '/pub/media/orderxml';
		if (!$this->driverFile->isExists($folderPath)) {
			$this->driverFile->createDirectory($folderPath);
		}
		$this->driverFile->filePutContents($folderPath . '/' . $fileName, $xmlContent);

		return $this;
	}
}