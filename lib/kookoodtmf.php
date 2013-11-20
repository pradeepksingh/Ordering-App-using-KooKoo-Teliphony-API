<?php
/*
 * KooKoo PHP DTMF lirary for features like
 * a. Click to call
 * b. Missed called alerts 
 * 
*/

class Kookoodtmf {
	
		private $doc;
		private $collect_dtmf;
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
            $this->collect_dtmf= $this->doc->createElement("collectdtmf");
			$this->doc->appendChild( $this->collect_dtmf);
		}
		
		function __construct3($max_digits,$term_char,$time_out=4000) //time out in ms
		{
			$this->doc= new DOMDocument("1.0", "UTF-8");
           		 $this->collect_dtmf= $this->doc->createElement("response");
			$this->collect_dtmf->setAttribute( "l", $max_digits);
			$this->collect_dtmf->setAttribute( "t", $term_char);
			$this->collect_dtmf->setAttribute( "o", $time_out);
			$this->doc->appendChild( $this->collect_dtmf);
		}
		
		public function setMaxDigits($maxDigits)
		{
			$this->collect_dtmf->setAttribute("l", $maxDigits);
		}
		
		public function setTermChar($termChar)
		{
		//if dtmf maxdigits not fixed and variable send termination
		//example if your asking enter amount, user can enter any input 
		// 1 - n number exampe 1 or 20 2000 etc
		//then ask cutomer to enter amount followed by hash set termchar=# 
		//set maxdigits=<maximum number to be allowed>
			$this->collect_dtmf->setAttribute("t", $termChar);
			
		}
		
		public function setTimeOut($timeOut)
		{
	    	$this->collect_dtmf->setAttribute("o", $timeOut=4000);
	    	//time out in ms default is 4000ms,
		}
		
        public function addPlayText($text,$speed=2,$lang="EN")
		{
            $play_text =$this->doc->createElement("playtext",$text);
			$play_text ->setAttribute("speed", $speed );
            $play_text ->setAttribute( "lang", $lang);
			$this->collect_dtmf->appendChild($play_text);
		}
		
		public function addPlayAudio($url,$speed=2){
			$play_audio =$this->doc->createElement("playaudio",$url);
			$this->collect_dtmf->appendChild($play_audio);
		}
		
		public function getRoot()
		{
			return $this->collect_dtmf;
		}
}
