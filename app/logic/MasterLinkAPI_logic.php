<?php

namespace App\logic;

use Ixudra\Curl\Facades\Curl;

class MasterLinkAPI_logic extends Basetool
{

	/*

		功能: 登入
		回傳編碼: big5
		目的: 取得驗證碼

	*/

	private function login()
	{

		$_this = new self();

		$result = [
			"status" 	=> '',
			"code" 		=> '',
			"msg" 		=> ''
		];

		$loginURL = env( 'loginURL' );

		$loginID = 'groupid=' . env( 'loginID' );

		$loginPassword = 'password=' . md5( env( 'loginPassword' ) );

		$url = $loginURL . '?' . $loginID . '&' . $loginPassword;

		$response = Curl::to( $url )->get();

		$response = iconv("BIG5", "UTF-8", trim($response) );

		$tmp = explode("|", $response);

		$result = [
			"status"	=> $tmp[1] === '000',
			"code" 		=> $tmp[1],
			"msg" 		=> $tmp[2]
		];

		return $result;

	}

	/*

		功能: 加簽

	*/

	/*

		功能: 下單

		stock_id=股票代碼
		tradeKind=交易別0:現股,3:融資,4:融券
		buysell=買賣別B:買進,S:賣出
		quantity=數量
		price=價格
		BrokerID=分公司
		Account=帳號
		password=授權碼
		LoginID=trade帳號
		channel=單源
		IP_TEL=IP
		priceLH=漲跌停,限價0:限價,L:跌停價,H:漲停價

		待解決:
			1.	加簽
			2.	URL

		@	https://www.php.net/manual/en/function.openssl-pkcs7-sign.php
		@	https://www.php.net/manual/en/function.openssl-pkcs7-encrypt.php
		@	https://www.php.net/manual/en/openssl.certparams.php

	*/

	private function order( $verify_code = '' )
	{

		$_this = new self();

		$result = [
			"code" 	=> '',
			"msg" 	=> ''
		];

		// 產生CA

		$ca = [
			'',
			'stock_id=1101',
			'tradeKind=0',
			'buysell=B',
			'quantity=1',
			'price=10',
			'BrokerID=' . env( "broker" ),
			'Account=' . env( "account" ),
			'password=' . $verify_code,
			'LoginID=' . env( "loginID" ),
			'channel=' . env( "channel" ),
			'IP_Tel=' . env( "ip_tel" ),
			'priceLH=0',
			'',
		];

		// 產生ACA

		$aca = [
			'',
			'iFix',
			'A0006',
			'000',
			'0001',
			implode("|", $CA), // 要加簽
		];

		dd( implode("|", $data) );


		$url = $loginURL . '?' . $loginID . '&' . $loginPassword;

		$response = Curl::to( $url )->get();

		$response = iconv("BIG5", "UTF-8", trim($response) );

		$tmp = explode("|", $response);

		$result = [
			"status"	=> $tmp[1] === '000',
			"code" 		=> $tmp[1],
			"msg" 		=> $tmp[2]
		];

		return $result;

	}

	/*

		功能: 刪單

	*/

	/*

		流程

	*/

	public static function process()
	{

		$_this = new self();

		try {

			// 取得登入驗證碼

			// $login_data = $_this->login();

			// if ( $login_data["status"] === false ) {

			// 	throw new \Exception($login_data["msg"]);
				
			// }
			
			// $verify_code = $login_data["msg"];

			$_this->order( $verify_code = '123456' );

			dd($verify_code);

		} 
		catch (\Exception $e) {
			
			Record_logic::write_error_log( $action = 'process', $e->getMessage() );

		}

	}

}







