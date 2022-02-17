<?php

class FaqSiteTreeControllerExtension extends Extension {
	
	public static $check_security_hash = false; // default off (eg caching), optionally on
	
	public static $allowed_actions = array(
        // someaction can be accessed by anyone, any time
        'faqvote', 
	);
	
	// Add this method to the Pages in order to get the Faqs in the right SortOrder (overwrite to do filtering)
	public function Faqs(){
		return $this->owner->getManyManyComponents('Faqs')->sort('FaqSortOrderForPage');
	}
	
	// Count clicks on FAQs in order to 'vote';
	public function faqvote(){
		if(self::$check_security_hash){ // may be off when caching
			$securityHash = $this->owner->urlParams['OtherID'];
			if(	! $securityHash || $securityHash !== SecurityToken::getSecurityID() ){
				return $this->owner->httpError(403, 'Possible CSRF');
			}
		}
		if(	$faqid = $this->owner->urlParams['ID'] ) {
			// check already voted;
			if( $voted = Cookie::get('votedfaqs') ){
				$voted_arr = explode(',',$voted);
				// 304: not changed (not sure if that's a correct status to report though...)
				if (is_array($voted_arr) && in_array($faqid, $voted_arr)) {
					return $this->owner->httpError(304, 'Already voted');
				}
			}
			// no cookie set yet, or not voted on faq yet...
			$faq = Faq::get()->byID($faqid);
			$faq->ClickCount = $faq->ClickCount +1;
			$faq->write();
			// save vote to cookie
			if ( $voted_arr ){
				$voted_arr[] = $faqid; //add
			} else {
				$voted_arr = array($faqid);
			}
			Cookie::set('votedfaqs', implode(',',$voted_arr));
			return "OK";
		};
	}
	

}