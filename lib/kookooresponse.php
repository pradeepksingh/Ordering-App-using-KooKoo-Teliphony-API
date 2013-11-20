<?php
/*
 * KooKoo XML PHP client for features like
 * a. Click to call
 * b. Missed called alerts 
 * 
*/

class Kookooresponse {
	
	private $doc;
	private $response;
	//constructor to have multiple constructors
	function __construct()
	{
		$a = func_get_args();
		$i = func_num_args();
		if (method_exists($this,$f='__construct'.$i)) {
			call_user_func_array(array($this,$f),$a);
		}
	}
	
	function __construct0()
	{
		$this->doc= new DOMDocument("1.0", "UTF-8");
	    $this->response= $this->doc->createElement("response");
		$this->doc->appendChild( $this->response);// root tag for xml responce
	}
	
	function __construct1($sid)
	{
		$this->doc= new DOMDocument("1.0", "UTF-8");
	    $this->response= $this->doc->createElement("response");
		$this->response->setAttribute( "sid", $sid);
		$this->doc->appendChild( $this->response); // root tag for xml responce
	}
	
	public function setSid($sid) // unique id for each call : session identifation number
	{
		$this->response->setAttribute( "sid", $sid);
	}
	
	public function setFiller($filler)
	{
		$this->response->setAttribute( "filler", $filler);
	}
	
	public function addPlayText($text,$speed=2,$lang="EN")// to play text
	{
         $play_text =$this->doc->createElement("playtext",$text);
         $play_text ->setAttribute( "lang", $lang);
         $play_text ->setAttribute( "speed", $speed );
         $this->response->appendChild($play_text);
    }
	
	public function addHangup(){// To Disconnect the call
		$hangup =$this->doc->createElement("hangup");
		$this->response->appendChild($hangup);
	}
	     //Dial
    public function addDial($no,$record="false",$limittime="1000",$timeout,$moh='default',$promptToCalledNumber ='no')
    {
           $dial= $this->doc->createElement("dial",$no);
           $dial ->setAttribute( "record", $record );
           $dial ->setAttribute( "limittime", $limittime );// for max calltime //maxtime call allowed after called_number answered
           $dial ->setAttribute( "timeout", $timeout );
           $dial ->setAttribute( "moh", $moh ); //moh=default will be music on hold moh=ring for normal ring
           $dial ->setAttribute( "promptToCalledNumber", $promptToCalledNumber ); //=no
         //If would like to play prompt to called number, give audio url
           // promptToCalledNumber = 'http://www.kookoo.in/recordings/promptToCallerParty.wav'
           $this->response->appendChild($dial);
    }
	       //for conferencing the call
	public function addConference($confno,$record="true")
    {
           //$confno confirence number to set
          $conf= $this->doc->createElement("conference",$confno);
          $conf ->setAttribute( "record", $record ); // to enable conference recording, record = 'true'
          // record file you can get http://recordings.kookoo.in/<kookoo-username>/<did><confno>.wav
          $this->response->appendChild($conf);
    }
	
	
	  //send sms
	public function sendSms($text,$no)
	{
		$sendsms = $this->doc->createElement( "sendsms",$text);
		$sendsms ->setAttribute( "to", $no );
		$this->response->appendChild($sendsms);
	}
	
	public function addPlayAudio($url){
		// audio to play
		//$url = 'http://ipadress/welcome.wav'
		//wav file format must be
		//PCM-128kbps-16bit-mono-8khz
		//see http://kookoo.in/index.php/kookoo-docs/audio for audio preparation
		$play_audio =$this->doc->createElement("playaudio",$url);
		$this->response->appendChild($play_audio);
	}
	
	public function addGoto($url){
	     //url should be full url : 'http://host../nextapp.app'
	     // it will jump to next url
		$goto =$this->doc->createElement("gotourl",$url);
		$this->response->appendChild($goto);
	}
	
	public function playdtmf(){
		$playdtmf =$this->doc->createElement("playtdtmf-i");
		$this->response->appendChild($playdtmf);
	}
	
	public function addCollectDtmf($cd){
		$collect_dtmf=$this->doc->importNode($cd->getRoot(),true);
		$this->response->appendChild($collect_dtmf);
	}
	
	//recordtag
	public function addRecord($filename,$format="wav",$silence="4",$maxduration="60",$option="k")
	{
		$record = $this->doc->createElement( "record",$filename);
		$record->setAttribute( "format", $format );
		$record->setAttribute( "silence", $silence);
		$record->setAttribute( "maxduration",$maxduration);
		$record->setAttribute( "option",$option);//k= keep recording after hangup
		$this->response->appendChild($record );
	}
	
	// Parse the XML.and Deconstruct
	
	public function getXML()
	{
		return $this->doc->saveXML();
	}
	
	public function send()
	{
		print $this->doc->saveXML();
	}
}
