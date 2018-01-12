<?php

 * Site-Babymarket.co
 * Cms-Magento
 *Description-Magento Shipping Method (Part Api functionality and generate shipping label)
 **/
class Babymarket_Carrier472_Model_Observer
{

    public function salesOrderShipmentSaveBefore(Varien_Event_Observer $observer)
    {
        Mage::log("Event salesOrderShipmentSaveBefore START", null, "carrier472.log");
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $shipment_count = Mage::getResourceModel('sales/order_shipment_collection')
            ->setOrderFilter($order)
            ->load()->count();


        if (($order->getShippingMethod() == 'carrier472_carrier472') && !$shipment->getAllTracks()) {


            $ship_address = $order->getShippingAddress();
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $city_table = Mage::getSingleton('core/resource')->getTableName('directory_country_region_city');
            $select_city = $read->select()->from($city_table);
            $select_city->where(
                $read->quoteInto(" region_id=? ", $ship_address->getRegionId()) .
                $read->quoteInto(" AND default_name=? ", $ship_address->getCity())
            );
            $row = $read->fetchAll($select_city);
            if (!empty($row)) {
                foreach ($row as $data) {
                    $codigo_dane = $data['codigo_dane'];
                    break;
                }
            }
            $gateway_get_url = Mage::helper('carrier472')->getConfigData('gateway_get_url');
            $gateway_post_url = Mage::helper('carrier472')->getConfigData('gateway_post_url');
            $Num_ValorDeclarado = Mage::helper('carrier472')->getConfigData('Num_ValorDeclarado');
            $codecity = Mage::helper('carrier472')->getConfigData('shipcity');

            $second_active = Mage::helper('carrier472')->getConfigData('second_active');
            $prefx = '';
            $cedulaNo = '';
            if ($second_active) {
                $payment = Mage::helper('carrier472')->getConfigData('payment');
                $pm = $order->getPayment()->getMethod();
                if ($pm == $payment) {
                    $prefx = 'second_';
                    $cedulaNo = $order->getPayment()->getCedulaNo();
                }
            }

            $login = Mage::helper('carrier472')->getConfigData($prefx . 'login');
            $pwd = Mage::helper('carrier472')->getConfigData($prefx . 'pwd');


            $shipcountry = Mage::helper('carrier472')->getConfigData('shipcountry');
            $shipcity = Mage::helper('carrier472')->getConfigData('shipcity');


            $tem = 'http://tempuri.org/';


            $Num_Alto = Mage::helper('carrier472')->getConfigData('Num_Alto');
            $Num_Ancho = Mage::helper('carrier472')->getConfigData('Num_Ancho');
            $Num_Largo = Mage::helper('carrier472')->getConfigData('Num_Largo');
            $Num_Peso = Mage::helper('carrier472')->getConfigData('Num_Peso');
            $TipoTrayecto = 1;

            //Make packages
            $totalWeight = 0;
            $totalPrice = 0;
            $packages = $shipment->getPackages();
            if (!is_array($packages) || !$packages) {
                $packages = array();
                $package_items = array();
                foreach ($shipment->getAllItems() as $item) {
                    $oii = $item->getOrderItemId();
                    $package_items[$oii]['qty'] = $item->getQty();
                    $package_items[$oii]['price'] = $item->getPrice();
                    $package_items[$oii]['customs_value'] = $item->getPrice();
                    $package_items[$oii]['name'] = $item->getName();
                    $package_items[$oii]['weight'] = $item->getWeight();
                    $package_items[$oii]['product_id'] = $item->getProductId();
                    $package_items[$oii]['order_item_id'] = $oii;
                    $totalWeight += $item->getWeight();
                    $totalPrice += $item->getPrice();
                }
                $package_params = array(
                    'container' => '',
                    'weight' => $totalWeight,
                    'customs_value' => $totalPrice,
                    'length' => $Num_Largo,
                    'width' => $Num_Ancho,
                    'height' => $Num_Alto,
                    'weight_units' => 'GRAM',
                    'dimension_units' => 'CENTIMETER',
                    'content_type' => '',
                    'content_type_other' => ''
                );
                $packages[1] = array(
                    'params' => $package_params,
                    'items' => $package_items
                );
            }
            $shipment->setPackages(serialize($packages));

            $totalWeight = $Num_Peso;

            if ($totalWeight < 200) {
                $totalWeight = 200;
            }


            if ($ship_address->getEmail() == null) {
                $email_cliente = $order->getCustomerEmail();
            } else {
                $email_cliente = $ship_address->getEmail();
            }

            $auth = $login . ':' . $pwd;
            $author = base64_encode($auth);
            $requestHeader = array('Content-Type: application/json', 'Authorization:Basic ' . $author);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $gateway_get_url);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $resposta = curl_exec($ch);


            $respost = json_decode($resposta);


            $boolTypeContract = $respost[0]->boolTypeContract;
            $decCurrentBalance = $respost[0]->decCurrentBalance;
            $intCodeAdmissionCenter = $respost[0]->intCodeAdmissionCenter;
            $intCodeCompany = $respost[0]->intCodeCompany;
            $intCodeContract = $respost[0]->intCodeContract;
            $intCodeHeadquarter = $respost[0]->intCodeHeadquarter;
            $intCodeService = $respost[0]->intCodeService;
            $intTypePay = $respost[0]->intTypePay;
            $strNameAdmissionCenter = $respost[0]->strNameAdmissionCenter;
            $strNameCompany = $respost[0]->strNameCompany;
            $strNameContract = $respost[0]->strNameContract;
            $strNameHeadquarter = $respost[0]->strNameHeadquarter;
            $strNamePay = $respost[0]->strNamePay;
            $strNameService = $respost[0]->strNameService;

            $o_id = $order->getId();


            $params = array(
                "boolMasterGuide" => false,
                "intAditionalOS" => 0,
                "intCodeContract" => $intCodeContract,
                "intCodeHeadquarter" => $intCodeHeadquarter,
                "intCodeService" => 100,
                "intGuidesNumber" => 2,
                "intTypePay" => $intTypePay,
                "intTypeRequest" => 2,
                "lstShippingTraceBe" => array(
                    array("boolMasterGuide" => false,
                        "placeReceiverBe" => array(
                            "intAditional" => 0,
                            "intCodeCity" => "$codigo_dane",
                            "intCodeHeadquarter" => $intCodeHeadquarter,
                            "intCodeOperationalCenter" => 0,
                            "intTypePlace" => 2,
                            "strAddress" => (implode(' ', $order->getShippingAddress()->getStreet())) . ' ' . $order->getShippingAddress()->getCity(),
                            "strAditional" => "",
                            "strEmail" => $email_cliente,
                            "strLocker" => " ",
                            "strNameCountry" => $order->getShippingAddress()->getCountryId(),
                            "strPhone" => $order->getShippingAddress()->getTelephone()
                        ),
                        "boolLading" => true,
                        "customerReceiverBe" => array(
                            "intAditional" => 0,
                            "intCodeCity" => "$codigo_dane",
                            "intTypeActor" => 3,
                            "intTypeDocument" => 1,
                            "strAddress" => (implode(' ', $order->getBillingAddress()->getStreet())) . ' ' . $order->getBillingAddress()->getCity(),
                            "strAditional" => "",
                            "strCountry" => $order->getShippingAddress()->getCountryId(),
                            "strDocument" => "",
                            "strEmail" => $order->getCustomerEmail(),
                            "strLastNames" => $order->getBillingAddress()->getLastname(),
                            "strNames" => $order->getBillingAddress()->getFirstname() . ' ' . $order->getBillingAddress()->getLastname(),
                            "strPhone" => $order->getBillingAddress()->getTelephone()
                        ),
                        "customerSenderBe" => array(
                            "intAditional" => 0,
                            "intCodeCity" => "$codecity",
                            "intTypeActor" => 2,
                            "intTypeDocument" => 1,
                            "strAddress" => Mage::getStoreConfig('general/store_information/address'),
                            "strAditional" => "",
                            "strCountry" => Mage::getStoreConfig('general/store_information/merchant_country'),
                            "strDocument" => "",
                            "strEmail" => Mage::getStoreConfig('trans_email/ident_general/email'),
                            "strLastNames" => Mage::getStoreConfig('general/store_information/name'),
                            "strNames" => Mage::getStoreConfig('general/store_information/name'),
                            "strPhone" => Mage::getStoreConfig('general/store_information/phone')
                        ),
                        "decCollectValue" => 0,
                        "decLading" => 0,
                        "intAditionalShipping" => 0,
                        "intAditionalShipping1" => 0,
                        "intAditionalShipping2" => 0,
                        "intDeclaredValue" => Mage::helper('carrier472')->getConfigData('Num_ValorDeclarado'),
                        "intHeight" => $Num_Alto,
                        "intLength" => $Num_Largo,
                        "intWeight" => $totalWeight,
                        "intWidth" => $Num_Ancho,
                        "placeSenderBe" => array(
                            "intAditional" => 0,
                            "intCodeCity" => "$codecity",
                            "intCodeHeadquarter" => $intCodeHeadquarter,
                            "intCodeOperationalCenter" => 0,
                            "intTypePlace" => 2,
                            "strAddress" => Mage::getStoreConfig('general/store_information/address'),
                            "strAditional" => "",
                            "strEmail" => Mage::getStoreConfig('trans_email/ident_general/email'),
                            "strLocker" => " ",
                            "strNameCountry" => Mage::getStoreConfig('general/store_information/merchant_country'),
                            "strPhone" => Mage::getStoreConfig('general/store_information/phone')
                        ),
                        "strAditionalShipping" => "",
                        "strIdentification" => "",
                        "strObservation" => "",
                        "strReference" => "$o_id"
                    ,)
                ),
                "strAditionalOS" => "",
            );


            $params = json_encode($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $gateway_post_url);
            curl_setopt($ch, CURLOPT_HTTPPOST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


            $response = curl_exec($ch);


            $response = json_decode($response);

            foreach ($shipment->getAllItems() as $it) {
                $_item = Mage::getModel('sales/order_item')->load($it->getOrderItemId());
                $total_shipment_price += $it->getPriceInclTax() * $it->getQty() - $_item->getDiscountAmount();
            }
            Mage::log("Request params\n" . print_r($params, true), null, "carrier472.log");
            Mage::log("\nCargueMasivoExterno response\n" . print_r($response, true), null, "carrier472.log");
            $trackingNumber = $response[0]->strBarcode;
            if ($trackingNumber) {
                if ($response[0]->byteGuidePDF) {

                    $shippingLabelContentt = "";
                    $shippingLabelContent = $response[0]->byteGuidePDF;
                    foreach ($shippingLabelContent as $key => $value) {
                        $shippingLabelContentt .= chr($value);
                    }
                    $outputPdf = new Zend_Pdf();
                    if (stripos($shippingLabelContent, '%PDF-') !== false) {
                        $pdfLabel = Zend_Pdf::parse($shippingLabelContentt);
                        foreach ($pdfLabel->pages as $page) {
                            $outputPdf->pages[] = clone $page;
                        }
                    }

                    $shipment->setShippingLabel($outputPdf->render());
                    $track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($trackingNumber)
                        ->setCarrierCode('carrier472')
                        ->setTitle(Mage::helper('carrier472')->getConfigData('title'));
                    $shipment->addTrack($track);
                    $shipment->sendEmail(true);
                    $shipment->setEmailSent(true);
                    Mage::log("trackingNumber = " . $trackingNumber, null, "carrier472.log");
                }
            }
            $trackinvoice = Mage::getModel('carrier472/trackinvoice');
            $trackinvoice->setOrderId($order->getId());
            $trackinvoice->setTrackNumber($trackingNumber);
            $trackinvoice->setTrackinvoiceTotal(round($total_shipment_price));
            $trackinvoice->save();
        }

        Mage::log("Event salesOrderShipmentSaveBefore END", null, "carrier472.log");
        return;
    }

}

