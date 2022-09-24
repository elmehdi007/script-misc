<?php

class Scrapper{
	
	 protected $webSite;
	 protected $domain;
	 protected $emails = [];
	 protected $pages = [];
	 protected $srapManyPages;
	 protected $nbrPageToscrap;

	 //$nbrPageToscrap=0 all infini
	 function __construct(string $webSite,bool $srapManyPages = false,int $nbrPageToscrap=0) {
        print "construct with url: $webSite <br/>";  
 		
		if (empty($webSite)) throw new Exception('url should not be empty.');
		
		$this->webSite = trim($webSite);
		$this->srapManyPages = $srapManyPages;
		$this->nbrPageToscrap = $nbrPageToscrap;
		$this->domain = parse_url($this->webSite)['scheme'].'://'.parse_url($this->webSite)['host'];
		$this->pages[] = $this->webSite;

		if($this->srapManyPages === true){
				$dom = new DOMDocument();
				$result = $this->curlGetContents($this->webSite);
				@$dom->loadHTML($result);
				$links = $dom->getElementsByTagName('a');

				foreach ($links  as $key => $link) {
					
					if($key >= $this->nbrPageToscrap && $this->nbrPageToscrap>0) break;

					$tmpPage = $link->getAttribute("href");
					
					//check link if contains schema or not
					if (
						(substr($link->getAttribute("href"), 0, strlen($this->domain) ) != $this->domain)
						&& ((substr($link->getAttribute("href"), 0, 7 ) != "http://") && (substr($link->getAttribute("href"), 0, 8 ) != "https://"))
					   )
					   {
						    $addSlash = (substr($link->getAttribute("href"), 0, 1 ) != "/")?'/':'';
						    $tmpPage = $this->domain . ($addSlash) . $link->getAttribute("href");							
					   }
					   
					   if(!in_array($tmpPage, $this->pages) && (parse_url($tmpPage)['scheme'].'://'.parse_url($tmpPage)['host']) == $this->domain ) $this->pages[] = $tmpPage ;  
				}			
		}
    }

	public function getMail():array{
		return $this->emails;
	}

	public function scrapeAllData() {
		print "scrapeAllData function: <br/>";
		
		foreach ($this->pages as $key => $page) {
			$this->scrapeEmail($page);

		}
	}

	protected function scrapeEmail(string $page) {
				
		print "scrapingEmail: ". $page ."<br/>";
		$result = $this->curlGetContents($page);
			
		if ($result != FALSE) { 
			
			// Convert to lowercase
			$result = strtolower($result);
				
			// Replace EMAIL DOT COM
			$result = preg_replace('#[(\\[\\<]?AT[)\\]\\>]?\\s*(\\w*)\\s*[(\\[\\<]?DOT[)\\]\\>]?\\s*[a-z]{3}#ms', '@$1.com', $result);
				
			// Email matches
			preg_match_all('#\\b([\\w\\._]*)[\\s(]*@[\\s)]*([\\w_\\-]{3,})\\s*\\.\\s*([a-z]{3})\\b#msi', $result, $matches);
				
			$usernames = $matches[1];
			$accounts = $matches[2];
			$suffixes = $matches[3];
			for ($i = 0; $i < count($usernames); $i++) {
					$tmpMail = $this->formatCleanEmail($usernames[$i], $accounts[$i], $suffixes[$i]);
					if(!in_array($tmpMail, $this->emails)) $this->emails[$i] = $tmpMail;
			}
		}

		return $this->emails;
	}

	protected function clean(string $str) { return ( !is_string($str) )? $str:trim(strtolower($str));}

	protected function formatCleanEmail(string $usernames,string $domainName,string $suffixes) { return $this->clean($usernames) . '@' . $this->clean($domainName) . '.' .$this->clean($suffixes) ;}

	protected function curlGetContents(string $page) {
		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		// For https connections, we do not require SSL verification
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		//curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		$content = curl_exec($ch);
		//$error = curl_error($ch);
		//$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $content;
	}
}


$scrapper  = new Scrapper("https://ksoutdoors.com/content/download/47637/485962/version/1/file/Cheyenne+Bottoms+Wildlife+Area+Newsletter+6-29-2016.html");
$scrapper->scrapeAllData();
var_dump($scrapper->getMail());

