<?php

class FaqAdmin extends ModelAdmin {
  public static $managed_models = array('Faq', 'FaqCategory'); // Can manage multiple models
  public static $sortable_models = array('Faq', 'FaqCategory'); // which should be sortable;
  static $url_segment = 'faqs'; // Linked as /admin/leftemails/
  static $menu_title = 'FAQs';
  public $showImportForm = false;
  
  public function getEditForm($id = null, $fields = null) {
      $form = parent::getEditForm($id = null, $fields = null);
	  foreach(self::$sortable_models as $model){
		  if( $GF = $form->Fields()->fieldByName($model) ){
				$GF->getConfig()->addComponent(new GridFieldOrderableRows());
		  }
	  }
      return $form;
  }
  
}