/*This file contains the code to convert WC_PRICE function of php to javascript */
function number_format( number ) {

    var decimals = wdm_bundle_params.currency_format_num_decimals;
    var decimal_sep = wdm_bundle_params.currency_format_decimal_sep;
    var thousands_sep = wdm_bundle_params.currency_format_thousand_sep;

    var n = number, c = isNaN( decimals = Math.abs( decimals ) ) ? 2 : decimals;
    var d = decimal_sep == undefined ? "," : decimal_sep;
    var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
    var i = parseInt( n = Math.abs( +n || 0 ).toFixed( c ) ) + "", j = ( j = i.length ) > 3 ? j % 3 : 0;

    return s + ( j ? i.substr( 0, j ) + t : "" ) + i.substr( j ).replace( /(\d{3})(?=\d)/g, "$1" + t ) + ( c ? d + Math.abs( n - i ).toFixed( c ).slice( 2 ) : "" );
}

function get_added_price( $product_price ) {

    var old_price = jQuery('.wdm-bundle-bundle-box').data('bundle-price');
    if(old_price == '') {
        old_price = '0.00';
    }
    var overall_qty = parseInt(jQuery('.bundle_button').find('.qty').val());
    var new_price = parseFloat( parseFloat( old_price ) + parseFloat( $product_price ) * overall_qty );
    jQuery('.wdm-bundle-bundle-box').data('bundle-price', new_price);
    // jQuery('meta[itemprop="price"]').attr('content', new_price );
    return new_price;
}

function wdm_get_price_format( new_price ) {

        var new_price = number_format( new_price );

        var remove = wdm_bundle_params.currency_format_decimal_sep;

        if ( wdm_bundle_params.currency_format_trim_zeros == 'yes' && wdm_bundle_params.currency_format_num_decimals > 0 ) {

            for ( var i = 0; i < wdm_bundle_params.currency_format_num_decimals; i++ ) {
                remove = remove + '0';
            }

            new_price = new_price.replace( remove, '' );
        }

        var new_price_format = '';

        if ( wdm_bundle_params.currency_position == 'left' ) {
            new_price_format = wdm_bundle_params.currency_symbol + new_price;
        }
        else if ( wdm_bundle_params.currency_position == 'right' ) {
            new_price_format = new_price + wdm_bundle_params.currency_symbol;
        }
        else if ( wdm_bundle_params.currency_position == 'left_space' ) {
            new_price_format = wdm_bundle_params.currency_symbol + ' ' + new_price;
        }
        else if ( wdm_bundle_params.currency_position == 'right_space' ) {
            new_price_format = new_price + ' ' + wdm_bundle_params.currency_symbol;
        }

        return new_price_format;

}

// /**
//  * Function for sale individual
//  */
// function canProductBeAdded( item_id )
// {
//     if ( sld_ind[ item_id ] == undefined) {
//         return true;
//     }
//     return false;
// }

function preg_quote(str, delimiter) {
  

  return String(str)
    .replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
}

