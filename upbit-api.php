<?php
/**
 * API-call related functions
 *
 * @author upbitdev
 * @license MIT License - https://github.com/upbitdev/php
 */
class UpbitAPI {
    
    const DIRECTION_BUY = 'buy';
    const DIRECTION_SELL = 'sell';
    protected $public_api = 'https://upbit.org/data/public/v1';

    protected $apiKey;
    protected $apiSecret;
    protected $nonce;

    public function __construct($apiKey, $apiSecret, $startNonce = false) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        if(!$startNonce)
            $this->noonce = time();
        else
            $this->noonce = $startNonce;
    }
    
    /**
     * Get the nonce
     * @return int
     */
    protected function getNonce() {
        $this->noonce++;
        return $this->noonce;
    }

	public function sendQuery($method, $req = array()) {
		$req['method']  = $method;
		$req['nonce']   = $this->getNonce();

		$post_data = http_build_query($req, '', '&');

		$sign = hash_hmac("sha512", $post_data, $this->apiSecret);

		$headers = array(
			'Sign: '.$sign,
			'Key: '.$this->apiKey,
		);

		// Create a CURL Handler for use
		$ch = null;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Marinu666 BTCE PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
		curl_setopt($ch, CURLOPT_URL, 'http://api.upbit.loc');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$res = curl_exec($ch);

		if($res === false) {
			$e = curl_error($ch);
			curl_close($ch);
			throw new UpbitAPIException('Could not get reply: '.$e);
		} else {
			curl_close($ch);
		}

		$result = json_decode($res, true);
		if(!$result)
			throw new UpbitAPIException('Invalid data received, please make sure connection is working and requested API exists');

		return $result;
	}

	public function getInfo()
	{
		return $this->sendQuery('getInfo');
	}

	/**
	 * @param string $pair
	 * @param int $limit
	 * @param string $order 'DESC'||'ASC'
	 * @param $since
	 * @param $end
	 * @return mixed
	 */
	public function getTradeHistory($pair = 'btc_usd', $limit = 1000, $order = 'DESC', $since = null, $end = null)
	{
		return $this->sendQuery('getTradeHistory', array(
			'pair'  => $pair,
			'limit' => $limit,
			'order' => $order,
			'since' => $since,
			'end'   => $end
		));
	}

	/**
	 * @param int $orderId
	 * @return mixed
	 */
	public function getOrder($orderId)
	{
		return $this->sendQuery('getOrder', array(
			'order_id' => $orderId
		));
	}

	/**
	 * @param string $pair
	 * @param $type null||'sell'||'buy'
	 * @param $status null||'open'||'closed'||'cancelled'
	 * @param int $limit
	 * @param string $order 'DESC'||'ASC'
	 * @param $since
	 * @param $end
	 * @return mixed
	 */
	public function getOrders($pair = null, $type = null, $status = null, $limit = 1000, $order = 'DESC', $since = null, $end = null)
	{
		return $this->sendQuery('getOrders', array(
			'pair'      => $pair,
			'type'      => $type,
			'status'    => $status,
			'limit'     => $limit,
			'order'     => $order,
			'since'     => $since,
			'end'       => $end
		));
	}

	/**
	 * @param $pair
	 * @param $price
	 * @param $amount
	 * @param $type 'sell'||'buy'
	 * @return mixed
	 */
	public function trade($pair, $price, $amount, $type)
	{
		return $this->sendQuery('trade', array(
			'pair'      => $pair,
			'price'     => $price,
			'amount'    => $amount,
			'type'      => $type
		));
	}

	/**
	 * @param int $orderId
	 * @return mixed
	 */
	public function cancel($orderId)
	{
		return $this->sendQuery('cancelOrder', array(
			'order_id' => $orderId
		));
	}

	/**
	 * Retrieve some JSON
	 * @param string $URL
	 * @return mixed
	 */
	protected function retrieveJSON($URL) {
		$opts = array('http' =>
			array(
				'method'  => 'GET',
				'timeout' => 10
			)
		);
		$context  = stream_context_create($opts);
		$feed = file_get_contents($URL, false, $context);
		$json = json_decode($feed, true);
		return $json;
	}

    /**
     * @param string $pair
     * @return array 
     */
    public function getTicker($pair = null) {
	    $url = $this->public_api.'/ticker/'.(($pair)?$pair:'');
        return $this->retrieveJSON($url);
    }

	/**
	 * @param $pair
	 * @param $limit
	 * @param $since
	 * @return mixed
	 */
	public function getAllTradeHistory($pair, $limit = null, $since = null) {
	    $url = $this->public_api.'/history/'.$pair.'?';
	    if($limit)
		    $url .= 'limit='.$limit.'&';
	    if($since)
		    $url .= 'since='.$since.'&';
        return $this->retrieveJSON($url);
    }

	/**
	 * @param $pair
	 * @param $limit
	 * @return mixed
	 */
	public function getOrderBook($pair, $limit = null) {
		$url = $this->public_api.'/orderbook/'.$pair.'?';
		if($limit)
			$url .= 'limit='.$limit.'&';
		return $this->retrieveJSON($url);
    }
}

/**
 * Exceptions
 */
class UpbitAPIException extends Exception {
	public function __construct($message = "", $code = 0, Exception $previous = null) {
		echo json_encode(array(
			'success' => $code,
			'error' => $message
		));
	}
}
