<?php

// @ini_set('display_errors', 1);

class CellactHttpRequest {
	
	var $_useCURL = true;
	var $_response = false;
	private $url;
	private $Company;
	private $UserName;
	private $password;
	public function __construct($Company,$UserName, $password)
  {
            $this->Company = $Company;
            $this->UserName = $UserName;
            $this->password = $password;
  }
	
	public function sendRequest($sender,$recipient,$content) {
	//using cdata for content
    $this->content= '<PALO><HEAD><FROM>'.$this->Company.'</FROM><APP USER="'.$this->UserName.'" PASSWORD="'.$this->password.'">LA</APP><CMD>sendtextmt</CMD></HEAD><BODY><SENDER>'.$sender.'</SENDER><CONTENT><![CDATA['.$content.']]></CONTENT><DEST_LIST><TO>'.$recipient.'</TO></DEST_LIST></BODY><OPTIONAL><MSG_ID>1123527</MSG_ID></OPTIONAL></PALO>';
	$this->url="http://la.cellactpro.com/unistart5.asp";
    // echo $this->content."\nSent to ".$this->url."\n";
		$this->_response = $this->_do_post_request_cURL($this->url, $this->content);
		return $this->_response;
	}
	
	public function getResponse() {
		return $_response;
	}
	

	private function _do_post_request_cURL($url, $XMLString) {
		$headers[] = "Content-type: application/x-www-form-urlencoded";
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER , 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	  $XMLString = str_replace('%' , '%25' , $XMLString);
	  $XMLString = str_replace(' ' , '%20' , $XMLString);
    $XMLString = str_replace('#' , '%23' , $XMLString);
    $XMLString = str_replace('&' , '%26' , $XMLString);
    $XMLString = str_replace('?' , '%3F' , $XMLString);
    $XMLString = str_replace('+' , '%2B' , $XMLString);
	$XMLString = str_replace('\n' , '&#xA;' , $XMLString);
		curl_setopt($curl, CURLOPT_POSTFIELDS, "XMLString=$XMLString");
		$response = curl_exec($curl);
		curl_close($curl);
	  return $response;
	}
	
}

// $cellact = new CellactHttpRequest("caesarstone","caesarstone", "LjipP2M2"); 
// $content = "Heelo\nWorld";
// $res=$cellact->sendRequest("1234","+972543240839","<CONTENT><![CDATA['.$content.']]></CONTENT>");
// echo $res;

// print_r($cellact->getResponse());
//$res=$cellact->sendRequest("1234","+972543240839","שלום  בדיקה");

?>