(function ( $, global ) {
    var element;
    var wdm_snackbar = {

        _hideSnackBar : function(){
            setTimeout(function(){ element.removeClass('show') }, 3000);  
        },

        _showSnackBar : function(){
            if( ! element.hasClass('show') ) {
                element.addClass('show');
            }
            this._hideSnackBar();
            return this;
        },

        _setMessage : function($message){
            element.html($message);
            return this;
        },

        _addSnackbarClass : function(){
            if(! element.hasClass('wdm-snackbar')) {
                element.addClass('wdm-snackbar');
            } 
            return this;
        },
        
        _createHTMLelement : function(){

            if( $('.wdm-snackbar').length <= 0) {
                element = $(document.createElement('div'));
            } else {
                element = $('.wdm-snackbar');
            }
            
            return this;
        }
    };

    var snackBarWrapper = function($message){
        wdm_snackbar._createHTMLelement()._addSnackbarClass()._setMessage($message)._showSnackBar();

        jQuery('body').append(element);
    }
    
    global.snackbar = snackBarWrapper;

}( jQuery, window ));