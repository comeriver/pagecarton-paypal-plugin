<?php

/**
 * PageCarton
 *
 * LICENSE
 *
 * @category   PageCarton
 * @package    Paypal_Pay
 * @copyright  Copyright (c) 2020 PageCarton (http://www.pagecarton.org)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @version    $Id: Pay.php Monday 6th of April 2020 05:24PM kayzenk@gmail.com $
 */

/**
 * @see PageCarton_Widget
 */

class Paypal_Pay extends Paypal_Paypal
{

	/**
	 * Whitelist and blacklist of currencies
	 *
	 * @var array
	 */
	protected static $_currency= array( 'whitelist' => '₦,NGN', 'blacklist' => 'ALL' );

	/**
	 * Form Action
	 *
	 * @var string
	 */
	protected static $_formAction = '';

	/**
	 * The method does the whole Class Process
	 *
	 */
	protected function init()
	{

		self::$_apiName = $this->getParameter( 'checkoutoption_name' ) ? : array_pop( explode( '_', get_class( $this ) ) );
		if( ! $cart = self::getStorage()->retrieve() ){ return; }
		$values = $cart['cart'];

		$parameters = static::getDefaultParameters();
		//	var_export( $parameters );
		$parameters['email'] = Ayoola_Form::getGlobalValue( 'email' ) ? : ( Ayoola_Form::getGlobalValue( 'email_address' ) ? : Ayoola_Application::getUserInfo( 'email' ) );
		$parameters['reference'] = $this->getParameter( 'reference' ) ? : $parameters['order_number'];
		$parameters['client_id'] = Paypal_Settings::retrieve( 'client_id' ) ? : 'ASD5Em1h1fmMoSM-LI8LiKz0Qu7STNfRSYRZYr6v_F3klJwXyrF9N_0BQJvs59bQrZyXX5bWm33MsdeJ'; 
		$parameters['currency'] = Paypal_Settings::retrieve( 'currency' ) ? : 'USD';
		$counter = 1;
		$parameters['price'] = 0.00;
		foreach( $values as $name => $value )
		{
			if( ! isset( $value['price'] ) )
			{
				$value = array_merge( self::getPriceInfo( $value['price_id'] ), $value );
			}
			@@$parameters['prod'] .= ' ' . $value['multiple'] . ' x ' . $value['subscription_label'];
			@$parameters['price'] += floatval( $value['price'] * $value['multiple'] );
			$counter++;
		}
		$parameters['amount'] = ( $this->getParameter( 'amount' ) ? : $parameters['price'] ) ;

		$this->setViewContent( 
                                '
								<div id="paypal-button-container"></div>
								<script src="https://www.paypal.com/sdk/js?client-id=' . $parameters['client_id']  . '&currency=' . $parameters['currency']  . '"></script>
									<script>
                                        paypal.Buttons({
                                        createOrder: function(data, actions) {
                                            // This function sets up the details of the transaction, including the amount and line item details.
                                            return actions.order.create({
                                            purchase_units: [{
                                                amount: {
                                                value: "'.$parameters['amount'].'",
                                                }
                                            }]
                                            });
                                        },
                                        onApprove: function(data, actions) {
                                            // This function captures the funds from the transaction.
                                                console.log( data );

                                                location.href = "' . $parameters['success_url'] . '?ref=" + data.orderID;
                                            //    alert( data.orderID );
                                                return actions.order.capture().then(function(details) {

                                            });
                                        }
                                        }).render("#paypal-button-container");
                                        //This function displays Smart Payment Buttons on your web page.
								    </script>' 
                            );
		}



		static function checkStatus( $orderNumber )
	    {
			$table = new Application_Subscription_Checkout_Order();
			if( ! $orderInfo = $table->selectOne( null, array( 'order_id' => $orderNumber ) ) )
			{
				return false;
			}
		//	var_export( $orderInfo );
			if( ! is_array( $orderInfo['order'] ) )
			{
				//	compatibility
				$orderInfo['order'] = unserialize( $orderInfo['order'] );
			}
		//	$orderInfo['order'] = unserialize( $orderInfo['order'] );
			$orderInfo['total'] = 0;

			foreach( $orderInfo['order']['cart'] as $name => $value )
			{
				if( ! isset( $value['price'] ) )
				{
					$value = array_merge( self::getPriceInfo( $value['price_id'] ), $value );
				}
				$orderInfo['total'] += $value['price'] * $value['multiple'];
		//		$counter++;
			}

			$secretKey = Application_Settings_Abstract::getSettings( 'paypal', 'secret_key' );
			$result = array();

			//The parameter after verify/ is the transaction reference to be verified
			$url = '//api.sandbox.paypal.com/v2/checkout/orders/' . $_REQUEST['ref'];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt(
				$ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']
			);
			curl_setopt(
				$ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer EEr2WID74opHHCdUNCZIoM0lDsgG2OqD3Uv5YXZ_rhDyCDPKvhZ2hgLMeMbsxyVG6i5vDqd8X2msK5OZ']
			);
			$request = curl_exec($ch);
			curl_close($ch);

			if ($request) {
				$result = json_decode($request, true);
			}
    		if( empty( $result['status'] ) )
			{
				//	Payment was not successful.
				$orderInfo['order_status'] = 'Payment Failed';

			}
			else
			{
				$orderInfo['order_status'] = 'Payment Successful';
			}

		//	var_export( $orderInfo );
			$orderInfo['order_random_code'] = $_REQUEST['ref'];
			$orderInfo['gateway_response'] = $result;

		//	var_export( $orderNumber );

			self::changeStatus( $orderInfo );
		//	$table->update( $orderInfo, array( 'order_id' => $orderNumber ) );

		//	$response = new SimpleXMLElement(file_get_contents($url));

	//		var_export( $orderInfo );
		//	var_export( $result );

			//	Code to change check status goes heres
		//	if( )
			return $orderInfo;
	    }


		/**
		 * Returns _formAction
		 *
		 */

	// END OF CLASS
}
