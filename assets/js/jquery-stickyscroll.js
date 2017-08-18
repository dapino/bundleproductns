/**
 * StickyScroll
 * written by Rick Harris - @iamrickharris
 * 
 * Requires jQuery 1.4+
 * 
 * Make elements stick to the top of your page as you scroll
 *
 * See README for details
 *
*/

(function($) {
  $.fn.stickyScroll = function(options) {
  
    var methods = {
      
      init : function(options) {
        
        var settings, topBoundaryVal, htmlMarginTop = 0;
        
        if (options.mode !== 'auto' && options.mode !== 'manual') {
          if (options.container) {
            options.mode = 'auto';
          }
          if (options.bottomBoundary) {
            options.mode = 'manual';
          }
        }
        
        settings = $.extend({
          mode: 'auto', // 'auto' or 'manual'
          container: $('body'),
          topBoundary: null,
          bottomBoundary: null,
          offsetBottomValue: 0,
        }, options);
        
        function bottomBoundary() {
          return $(document).height() - settings.container.offset().top
            - settings.container.outerHeight() + settings.offsetBottomValue;
        }


        topBoundaryVal = $(this).offset().top;

        if($("#wpadminbar").length == 1){
          htmlMarginTop = parseInt($("html").css("margin-top"));
        }

        function topBoundary() {
          //return settings.container.offset().top
          return topBoundaryVal;
        }

        function elHeight(el) {
          return $(el).outerHeight();
        }
       
        // make sure user input is a jQuery object
        settings.container = $(settings.container);
        if(!settings.container.length) {
          if(console) {
            console.log('StickyScroll: the element ' + options.container +
              ' does not exist, we\'re throwing in the towel');
          }
          return;
        }

        // calculate automatic bottomBoundary
        if(settings.mode === 'auto') {
          settings.topBoundary = topBoundary();
          settings.bottomBoundary = bottomBoundary();
        }

        return this.each(function(index) {

          var el = $(this),
            win = $(window),
            id = Date.now() + index,
            height = elHeight(el),
            HeaderElementRefrence = el.parents("body").find("header"),
            HeaderElementTopSpace = parseInt(el.css('padding-top'))/2;

            
            
          el.data('sticky-id', id);
          
          win.bind('scroll.stickyscroll-' + id, function() {
            var top = $(document).scrollTop(),
              bottom = $(document).height() - top - height;

           function fixedHeaderHeight()
            {  
              if((HeaderElementRefrence.css("position") === "fixed") || (HeaderElementRefrence.children().css("position") === "fixed")  && $(window).scrollTop() > (settings.topBoundary / 2))
              {
                return HeaderElementRefrence.outerHeight(true);
              }
              return 0;
            } 

            if(bottom <= settings.bottomBoundary + fixedHeaderHeight() + htmlMarginTop) {
              el.offset({
                top: $(document).height() - settings.bottomBoundary - height
              })
              .removeClass('sticky-active')
              .removeClass('sticky-inactive')
              .addClass('sticky-stopped');
            }
            else if(top > settings.topBoundary - fixedHeaderHeight() - htmlMarginTop) {
              el.offset({
                top: $(window).scrollTop() + fixedHeaderHeight() + htmlMarginTop
              })
              .removeClass('sticky-stopped')
              .removeClass('sticky-inactive')
              .addClass('sticky-active').addClass("test");

            }
            else if(top < settings.topBoundary) {
              el.css({
                position: 'relative',
                top: '0',
                bottom: ''
              })
              .removeClass('sticky-stopped')
              .removeClass('sticky-active')
              .addClass('sticky-inactive');
            }
          });
          
          win.bind('resize.stickyscroll-' + id, function() {
            if (settings.mode === 'auto') {
              settings.topBoundary = topBoundary();
              settings.bottomBoundary = bottomBoundary();
            }
            height = elHeight(el);
            $(this).scroll();
          })
          
          el.addClass('sticky-processed');
          
          // start it off
          win.scroll();

        });
        
      },
      
      reset : function() {
        return this.each(function() {
          var el = $(this),
            id = el.data('sticky-id');
            
          el.css({
            position: 'relative',
            top: '0',
            bottom: ''
          })
          .removeClass('sticky-stopped')
          .removeClass('sticky-active')
          .removeClass('sticky-inactive')
          .removeClass('sticky-processed');
          
          $(window).unbind('.stickyscroll-' + id);
        });
      }
      
    };
    
    // if options is a valid method, execute it
    if (methods[options]) {
      return methods[options].apply(this,
        Array.prototype.slice.call(arguments, 1));
    }
    // or, if options is a config object, or no options are passed, init
    else if (typeof options === 'object' || !options) {
      return methods.init.apply(this, arguments);
    }
    
    else if(console) {
      console.log('Method' + options +
        'does not exist on jQuery.stickyScroll');
    }

  };
})(jQuery);