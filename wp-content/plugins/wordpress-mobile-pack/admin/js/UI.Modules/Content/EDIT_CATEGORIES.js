/*****************************************************************************************************/
/*                                                                                                   */
/*                                    	'ACTIVATE / DEACTIVATE CATEGORIES'                           */
/*                                                                                                   */
/*****************************************************************************************************/

function EDIT_CATEGORIES(){

    var JSObject = this;

    this.type = "editcategories";

    this.form;
    this.DOMDoc;
    
    this.changingStatus = false;

	/*****************************************************************************************************/
    /*                                                                                                   */
    /*                              FUNCTION INIT - called from JSInterface                              */
    /*                                                                                                   */
    /*****************************************************************************************************/
    this.init = function(){

        // save a reference to JSInterface Object
        JSInterface = window.parent.JSInterface;

        // save a reference to the FORM and remove the default submit action
        this.form = this.DOMDoc.getElementById(this.type+'_form');

        if (this.form == null){
            return;
        }

        this.initCategories();
    }


    /*****************************************************************************************************/
    /*                                                                                                   */
    /*                                  FUNCTION INIT VALIDATION                                         */
    /*                                                                                                   */
    /*****************************************************************************************************/
    this.initCategories = function(){

        /*****************************************************************************************************/
	    /*                                                                                                   */
	    /*                                	CHANGE STATUS ITEM ACTIONS                                		 */
	    /*                                                                                                   */
	    /*****************************************************************************************************/
	    
        console.log(ajaxurl)
        jQuery( "li .status", this.form ).on("click", function(){
		
			var isConfirmed = confirm("Are you sure you want to change the status for this category?");
	
			if (isConfirmed) {

				var currentStatus;
                
                var statusContainer = jQuery(this);
				var categoryId = statusContainer.attr("data-category-id");
                
				if (statusContainer.hasClass("active") == false) {
					currentStatus = "active";	
				} else {
					currentStatus = "inactive";
				}
					
				if (JSObject.changingStatus == false) {
					
                    JSInterface.Preloader.start();
                    
                    jQuery.post(
                        ajaxurl, 
                        {
                            'action': 'wmp_content_save',
                            'id':   categoryId,
                            'status': currentStatus
                        }, 
                        function(response){
                            
                            JSObject.changingStatus = false;
                            JSInterface.Preloader.remove(100);
                            
                           	var response = Boolean(Number(String(response)));
						 
							if (response == true) {
							
                                // change status class and text
                                statusContainer.addClass(currentStatus);
                                statusContainer.removeClass(currentStatus == 'active' ? 'inactive' : 'active');
                                
                                statusContainer.text(currentStatus);
                                
								// success message								
								var message = 'The status of this category has been changed.';
                                JSInterface.Loader.display({message: message});
                                
                                // count remaining active categories
                                var no_active_categories = jQuery( "li span.active", JSObject.form ).length;
                                if (no_active_categories > 0){
                                    jQuery('#'+JSObject.type+'_warning', JSObject.DOMDoc).hide();
                                } else {
                                    jQuery('#'+JSObject.type+'_warning', JSObject.DOMDoc).show();
                                }
                                
							} else {
							
								// error message
								var message = 'There was an error. Please reload the page and try again in few seconds or contact the plugin administrator if the problem persists.';
                                JSInterface.Loader.display({message: message});	
                                
							}
                        }
                    );
				}
			}
		});
    }

}