function sprintf() {
  var regex = /%%|%(\d+\$)?([-+\'#0 ]*)(\*\d+\$|\*|\d+)?(\.(\*\d+\$|\*|\d+))?([scboxXuideEfFgG])/g;
  var a = arguments;
  var i = 0;
  var format = a[i++];

  // pad()
  var pad = function(str, len, chr, leftJustify) {
    if (!chr) {
      chr = ' ';
    }
    var padding = (str.length >= len) ? '' : new Array(1 + len - str.length >>> 0)
      .join(chr);
    return leftJustify ? str + padding : padding + str;
  };

  // justify()
  var justify = function(value, prefix, leftJustify, minWidth, zeroPad, customPadChar) {
    var diff = minWidth - value.length;
    if (diff > 0) {
      if (leftJustify || !zeroPad) {
        value = pad(value, minWidth, customPadChar, leftJustify);
      } else {
        value = value.slice(0, prefix.length) + pad('', diff, '0', true) + value.slice(prefix.length);
      }
    }
    return value;
  };

  // formatBaseX()
  var formatBaseX = function(value, base, prefix, leftJustify, minWidth, precision, zeroPad) {
    // Note: casts negative numbers to positive ones
    var number = value >>> 0;
    prefix = prefix && number && {
      '2': '0b',
      '8': '0',
      '16': '0x'
    }[base] || '';
    value = prefix + pad(number.toString(base), precision || 0, '0', false);
    return justify(value, prefix, leftJustify, minWidth, zeroPad);
  };

  // formatString()
  var formatString = function(value, leftJustify, minWidth, precision, zeroPad, customPadChar) {
    if (precision != null) {
      value = value.slice(0, precision);
    }
    return justify(value, '', leftJustify, minWidth, zeroPad, customPadChar);
  };

  // doFormat()
  var doFormat = function(substring, valueIndex, flags, minWidth, _, precision, type) {
    var number, prefix, method, textTransform, value;

    if (substring === '%%') {
      return '%';
    }

    // parse flags
    var leftJustify = false;
    var positivePrefix = '';
    var zeroPad = false;
    var prefixBaseX = false;
    var customPadChar = ' ';
    var flagsl = flags.length;
    for (var j = 0; flags && j < flagsl; j++) {
      switch (flags.charAt(j)) {
        case ' ':
          positivePrefix = ' ';
          break;
        case '+':
          positivePrefix = '+';
          break;
        case '-':
          leftJustify = true;
          break;
        case "'":
          customPadChar = flags.charAt(j + 1);
          break;
        case '0':
          zeroPad = true;
          customPadChar = '0';
          break;
        case '#':
          prefixBaseX = true;
          break;
      }
    }

    // parameters may be null, undefined, empty-string or real valued
    // we want to ignore null, undefined and empty-string values
    if (!minWidth) {
      minWidth = 0;
    } else if (minWidth === '*') {
      minWidth = +a[i++];
    } else if (minWidth.charAt(0) == '*') {
      minWidth = +a[minWidth.slice(1, -1)];
    } else {
      minWidth = +minWidth;
    }

    // Note: undocumented perl feature:
    if (minWidth < 0) {
      minWidth = -minWidth;
      leftJustify = true;
    }

    if (!isFinite(minWidth)) {
      throw new Error('sprintf: (minimum-)width must be finite');
    }

    if (!precision) {
      precision = 'fFeE'.indexOf(type) > -1 ? 6 : (type === 'd') ? 0 : undefined;
    } else if (precision === '*') {
      precision = +a[i++];
    } else if (precision.charAt(0) == '*') {
      precision = +a[precision.slice(1, -1)];
    } else {
      precision = +precision;
    }

    // grab value using valueIndex if required?
    value = valueIndex ? a[valueIndex.slice(0, -1)] : a[i++];

    switch (type) {
      case 's':
        return formatString(String(value), leftJustify, minWidth, precision, zeroPad, customPadChar);
      case 'c':
        return formatString(String.fromCharCode(+value), leftJustify, minWidth, precision, zeroPad);
      case 'b':
        return formatBaseX(value, 2, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
      case 'o':
        return formatBaseX(value, 8, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
      case 'x':
        return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
      case 'X':
        return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
          .toUpperCase();
      case 'u':
        return formatBaseX(value, 10, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
      case 'i':
      case 'd':
        number = +value || 0;
        number = Math.round(number - number % 1); // Plain Math.round doesn't just truncate
        prefix = number < 0 ? '-' : positivePrefix;
        value = prefix + pad(String(Math.abs(number)), precision, '0', false);
        return justify(value, prefix, leftJustify, minWidth, zeroPad);
      case 'e':
      case 'E':
      case 'f': // Should handle locales (as per setlocale)
      case 'F':
      case 'g':
      case 'G':
        number = +value;
        prefix = number < 0 ? '-' : positivePrefix;
        method = ['toExponential', 'toFixed', 'toPrecision']['efg'.indexOf(type.toLowerCase())];
        textTransform = ['toString', 'toUpperCase']['eEfFgG'.indexOf(type) % 2];
        value = prefix + Math.abs(number)[method](precision);
        return justify(value, prefix, leftJustify, minWidth, zeroPad)[textTransform]();
      default:
        return substring;
    }
  };

  return format.replace(regex, doFormat);
}

function cpbFormatPrice(price){
            decimal_separator = wdm_bundle_params.currency_format_decimal_sep;
            thousand_separator = wdm_bundle_params.currency_format_thousand_sep;
            decimals = wdm_bundle_params.decimals;
            price_format = wdm_bundle_params.price_format;

            negative        = price < 0;
            price           = parseFloat(negative ? price * -1 : price) || 0
            price           = number_format( price, decimals, decimal_separator, thousand_separator )

            if ( decimals > 0 ) {
                price = price.replace('/' + preg_quote( decimal_separator, '/' ) + '0++$/', '');
                //price = preg_replace( '/' + preg_quote( decimal_separator, '/' ) + '0++$/', '', price );
            }

            return ( negative ? '-' : '' ) + sprintf( price_format, quote_data.currency_symbol, price );
}

function array_diff (arr1) { // eslint-disable-line camelcase
  //  discuss at: http://locutus.io/php/array_diff/
  // original by: Kevin van Zonneveld (http://kvz.io)
  // improved by: Sanjoy Roy
  //  revised by: Brett Zamir (http://brett-zamir.me)
  //   example 1: array_diff(['Kevin', 'van', 'Zonneveld'], ['van', 'Zonneveld'])
  //   returns 1: {0:'Kevin'}
  var retArr = {}
  var argl = arguments.length
  var k1 = ''
  var i = 1
  var k = ''
  var arr = {}
  arr1keys: for (k1 in arr1) { // eslint-disable-line no-labels
    for (i = 1; i < argl; i++) {
      arr = arguments[i]
      for (k in arr) {
        if (arr[k] === arr1[k1]) {
          // If it reaches here, it was found in at least one array, so try next value
          continue arr1keys // eslint-disable-line no-labels
        }
      }
      retArr[k1] = arr1[k1]
    }
  }
  return retArr
}


function object_diff(obj1, obj2) {
    var result = {};
    jQuery.each(obj1, function (key, value) {
        if (!obj2.hasOwnProperty(key) || obj2[key] !== obj1[key]) {
            result[key] = value;
        }
    });

    return result;
}


// function cpbPriceForCalculation(price){
//             if(wdm_bundle_params.use_value_for_calculation == false){
//               decimal_separator = wdm_bundle_params.currency_format_decimal_sep;
//               thousand_separator = wdm_bundle_params.currency_format_thousand_sep;
//               decimals = wdm_bundle_params.decimals;
//               price = price.replace(thousand_separator, "");
//               price = price.replace(decimal_separator, ".");
//               wdm_bundle_params.use_value_for_calculation = true;
//               return parseFloat(price);
//             } else {
//               return parseFloat(price);
//             }
            
// }
/***  
 * Reference: https://gist.github.com/plepe/3891980
 * 
 * Defining __FILE__ variable, which holds the path to the file
 * from which the currently running source is being executed.
 *
 * Usage example: alert(__FILE__);
 *
 * Thanks to http://ejohn.org/blog/__file__-in-javascript/ on which this gist
 * is based on.
 *
 * Tested in Mozilla Firefox 9, Mozilla Firefox 16, Opera 12, Chromium 18
 */
(function(){
  this.__defineGetter__("__FILE__", function() {
    var stack=((new Error).stack).split("\n");

    if(stack[0]=="Error") { // Chromium
      var m;
      if(m=stack[2].match(/\((.*):[0-9]+:[0-9]+\)/))
  return m[1];
    }
    else { // Firefox, Opera
      return stack[1].split("@")[1].split(":").slice(0,-1).join(":");
    }
  });
})();