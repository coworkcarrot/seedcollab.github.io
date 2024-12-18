(function (root, factory) {
    //amd
    if (typeof define === "function" && define.amd) {
        define(['sprintf-js'], function (sprintf) {
            return factory(sprintf.vsprintf);
        });

        //commonjs
    } else if (typeof module === "object" && module.exports) {
        module.exports = factory(require('sprintf-js').vsprintf);

        //global
    } else {
        root.Translator = factory(window.vsprintf);
    }

    var i18n = new Translator(TRANSLATIONS);
    window['__'] = function (sMsg, sGroupID) {
        return i18n.p__(sGroupID, sMsg);
    };

    window['__pn'] = function (sMsgID, sGroupID, iValue) {
        if (iValue === undefined || I18N_FN.isNumber(iValue) === false) {
            iValue = 0;
        }
        return i18n.np__(sGroupID, sMsgID, sMsgID + '.PLURAL', iValue);
    };
}(this, function (vsprintf) {
    "use strict";
    function Translator (translations) {
        this.dictionary = {};
        this.plurals = {};
        this.domain = null;

        if (translations) {
            this.loadTranslations(translations);
        }
    }

    Translator.prototype = {
        loadTranslations: function (translations) {
            var domain = translations.domain || '';

            if (this.domain === null) {
                this.domain = domain;
            }

            if (this.dictionary[domain]) {
                mergeTranslations(this.dictionary[domain], translations.messages);
                return this;
            }

            if (translations.fn) {
                this.plurals[domain] = { fn: translations.fn };
            } else if (translations['plural-forms']) {
                var plural = translations['plural-forms'].split(';', 2);

                this.plurals[domain] = {
                    count: parseInt(plural[0].replace('nplurals=', '')),
                    code: plural[1].replace('plural=', 'return ') + ';'
                };
            }

            this.dictionary[domain] = translations.messages;

            return this;
        },

        defaultDomain: function (domain) {
            this.domain = domain;

            return this;
        },

        gettext: function (original) {
            return this.dpgettext(this.domain, null, original);
        },

        ngettext: function (original, plural, value) {
            return this.dnpgettext(this.domain, null, original, plural, value);
        },

        dngettext: function (domain, original, plural, value) {
            return this.dnpgettext(domain, null, original, plural, value);
        },

        npgettext: function (context, original, plural, value) {
            return this.dnpgettext(this.domain, context, original, plural, value);
        },

        pgettext: function (context, original) {
            return this.dpgettext(this.domain, context, original);
        },

        dgettext: function (domain, original) {
            return this.dpgettext(domain, null, original);
        },

        dpgettext: function (domain, context, original) {
            var translation = getTranslation(this.dictionary, domain, context, original);

            if (translation !== false && translation[0] !== '') {
                return translation[0];
            }

            return original;
        },

        dnpgettext: function (domain, context, original, plural, value) {
            var index = getPluralIndex(this.plurals, domain, value);
            var translation = getTranslation(this.dictionary, domain, context, original);

            if (translation[index] && translation[index] !== '') {
                return translation[index];
            }

            return (index === 0) ? original : plural;
        },

        __: function (original) {
            return format(
                this.gettext(original),
                Array.prototype.slice.call(arguments, 1)
            );
        },

        n__: function (original, plural, value) {
            return format(
                this.ngettext(original, plural, value),
                Array.prototype.slice.call(arguments, 3)
            );
        },

        p__: function (context, original) {
            return format(
                this.pgettext(context, original),
                Array.prototype.slice.call(arguments, 2)
            );
        },

        d__: function (domain, original) {
            return format(
                this.dgettext(domain, original),
                Array.prototype.slice.call(arguments, 2)
            );
        },

        dp__: function (domain, context, original) {
            return format(
                this.dgettext(domain, context, original),
                Array.prototype.slice.call(arguments, 3)
            );
        },

        np__: function (context, original, plural, value) {
            return format(
                this.npgettext(context, original, plural, value),
                Array.prototype.slice.call(arguments, 4)
            );
        },

        dnp__: function (domain, context, original, plural, value) {
            return format(
                this.dnpgettext(domain, context, original, plural, value),
                Array.prototype.slice.call(arguments, 5)
            );
        }
    };

    function getTranslation(dictionary, domain, context, original) {
        context = context || '';

        if (!dictionary[domain] || !dictionary[domain][context] || !dictionary[domain][context][original]) {
            return false;
        }

        try {
            I18N_LOG_COLLECT.set(original, context);
        } catch (e) {}

        return dictionary[domain][context][original];
    }

    function getPluralIndex(plurals, domain, value) {
        if (!plurals[domain]) {
            return value == 1 ? 0 : 1;
        }

        if (!plurals[domain].fn) {
            plurals[domain].fn = new Function('n', plurals[domain].code);
        }

        return plurals[domain].fn.call(this, value) + 0;
    }

    function mergeTranslations(translations, newTranslations) {
        for (var context in newTranslations) {
            if (!translations[context]) {
                translations[context] = newTranslations[context];
                continue;
            }

            for (var original in newTranslations[context]) {
                translations[context][original] = newTranslations[context][original];
            }
        }
    }

    function format (text, args) {
        if (!args.length) {
            return text;
        }

        if (args[0] instanceof Array) {
            return vsprintf(text, args[0]);
        }

        return vsprintf(text, args);
    }

    return Translator;
}));

/**
 * i18n 관련 함수 모음
 * @type {{ordinalSuffixes: string[], ordinalNumber: I18N_FN.ordinalNumber}}
 */
var I18N_FN = {
    ordinalSuffixes: ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'],

    ordinalNumber: function (iValue) {
        if (iValue === undefined) {
            return '';
        }

        var iNum = String(iValue).replace(/,/g, "");
        if (this.isNumber(iNum) === false) {
            return iValue;
        }
        if (__('__LANGUAGE.CODE__') !== 'en_US') {
            return iValue;
        }
        iNum = Math.abs(iNum);
        iNum = parseFloat(iNum);
        if (((iNum % 100) >= 11 && ((iNum % 100) <= 13)) || iNum % 1 != 0) {
            return iValue + 'th';
        }

        return iValue + this.ordinalSuffixes[iNum % 10];
    },
    isNumber: function (v) {
        return /^[+-]?\d*(\.?\d*)$/.test(v);
    }
};

var I18N_LOG_COLLECT = {
    aTranslationCodes: [],
    bIsCallApiOnLoaded: false,
    request_url: window.location.pathname,

    call: function () {
        var data = I18N_LOG_COLLECT.aTranslationCodes;
        if (data.length === 0) {
            return false;
        }
        I18N_LOG_COLLECT.aTranslationCodes = [];
        $.ajax({
            url: '/exec/common/translate/logging',
            data: {"data": data},
            type: 'POST',
            dataType: 'json',
            success: function (aData) {}
        });
    },
    set: function (sMsg_id, sGroup_id) {
        if (typeof CAFE24.TRANSLATE_LOG_STATUS === 'undefined' || CAFE24.TRANSLATE_LOG_STATUS !== 'T') {
            return;
        }

        var item = {
            'request_url': I18N_LOG_COLLECT.request_url,
            'msg_id': sMsg_id,
            'group_id': sGroup_id
        };

        if (I18N_LOG_COLLECT.bIsCallApiOnLoaded) {
            I18N_LOG_COLLECT.aTranslationCodes.push(item);
            I18N_LOG_COLLECT.call();
            return true;
        }
        I18N_LOG_COLLECT.aTranslationCodes.push(item);
    },
    loadComplete: function () {
        I18N_LOG_COLLECT.bIsCallApiOnLoaded = true;
        I18N_LOG_COLLECT.call();
    }
};

if (typeof CAFE24.TRANSLATE_LOG_STATUS !== 'undefined' && CAFE24.TRANSLATE_LOG_STATUS === 'T') {
    if (document.addEventListener) {
        document.addEventListener("DOMContentLoaded", function () {
            I18N_LOG_COLLECT.loadComplete();
        }, false);
    } else if (document.attachEvent) {
        document.attachEvent("onreadystatechange", function () {
            if (document.readyState === "complete") {
                document.detachEvent("onreadystatechange", arguments.callee);
                I18N_LOG_COLLECT.loadComplete();
            }
        });
    }
}
/*!
 * jQuery JavaScript Library v3.6.0
 * https://jquery.com/
 *
 * Includes Sizzle.js
 * https://sizzlejs.com/
 *
 * Copyright OpenJS Foundation and other contributors
 * Released under the MIT license
 * https://jquery.org/license
 *
 * Date: 2021-03-02T17:08Z
 */
( function( global, factory ) {

    "use strict";

    if ( typeof module === "object" && typeof module.exports === "object" ) {

        // For CommonJS and CommonJS-like environments where a proper `window`
        // is present, execute the factory and get jQuery.
        // For environments that do not have a `window` with a `document`
        // (such as Node.js), expose a factory as module.exports.
        // This accentuates the need for the creation of a real `window`.
        // e.g. var jQuery = require("jquery")(window);
        // See ticket #14549 for more info.
        module.exports = global.document ?
            factory( global, true ) :
            function( w ) {
                if ( !w.document ) {
                    throw new Error( "jQuery requires a window with a document" );
                }
                return factory( w );
            };
    } else {
        factory( global );
    }

// Pass this if window is not defined yet
} )( typeof window !== "undefined" ? window : this, function( window, noGlobal ) {

// Edge <= 12 - 13+, Firefox <=18 - 45+, IE 10 - 11, Safari 5.1 - 9+, iOS 6 - 9.1
// throw exceptions when non-strict code (e.g., ASP.NET 4.5) accesses strict mode
// arguments.callee.caller (trac-13335). But as of jQuery 3.0 (2016), strict mode should be common
// enough that all such attempts are guarded in a try block.
    "use strict";

    var arr = [];

    var getProto = Object.getPrototypeOf;

    var slice = arr.slice;

    var flat = arr.flat ? function( array ) {
        return arr.flat.call( array );
    } : function( array ) {
        return arr.concat.apply( [], array );
    };


    var push = arr.push;

    var indexOf = arr.indexOf;

    var class2type = {};

    var toString = class2type.toString;

    var hasOwn = class2type.hasOwnProperty;

    var fnToString = hasOwn.toString;

    var ObjectFunctionString = fnToString.call( Object );

    var support = {};

    var isFunction = function isFunction( obj ) {

        // Support: Chrome <=57, Firefox <=52
        // In some browsers, typeof returns "function" for HTML <object> elements
        // (i.e., `typeof document.createElement( "object" ) === "function"`).
        // We don't want to classify *any* DOM node as a function.
        // Support: QtWeb <=3.8.5, WebKit <=534.34, wkhtmltopdf tool <=0.12.5
        // Plus for old WebKit, typeof returns "function" for HTML collections
        // (e.g., `typeof document.getElementsByTagName("div") === "function"`). (gh-4756)
        return typeof obj === "function" && typeof obj.nodeType !== "number" &&
            typeof obj.item !== "function";
    };


    var isWindow = function isWindow( obj ) {
        return obj != null && obj === obj.window;
    };


    var document = window.document;



    var preservedScriptAttributes = {
        type: true,
        src: true,
        nonce: true,
        noModule: true
    };

    function DOMEval( code, node, doc ) {
        doc = doc || document;

        var i, val,
            script = doc.createElement( "script" );

        script.text = code;
        if ( node ) {
            for ( i in preservedScriptAttributes ) {

                // Support: Firefox 64+, Edge 18+
                // Some browsers don't support the "nonce" property on scripts.
                // On the other hand, just using `getAttribute` is not enough as
                // the `nonce` attribute is reset to an empty string whenever it
                // becomes browsing-context connected.
                // See https://github.com/whatwg/html/issues/2369
                // See https://html.spec.whatwg.org/#nonce-attributes
                // The `node.getAttribute` check was added for the sake of
                // `jQuery.globalEval` so that it can fake a nonce-containing node
                // via an object.
                val = node[ i ] || node.getAttribute && node.getAttribute( i );
                if ( val ) {
                    script.setAttribute( i, val );
                }
            }
        }
        doc.head.appendChild( script ).parentNode.removeChild( script );
    }


    function toType( obj ) {
        if ( obj == null ) {
            return obj + "";
        }

        // Support: Android <=2.3 only (functionish RegExp)
        return typeof obj === "object" || typeof obj === "function" ?
            class2type[ toString.call( obj ) ] || "object" :
            typeof obj;
    }
    /* global Symbol */
// Defining this global in .eslintrc.json would create a danger of using the global
// unguarded in another place, it seems safer to define global only for this module



    var
        version = "3.6.0",

        // Define a local copy of jQuery
        jQuery = function( selector, context ) {

            // The jQuery object is actually just the init constructor 'enhanced'
            // Need init if jQuery is called (just allow error to be thrown if not included)
            return new jQuery.fn.init( selector, context );
        };

    jQuery.fn = jQuery.prototype = {

        // The current version of jQuery being used
        jquery: version,

        constructor: jQuery,

        // The default length of a jQuery object is 0
        length: 0,

        toArray: function() {
            return slice.call( this );
        },

        // Get the Nth element in the matched element set OR
        // Get the whole matched element set as a clean array
        get: function( num ) {

            // Return all the elements in a clean array
            if ( num == null ) {
                return slice.call( this );
            }

            // Return just the one element from the set
            return num < 0 ? this[ num + this.length ] : this[ num ];
        },

        // Take an array of elements and push it onto the stack
        // (returning the new matched element set)
        pushStack: function( elems ) {

            // Build a new jQuery matched element set
            var ret = jQuery.merge( this.constructor(), elems );

            // Add the old object onto the stack (as a reference)
            ret.prevObject = this;

            // Return the newly-formed element set
            return ret;
        },

        // Execute a callback for every element in the matched set.
        each: function( callback ) {
            return jQuery.each( this, callback );
        },

        map: function( callback ) {
            return this.pushStack( jQuery.map( this, function( elem, i ) {
                return callback.call( elem, i, elem );
            } ) );
        },

        slice: function() {
            return this.pushStack( slice.apply( this, arguments ) );
        },

        first: function() {
            return this.eq( 0 );
        },

        last: function() {
            return this.eq( -1 );
        },

        even: function() {
            return this.pushStack( jQuery.grep( this, function( _elem, i ) {
                return ( i + 1 ) % 2;
            } ) );
        },

        odd: function() {
            return this.pushStack( jQuery.grep( this, function( _elem, i ) {
                return i % 2;
            } ) );
        },

        eq: function( i ) {
            var len = this.length,
                j = +i + ( i < 0 ? len : 0 );
            return this.pushStack( j >= 0 && j < len ? [ this[ j ] ] : [] );
        },

        end: function() {
            return this.prevObject || this.constructor();
        },

        // For internal use only.
        // Behaves like an Array's method, not like a jQuery method.
        push: push,
        sort: arr.sort,
        splice: arr.splice
    };

    jQuery.extend = jQuery.fn.extend = function() {
        var options, name, src, copy, copyIsArray, clone,
            target = arguments[ 0 ] || {},
            i = 1,
            length = arguments.length,
            deep = false;

        // Handle a deep copy situation
        if ( typeof target === "boolean" ) {
            deep = target;

            // Skip the boolean and the target
            target = arguments[ i ] || {};
            i++;
        }

        // Handle case when target is a string or something (possible in deep copy)
        if ( typeof target !== "object" && !isFunction( target ) ) {
            target = {};
        }

        // Extend jQuery itself if only one argument is passed
        if ( i === length ) {
            target = this;
            i--;
        }

        for ( ; i < length; i++ ) {

            // Only deal with non-null/undefined values
            if ( ( options = arguments[ i ] ) != null ) {

                // Extend the base object
                for ( name in options ) {
                    copy = options[ name ];

                    // Prevent Object.prototype pollution
                    // Prevent never-ending loop
                    if ( name === "__proto__" || target === copy ) {
                        continue;
                    }

                    // Recurse if we're merging plain objects or arrays
                    if ( deep && copy && ( jQuery.isPlainObject( copy ) ||
                        ( copyIsArray = Array.isArray( copy ) ) ) ) {
                        src = target[ name ];

                        // Ensure proper type for the source value
                        if ( copyIsArray && !Array.isArray( src ) ) {
                            clone = [];
                        } else if ( !copyIsArray && !jQuery.isPlainObject( src ) ) {
                            clone = {};
                        } else {
                            clone = src;
                        }
                        copyIsArray = false;

                        // Never move original objects, clone them
                        target[ name ] = jQuery.extend( deep, clone, copy );

                        // Don't bring in undefined values
                    } else if ( copy !== undefined ) {
                        target[ name ] = copy;
                    }
                }
            }
        }

        // Return the modified object
        return target;
    };

    jQuery.extend( {

        // Unique for each copy of jQuery on the page
        expando: "jQuery" + ( version + Math.random() ).replace( /\D/g, "" ),

        // Assume jQuery is ready without the ready module
        isReady: true,

        error: function( msg ) {
            throw new Error( msg );
        },

        noop: function() {},

        isPlainObject: function( obj ) {
            var key;

            // Detect obvious negatives
            // Use toString instead of jQuery.type to catch host objects
            if ( !obj || toString.call( obj ) !== "[object Object]" ) {
                return false;
            }

            // Not own constructor property must be Object
            if ( obj.constructor &&
                !hasOwn.call( obj, "constructor" ) &&
                !hasOwn.call( obj.constructor.prototype || {}, "isPrototypeOf" ) ) {
                return false;
            }

            // Own properties are enumerated firstly, so to speed up,
            // if last one is own, then all properties are own
            for ( key in obj ) {}

            return key === undefined || hasOwn.call( obj, key );
        },

        isEmptyObject: function( obj ) {
            var name;

            for ( name in obj ) {
                return false;
            }
            return true;
        },

        // Evaluates a script in a provided context; falls back to the global one
        // if not specified.
        globalEval: function( code, options, doc ) {
            DOMEval( code, { nonce: options && options.nonce }, doc );
        },

        each: function( obj, callback ) {
            var length, i = 0;

            if ( isArrayLike( obj ) ) {
                length = obj.length;
                for ( ; i < length; i++ ) {
                    if ( callback.call( obj[ i ], i, obj[ i ] ) === false ) {
                        break;
                    }
                }
            } else {
                for ( i in obj ) {
                    if ( callback.call( obj[ i ], i, obj[ i ] ) === false ) {
                        break;
                    }
                }
            }

            return obj;
        },

        // results is for internal usage only
        makeArray: function( arr, results ) {
            var ret = results || [];

            if ( arr != null ) {
                if ( isArrayLike( Object( arr ) ) ) {
                    jQuery.merge( ret,
                        typeof arr === "string" ?
                            [ arr ] : arr
                    );
                } else {
                    push.call( ret, arr );
                }
            }

            return ret;
        },

        inArray: function( elem, arr, i ) {
            return arr == null ? -1 : indexOf.call( arr, elem, i );
        },

        // Support: Android <=4.0 only, PhantomJS 1 only
        // push.apply(_, arraylike) throws on ancient WebKit
        merge: function( first, second ) {
            var len = +second.length,
                j = 0,
                i = first.length;

            for ( ; j < len; j++ ) {
                first[ i++ ] = second[ j ];
            }

            first.length = i;

            return first;
        },

        grep: function( elems, callback, invert ) {
            var callbackInverse,
                matches = [],
                i = 0,
                length = elems.length,
                callbackExpect = !invert;

            // Go through the array, only saving the items
            // that pass the validator function
            for ( ; i < length; i++ ) {
                callbackInverse = !callback( elems[ i ], i );
                if ( callbackInverse !== callbackExpect ) {
                    matches.push( elems[ i ] );
                }
            }

            return matches;
        },

        // arg is for internal usage only
        map: function( elems, callback, arg ) {
            var length, value,
                i = 0,
                ret = [];

            // Go through the array, translating each of the items to their new values
            if ( isArrayLike( elems ) ) {
                length = elems.length;
                for ( ; i < length; i++ ) {
                    value = callback( elems[ i ], i, arg );

                    if ( value != null ) {
                        ret.push( value );
                    }
                }

                // Go through every key on the object,
            } else {
                for ( i in elems ) {
                    value = callback( elems[ i ], i, arg );

                    if ( value != null ) {
                        ret.push( value );
                    }
                }
            }

            // Flatten any nested arrays
            return flat( ret );
        },

        // A global GUID counter for objects
        guid: 1,

        // jQuery.support is not used in Core but other projects attach their
        // properties to it so it needs to exist.
        support: support
    } );

    if ( typeof Symbol === "function" ) {
        jQuery.fn[ Symbol.iterator ] = arr[ Symbol.iterator ];
    }

// Populate the class2type map
    jQuery.each( "Boolean Number String Function Array Date RegExp Object Error Symbol".split( " " ),
        function( _i, name ) {
            class2type[ "[object " + name + "]" ] = name.toLowerCase();
        } );

    function isArrayLike( obj ) {

        // Support: real iOS 8.2 only (not reproducible in simulator)
        // `in` check used to prevent JIT error (gh-2145)
        // hasOwn isn't used here due to false negatives
        // regarding Nodelist length in IE
        var length = !!obj && "length" in obj && obj.length,
            type = toType( obj );

        if ( isFunction( obj ) || isWindow( obj ) ) {
            return false;
        }

        return type === "array" || length === 0 ||
            typeof length === "number" && length > 0 && ( length - 1 ) in obj;
    }
    var Sizzle =
        /*!
 * Sizzle CSS Selector Engine v2.3.6
 * https://sizzlejs.com/
 *
 * Copyright JS Foundation and other contributors
 * Released under the MIT license
 * https://js.foundation/
 *
 * Date: 2021-02-16
 */
        ( function( window ) {
            var i,
                support,
                Expr,
                getText,
                isXML,
                tokenize,
                compile,
                select,
                outermostContext,
                sortInput,
                hasDuplicate,

                // Local document vars
                setDocument,
                document,
                docElem,
                documentIsHTML,
                rbuggyQSA,
                rbuggyMatches,
                matches,
                contains,

                // Instance-specific data
                expando = "sizzle" + 1 * new Date(),
                preferredDoc = window.document,
                dirruns = 0,
                done = 0,
                classCache = createCache(),
                tokenCache = createCache(),
                compilerCache = createCache(),
                nonnativeSelectorCache = createCache(),
                sortOrder = function( a, b ) {
                    if ( a === b ) {
                        hasDuplicate = true;
                    }
                    return 0;
                },

                // Instance methods
                hasOwn = ( {} ).hasOwnProperty,
                arr = [],
                pop = arr.pop,
                pushNative = arr.push,
                push = arr.push,
                slice = arr.slice,

                // Use a stripped-down indexOf as it's faster than native
                // https://jsperf.com/thor-indexof-vs-for/5
                indexOf = function( list, elem ) {
                    var i = 0,
                        len = list.length;
                    for ( ; i < len; i++ ) {
                        if ( list[ i ] === elem ) {
                            return i;
                        }
                    }
                    return -1;
                },

                booleans = "checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|" +
                    "ismap|loop|multiple|open|readonly|required|scoped",

                // Regular expressions

                // http://www.w3.org/TR/css3-selectors/#whitespace
                whitespace = "[\\x20\\t\\r\\n\\f]",

                // https://www.w3.org/TR/css-syntax-3/#ident-token-diagram
                identifier = "(?:\\\\[\\da-fA-F]{1,6}" + whitespace +
                    "?|\\\\[^\\r\\n\\f]|[\\w-]|[^\0-\\x7f])+",

                // Attribute selectors: http://www.w3.org/TR/selectors/#attribute-selectors
                attributes = "\\[" + whitespace + "*(" + identifier + ")(?:" + whitespace +

                    // Operator (capture 2)
                    "*([*^$|!~]?=)" + whitespace +

                    // "Attribute values must be CSS identifiers [capture 5]
                    // or strings [capture 3 or capture 4]"
                    "*(?:'((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\"|(" + identifier + "))|)" +
                    whitespace + "*\\]",

                pseudos = ":(" + identifier + ")(?:\\((" +

                    // To reduce the number of selectors needing tokenize in the preFilter, prefer arguments:
                    // 1. quoted (capture 3; capture 4 or capture 5)
                    "('((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\")|" +

                    // 2. simple (capture 6)
                    "((?:\\\\.|[^\\\\()[\\]]|" + attributes + ")*)|" +

                    // 3. anything else (capture 2)
                    ".*" +
                    ")\\)|)",

                // Leading and non-escaped trailing whitespace, capturing some non-whitespace characters preceding the latter
                rwhitespace = new RegExp( whitespace + "+", "g" ),
                rtrim = new RegExp( "^" + whitespace + "+|((?:^|[^\\\\])(?:\\\\.)*)" +
                    whitespace + "+$", "g" ),

                rcomma = new RegExp( "^" + whitespace + "*," + whitespace + "*" ),
                rcombinators = new RegExp( "^" + whitespace + "*([>+~]|" + whitespace + ")" + whitespace +
                    "*" ),
                rdescend = new RegExp( whitespace + "|>" ),

                rpseudo = new RegExp( pseudos ),
                ridentifier = new RegExp( "^" + identifier + "$" ),

                matchExpr = {
                    "ID": new RegExp( "^#(" + identifier + ")" ),
                    "CLASS": new RegExp( "^\\.(" + identifier + ")" ),
                    "TAG": new RegExp( "^(" + identifier + "|[*])" ),
                    "ATTR": new RegExp( "^" + attributes ),
                    "PSEUDO": new RegExp( "^" + pseudos ),
                    "CHILD": new RegExp( "^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\(" +
                        whitespace + "*(even|odd|(([+-]|)(\\d*)n|)" + whitespace + "*(?:([+-]|)" +
                        whitespace + "*(\\d+)|))" + whitespace + "*\\)|)", "i" ),
                    "bool": new RegExp( "^(?:" + booleans + ")$", "i" ),

                    // For use in libraries implementing .is()
                    // We use this for POS matching in `select`
                    "needsContext": new RegExp( "^" + whitespace +
                        "*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\(" + whitespace +
                        "*((?:-\\d)?\\d*)" + whitespace + "*\\)|)(?=[^-]|$)", "i" )
                },

                rhtml = /HTML$/i,
                rinputs = /^(?:input|select|textarea|button)$/i,
                rheader = /^h\d$/i,

                rnative = /^[^{]+\{\s*\[native \w/,

                // Easily-parseable/retrievable ID or TAG or CLASS selectors
                rquickExpr = /^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,

                rsibling = /[+~]/,

                // CSS escapes
                // http://www.w3.org/TR/CSS21/syndata.html#escaped-characters
                runescape = new RegExp( "\\\\[\\da-fA-F]{1,6}" + whitespace + "?|\\\\([^\\r\\n\\f])", "g" ),
                funescape = function( escape, nonHex ) {
                    var high = "0x" + escape.slice( 1 ) - 0x10000;

                    return nonHex ?

                        // Strip the backslash prefix from a non-hex escape sequence
                        nonHex :

                        // Replace a hexadecimal escape sequence with the encoded Unicode code point
                        // Support: IE <=11+
                        // For values outside the Basic Multilingual Plane (BMP), manually construct a
                        // surrogate pair
                        high < 0 ?
                            String.fromCharCode( high + 0x10000 ) :
                            String.fromCharCode( high >> 10 | 0xD800, high & 0x3FF | 0xDC00 );
                },

                // CSS string/identifier serialization
                // https://drafts.csswg.org/cssom/#common-serializing-idioms
                rcssescape = /([\0-\x1f\x7f]|^-?\d)|^-$|[^\0-\x1f\x7f-\uFFFF\w-]/g,
                fcssescape = function( ch, asCodePoint ) {
                    if ( asCodePoint ) {

                        // U+0000 NULL becomes U+FFFD REPLACEMENT CHARACTER
                        if ( ch === "\0" ) {
                            return "\uFFFD";
                        }

                        // Control characters and (dependent upon position) numbers get escaped as code points
                        return ch.slice( 0, -1 ) + "\\" +
                            ch.charCodeAt( ch.length - 1 ).toString( 16 ) + " ";
                    }

                    // Other potentially-special ASCII characters get backslash-escaped
                    return "\\" + ch;
                },

                // Used for iframes
                // See setDocument()
                // Removing the function wrapper causes a "Permission Denied"
                // error in IE
                unloadHandler = function() {
                    setDocument();
                },

                inDisabledFieldset = addCombinator(
                    function( elem ) {
                        return elem.disabled === true && elem.nodeName.toLowerCase() === "fieldset";
                    },
                    { dir: "parentNode", next: "legend" }
                );

// Optimize for push.apply( _, NodeList )
            try {
                push.apply(
                    ( arr = slice.call( preferredDoc.childNodes ) ),
                    preferredDoc.childNodes
                );

                // Support: Android<4.0
                // Detect silently failing push.apply
                // eslint-disable-next-line no-unused-expressions
                arr[ preferredDoc.childNodes.length ].nodeType;
            } catch ( e ) {
                push = { apply: arr.length ?

                        // Leverage slice if possible
                        function( target, els ) {
                            pushNative.apply( target, slice.call( els ) );
                        } :

                        // Support: IE<9
                        // Otherwise append directly
                        function( target, els ) {
                            var j = target.length,
                                i = 0;

                            // Can't trust NodeList.length
                            while ( ( target[ j++ ] = els[ i++ ] ) ) {}
                            target.length = j - 1;
                        }
                };
            }

            function Sizzle( selector, context, results, seed ) {
                var m, i, elem, nid, match, groups, newSelector,
                    newContext = context && context.ownerDocument,

                    // nodeType defaults to 9, since context defaults to document
                    nodeType = context ? context.nodeType : 9;

                results = results || [];

                // Return early from calls with invalid selector or context
                if ( typeof selector !== "string" || !selector ||
                    nodeType !== 1 && nodeType !== 9 && nodeType !== 11 ) {

                    return results;
                }

                // Try to shortcut find operations (as opposed to filters) in HTML documents
                if ( !seed ) {
                    setDocument( context );
                    context = context || document;

                    if ( documentIsHTML ) {

                        // If the selector is sufficiently simple, try using a "get*By*" DOM method
                        // (excepting DocumentFragment context, where the methods don't exist)
                        if ( nodeType !== 11 && ( match = rquickExpr.exec( selector ) ) ) {

                            // ID selector
                            if ( ( m = match[ 1 ] ) ) {

                                // Document context
                                if ( nodeType === 9 ) {
                                    if ( ( elem = context.getElementById( m ) ) ) {

                                        // Support: IE, Opera, Webkit
                                        // TODO: identify versions
                                        // getElementById can match elements by name instead of ID
                                        if ( elem.id === m ) {
                                            results.push( elem );
                                            return results;
                                        }
                                    } else {
                                        return results;
                                    }

                                    // Element context
                                } else {

                                    // Support: IE, Opera, Webkit
                                    // TODO: identify versions
                                    // getElementById can match elements by name instead of ID
                                    if ( newContext && ( elem = newContext.getElementById( m ) ) &&
                                        contains( context, elem ) &&
                                        elem.id === m ) {

                                        results.push( elem );
                                        return results;
                                    }
                                }

                                // Type selector
                            } else if ( match[ 2 ] ) {
                                push.apply( results, context.getElementsByTagName( selector ) );
                                return results;

                                // Class selector
                            } else if ( ( m = match[ 3 ] ) && support.getElementsByClassName &&
                                context.getElementsByClassName ) {

                                push.apply( results, context.getElementsByClassName( m ) );
                                return results;
                            }
                        }

                        // Take advantage of querySelectorAll
                        if ( support.qsa &&
                            !nonnativeSelectorCache[ selector + " " ] &&
                            ( !rbuggyQSA || !rbuggyQSA.test( selector ) ) &&

                            // Support: IE 8 only
                            // Exclude object elements
                            ( nodeType !== 1 || context.nodeName.toLowerCase() !== "object" ) ) {

                            newSelector = selector;
                            newContext = context;

                            // qSA considers elements outside a scoping root when evaluating child or
                            // descendant combinators, which is not what we want.
                            // In such cases, we work around the behavior by prefixing every selector in the
                            // list with an ID selector referencing the scope context.
                            // The technique has to be used as well when a leading combinator is used
                            // as such selectors are not recognized by querySelectorAll.
                            // Thanks to Andrew Dupont for this technique.
                            if ( nodeType === 1 &&
                                ( rdescend.test( selector ) || rcombinators.test( selector ) ) ) {

                                // Expand context for sibling selectors
                                newContext = rsibling.test( selector ) && testContext( context.parentNode ) ||
                                    context;

                                // We can use :scope instead of the ID hack if the browser
                                // supports it & if we're not changing the context.
                                if ( newContext !== context || !support.scope ) {

                                    // Capture the context ID, setting it first if necessary
                                    if ( ( nid = context.getAttribute( "id" ) ) ) {
                                        nid = nid.replace( rcssescape, fcssescape );
                                    } else {
                                        context.setAttribute( "id", ( nid = expando ) );
                                    }
                                }

                                // Prefix every selector in the list
                                groups = tokenize( selector );
                                i = groups.length;
                                while ( i-- ) {
                                    groups[ i ] = ( nid ? "#" + nid : ":scope" ) + " " +
                                        toSelector( groups[ i ] );
                                }
                                newSelector = groups.join( "," );
                            }

                            try {
                                push.apply( results,
                                    newContext.querySelectorAll( newSelector )
                                );
                                return results;
                            } catch ( qsaError ) {
                                nonnativeSelectorCache( selector, true );
                            } finally {
                                if ( nid === expando ) {
                                    context.removeAttribute( "id" );
                                }
                            }
                        }
                    }
                }

                // All others
                return select( selector.replace( rtrim, "$1" ), context, results, seed );
            }

            /**
             * Create key-value caches of limited size
             * @returns {function(string, object)} Returns the Object data after storing it on itself with
             *	property name the (space-suffixed) string and (if the cache is larger than Expr.cacheLength)
             *	deleting the oldest entry
             */
            function createCache() {
                var keys = [];

                function cache( key, value ) {

                    // Use (key + " ") to avoid collision with native prototype properties (see Issue #157)
                    if ( keys.push( key + " " ) > Expr.cacheLength ) {

                        // Only keep the most recent entries
                        delete cache[ keys.shift() ];
                    }
                    return ( cache[ key + " " ] = value );
                }
                return cache;
            }

            /**
             * Mark a function for special use by Sizzle
             * @param {Function} fn The function to mark
             */
            function markFunction( fn ) {
                fn[ expando ] = true;
                return fn;
            }

            /**
             * Support testing using an element
             * @param {Function} fn Passed the created element and returns a boolean result
             */
            function assert( fn ) {
                var el = document.createElement( "fieldset" );

                try {
                    return !!fn( el );
                } catch ( e ) {
                    return false;
                } finally {

                    // Remove from its parent by default
                    if ( el.parentNode ) {
                        el.parentNode.removeChild( el );
                    }

                    // release memory in IE
                    el = null;
                }
            }

            /**
             * Adds the same handler for all of the specified attrs
             * @param {String} attrs Pipe-separated list of attributes
             * @param {Function} handler The method that will be applied
             */
            function addHandle( attrs, handler ) {
                var arr = attrs.split( "|" ),
                    i = arr.length;

                while ( i-- ) {
                    Expr.attrHandle[ arr[ i ] ] = handler;
                }
            }

            /**
             * Checks document order of two siblings
             * @param {Element} a
             * @param {Element} b
             * @returns {Number} Returns less than 0 if a precedes b, greater than 0 if a follows b
             */
            function siblingCheck( a, b ) {
                var cur = b && a,
                    diff = cur && a.nodeType === 1 && b.nodeType === 1 &&
                        a.sourceIndex - b.sourceIndex;

                // Use IE sourceIndex if available on both nodes
                if ( diff ) {
                    return diff;
                }

                // Check if b follows a
                if ( cur ) {
                    while ( ( cur = cur.nextSibling ) ) {
                        if ( cur === b ) {
                            return -1;
                        }
                    }
                }

                return a ? 1 : -1;
            }

            /**
             * Returns a function to use in pseudos for input types
             * @param {String} type
             */
            function createInputPseudo( type ) {
                return function( elem ) {
                    var name = elem.nodeName.toLowerCase();
                    return name === "input" && elem.type === type;
                };
            }

            /**
             * Returns a function to use in pseudos for buttons
             * @param {String} type
             */
            function createButtonPseudo( type ) {
                return function( elem ) {
                    var name = elem.nodeName.toLowerCase();
                    return ( name === "input" || name === "button" ) && elem.type === type;
                };
            }

            /**
             * Returns a function to use in pseudos for :enabled/:disabled
             * @param {Boolean} disabled true for :disabled; false for :enabled
             */
            function createDisabledPseudo( disabled ) {

                // Known :disabled false positives: fieldset[disabled] > legend:nth-of-type(n+2) :can-disable
                return function( elem ) {

                    // Only certain elements can match :enabled or :disabled
                    // https://html.spec.whatwg.org/multipage/scripting.html#selector-enabled
                    // https://html.spec.whatwg.org/multipage/scripting.html#selector-disabled
                    if ( "form" in elem ) {

                        // Check for inherited disabledness on relevant non-disabled elements:
                        // * listed form-associated elements in a disabled fieldset
                        //   https://html.spec.whatwg.org/multipage/forms.html#category-listed
                        //   https://html.spec.whatwg.org/multipage/forms.html#concept-fe-disabled
                        // * option elements in a disabled optgroup
                        //   https://html.spec.whatwg.org/multipage/forms.html#concept-option-disabled
                        // All such elements have a "form" property.
                        if ( elem.parentNode && elem.disabled === false ) {

                            // Option elements defer to a parent optgroup if present
                            if ( "label" in elem ) {
                                if ( "label" in elem.parentNode ) {
                                    return elem.parentNode.disabled === disabled;
                                } else {
                                    return elem.disabled === disabled;
                                }
                            }

                            // Support: IE 6 - 11
                            // Use the isDisabled shortcut property to check for disabled fieldset ancestors
                            return elem.isDisabled === disabled ||

                                // Where there is no isDisabled, check manually
                                /* jshint -W018 */
                                elem.isDisabled !== !disabled &&
                                inDisabledFieldset( elem ) === disabled;
                        }

                        return elem.disabled === disabled;

                        // Try to winnow out elements that can't be disabled before trusting the disabled property.
                        // Some victims get caught in our net (label, legend, menu, track), but it shouldn't
                        // even exist on them, let alone have a boolean value.
                    } else if ( "label" in elem ) {
                        return elem.disabled === disabled;
                    }

                    // Remaining elements are neither :enabled nor :disabled
                    return false;
                };
            }

            /**
             * Returns a function to use in pseudos for positionals
             * @param {Function} fn
             */
            function createPositionalPseudo( fn ) {
                return markFunction( function( argument ) {
                    argument = +argument;
                    return markFunction( function( seed, matches ) {
                        var j,
                            matchIndexes = fn( [], seed.length, argument ),
                            i = matchIndexes.length;

                        // Match elements found at the specified indexes
                        while ( i-- ) {
                            if ( seed[ ( j = matchIndexes[ i ] ) ] ) {
                                seed[ j ] = !( matches[ j ] = seed[ j ] );
                            }
                        }
                    } );
                } );
            }

            /**
             * Checks a node for validity as a Sizzle context
             * @param {Element|Object=} context
             * @returns {Element|Object|Boolean} The input node if acceptable, otherwise a falsy value
             */
            function testContext( context ) {
                return context && typeof context.getElementsByTagName !== "undefined" && context;
            }

// Expose support vars for convenience
            support = Sizzle.support = {};

            /**
             * Detects XML nodes
             * @param {Element|Object} elem An element or a document
             * @returns {Boolean} True iff elem is a non-HTML XML node
             */
            isXML = Sizzle.isXML = function( elem ) {
                var namespace = elem && elem.namespaceURI,
                    docElem = elem && ( elem.ownerDocument || elem ).documentElement;

                // Support: IE <=8
                // Assume HTML when documentElement doesn't yet exist, such as inside loading iframes
                // https://bugs.jquery.com/ticket/4833
                return !rhtml.test( namespace || docElem && docElem.nodeName || "HTML" );
            };

            /**
             * Sets document-related variables once based on the current document
             * @param {Element|Object} [doc] An element or document object to use to set the document
             * @returns {Object} Returns the current document
             */
            setDocument = Sizzle.setDocument = function( node ) {
                var hasCompare, subWindow,
                    doc = node ? node.ownerDocument || node : preferredDoc;

                // Return early if doc is invalid or already selected
                // Support: IE 11+, Edge 17 - 18+
                // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                // two documents; shallow comparisons work.
                // eslint-disable-next-line eqeqeq
                if ( doc == document || doc.nodeType !== 9 || !doc.documentElement ) {
                    return document;
                }

                // Update global variables
                document = doc;
                docElem = document.documentElement;
                documentIsHTML = !isXML( document );

                // Support: IE 9 - 11+, Edge 12 - 18+
                // Accessing iframe documents after unload throws "permission denied" errors (jQuery #13936)
                // Support: IE 11+, Edge 17 - 18+
                // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                // two documents; shallow comparisons work.
                // eslint-disable-next-line eqeqeq
                if ( preferredDoc != document &&
                    ( subWindow = document.defaultView ) && subWindow.top !== subWindow ) {

                    // Support: IE 11, Edge
                    if ( subWindow.addEventListener ) {
                        subWindow.addEventListener( "unload", unloadHandler, false );

                        // Support: IE 9 - 10 only
                    } else if ( subWindow.attachEvent ) {
                        subWindow.attachEvent( "onunload", unloadHandler );
                    }
                }

                // Support: IE 8 - 11+, Edge 12 - 18+, Chrome <=16 - 25 only, Firefox <=3.6 - 31 only,
                // Safari 4 - 5 only, Opera <=11.6 - 12.x only
                // IE/Edge & older browsers don't support the :scope pseudo-class.
                // Support: Safari 6.0 only
                // Safari 6.0 supports :scope but it's an alias of :root there.
                support.scope = assert( function( el ) {
                    docElem.appendChild( el ).appendChild( document.createElement( "div" ) );
                    return typeof el.querySelectorAll !== "undefined" &&
                        !el.querySelectorAll( ":scope fieldset div" ).length;
                } );

                /* Attributes
	---------------------------------------------------------------------- */

                // Support: IE<8
                // Verify that getAttribute really returns attributes and not properties
                // (excepting IE8 booleans)
                support.attributes = assert( function( el ) {
                    el.className = "i";
                    return !el.getAttribute( "className" );
                } );

                /* getElement(s)By*
	---------------------------------------------------------------------- */

                // Check if getElementsByTagName("*") returns only elements
                support.getElementsByTagName = assert( function( el ) {
                    el.appendChild( document.createComment( "" ) );
                    return !el.getElementsByTagName( "*" ).length;
                } );

                // Support: IE<9
                support.getElementsByClassName = rnative.test( document.getElementsByClassName );

                // Support: IE<10
                // Check if getElementById returns elements by name
                // The broken getElementById methods don't pick up programmatically-set names,
                // so use a roundabout getElementsByName test
                support.getById = assert( function( el ) {
                    docElem.appendChild( el ).id = expando;
                    return !document.getElementsByName || !document.getElementsByName( expando ).length;
                } );

                // ID filter and find
                if ( support.getById ) {
                    Expr.filter[ "ID" ] = function( id ) {
                        var attrId = id.replace( runescape, funescape );
                        return function( elem ) {
                            return elem.getAttribute( "id" ) === attrId;
                        };
                    };
                    Expr.find[ "ID" ] = function( id, context ) {
                        if ( typeof context.getElementById !== "undefined" && documentIsHTML ) {
                            var elem = context.getElementById( id );
                            return elem ? [ elem ] : [];
                        }
                    };
                } else {
                    Expr.filter[ "ID" ] =  function( id ) {
                        var attrId = id.replace( runescape, funescape );
                        return function( elem ) {
                            var node = typeof elem.getAttributeNode !== "undefined" &&
                                elem.getAttributeNode( "id" );
                            return node && node.value === attrId;
                        };
                    };

                    // Support: IE 6 - 7 only
                    // getElementById is not reliable as a find shortcut
                    Expr.find[ "ID" ] = function( id, context ) {
                        if ( typeof context.getElementById !== "undefined" && documentIsHTML ) {
                            var node, i, elems,
                                elem = context.getElementById( id );

                            if ( elem ) {

                                // Verify the id attribute
                                node = elem.getAttributeNode( "id" );
                                if ( node && node.value === id ) {
                                    return [ elem ];
                                }

                                // Fall back on getElementsByName
                                elems = context.getElementsByName( id );
                                i = 0;
                                while ( ( elem = elems[ i++ ] ) ) {
                                    node = elem.getAttributeNode( "id" );
                                    if ( node && node.value === id ) {
                                        return [ elem ];
                                    }
                                }
                            }

                            return [];
                        }
                    };
                }

                // Tag
                Expr.find[ "TAG" ] = support.getElementsByTagName ?
                    function( tag, context ) {
                        if ( typeof context.getElementsByTagName !== "undefined" ) {
                            return context.getElementsByTagName( tag );

                            // DocumentFragment nodes don't have gEBTN
                        } else if ( support.qsa ) {
                            return context.querySelectorAll( tag );
                        }
                    } :

                    function( tag, context ) {
                        var elem,
                            tmp = [],
                            i = 0,

                            // By happy coincidence, a (broken) gEBTN appears on DocumentFragment nodes too
                            results = context.getElementsByTagName( tag );

                        // Filter out possible comments
                        if ( tag === "*" ) {
                            while ( ( elem = results[ i++ ] ) ) {
                                if ( elem.nodeType === 1 ) {
                                    tmp.push( elem );
                                }
                            }

                            return tmp;
                        }
                        return results;
                    };

                // Class
                Expr.find[ "CLASS" ] = support.getElementsByClassName && function( className, context ) {
                    if ( typeof context.getElementsByClassName !== "undefined" && documentIsHTML ) {
                        return context.getElementsByClassName( className );
                    }
                };

                /* QSA/matchesSelector
	---------------------------------------------------------------------- */

                // QSA and matchesSelector support

                // matchesSelector(:active) reports false when true (IE9/Opera 11.5)
                rbuggyMatches = [];

                // qSa(:focus) reports false when true (Chrome 21)
                // We allow this because of a bug in IE8/9 that throws an error
                // whenever `document.activeElement` is accessed on an iframe
                // So, we allow :focus to pass through QSA all the time to avoid the IE error
                // See https://bugs.jquery.com/ticket/13378
                rbuggyQSA = [];

                if ( ( support.qsa = rnative.test( document.querySelectorAll ) ) ) {

                    // Build QSA regex
                    // Regex strategy adopted from Diego Perini
                    assert( function( el ) {

                        var input;

                        // Select is set to empty string on purpose
                        // This is to test IE's treatment of not explicitly
                        // setting a boolean content attribute,
                        // since its presence should be enough
                        // https://bugs.jquery.com/ticket/12359
                        docElem.appendChild( el ).innerHTML = "<a id='" + expando + "'></a>" +
                            "<select id='" + expando + "-\r\\' msallowcapture=''>" +
                            "<option selected=''></option></select>";

                        // Support: IE8, Opera 11-12.16
                        // Nothing should be selected when empty strings follow ^= or $= or *=
                        // The test attribute must be unknown in Opera but "safe" for WinRT
                        // https://msdn.microsoft.com/en-us/library/ie/hh465388.aspx#attribute_section
                        if ( el.querySelectorAll( "[msallowcapture^='']" ).length ) {
                            rbuggyQSA.push( "[*^$]=" + whitespace + "*(?:''|\"\")" );
                        }

                        // Support: IE8
                        // Boolean attributes and "value" are not treated correctly
                        if ( !el.querySelectorAll( "[selected]" ).length ) {
                            rbuggyQSA.push( "\\[" + whitespace + "*(?:value|" + booleans + ")" );
                        }

                        // Support: Chrome<29, Android<4.4, Safari<7.0+, iOS<7.0+, PhantomJS<1.9.8+
                        if ( !el.querySelectorAll( "[id~=" + expando + "-]" ).length ) {
                            rbuggyQSA.push( "~=" );
                        }

                        // Support: IE 11+, Edge 15 - 18+
                        // IE 11/Edge don't find elements on a `[name='']` query in some cases.
                        // Adding a temporary attribute to the document before the selection works
                        // around the issue.
                        // Interestingly, IE 10 & older don't seem to have the issue.
                        input = document.createElement( "input" );
                        input.setAttribute( "name", "" );
                        el.appendChild( input );
                        if ( !el.querySelectorAll( "[name='']" ).length ) {
                            rbuggyQSA.push( "\\[" + whitespace + "*name" + whitespace + "*=" +
                                whitespace + "*(?:''|\"\")" );
                        }

                        // Webkit/Opera - :checked should return selected option elements
                        // http://www.w3.org/TR/2011/REC-css3-selectors-20110929/#checked
                        // IE8 throws error here and will not see later tests
                        if ( !el.querySelectorAll( ":checked" ).length ) {
                            rbuggyQSA.push( ":checked" );
                        }

                        // Support: Safari 8+, iOS 8+
                        // https://bugs.webkit.org/show_bug.cgi?id=136851
                        // In-page `selector#id sibling-combinator selector` fails
                        if ( !el.querySelectorAll( "a#" + expando + "+*" ).length ) {
                            rbuggyQSA.push( ".#.+[+~]" );
                        }

                        // Support: Firefox <=3.6 - 5 only
                        // Old Firefox doesn't throw on a badly-escaped identifier.
                        el.querySelectorAll( "\\\f" );
                        rbuggyQSA.push( "[\\r\\n\\f]" );
                    } );

                    assert( function( el ) {
                        el.innerHTML = "<a href='' disabled='disabled'></a>" +
                            "<select disabled='disabled'><option/></select>";

                        // Support: Windows 8 Native Apps
                        // The type and name attributes are restricted during .innerHTML assignment
                        var input = document.createElement( "input" );
                        input.setAttribute( "type", "hidden" );
                        el.appendChild( input ).setAttribute( "name", "D" );

                        // Support: IE8
                        // Enforce case-sensitivity of name attribute
                        if ( el.querySelectorAll( "[name=d]" ).length ) {
                            rbuggyQSA.push( "name" + whitespace + "*[*^$|!~]?=" );
                        }

                        // FF 3.5 - :enabled/:disabled and hidden elements (hidden elements are still enabled)
                        // IE8 throws error here and will not see later tests
                        if ( el.querySelectorAll( ":enabled" ).length !== 2 ) {
                            rbuggyQSA.push( ":enabled", ":disabled" );
                        }

                        // Support: IE9-11+
                        // IE's :disabled selector does not pick up the children of disabled fieldsets
                        docElem.appendChild( el ).disabled = true;
                        if ( el.querySelectorAll( ":disabled" ).length !== 2 ) {
                            rbuggyQSA.push( ":enabled", ":disabled" );
                        }

                        // Support: Opera 10 - 11 only
                        // Opera 10-11 does not throw on post-comma invalid pseudos
                        el.querySelectorAll( "*,:x" );
                        rbuggyQSA.push( ",.*:" );
                    } );
                }

                if ( ( support.matchesSelector = rnative.test( ( matches = docElem.matches ||
                    docElem.webkitMatchesSelector ||
                    docElem.mozMatchesSelector ||
                    docElem.oMatchesSelector ||
                    docElem.msMatchesSelector ) ) ) ) {

                    assert( function( el ) {

                        // Check to see if it's possible to do matchesSelector
                        // on a disconnected node (IE 9)
                        support.disconnectedMatch = matches.call( el, "*" );

                        // This should fail with an exception
                        // Gecko does not error, returns false instead
                        matches.call( el, "[s!='']:x" );
                        rbuggyMatches.push( "!=", pseudos );
                    } );
                }

                rbuggyQSA = rbuggyQSA.length && new RegExp( rbuggyQSA.join( "|" ) );
                rbuggyMatches = rbuggyMatches.length && new RegExp( rbuggyMatches.join( "|" ) );

                /* Contains
	---------------------------------------------------------------------- */
                hasCompare = rnative.test( docElem.compareDocumentPosition );

                // Element contains another
                // Purposefully self-exclusive
                // As in, an element does not contain itself
                contains = hasCompare || rnative.test( docElem.contains ) ?
                    function( a, b ) {
                        var adown = a.nodeType === 9 ? a.documentElement : a,
                            bup = b && b.parentNode;
                        return a === bup || !!( bup && bup.nodeType === 1 && (
                            adown.contains ?
                                adown.contains( bup ) :
                                a.compareDocumentPosition && a.compareDocumentPosition( bup ) & 16
                        ) );
                    } :
                    function( a, b ) {
                        if ( b ) {
                            while ( ( b = b.parentNode ) ) {
                                if ( b === a ) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    };

                /* Sorting
	---------------------------------------------------------------------- */

                // Document order sorting
                sortOrder = hasCompare ?
                    function( a, b ) {

                        // Flag for duplicate removal
                        if ( a === b ) {
                            hasDuplicate = true;
                            return 0;
                        }

                        // Sort on method existence if only one input has compareDocumentPosition
                        var compare = !a.compareDocumentPosition - !b.compareDocumentPosition;
                        if ( compare ) {
                            return compare;
                        }

                        // Calculate position if both inputs belong to the same document
                        // Support: IE 11+, Edge 17 - 18+
                        // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                        // two documents; shallow comparisons work.
                        // eslint-disable-next-line eqeqeq
                        compare = ( a.ownerDocument || a ) == ( b.ownerDocument || b ) ?
                            a.compareDocumentPosition( b ) :

                            // Otherwise we know they are disconnected
                            1;

                        // Disconnected nodes
                        if ( compare & 1 ||
                            ( !support.sortDetached && b.compareDocumentPosition( a ) === compare ) ) {

                            // Choose the first element that is related to our preferred document
                            // Support: IE 11+, Edge 17 - 18+
                            // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                            // two documents; shallow comparisons work.
                            // eslint-disable-next-line eqeqeq
                            if ( a == document || a.ownerDocument == preferredDoc &&
                                contains( preferredDoc, a ) ) {
                                return -1;
                            }

                            // Support: IE 11+, Edge 17 - 18+
                            // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                            // two documents; shallow comparisons work.
                            // eslint-disable-next-line eqeqeq
                            if ( b == document || b.ownerDocument == preferredDoc &&
                                contains( preferredDoc, b ) ) {
                                return 1;
                            }

                            // Maintain original order
                            return sortInput ?
                                ( indexOf( sortInput, a ) - indexOf( sortInput, b ) ) :
                                0;
                        }

                        return compare & 4 ? -1 : 1;
                    } :
                    function( a, b ) {

                        // Exit early if the nodes are identical
                        if ( a === b ) {
                            hasDuplicate = true;
                            return 0;
                        }

                        var cur,
                            i = 0,
                            aup = a.parentNode,
                            bup = b.parentNode,
                            ap = [ a ],
                            bp = [ b ];

                        // Parentless nodes are either documents or disconnected
                        if ( !aup || !bup ) {

                            // Support: IE 11+, Edge 17 - 18+
                            // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                            // two documents; shallow comparisons work.
                            /* eslint-disable eqeqeq */
                            return a == document ? -1 :
                                b == document ? 1 :
                                    /* eslint-enable eqeqeq */
                                    aup ? -1 :
                                        bup ? 1 :
                                            sortInput ?
                                                ( indexOf( sortInput, a ) - indexOf( sortInput, b ) ) :
                                                0;

                            // If the nodes are siblings, we can do a quick check
                        } else if ( aup === bup ) {
                            return siblingCheck( a, b );
                        }

                        // Otherwise we need full lists of their ancestors for comparison
                        cur = a;
                        while ( ( cur = cur.parentNode ) ) {
                            ap.unshift( cur );
                        }
                        cur = b;
                        while ( ( cur = cur.parentNode ) ) {
                            bp.unshift( cur );
                        }

                        // Walk down the tree looking for a discrepancy
                        while ( ap[ i ] === bp[ i ] ) {
                            i++;
                        }

                        return i ?

                            // Do a sibling check if the nodes have a common ancestor
                            siblingCheck( ap[ i ], bp[ i ] ) :

                            // Otherwise nodes in our document sort first
                            // Support: IE 11+, Edge 17 - 18+
                            // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                            // two documents; shallow comparisons work.
                            /* eslint-disable eqeqeq */
                            ap[ i ] == preferredDoc ? -1 :
                                bp[ i ] == preferredDoc ? 1 :
                                    /* eslint-enable eqeqeq */
                                    0;
                    };

                return document;
            };

            Sizzle.matches = function( expr, elements ) {
                return Sizzle( expr, null, null, elements );
            };

            Sizzle.matchesSelector = function( elem, expr ) {
                setDocument( elem );

                if ( support.matchesSelector && documentIsHTML &&
                    !nonnativeSelectorCache[ expr + " " ] &&
                    ( !rbuggyMatches || !rbuggyMatches.test( expr ) ) &&
                    ( !rbuggyQSA     || !rbuggyQSA.test( expr ) ) ) {

                    try {
                        var ret = matches.call( elem, expr );

                        // IE 9's matchesSelector returns false on disconnected nodes
                        if ( ret || support.disconnectedMatch ||

                            // As well, disconnected nodes are said to be in a document
                            // fragment in IE 9
                            elem.document && elem.document.nodeType !== 11 ) {
                            return ret;
                        }
                    } catch ( e ) {
                        nonnativeSelectorCache( expr, true );
                    }
                }

                return Sizzle( expr, document, null, [ elem ] ).length > 0;
            };

            Sizzle.contains = function( context, elem ) {

                // Set document vars if needed
                // Support: IE 11+, Edge 17 - 18+
                // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                // two documents; shallow comparisons work.
                // eslint-disable-next-line eqeqeq
                if ( ( context.ownerDocument || context ) != document ) {
                    setDocument( context );
                }
                return contains( context, elem );
            };

            Sizzle.attr = function( elem, name ) {

                // Set document vars if needed
                // Support: IE 11+, Edge 17 - 18+
                // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                // two documents; shallow comparisons work.
                // eslint-disable-next-line eqeqeq
                if ( ( elem.ownerDocument || elem ) != document ) {
                    setDocument( elem );
                }

                var fn = Expr.attrHandle[ name.toLowerCase() ],

                    // Don't get fooled by Object.prototype properties (jQuery #13807)
                    val = fn && hasOwn.call( Expr.attrHandle, name.toLowerCase() ) ?
                        fn( elem, name, !documentIsHTML ) :
                        undefined;

                return val !== undefined ?
                    val :
                    support.attributes || !documentIsHTML ?
                        elem.getAttribute( name ) :
                        ( val = elem.getAttributeNode( name ) ) && val.specified ?
                            val.value :
                            null;
            };

            Sizzle.escape = function( sel ) {
                return ( sel + "" ).replace( rcssescape, fcssescape );
            };

            Sizzle.error = function( msg ) {
                throw new Error( "Syntax error, unrecognized expression: " + msg );
            };

            /**
             * Document sorting and removing duplicates
             * @param {ArrayLike} results
             */
            Sizzle.uniqueSort = function( results ) {
                var elem,
                    duplicates = [],
                    j = 0,
                    i = 0;

                // Unless we *know* we can detect duplicates, assume their presence
                hasDuplicate = !support.detectDuplicates;
                sortInput = !support.sortStable && results.slice( 0 );
                results.sort( sortOrder );

                if ( hasDuplicate ) {
                    while ( ( elem = results[ i++ ] ) ) {
                        if ( elem === results[ i ] ) {
                            j = duplicates.push( i );
                        }
                    }
                    while ( j-- ) {
                        results.splice( duplicates[ j ], 1 );
                    }
                }

                // Clear input after sorting to release objects
                // See https://github.com/jquery/sizzle/pull/225
                sortInput = null;

                return results;
            };

            /**
             * Utility function for retrieving the text value of an array of DOM nodes
             * @param {Array|Element} elem
             */
            getText = Sizzle.getText = function( elem ) {
                var node,
                    ret = "",
                    i = 0,
                    nodeType = elem.nodeType;

                if ( !nodeType ) {

                    // If no nodeType, this is expected to be an array
                    while ( ( node = elem[ i++ ] ) ) {

                        // Do not traverse comment nodes
                        ret += getText( node );
                    }
                } else if ( nodeType === 1 || nodeType === 9 || nodeType === 11 ) {

                    // Use textContent for elements
                    // innerText usage removed for consistency of new lines (jQuery #11153)
                    if ( typeof elem.textContent === "string" ) {
                        return elem.textContent;
                    } else {

                        // Traverse its children
                        for ( elem = elem.firstChild; elem; elem = elem.nextSibling ) {
                            ret += getText( elem );
                        }
                    }
                } else if ( nodeType === 3 || nodeType === 4 ) {
                    return elem.nodeValue;
                }

                // Do not include comment or processing instruction nodes

                return ret;
            };

            Expr = Sizzle.selectors = {

                // Can be adjusted by the user
                cacheLength: 50,

                createPseudo: markFunction,

                match: matchExpr,

                attrHandle: {},

                find: {},

                relative: {
                    ">": { dir: "parentNode", first: true },
                    " ": { dir: "parentNode" },
                    "+": { dir: "previousSibling", first: true },
                    "~": { dir: "previousSibling" }
                },

                preFilter: {
                    "ATTR": function( match ) {
                        match[ 1 ] = match[ 1 ].replace( runescape, funescape );

                        // Move the given value to match[3] whether quoted or unquoted
                        match[ 3 ] = ( match[ 3 ] || match[ 4 ] ||
                            match[ 5 ] || "" ).replace( runescape, funescape );

                        if ( match[ 2 ] === "~=" ) {
                            match[ 3 ] = " " + match[ 3 ] + " ";
                        }

                        return match.slice( 0, 4 );
                    },

                    "CHILD": function( match ) {

                        /* matches from matchExpr["CHILD"]
				1 type (only|nth|...)
				2 what (child|of-type)
				3 argument (even|odd|\d*|\d*n([+-]\d+)?|...)
				4 xn-component of xn+y argument ([+-]?\d*n|)
				5 sign of xn-component
				6 x of xn-component
				7 sign of y-component
				8 y of y-component
			*/
                        match[ 1 ] = match[ 1 ].toLowerCase();

                        if ( match[ 1 ].slice( 0, 3 ) === "nth" ) {

                            // nth-* requires argument
                            if ( !match[ 3 ] ) {
                                Sizzle.error( match[ 0 ] );
                            }

                            // numeric x and y parameters for Expr.filter.CHILD
                            // remember that false/true cast respectively to 0/1
                            match[ 4 ] = +( match[ 4 ] ?
                                match[ 5 ] + ( match[ 6 ] || 1 ) :
                                2 * ( match[ 3 ] === "even" || match[ 3 ] === "odd" ) );
                            match[ 5 ] = +( ( match[ 7 ] + match[ 8 ] ) || match[ 3 ] === "odd" );

                            // other types prohibit arguments
                        } else if ( match[ 3 ] ) {
                            Sizzle.error( match[ 0 ] );
                        }

                        return match;
                    },

                    "PSEUDO": function( match ) {
                        var excess,
                            unquoted = !match[ 6 ] && match[ 2 ];

                        if ( matchExpr[ "CHILD" ].test( match[ 0 ] ) ) {
                            return null;
                        }

                        // Accept quoted arguments as-is
                        if ( match[ 3 ] ) {
                            match[ 2 ] = match[ 4 ] || match[ 5 ] || "";

                            // Strip excess characters from unquoted arguments
                        } else if ( unquoted && rpseudo.test( unquoted ) &&

                            // Get excess from tokenize (recursively)
                            ( excess = tokenize( unquoted, true ) ) &&

                            // advance to the next closing parenthesis
                            ( excess = unquoted.indexOf( ")", unquoted.length - excess ) - unquoted.length ) ) {

                            // excess is a negative index
                            match[ 0 ] = match[ 0 ].slice( 0, excess );
                            match[ 2 ] = unquoted.slice( 0, excess );
                        }

                        // Return only captures needed by the pseudo filter method (type and argument)
                        return match.slice( 0, 3 );
                    }
                },

                filter: {

                    "TAG": function( nodeNameSelector ) {
                        var nodeName = nodeNameSelector.replace( runescape, funescape ).toLowerCase();
                        return nodeNameSelector === "*" ?
                            function() {
                                return true;
                            } :
                            function( elem ) {
                                return elem.nodeName && elem.nodeName.toLowerCase() === nodeName;
                            };
                    },

                    "CLASS": function( className ) {
                        var pattern = classCache[ className + " " ];

                        return pattern ||
                            ( pattern = new RegExp( "(^|" + whitespace +
                                ")" + className + "(" + whitespace + "|$)" ) ) && classCache(
                                className, function( elem ) {
                                    return pattern.test(
                                        typeof elem.className === "string" && elem.className ||
                                        typeof elem.getAttribute !== "undefined" &&
                                        elem.getAttribute( "class" ) ||
                                        ""
                                    );
                                } );
                    },

                    "ATTR": function( name, operator, check ) {
                        return function( elem ) {
                            var result = Sizzle.attr( elem, name );

                            if ( result == null ) {
                                return operator === "!=";
                            }
                            if ( !operator ) {
                                return true;
                            }

                            result += "";

                            /* eslint-disable max-len */

                            return operator === "=" ? result === check :
                                operator === "!=" ? result !== check :
                                    operator === "^=" ? check && result.indexOf( check ) === 0 :
                                        operator === "*=" ? check && result.indexOf( check ) > -1 :
                                            operator === "$=" ? check && result.slice( -check.length ) === check :
                                                operator === "~=" ? ( " " + result.replace( rwhitespace, " " ) + " " ).indexOf( check ) > -1 :
                                                    operator === "|=" ? result === check || result.slice( 0, check.length + 1 ) === check + "-" :
                                                        false;
                            /* eslint-enable max-len */

                        };
                    },

                    "CHILD": function( type, what, _argument, first, last ) {
                        var simple = type.slice( 0, 3 ) !== "nth",
                            forward = type.slice( -4 ) !== "last",
                            ofType = what === "of-type";

                        return first === 1 && last === 0 ?

                            // Shortcut for :nth-*(n)
                            function( elem ) {
                                return !!elem.parentNode;
                            } :

                            function( elem, _context, xml ) {
                                var cache, uniqueCache, outerCache, node, nodeIndex, start,
                                    dir = simple !== forward ? "nextSibling" : "previousSibling",
                                    parent = elem.parentNode,
                                    name = ofType && elem.nodeName.toLowerCase(),
                                    useCache = !xml && !ofType,
                                    diff = false;

                                if ( parent ) {

                                    // :(first|last|only)-(child|of-type)
                                    if ( simple ) {
                                        while ( dir ) {
                                            node = elem;
                                            while ( ( node = node[ dir ] ) ) {
                                                if ( ofType ?
                                                    node.nodeName.toLowerCase() === name :
                                                    node.nodeType === 1 ) {

                                                    return false;
                                                }
                                            }

                                            // Reverse direction for :only-* (if we haven't yet done so)
                                            start = dir = type === "only" && !start && "nextSibling";
                                        }
                                        return true;
                                    }

                                    start = [ forward ? parent.firstChild : parent.lastChild ];

                                    // non-xml :nth-child(...) stores cache data on `parent`
                                    if ( forward && useCache ) {

                                        // Seek `elem` from a previously-cached index

                                        // ...in a gzip-friendly way
                                        node = parent;
                                        outerCache = node[ expando ] || ( node[ expando ] = {} );

                                        // Support: IE <9 only
                                        // Defend against cloned attroperties (jQuery gh-1709)
                                        uniqueCache = outerCache[ node.uniqueID ] ||
                                            ( outerCache[ node.uniqueID ] = {} );

                                        cache = uniqueCache[ type ] || [];
                                        nodeIndex = cache[ 0 ] === dirruns && cache[ 1 ];
                                        diff = nodeIndex && cache[ 2 ];
                                        node = nodeIndex && parent.childNodes[ nodeIndex ];

                                        while ( ( node = ++nodeIndex && node && node[ dir ] ||

                                            // Fallback to seeking `elem` from the start
                                            ( diff = nodeIndex = 0 ) || start.pop() ) ) {

                                            // When found, cache indexes on `parent` and break
                                            if ( node.nodeType === 1 && ++diff && node === elem ) {
                                                uniqueCache[ type ] = [ dirruns, nodeIndex, diff ];
                                                break;
                                            }
                                        }

                                    } else {

                                        // Use previously-cached element index if available
                                        if ( useCache ) {

                                            // ...in a gzip-friendly way
                                            node = elem;
                                            outerCache = node[ expando ] || ( node[ expando ] = {} );

                                            // Support: IE <9 only
                                            // Defend against cloned attroperties (jQuery gh-1709)
                                            uniqueCache = outerCache[ node.uniqueID ] ||
                                                ( outerCache[ node.uniqueID ] = {} );

                                            cache = uniqueCache[ type ] || [];
                                            nodeIndex = cache[ 0 ] === dirruns && cache[ 1 ];
                                            diff = nodeIndex;
                                        }

                                        // xml :nth-child(...)
                                        // or :nth-last-child(...) or :nth(-last)?-of-type(...)
                                        if ( diff === false ) {

                                            // Use the same loop as above to seek `elem` from the start
                                            while ( ( node = ++nodeIndex && node && node[ dir ] ||
                                                ( diff = nodeIndex = 0 ) || start.pop() ) ) {

                                                if ( ( ofType ?
                                                    node.nodeName.toLowerCase() === name :
                                                    node.nodeType === 1 ) &&
                                                    ++diff ) {

                                                    // Cache the index of each encountered element
                                                    if ( useCache ) {
                                                        outerCache = node[ expando ] ||
                                                            ( node[ expando ] = {} );

                                                        // Support: IE <9 only
                                                        // Defend against cloned attroperties (jQuery gh-1709)
                                                        uniqueCache = outerCache[ node.uniqueID ] ||
                                                            ( outerCache[ node.uniqueID ] = {} );

                                                        uniqueCache[ type ] = [ dirruns, diff ];
                                                    }

                                                    if ( node === elem ) {
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // Incorporate the offset, then check against cycle size
                                    diff -= last;
                                    return diff === first || ( diff % first === 0 && diff / first >= 0 );
                                }
                            };
                    },

                    "PSEUDO": function( pseudo, argument ) {

                        // pseudo-class names are case-insensitive
                        // http://www.w3.org/TR/selectors/#pseudo-classes
                        // Prioritize by case sensitivity in case custom pseudos are added with uppercase letters
                        // Remember that setFilters inherits from pseudos
                        var args,
                            fn = Expr.pseudos[ pseudo ] || Expr.setFilters[ pseudo.toLowerCase() ] ||
                                Sizzle.error( "unsupported pseudo: " + pseudo );

                        // The user may use createPseudo to indicate that
                        // arguments are needed to create the filter function
                        // just as Sizzle does
                        if ( fn[ expando ] ) {
                            return fn( argument );
                        }

                        // But maintain support for old signatures
                        if ( fn.length > 1 ) {
                            args = [ pseudo, pseudo, "", argument ];
                            return Expr.setFilters.hasOwnProperty( pseudo.toLowerCase() ) ?
                                markFunction( function( seed, matches ) {
                                    var idx,
                                        matched = fn( seed, argument ),
                                        i = matched.length;
                                    while ( i-- ) {
                                        idx = indexOf( seed, matched[ i ] );
                                        seed[ idx ] = !( matches[ idx ] = matched[ i ] );
                                    }
                                } ) :
                                function( elem ) {
                                    return fn( elem, 0, args );
                                };
                        }

                        return fn;
                    }
                },

                pseudos: {

                    // Potentially complex pseudos
                    "not": markFunction( function( selector ) {

                        // Trim the selector passed to compile
                        // to avoid treating leading and trailing
                        // spaces as combinators
                        var input = [],
                            results = [],
                            matcher = compile( selector.replace( rtrim, "$1" ) );

                        return matcher[ expando ] ?
                            markFunction( function( seed, matches, _context, xml ) {
                                var elem,
                                    unmatched = matcher( seed, null, xml, [] ),
                                    i = seed.length;

                                // Match elements unmatched by `matcher`
                                while ( i-- ) {
                                    if ( ( elem = unmatched[ i ] ) ) {
                                        seed[ i ] = !( matches[ i ] = elem );
                                    }
                                }
                            } ) :
                            function( elem, _context, xml ) {
                                input[ 0 ] = elem;
                                matcher( input, null, xml, results );

                                // Don't keep the element (issue #299)
                                input[ 0 ] = null;
                                return !results.pop();
                            };
                    } ),

                    "has": markFunction( function( selector ) {
                        return function( elem ) {
                            return Sizzle( selector, elem ).length > 0;
                        };
                    } ),

                    "contains": markFunction( function( text ) {
                        text = text.replace( runescape, funescape );
                        return function( elem ) {
                            return ( elem.textContent || getText( elem ) ).indexOf( text ) > -1;
                        };
                    } ),

                    // "Whether an element is represented by a :lang() selector
                    // is based solely on the element's language value
                    // being equal to the identifier C,
                    // or beginning with the identifier C immediately followed by "-".
                    // The matching of C against the element's language value is performed case-insensitively.
                    // The identifier C does not have to be a valid language name."
                    // http://www.w3.org/TR/selectors/#lang-pseudo
                    "lang": markFunction( function( lang ) {

                        // lang value must be a valid identifier
                        if ( !ridentifier.test( lang || "" ) ) {
                            Sizzle.error( "unsupported lang: " + lang );
                        }
                        lang = lang.replace( runescape, funescape ).toLowerCase();
                        return function( elem ) {
                            var elemLang;
                            do {
                                if ( ( elemLang = documentIsHTML ?
                                    elem.lang :
                                    elem.getAttribute( "xml:lang" ) || elem.getAttribute( "lang" ) ) ) {

                                    elemLang = elemLang.toLowerCase();
                                    return elemLang === lang || elemLang.indexOf( lang + "-" ) === 0;
                                }
                            } while ( ( elem = elem.parentNode ) && elem.nodeType === 1 );
                            return false;
                        };
                    } ),

                    // Miscellaneous
                    "target": function( elem ) {
                        var hash = window.location && window.location.hash;
                        return hash && hash.slice( 1 ) === elem.id;
                    },

                    "root": function( elem ) {
                        return elem === docElem;
                    },

                    "focus": function( elem ) {
                        return elem === document.activeElement &&
                            ( !document.hasFocus || document.hasFocus() ) &&
                            !!( elem.type || elem.href || ~elem.tabIndex );
                    },

                    // Boolean properties
                    "enabled": createDisabledPseudo( false ),
                    "disabled": createDisabledPseudo( true ),

                    "checked": function( elem ) {

                        // In CSS3, :checked should return both checked and selected elements
                        // http://www.w3.org/TR/2011/REC-css3-selectors-20110929/#checked
                        var nodeName = elem.nodeName.toLowerCase();
                        return ( nodeName === "input" && !!elem.checked ) ||
                            ( nodeName === "option" && !!elem.selected );
                    },

                    "selected": function( elem ) {

                        // Accessing this property makes selected-by-default
                        // options in Safari work properly
                        if ( elem.parentNode ) {
                            // eslint-disable-next-line no-unused-expressions
                            elem.parentNode.selectedIndex;
                        }

                        return elem.selected === true;
                    },

                    // Contents
                    "empty": function( elem ) {

                        // http://www.w3.org/TR/selectors/#empty-pseudo
                        // :empty is negated by element (1) or content nodes (text: 3; cdata: 4; entity ref: 5),
                        //   but not by others (comment: 8; processing instruction: 7; etc.)
                        // nodeType < 6 works because attributes (2) do not appear as children
                        for ( elem = elem.firstChild; elem; elem = elem.nextSibling ) {
                            if ( elem.nodeType < 6 ) {
                                return false;
                            }
                        }
                        return true;
                    },

                    "parent": function( elem ) {
                        return !Expr.pseudos[ "empty" ]( elem );
                    },

                    // Element/input types
                    "header": function( elem ) {
                        return rheader.test( elem.nodeName );
                    },

                    "input": function( elem ) {
                        return rinputs.test( elem.nodeName );
                    },

                    "button": function( elem ) {
                        var name = elem.nodeName.toLowerCase();
                        return name === "input" && elem.type === "button" || name === "button";
                    },

                    "text": function( elem ) {
                        var attr;
                        return elem.nodeName.toLowerCase() === "input" &&
                            elem.type === "text" &&

                            // Support: IE<8
                            // New HTML5 attribute values (e.g., "search") appear with elem.type === "text"
                            ( ( attr = elem.getAttribute( "type" ) ) == null ||
                                attr.toLowerCase() === "text" );
                    },

                    // Position-in-collection
                    "first": createPositionalPseudo( function() {
                        return [ 0 ];
                    } ),

                    "last": createPositionalPseudo( function( _matchIndexes, length ) {
                        return [ length - 1 ];
                    } ),

                    "eq": createPositionalPseudo( function( _matchIndexes, length, argument ) {
                        return [ argument < 0 ? argument + length : argument ];
                    } ),

                    "even": createPositionalPseudo( function( matchIndexes, length ) {
                        var i = 0;
                        for ( ; i < length; i += 2 ) {
                            matchIndexes.push( i );
                        }
                        return matchIndexes;
                    } ),

                    "odd": createPositionalPseudo( function( matchIndexes, length ) {
                        var i = 1;
                        for ( ; i < length; i += 2 ) {
                            matchIndexes.push( i );
                        }
                        return matchIndexes;
                    } ),

                    "lt": createPositionalPseudo( function( matchIndexes, length, argument ) {
                        var i = argument < 0 ?
                            argument + length :
                            argument > length ?
                                length :
                                argument;
                        for ( ; --i >= 0; ) {
                            matchIndexes.push( i );
                        }
                        return matchIndexes;
                    } ),

                    "gt": createPositionalPseudo( function( matchIndexes, length, argument ) {
                        var i = argument < 0 ? argument + length : argument;
                        for ( ; ++i < length; ) {
                            matchIndexes.push( i );
                        }
                        return matchIndexes;
                    } )
                }
            };

            Expr.pseudos[ "nth" ] = Expr.pseudos[ "eq" ];

// Add button/input type pseudos
            for ( i in { radio: true, checkbox: true, file: true, password: true, image: true } ) {
                Expr.pseudos[ i ] = createInputPseudo( i );
            }
            for ( i in { submit: true, reset: true } ) {
                Expr.pseudos[ i ] = createButtonPseudo( i );
            }

// Easy API for creating new setFilters
            function setFilters() {}
            setFilters.prototype = Expr.filters = Expr.pseudos;
            Expr.setFilters = new setFilters();

            tokenize = Sizzle.tokenize = function( selector, parseOnly ) {
                var matched, match, tokens, type,
                    soFar, groups, preFilters,
                    cached = tokenCache[ selector + " " ];

                if ( cached ) {
                    return parseOnly ? 0 : cached.slice( 0 );
                }

                soFar = selector;
                groups = [];
                preFilters = Expr.preFilter;

                while ( soFar ) {

                    // Comma and first run
                    if ( !matched || ( match = rcomma.exec( soFar ) ) ) {
                        if ( match ) {

                            // Don't consume trailing commas as valid
                            soFar = soFar.slice( match[ 0 ].length ) || soFar;
                        }
                        groups.push( ( tokens = [] ) );
                    }

                    matched = false;

                    // Combinators
                    if ( ( match = rcombinators.exec( soFar ) ) ) {
                        matched = match.shift();
                        tokens.push( {
                            value: matched,

                            // Cast descendant combinators to space
                            type: match[ 0 ].replace( rtrim, " " )
                        } );
                        soFar = soFar.slice( matched.length );
                    }

                    // Filters
                    for ( type in Expr.filter ) {
                        if ( ( match = matchExpr[ type ].exec( soFar ) ) && ( !preFilters[ type ] ||
                            ( match = preFilters[ type ]( match ) ) ) ) {
                            matched = match.shift();
                            tokens.push( {
                                value: matched,
                                type: type,
                                matches: match
                            } );
                            soFar = soFar.slice( matched.length );
                        }
                    }

                    if ( !matched ) {
                        break;
                    }
                }

                // Return the length of the invalid excess
                // if we're just parsing
                // Otherwise, throw an error or return tokens
                return parseOnly ?
                    soFar.length :
                    soFar ?
                        Sizzle.error( selector ) :

                        // Cache the tokens
                        tokenCache( selector, groups ).slice( 0 );
            };

            function toSelector( tokens ) {
                var i = 0,
                    len = tokens.length,
                    selector = "";
                for ( ; i < len; i++ ) {
                    selector += tokens[ i ].value;
                }
                return selector;
            }

            function addCombinator( matcher, combinator, base ) {
                var dir = combinator.dir,
                    skip = combinator.next,
                    key = skip || dir,
                    checkNonElements = base && key === "parentNode",
                    doneName = done++;

                return combinator.first ?

                    // Check against closest ancestor/preceding element
                    function( elem, context, xml ) {
                        while ( ( elem = elem[ dir ] ) ) {
                            if ( elem.nodeType === 1 || checkNonElements ) {
                                return matcher( elem, context, xml );
                            }
                        }
                        return false;
                    } :

                    // Check against all ancestor/preceding elements
                    function( elem, context, xml ) {
                        var oldCache, uniqueCache, outerCache,
                            newCache = [ dirruns, doneName ];

                        // We can't set arbitrary data on XML nodes, so they don't benefit from combinator caching
                        if ( xml ) {
                            while ( ( elem = elem[ dir ] ) ) {
                                if ( elem.nodeType === 1 || checkNonElements ) {
                                    if ( matcher( elem, context, xml ) ) {
                                        return true;
                                    }
                                }
                            }
                        } else {
                            while ( ( elem = elem[ dir ] ) ) {
                                if ( elem.nodeType === 1 || checkNonElements ) {
                                    outerCache = elem[ expando ] || ( elem[ expando ] = {} );

                                    // Support: IE <9 only
                                    // Defend against cloned attroperties (jQuery gh-1709)
                                    uniqueCache = outerCache[ elem.uniqueID ] ||
                                        ( outerCache[ elem.uniqueID ] = {} );

                                    if ( skip && skip === elem.nodeName.toLowerCase() ) {
                                        elem = elem[ dir ] || elem;
                                    } else if ( ( oldCache = uniqueCache[ key ] ) &&
                                        oldCache[ 0 ] === dirruns && oldCache[ 1 ] === doneName ) {

                                        // Assign to newCache so results back-propagate to previous elements
                                        return ( newCache[ 2 ] = oldCache[ 2 ] );
                                    } else {

                                        // Reuse newcache so results back-propagate to previous elements
                                        uniqueCache[ key ] = newCache;

                                        // A match means we're done; a fail means we have to keep checking
                                        if ( ( newCache[ 2 ] = matcher( elem, context, xml ) ) ) {
                                            return true;
                                        }
                                    }
                                }
                            }
                        }
                        return false;
                    };
            }

            function elementMatcher( matchers ) {
                return matchers.length > 1 ?
                    function( elem, context, xml ) {
                        var i = matchers.length;
                        while ( i-- ) {
                            if ( !matchers[ i ]( elem, context, xml ) ) {
                                return false;
                            }
                        }
                        return true;
                    } :
                    matchers[ 0 ];
            }

            function multipleContexts( selector, contexts, results ) {
                var i = 0,
                    len = contexts.length;
                for ( ; i < len; i++ ) {
                    Sizzle( selector, contexts[ i ], results );
                }
                return results;
            }

            function condense( unmatched, map, filter, context, xml ) {
                var elem,
                    newUnmatched = [],
                    i = 0,
                    len = unmatched.length,
                    mapped = map != null;

                for ( ; i < len; i++ ) {
                    if ( ( elem = unmatched[ i ] ) ) {
                        if ( !filter || filter( elem, context, xml ) ) {
                            newUnmatched.push( elem );
                            if ( mapped ) {
                                map.push( i );
                            }
                        }
                    }
                }

                return newUnmatched;
            }

            function setMatcher( preFilter, selector, matcher, postFilter, postFinder, postSelector ) {
                if ( postFilter && !postFilter[ expando ] ) {
                    postFilter = setMatcher( postFilter );
                }
                if ( postFinder && !postFinder[ expando ] ) {
                    postFinder = setMatcher( postFinder, postSelector );
                }
                return markFunction( function( seed, results, context, xml ) {
                    var temp, i, elem,
                        preMap = [],
                        postMap = [],
                        preexisting = results.length,

                        // Get initial elements from seed or context
                        elems = seed || multipleContexts(
                            selector || "*",
                            context.nodeType ? [ context ] : context,
                            []
                        ),

                        // Prefilter to get matcher input, preserving a map for seed-results synchronization
                        matcherIn = preFilter && ( seed || !selector ) ?
                            condense( elems, preMap, preFilter, context, xml ) :
                            elems,

                        matcherOut = matcher ?

                            // If we have a postFinder, or filtered seed, or non-seed postFilter or preexisting results,
                            postFinder || ( seed ? preFilter : preexisting || postFilter ) ?

                                // ...intermediate processing is necessary
                                [] :

                                // ...otherwise use results directly
                                results :
                            matcherIn;

                    // Find primary matches
                    if ( matcher ) {
                        matcher( matcherIn, matcherOut, context, xml );
                    }

                    // Apply postFilter
                    if ( postFilter ) {
                        temp = condense( matcherOut, postMap );
                        postFilter( temp, [], context, xml );

                        // Un-match failing elements by moving them back to matcherIn
                        i = temp.length;
                        while ( i-- ) {
                            if ( ( elem = temp[ i ] ) ) {
                                matcherOut[ postMap[ i ] ] = !( matcherIn[ postMap[ i ] ] = elem );
                            }
                        }
                    }

                    if ( seed ) {
                        if ( postFinder || preFilter ) {
                            if ( postFinder ) {

                                // Get the final matcherOut by condensing this intermediate into postFinder contexts
                                temp = [];
                                i = matcherOut.length;
                                while ( i-- ) {
                                    if ( ( elem = matcherOut[ i ] ) ) {

                                        // Restore matcherIn since elem is not yet a final match
                                        temp.push( ( matcherIn[ i ] = elem ) );
                                    }
                                }
                                postFinder( null, ( matcherOut = [] ), temp, xml );
                            }

                            // Move matched elements from seed to results to keep them synchronized
                            i = matcherOut.length;
                            while ( i-- ) {
                                if ( ( elem = matcherOut[ i ] ) &&
                                    ( temp = postFinder ? indexOf( seed, elem ) : preMap[ i ] ) > -1 ) {

                                    seed[ temp ] = !( results[ temp ] = elem );
                                }
                            }
                        }

                        // Add elements to results, through postFinder if defined
                    } else {
                        matcherOut = condense(
                            matcherOut === results ?
                                matcherOut.splice( preexisting, matcherOut.length ) :
                                matcherOut
                        );
                        if ( postFinder ) {
                            postFinder( null, results, matcherOut, xml );
                        } else {
                            push.apply( results, matcherOut );
                        }
                    }
                } );
            }

            function matcherFromTokens( tokens ) {
                var checkContext, matcher, j,
                    len = tokens.length,
                    leadingRelative = Expr.relative[ tokens[ 0 ].type ],
                    implicitRelative = leadingRelative || Expr.relative[ " " ],
                    i = leadingRelative ? 1 : 0,

                    // The foundational matcher ensures that elements are reachable from top-level context(s)
                    matchContext = addCombinator( function( elem ) {
                        return elem === checkContext;
                    }, implicitRelative, true ),
                    matchAnyContext = addCombinator( function( elem ) {
                        return indexOf( checkContext, elem ) > -1;
                    }, implicitRelative, true ),
                    matchers = [ function( elem, context, xml ) {
                        var ret = ( !leadingRelative && ( xml || context !== outermostContext ) ) || (
                            ( checkContext = context ).nodeType ?
                                matchContext( elem, context, xml ) :
                                matchAnyContext( elem, context, xml ) );

                        // Avoid hanging onto element (issue #299)
                        checkContext = null;
                        return ret;
                    } ];

                for ( ; i < len; i++ ) {
                    if ( ( matcher = Expr.relative[ tokens[ i ].type ] ) ) {
                        matchers = [ addCombinator( elementMatcher( matchers ), matcher ) ];
                    } else {
                        matcher = Expr.filter[ tokens[ i ].type ].apply( null, tokens[ i ].matches );

                        // Return special upon seeing a positional matcher
                        if ( matcher[ expando ] ) {

                            // Find the next relative operator (if any) for proper handling
                            j = ++i;
                            for ( ; j < len; j++ ) {
                                if ( Expr.relative[ tokens[ j ].type ] ) {
                                    break;
                                }
                            }
                            return setMatcher(
                                i > 1 && elementMatcher( matchers ),
                                i > 1 && toSelector(

                                // If the preceding token was a descendant combinator, insert an implicit any-element `*`
                                tokens
                                    .slice( 0, i - 1 )
                                    .concat( { value: tokens[ i - 2 ].type === " " ? "*" : "" } )
                                ).replace( rtrim, "$1" ),
                                matcher,
                                i < j && matcherFromTokens( tokens.slice( i, j ) ),
                                j < len && matcherFromTokens( ( tokens = tokens.slice( j ) ) ),
                                j < len && toSelector( tokens )
                            );
                        }
                        matchers.push( matcher );
                    }
                }

                return elementMatcher( matchers );
            }

            function matcherFromGroupMatchers( elementMatchers, setMatchers ) {
                var bySet = setMatchers.length > 0,
                    byElement = elementMatchers.length > 0,
                    superMatcher = function( seed, context, xml, results, outermost ) {
                        var elem, j, matcher,
                            matchedCount = 0,
                            i = "0",
                            unmatched = seed && [],
                            setMatched = [],
                            contextBackup = outermostContext,

                            // We must always have either seed elements or outermost context
                            elems = seed || byElement && Expr.find[ "TAG" ]( "*", outermost ),

                            // Use integer dirruns iff this is the outermost matcher
                            dirrunsUnique = ( dirruns += contextBackup == null ? 1 : Math.random() || 0.1 ),
                            len = elems.length;

                        if ( outermost ) {

                            // Support: IE 11+, Edge 17 - 18+
                            // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                            // two documents; shallow comparisons work.
                            // eslint-disable-next-line eqeqeq
                            outermostContext = context == document || context || outermost;
                        }

                        // Add elements passing elementMatchers directly to results
                        // Support: IE<9, Safari
                        // Tolerate NodeList properties (IE: "length"; Safari: <number>) matching elements by id
                        for ( ; i !== len && ( elem = elems[ i ] ) != null; i++ ) {
                            if ( byElement && elem ) {
                                j = 0;

                                // Support: IE 11+, Edge 17 - 18+
                                // IE/Edge sometimes throw a "Permission denied" error when strict-comparing
                                // two documents; shallow comparisons work.
                                // eslint-disable-next-line eqeqeq
                                if ( !context && elem.ownerDocument != document ) {
                                    setDocument( elem );
                                    xml = !documentIsHTML;
                                }
                                while ( ( matcher = elementMatchers[ j++ ] ) ) {
                                    if ( matcher( elem, context || document, xml ) ) {
                                        results.push( elem );
                                        break;
                                    }
                                }
                                if ( outermost ) {
                                    dirruns = dirrunsUnique;
                                }
                            }

                            // Track unmatched elements for set filters
                            if ( bySet ) {

                                // They will have gone through all possible matchers
                                if ( ( elem = !matcher && elem ) ) {
                                    matchedCount--;
                                }

                                // Lengthen the array for every element, matched or not
                                if ( seed ) {
                                    unmatched.push( elem );
                                }
                            }
                        }

                        // `i` is now the count of elements visited above, and adding it to `matchedCount`
                        // makes the latter nonnegative.
                        matchedCount += i;

                        // Apply set filters to unmatched elements
                        // NOTE: This can be skipped if there are no unmatched elements (i.e., `matchedCount`
                        // equals `i`), unless we didn't visit _any_ elements in the above loop because we have
                        // no element matchers and no seed.
                        // Incrementing an initially-string "0" `i` allows `i` to remain a string only in that
                        // case, which will result in a "00" `matchedCount` that differs from `i` but is also
                        // numerically zero.
                        if ( bySet && i !== matchedCount ) {
                            j = 0;
                            while ( ( matcher = setMatchers[ j++ ] ) ) {
                                matcher( unmatched, setMatched, context, xml );
                            }

                            if ( seed ) {

                                // Reintegrate element matches to eliminate the need for sorting
                                if ( matchedCount > 0 ) {
                                    while ( i-- ) {
                                        if ( !( unmatched[ i ] || setMatched[ i ] ) ) {
                                            setMatched[ i ] = pop.call( results );
                                        }
                                    }
                                }

                                // Discard index placeholder values to get only actual matches
                                setMatched = condense( setMatched );
                            }

                            // Add matches to results
                            push.apply( results, setMatched );

                            // Seedless set matches succeeding multiple successful matchers stipulate sorting
                            if ( outermost && !seed && setMatched.length > 0 &&
                                ( matchedCount + setMatchers.length ) > 1 ) {

                                Sizzle.uniqueSort( results );
                            }
                        }

                        // Override manipulation of globals by nested matchers
                        if ( outermost ) {
                            dirruns = dirrunsUnique;
                            outermostContext = contextBackup;
                        }

                        return unmatched;
                    };

                return bySet ?
                    markFunction( superMatcher ) :
                    superMatcher;
            }

            compile = Sizzle.compile = function( selector, match /* Internal Use Only */ ) {
                var i,
                    setMatchers = [],
                    elementMatchers = [],
                    cached = compilerCache[ selector + " " ];

                if ( !cached ) {

                    // Generate a function of recursive functions that can be used to check each element
                    if ( !match ) {
                        match = tokenize( selector );
                    }
                    i = match.length;
                    while ( i-- ) {
                        cached = matcherFromTokens( match[ i ] );
                        if ( cached[ expando ] ) {
                            setMatchers.push( cached );
                        } else {
                            elementMatchers.push( cached );
                        }
                    }

                    // Cache the compiled function
                    cached = compilerCache(
                        selector,
                        matcherFromGroupMatchers( elementMatchers, setMatchers )
                    );

                    // Save selector and tokenization
                    cached.selector = selector;
                }
                return cached;
            };

            /**
             * A low-level selection function that works with Sizzle's compiled
             *  selector functions
             * @param {String|Function} selector A selector or a pre-compiled
             *  selector function built with Sizzle.compile
             * @param {Element} context
             * @param {Array} [results]
             * @param {Array} [seed] A set of elements to match against
             */
            select = Sizzle.select = function( selector, context, results, seed ) {
                var i, tokens, token, type, find,
                    compiled = typeof selector === "function" && selector,
                    match = !seed && tokenize( ( selector = compiled.selector || selector ) );

                results = results || [];

                // Try to minimize operations if there is only one selector in the list and no seed
                // (the latter of which guarantees us context)
                if ( match.length === 1 ) {

                    // Reduce context if the leading compound selector is an ID
                    tokens = match[ 0 ] = match[ 0 ].slice( 0 );
                    if ( tokens.length > 2 && ( token = tokens[ 0 ] ).type === "ID" &&
                        context.nodeType === 9 && documentIsHTML && Expr.relative[ tokens[ 1 ].type ] ) {

                        context = ( Expr.find[ "ID" ]( token.matches[ 0 ]
                            .replace( runescape, funescape ), context ) || [] )[ 0 ];
                        if ( !context ) {
                            return results;

                            // Precompiled matchers will still verify ancestry, so step up a level
                        } else if ( compiled ) {
                            context = context.parentNode;
                        }

                        selector = selector.slice( tokens.shift().value.length );
                    }

                    // Fetch a seed set for right-to-left matching
                    i = matchExpr[ "needsContext" ].test( selector ) ? 0 : tokens.length;
                    while ( i-- ) {
                        token = tokens[ i ];

                        // Abort if we hit a combinator
                        if ( Expr.relative[ ( type = token.type ) ] ) {
                            break;
                        }
                        if ( ( find = Expr.find[ type ] ) ) {

                            // Search, expanding context for leading sibling combinators
                            if ( ( seed = find(
                                token.matches[ 0 ].replace( runescape, funescape ),
                                rsibling.test( tokens[ 0 ].type ) && testContext( context.parentNode ) ||
                                context
                            ) ) ) {

                                // If seed is empty or no tokens remain, we can return early
                                tokens.splice( i, 1 );
                                selector = seed.length && toSelector( tokens );
                                if ( !selector ) {
                                    push.apply( results, seed );
                                    return results;
                                }

                                break;
                            }
                        }
                    }
                }

                // Compile and execute a filtering function if one is not provided
                // Provide `match` to avoid retokenization if we modified the selector above
                ( compiled || compile( selector, match ) )(
                    seed,
                    context,
                    !documentIsHTML,
                    results,
                    !context || rsibling.test( selector ) && testContext( context.parentNode ) || context
                );
                return results;
            };

// One-time assignments

// Sort stability
            support.sortStable = expando.split( "" ).sort( sortOrder ).join( "" ) === expando;

// Support: Chrome 14-35+
// Always assume duplicates if they aren't passed to the comparison function
            support.detectDuplicates = !!hasDuplicate;

// Initialize against the default document
            setDocument();

// Support: Webkit<537.32 - Safari 6.0.3/Chrome 25 (fixed in Chrome 27)
// Detached nodes confoundingly follow *each other*
            support.sortDetached = assert( function( el ) {

                // Should return 1, but returns 4 (following)
                return el.compareDocumentPosition( document.createElement( "fieldset" ) ) & 1;
            } );

// Support: IE<8
// Prevent attribute/property "interpolation"
// https://msdn.microsoft.com/en-us/library/ms536429%28VS.85%29.aspx
            if ( !assert( function( el ) {
                el.innerHTML = "<a href='#'></a>";
                return el.firstChild.getAttribute( "href" ) === "#";
            } ) ) {
                addHandle( "type|href|height|width", function( elem, name, isXML ) {
                    if ( !isXML ) {
                        return elem.getAttribute( name, name.toLowerCase() === "type" ? 1 : 2 );
                    }
                } );
            }

// Support: IE<9
// Use defaultValue in place of getAttribute("value")
            if ( !support.attributes || !assert( function( el ) {
                el.innerHTML = "<input/>";
                el.firstChild.setAttribute( "value", "" );
                return el.firstChild.getAttribute( "value" ) === "";
            } ) ) {
                addHandle( "value", function( elem, _name, isXML ) {
                    if ( !isXML && elem.nodeName.toLowerCase() === "input" ) {
                        return elem.defaultValue;
                    }
                } );
            }

// Support: IE<9
// Use getAttributeNode to fetch booleans when getAttribute lies
            if ( !assert( function( el ) {
                return el.getAttribute( "disabled" ) == null;
            } ) ) {
                addHandle( booleans, function( elem, name, isXML ) {
                    var val;
                    if ( !isXML ) {
                        return elem[ name ] === true ? name.toLowerCase() :
                            ( val = elem.getAttributeNode( name ) ) && val.specified ?
                                val.value :
                                null;
                    }
                } );
            }

            return Sizzle;

        } )( window );



    jQuery.find = Sizzle;
    jQuery.expr = Sizzle.selectors;

// Deprecated
    jQuery.expr[ ":" ] = jQuery.expr.pseudos;
    jQuery.uniqueSort = jQuery.unique = Sizzle.uniqueSort;
    jQuery.text = Sizzle.getText;
    jQuery.isXMLDoc = Sizzle.isXML;
    jQuery.contains = Sizzle.contains;
    jQuery.escapeSelector = Sizzle.escape;




    var dir = function( elem, dir, until ) {
        var matched = [],
            truncate = until !== undefined;

        while ( ( elem = elem[ dir ] ) && elem.nodeType !== 9 ) {
            if ( elem.nodeType === 1 ) {
                if ( truncate && jQuery( elem ).is( until ) ) {
                    break;
                }
                matched.push( elem );
            }
        }
        return matched;
    };


    var siblings = function( n, elem ) {
        var matched = [];

        for ( ; n; n = n.nextSibling ) {
            if ( n.nodeType === 1 && n !== elem ) {
                matched.push( n );
            }
        }

        return matched;
    };


    var rneedsContext = jQuery.expr.match.needsContext;



    function nodeName( elem, name ) {

        return elem.nodeName && elem.nodeName.toLowerCase() === name.toLowerCase();

    }
    var rsingleTag = ( /^<([a-z][^\/\0>:\x20\t\r\n\f]*)[\x20\t\r\n\f]*\/?>(?:<\/\1>|)$/i );



// Implement the identical functionality for filter and not
    function winnow( elements, qualifier, not ) {
        if ( isFunction( qualifier ) ) {
            return jQuery.grep( elements, function( elem, i ) {
                return !!qualifier.call( elem, i, elem ) !== not;
            } );
        }

        // Single element
        if ( qualifier.nodeType ) {
            return jQuery.grep( elements, function( elem ) {
                return ( elem === qualifier ) !== not;
            } );
        }

        // Arraylike of elements (jQuery, arguments, Array)
        if ( typeof qualifier !== "string" ) {
            return jQuery.grep( elements, function( elem ) {
                return ( indexOf.call( qualifier, elem ) > -1 ) !== not;
            } );
        }

        // Filtered directly for both simple and complex selectors
        return jQuery.filter( qualifier, elements, not );
    }

    jQuery.filter = function( expr, elems, not ) {
        var elem = elems[ 0 ];

        if ( not ) {
            expr = ":not(" + expr + ")";
        }

        if ( elems.length === 1 && elem.nodeType === 1 ) {
            return jQuery.find.matchesSelector( elem, expr ) ? [ elem ] : [];
        }

        return jQuery.find.matches( expr, jQuery.grep( elems, function( elem ) {
            return elem.nodeType === 1;
        } ) );
    };

    jQuery.fn.extend( {
        find: function( selector ) {
            var i, ret,
                len = this.length,
                self = this;

            if ( typeof selector !== "string" ) {
                return this.pushStack( jQuery( selector ).filter( function() {
                    for ( i = 0; i < len; i++ ) {
                        if ( jQuery.contains( self[ i ], this ) ) {
                            return true;
                        }
                    }
                } ) );
            }

            ret = this.pushStack( [] );

            for ( i = 0; i < len; i++ ) {
                jQuery.find( selector, self[ i ], ret );
            }

            return len > 1 ? jQuery.uniqueSort( ret ) : ret;
        },
        filter: function( selector ) {
            return this.pushStack( winnow( this, selector || [], false ) );
        },
        not: function( selector ) {
            return this.pushStack( winnow( this, selector || [], true ) );
        },
        is: function( selector ) {
            return !!winnow(
                this,

                // If this is a positional/relative selector, check membership in the returned set
                // so $("p:first").is("p:last") won't return true for a doc with two "p".
                typeof selector === "string" && rneedsContext.test( selector ) ?
                    jQuery( selector ) :
                    selector || [],
                false
            ).length;
        }
    } );


// Initialize a jQuery object


// A central reference to the root jQuery(document)
    var rootjQuery,

        // A simple way to check for HTML strings
        // Prioritize #id over <tag> to avoid XSS via location.hash (#9521)
        // Strict HTML recognition (#11290: must start with <)
        // Shortcut simple #id case for speed
        rquickExpr = /^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]+))$/,

        init = jQuery.fn.init = function( selector, context, root ) {
            var match, elem;

            // HANDLE: $(""), $(null), $(undefined), $(false)
            if ( !selector ) {
                return this;
            }

            // Method init() accepts an alternate rootjQuery
            // so migrate can support jQuery.sub (gh-2101)
            root = root || rootjQuery;

            // Handle HTML strings
            if ( typeof selector === "string" ) {
                if ( selector[ 0 ] === "<" &&
                    selector[ selector.length - 1 ] === ">" &&
                    selector.length >= 3 ) {

                    // Assume that strings that start and end with <> are HTML and skip the regex check
                    match = [ null, selector, null ];

                } else {
                    match = rquickExpr.exec( selector );
                }

                // Match html or make sure no context is specified for #id
                if ( match && ( match[ 1 ] || !context ) ) {

                    // HANDLE: $(html) -> $(array)
                    if ( match[ 1 ] ) {
                        context = context instanceof jQuery ? context[ 0 ] : context;

                        // Option to run scripts is true for back-compat
                        // Intentionally let the error be thrown if parseHTML is not present
                        jQuery.merge( this, jQuery.parseHTML(
                            match[ 1 ],
                            context && context.nodeType ? context.ownerDocument || context : document,
                            true
                        ) );

                        // HANDLE: $(html, props)
                        if ( rsingleTag.test( match[ 1 ] ) && jQuery.isPlainObject( context ) ) {
                            for ( match in context ) {

                                // Properties of context are called as methods if possible
                                if ( isFunction( this[ match ] ) ) {
                                    this[ match ]( context[ match ] );

                                    // ...and otherwise set as attributes
                                } else {
                                    this.attr( match, context[ match ] );
                                }
                            }
                        }

                        return this;

                        // HANDLE: $(#id)
                    } else {
                        elem = document.getElementById( match[ 2 ] );

                        if ( elem ) {

                            // Inject the element directly into the jQuery object
                            this[ 0 ] = elem;
                            this.length = 1;
                        }
                        return this;
                    }

                    // HANDLE: $(expr, $(...))
                } else if ( !context || context.jquery ) {
                    return ( context || root ).find( selector );

                    // HANDLE: $(expr, context)
                    // (which is just equivalent to: $(context).find(expr)
                } else {
                    return this.constructor( context ).find( selector );
                }

                // HANDLE: $(DOMElement)
            } else if ( selector.nodeType ) {
                this[ 0 ] = selector;
                this.length = 1;
                return this;

                // HANDLE: $(function)
                // Shortcut for document ready
            } else if ( isFunction( selector ) ) {
                return root.ready !== undefined ?
                    root.ready( selector ) :

                    // Execute immediately if ready is not present
                    selector( jQuery );
            }

            return jQuery.makeArray( selector, this );
        };

// Give the init function the jQuery prototype for later instantiation
    init.prototype = jQuery.fn;

// Initialize central reference
    rootjQuery = jQuery( document );


    var rparentsprev = /^(?:parents|prev(?:Until|All))/,

        // Methods guaranteed to produce a unique set when starting from a unique set
        guaranteedUnique = {
            children: true,
            contents: true,
            next: true,
            prev: true
        };

    jQuery.fn.extend( {
        has: function( target ) {
            var targets = jQuery( target, this ),
                l = targets.length;

            return this.filter( function() {
                var i = 0;
                for ( ; i < l; i++ ) {
                    if ( jQuery.contains( this, targets[ i ] ) ) {
                        return true;
                    }
                }
            } );
        },

        closest: function( selectors, context ) {
            var cur,
                i = 0,
                l = this.length,
                matched = [],
                targets = typeof selectors !== "string" && jQuery( selectors );

            // Positional selectors never match, since there's no _selection_ context
            if ( !rneedsContext.test( selectors ) ) {
                for ( ; i < l; i++ ) {
                    for ( cur = this[ i ]; cur && cur !== context; cur = cur.parentNode ) {

                        // Always skip document fragments
                        if ( cur.nodeType < 11 && ( targets ?
                            targets.index( cur ) > -1 :

                            // Don't pass non-elements to Sizzle
                            cur.nodeType === 1 &&
                            jQuery.find.matchesSelector( cur, selectors ) ) ) {

                            matched.push( cur );
                            break;
                        }
                    }
                }
            }

            return this.pushStack( matched.length > 1 ? jQuery.uniqueSort( matched ) : matched );
        },

        // Determine the position of an element within the set
        index: function( elem ) {

            // No argument, return index in parent
            if ( !elem ) {
                return ( this[ 0 ] && this[ 0 ].parentNode ) ? this.first().prevAll().length : -1;
            }

            // Index in selector
            if ( typeof elem === "string" ) {
                return indexOf.call( jQuery( elem ), this[ 0 ] );
            }

            // Locate the position of the desired element
            return indexOf.call( this,

                // If it receives a jQuery object, the first element is used
                elem.jquery ? elem[ 0 ] : elem
            );
        },

        add: function( selector, context ) {
            return this.pushStack(
                jQuery.uniqueSort(
                    jQuery.merge( this.get(), jQuery( selector, context ) )
                )
            );
        },

        addBack: function( selector ) {
            return this.add( selector == null ?
                this.prevObject : this.prevObject.filter( selector )
            );
        }
    } );

    function sibling( cur, dir ) {
        while ( ( cur = cur[ dir ] ) && cur.nodeType !== 1 ) {}
        return cur;
    }

    jQuery.each( {
        parent: function( elem ) {
            var parent = elem.parentNode;
            return parent && parent.nodeType !== 11 ? parent : null;
        },
        parents: function( elem ) {
            return dir( elem, "parentNode" );
        },
        parentsUntil: function( elem, _i, until ) {
            return dir( elem, "parentNode", until );
        },
        next: function( elem ) {
            return sibling( elem, "nextSibling" );
        },
        prev: function( elem ) {
            return sibling( elem, "previousSibling" );
        },
        nextAll: function( elem ) {
            return dir( elem, "nextSibling" );
        },
        prevAll: function( elem ) {
            return dir( elem, "previousSibling" );
        },
        nextUntil: function( elem, _i, until ) {
            return dir( elem, "nextSibling", until );
        },
        prevUntil: function( elem, _i, until ) {
            return dir( elem, "previousSibling", until );
        },
        siblings: function( elem ) {
            return siblings( ( elem.parentNode || {} ).firstChild, elem );
        },
        children: function( elem ) {
            return siblings( elem.firstChild );
        },
        contents: function( elem ) {
            if ( elem.contentDocument != null &&

                // Support: IE 11+
                // <object> elements with no `data` attribute has an object
                // `contentDocument` with a `null` prototype.
                getProto( elem.contentDocument ) ) {

                return elem.contentDocument;
            }

            // Support: IE 9 - 11 only, iOS 7 only, Android Browser <=4.3 only
            // Treat the template element as a regular one in browsers that
            // don't support it.
            if ( nodeName( elem, "template" ) ) {
                elem = elem.content || elem;
            }

            return jQuery.merge( [], elem.childNodes );
        }
    }, function( name, fn ) {
        jQuery.fn[ name ] = function( until, selector ) {
            var matched = jQuery.map( this, fn, until );

            if ( name.slice( -5 ) !== "Until" ) {
                selector = until;
            }

            if ( selector && typeof selector === "string" ) {
                matched = jQuery.filter( selector, matched );
            }

            if ( this.length > 1 ) {

                // Remove duplicates
                if ( !guaranteedUnique[ name ] ) {
                    jQuery.uniqueSort( matched );
                }

                // Reverse order for parents* and prev-derivatives
                if ( rparentsprev.test( name ) ) {
                    matched.reverse();
                }
            }

            return this.pushStack( matched );
        };
    } );
    var rnothtmlwhite = ( /[^\x20\t\r\n\f]+/g );



// Convert String-formatted options into Object-formatted ones
    function createOptions( options ) {
        var object = {};
        jQuery.each( options.match( rnothtmlwhite ) || [], function( _, flag ) {
            object[ flag ] = true;
        } );
        return object;
    }

    /*
 * Create a callback list using the following parameters:
 *
 *	options: an optional list of space-separated options that will change how
 *			the callback list behaves or a more traditional option object
 *
 * By default a callback list will act like an event callback list and can be
 * "fired" multiple times.
 *
 * Possible options:
 *
 *	once:			will ensure the callback list can only be fired once (like a Deferred)
 *
 *	memory:			will keep track of previous values and will call any callback added
 *					after the list has been fired right away with the latest "memorized"
 *					values (like a Deferred)
 *
 *	unique:			will ensure a callback can only be added once (no duplicate in the list)
 *
 *	stopOnFalse:	interrupt callings when a callback returns false
 *
 */
    jQuery.Callbacks = function( options ) {

        // Convert options from String-formatted to Object-formatted if needed
        // (we check in cache first)
        options = typeof options === "string" ?
            createOptions( options ) :
            jQuery.extend( {}, options );

        var // Flag to know if list is currently firing
            firing,

            // Last fire value for non-forgettable lists
            memory,

            // Flag to know if list was already fired
            fired,

            // Flag to prevent firing
            locked,

            // Actual callback list
            list = [],

            // Queue of execution data for repeatable lists
            queue = [],

            // Index of currently firing callback (modified by add/remove as needed)
            firingIndex = -1,

            // Fire callbacks
            fire = function() {

                // Enforce single-firing
                locked = locked || options.once;

                // Execute callbacks for all pending executions,
                // respecting firingIndex overrides and runtime changes
                fired = firing = true;
                for ( ; queue.length; firingIndex = -1 ) {
                    memory = queue.shift();
                    while ( ++firingIndex < list.length ) {

                        // Run callback and check for early termination
                        if ( list[ firingIndex ].apply( memory[ 0 ], memory[ 1 ] ) === false &&
                            options.stopOnFalse ) {

                            // Jump to end and forget the data so .add doesn't re-fire
                            firingIndex = list.length;
                            memory = false;
                        }
                    }
                }

                // Forget the data if we're done with it
                if ( !options.memory ) {
                    memory = false;
                }

                firing = false;

                // Clean up if we're done firing for good
                if ( locked ) {

                    // Keep an empty list if we have data for future add calls
                    if ( memory ) {
                        list = [];

                        // Otherwise, this object is spent
                    } else {
                        list = "";
                    }
                }
            },

            // Actual Callbacks object
            self = {

                // Add a callback or a collection of callbacks to the list
                add: function() {
                    if ( list ) {

                        // If we have memory from a past run, we should fire after adding
                        if ( memory && !firing ) {
                            firingIndex = list.length - 1;
                            queue.push( memory );
                        }

                        ( function add( args ) {
                            jQuery.each( args, function( _, arg ) {
                                if ( isFunction( arg ) ) {
                                    if ( !options.unique || !self.has( arg ) ) {
                                        list.push( arg );
                                    }
                                } else if ( arg && arg.length && toType( arg ) !== "string" ) {

                                    // Inspect recursively
                                    add( arg );
                                }
                            } );
                        } )( arguments );

                        if ( memory && !firing ) {
                            fire();
                        }
                    }
                    return this;
                },

                // Remove a callback from the list
                remove: function() {
                    jQuery.each( arguments, function( _, arg ) {
                        var index;
                        while ( ( index = jQuery.inArray( arg, list, index ) ) > -1 ) {
                            list.splice( index, 1 );

                            // Handle firing indexes
                            if ( index <= firingIndex ) {
                                firingIndex--;
                            }
                        }
                    } );
                    return this;
                },

                // Check if a given callback is in the list.
                // If no argument is given, return whether or not list has callbacks attached.
                has: function( fn ) {
                    return fn ?
                        jQuery.inArray( fn, list ) > -1 :
                        list.length > 0;
                },

                // Remove all callbacks from the list
                empty: function() {
                    if ( list ) {
                        list = [];
                    }
                    return this;
                },

                // Disable .fire and .add
                // Abort any current/pending executions
                // Clear all callbacks and values
                disable: function() {
                    locked = queue = [];
                    list = memory = "";
                    return this;
                },
                disabled: function() {
                    return !list;
                },

                // Disable .fire
                // Also disable .add unless we have memory (since it would have no effect)
                // Abort any pending executions
                lock: function() {
                    locked = queue = [];
                    if ( !memory && !firing ) {
                        list = memory = "";
                    }
                    return this;
                },
                locked: function() {
                    return !!locked;
                },

                // Call all callbacks with the given context and arguments
                fireWith: function( context, args ) {
                    if ( !locked ) {
                        args = args || [];
                        args = [ context, args.slice ? args.slice() : args ];
                        queue.push( args );
                        if ( !firing ) {
                            fire();
                        }
                    }
                    return this;
                },

                // Call all the callbacks with the given arguments
                fire: function() {
                    self.fireWith( this, arguments );
                    return this;
                },

                // To know if the callbacks have already been called at least once
                fired: function() {
                    return !!fired;
                }
            };

        return self;
    };


    function Identity( v ) {
        return v;
    }
    function Thrower( ex ) {
        throw ex;
    }

    function adoptValue( value, resolve, reject, noValue ) {
        var method;

        try {

            // Check for promise aspect first to privilege synchronous behavior
            if ( value && isFunction( ( method = value.promise ) ) ) {
                method.call( value ).done( resolve ).fail( reject );

                // Other thenables
            } else if ( value && isFunction( ( method = value.then ) ) ) {
                method.call( value, resolve, reject );

                // Other non-thenables
            } else {

                // Control `resolve` arguments by letting Array#slice cast boolean `noValue` to integer:
                // * false: [ value ].slice( 0 ) => resolve( value )
                // * true: [ value ].slice( 1 ) => resolve()
                resolve.apply( undefined, [ value ].slice( noValue ) );
            }

            // For Promises/A+, convert exceptions into rejections
            // Since jQuery.when doesn't unwrap thenables, we can skip the extra checks appearing in
            // Deferred#then to conditionally suppress rejection.
        } catch ( value ) {

            // Support: Android 4.0 only
            // Strict mode functions invoked without .call/.apply get global-object context
            reject.apply( undefined, [ value ] );
        }
    }

    jQuery.extend( {

        Deferred: function( func ) {
            var tuples = [

                    // action, add listener, callbacks,
                    // ... .then handlers, argument index, [final state]
                    [ "notify", "progress", jQuery.Callbacks( "memory" ),
                        jQuery.Callbacks( "memory" ), 2 ],
                    [ "resolve", "done", jQuery.Callbacks( "once memory" ),
                        jQuery.Callbacks( "once memory" ), 0, "resolved" ],
                    [ "reject", "fail", jQuery.Callbacks( "once memory" ),
                        jQuery.Callbacks( "once memory" ), 1, "rejected" ]
                ],
                state = "pending",
                promise = {
                    state: function() {
                        return state;
                    },
                    always: function() {
                        deferred.done( arguments ).fail( arguments );
                        return this;
                    },
                    "catch": function( fn ) {
                        return promise.then( null, fn );
                    },

                    // Keep pipe for back-compat
                    pipe: function( /* fnDone, fnFail, fnProgress */ ) {
                        var fns = arguments;

                        return jQuery.Deferred( function( newDefer ) {
                            jQuery.each( tuples, function( _i, tuple ) {

                                // Map tuples (progress, done, fail) to arguments (done, fail, progress)
                                var fn = isFunction( fns[ tuple[ 4 ] ] ) && fns[ tuple[ 4 ] ];

                                // deferred.progress(function() { bind to newDefer or newDefer.notify })
                                // deferred.done(function() { bind to newDefer or newDefer.resolve })
                                // deferred.fail(function() { bind to newDefer or newDefer.reject })
                                deferred[ tuple[ 1 ] ]( function() {
                                    var returned = fn && fn.apply( this, arguments );
                                    if ( returned && isFunction( returned.promise ) ) {
                                        returned.promise()
                                            .progress( newDefer.notify )
                                            .done( newDefer.resolve )
                                            .fail( newDefer.reject );
                                    } else {
                                        newDefer[ tuple[ 0 ] + "With" ](
                                            this,
                                            fn ? [ returned ] : arguments
                                        );
                                    }
                                } );
                            } );
                            fns = null;
                        } ).promise();
                    },
                    then: function( onFulfilled, onRejected, onProgress ) {
                        var maxDepth = 0;
                        function resolve( depth, deferred, handler, special ) {
                            return function() {
                                var that = this,
                                    args = arguments,
                                    mightThrow = function() {
                                        var returned, then;

                                        // Support: Promises/A+ section 2.3.3.3.3
                                        // https://promisesaplus.com/#point-59
                                        // Ignore double-resolution attempts
                                        if ( depth < maxDepth ) {
                                            return;
                                        }

                                        returned = handler.apply( that, args );

                                        // Support: Promises/A+ section 2.3.1
                                        // https://promisesaplus.com/#point-48
                                        if ( returned === deferred.promise() ) {
                                            throw new TypeError( "Thenable self-resolution" );
                                        }

                                        // Support: Promises/A+ sections 2.3.3.1, 3.5
                                        // https://promisesaplus.com/#point-54
                                        // https://promisesaplus.com/#point-75
                                        // Retrieve `then` only once
                                        then = returned &&

                                            // Support: Promises/A+ section 2.3.4
                                            // https://promisesaplus.com/#point-64
                                            // Only check objects and functions for thenability
                                            ( typeof returned === "object" ||
                                                typeof returned === "function" ) &&
                                            returned.then;

                                        // Handle a returned thenable
                                        if ( isFunction( then ) ) {

                                            // Special processors (notify) just wait for resolution
                                            if ( special ) {
                                                then.call(
                                                    returned,
                                                    resolve( maxDepth, deferred, Identity, special ),
                                                    resolve( maxDepth, deferred, Thrower, special )
                                                );

                                                // Normal processors (resolve) also hook into progress
                                            } else {

                                                // ...and disregard older resolution values
                                                maxDepth++;

                                                then.call(
                                                    returned,
                                                    resolve( maxDepth, deferred, Identity, special ),
                                                    resolve( maxDepth, deferred, Thrower, special ),
                                                    resolve( maxDepth, deferred, Identity,
                                                        deferred.notifyWith )
                                                );
                                            }

                                            // Handle all other returned values
                                        } else {

                                            // Only substitute handlers pass on context
                                            // and multiple values (non-spec behavior)
                                            if ( handler !== Identity ) {
                                                that = undefined;
                                                args = [ returned ];
                                            }

                                            // Process the value(s)
                                            // Default process is resolve
                                            ( special || deferred.resolveWith )( that, args );
                                        }
                                    },

                                    // Only normal processors (resolve) catch and reject exceptions
                                    process = special ?
                                        mightThrow :
                                        function() {
                                            try {
                                                mightThrow();
                                            } catch ( e ) {

                                                if ( jQuery.Deferred.exceptionHook ) {
                                                    jQuery.Deferred.exceptionHook( e,
                                                        process.stackTrace );
                                                }

                                                // Support: Promises/A+ section 2.3.3.3.4.1
                                                // https://promisesaplus.com/#point-61
                                                // Ignore post-resolution exceptions
                                                if ( depth + 1 >= maxDepth ) {

                                                    // Only substitute handlers pass on context
                                                    // and multiple values (non-spec behavior)
                                                    if ( handler !== Thrower ) {
                                                        that = undefined;
                                                        args = [ e ];
                                                    }

                                                    deferred.rejectWith( that, args );
                                                }
                                            }
                                        };

                                // Support: Promises/A+ section 2.3.3.3.1
                                // https://promisesaplus.com/#point-57
                                // Re-resolve promises immediately to dodge false rejection from
                                // subsequent errors
                                if ( depth ) {
                                    process();
                                } else {

                                    // Call an optional hook to record the stack, in case of exception
                                    // since it's otherwise lost when execution goes async
                                    if ( jQuery.Deferred.getStackHook ) {
                                        process.stackTrace = jQuery.Deferred.getStackHook();
                                    }
                                    window.setTimeout( process );
                                }
                            };
                        }

                        return jQuery.Deferred( function( newDefer ) {

                            // progress_handlers.add( ... )
                            tuples[ 0 ][ 3 ].add(
                                resolve(
                                    0,
                                    newDefer,
                                    isFunction( onProgress ) ?
                                        onProgress :
                                        Identity,
                                    newDefer.notifyWith
                                )
                            );

                            // fulfilled_handlers.add( ... )
                            tuples[ 1 ][ 3 ].add(
                                resolve(
                                    0,
                                    newDefer,
                                    isFunction( onFulfilled ) ?
                                        onFulfilled :
                                        Identity
                                )
                            );

                            // rejected_handlers.add( ... )
                            tuples[ 2 ][ 3 ].add(
                                resolve(
                                    0,
                                    newDefer,
                                    isFunction( onRejected ) ?
                                        onRejected :
                                        Thrower
                                )
                            );
                        } ).promise();
                    },

                    // Get a promise for this deferred
                    // If obj is provided, the promise aspect is added to the object
                    promise: function( obj ) {
                        return obj != null ? jQuery.extend( obj, promise ) : promise;
                    }
                },
                deferred = {};

            // Add list-specific methods
            jQuery.each( tuples, function( i, tuple ) {
                var list = tuple[ 2 ],
                    stateString = tuple[ 5 ];

                // promise.progress = list.add
                // promise.done = list.add
                // promise.fail = list.add
                promise[ tuple[ 1 ] ] = list.add;

                // Handle state
                if ( stateString ) {
                    list.add(
                        function() {

                            // state = "resolved" (i.e., fulfilled)
                            // state = "rejected"
                            state = stateString;
                        },

                        // rejected_callbacks.disable
                        // fulfilled_callbacks.disable
                        tuples[ 3 - i ][ 2 ].disable,

                        // rejected_handlers.disable
                        // fulfilled_handlers.disable
                        tuples[ 3 - i ][ 3 ].disable,

                        // progress_callbacks.lock
                        tuples[ 0 ][ 2 ].lock,

                        // progress_handlers.lock
                        tuples[ 0 ][ 3 ].lock
                    );
                }

                // progress_handlers.fire
                // fulfilled_handlers.fire
                // rejected_handlers.fire
                list.add( tuple[ 3 ].fire );

                // deferred.notify = function() { deferred.notifyWith(...) }
                // deferred.resolve = function() { deferred.resolveWith(...) }
                // deferred.reject = function() { deferred.rejectWith(...) }
                deferred[ tuple[ 0 ] ] = function() {
                    deferred[ tuple[ 0 ] + "With" ]( this === deferred ? undefined : this, arguments );
                    return this;
                };

                // deferred.notifyWith = list.fireWith
                // deferred.resolveWith = list.fireWith
                // deferred.rejectWith = list.fireWith
                deferred[ tuple[ 0 ] + "With" ] = list.fireWith;
            } );

            // Make the deferred a promise
            promise.promise( deferred );

            // Call given func if any
            if ( func ) {
                func.call( deferred, deferred );
            }

            // All done!
            return deferred;
        },

        // Deferred helper
        when: function( singleValue ) {
            var

                // count of uncompleted subordinates
                remaining = arguments.length,

                // count of unprocessed arguments
                i = remaining,

                // subordinate fulfillment data
                resolveContexts = Array( i ),
                resolveValues = slice.call( arguments ),

                // the primary Deferred
                primary = jQuery.Deferred(),

                // subordinate callback factory
                updateFunc = function( i ) {
                    return function( value ) {
                        resolveContexts[ i ] = this;
                        resolveValues[ i ] = arguments.length > 1 ? slice.call( arguments ) : value;
                        if ( !( --remaining ) ) {
                            primary.resolveWith( resolveContexts, resolveValues );
                        }
                    };
                };

            // Single- and empty arguments are adopted like Promise.resolve
            if ( remaining <= 1 ) {
                adoptValue( singleValue, primary.done( updateFunc( i ) ).resolve, primary.reject,
                    !remaining );

                // Use .then() to unwrap secondary thenables (cf. gh-3000)
                if ( primary.state() === "pending" ||
                    isFunction( resolveValues[ i ] && resolveValues[ i ].then ) ) {

                    return primary.then();
                }
            }

            // Multiple arguments are aggregated like Promise.all array elements
            while ( i-- ) {
                adoptValue( resolveValues[ i ], updateFunc( i ), primary.reject );
            }

            return primary.promise();
        }
    } );


// These usually indicate a programmer mistake during development,
// warn about them ASAP rather than swallowing them by default.
    var rerrorNames = /^(Eval|Internal|Range|Reference|Syntax|Type|URI)Error$/;

    jQuery.Deferred.exceptionHook = function( error, stack ) {

        // Support: IE 8 - 9 only
        // Console exists when dev tools are open, which can happen at any time
        if ( window.console && window.console.warn && error && rerrorNames.test( error.name ) ) {
            window.console.warn( "jQuery.Deferred exception: " + error.message, error.stack, stack );
        }
    };




    jQuery.readyException = function( error ) {
        window.setTimeout( function() {
            throw error;
        } );
    };




// The deferred used on DOM ready
    var readyList = jQuery.Deferred();

    jQuery.fn.ready = function( fn ) {

        readyList
            .then( fn )

            // Wrap jQuery.readyException in a function so that the lookup
            // happens at the time of error handling instead of callback
            // registration.
            .catch( function( error ) {
                jQuery.readyException( error );
            } );

        return this;
    };

    jQuery.extend( {

        // Is the DOM ready to be used? Set to true once it occurs.
        isReady: false,

        // A counter to track how many items to wait for before
        // the ready event fires. See #6781
        readyWait: 1,

        // Handle when the DOM is ready
        ready: function( wait ) {

            // Abort if there are pending holds or we're already ready
            if ( wait === true ? --jQuery.readyWait : jQuery.isReady ) {
                return;
            }

            // Remember that the DOM is ready
            jQuery.isReady = true;

            // If a normal DOM Ready event fired, decrement, and wait if need be
            if ( wait !== true && --jQuery.readyWait > 0 ) {
                return;
            }

            // If there are functions bound, to execute
            readyList.resolveWith( document, [ jQuery ] );
        }
    } );

    jQuery.ready.then = readyList.then;

// The ready event handler and self cleanup method
    function completed() {
        document.removeEventListener( "DOMContentLoaded", completed );
        window.removeEventListener( "load", completed );
        jQuery.ready();
    }

// Catch cases where $(document).ready() is called
// after the browser event has already occurred.
// Support: IE <=9 - 10 only
// Older IE sometimes signals "interactive" too soon
    if ( document.readyState === "complete" ||
        ( document.readyState !== "loading" && !document.documentElement.doScroll ) ) {

        // Handle it asynchronously to allow scripts the opportunity to delay ready
        window.setTimeout( jQuery.ready );

    } else {

        // Use the handy event callback
        document.addEventListener( "DOMContentLoaded", completed );

        // A fallback to window.onload, that will always work
        window.addEventListener( "load", completed );
    }




// Multifunctional method to get and set values of a collection
// The value/s can optionally be executed if it's a function
    var access = function( elems, fn, key, value, chainable, emptyGet, raw ) {
        var i = 0,
            len = elems.length,
            bulk = key == null;

        // Sets many values
        if ( toType( key ) === "object" ) {
            chainable = true;
            for ( i in key ) {
                access( elems, fn, i, key[ i ], true, emptyGet, raw );
            }

            // Sets one value
        } else if ( value !== undefined ) {
            chainable = true;

            if ( !isFunction( value ) ) {
                raw = true;
            }

            if ( bulk ) {

                // Bulk operations run against the entire set
                if ( raw ) {
                    fn.call( elems, value );
                    fn = null;

                    // ...except when executing function values
                } else {
                    bulk = fn;
                    fn = function( elem, _key, value ) {
                        return bulk.call( jQuery( elem ), value );
                    };
                }
            }

            if ( fn ) {
                for ( ; i < len; i++ ) {
                    fn(
                        elems[ i ], key, raw ?
                            value :
                            value.call( elems[ i ], i, fn( elems[ i ], key ) )
                    );
                }
            }
        }

        if ( chainable ) {
            return elems;
        }

        // Gets
        if ( bulk ) {
            return fn.call( elems );
        }

        return len ? fn( elems[ 0 ], key ) : emptyGet;
    };


// Matches dashed string for camelizing
    var rmsPrefix = /^-ms-/,
        rdashAlpha = /-([a-z])/g;

// Used by camelCase as callback to replace()
    function fcamelCase( _all, letter ) {
        return letter.toUpperCase();
    }

// Convert dashed to camelCase; used by the css and data modules
// Support: IE <=9 - 11, Edge 12 - 15
// Microsoft forgot to hump their vendor prefix (#9572)
    function camelCase( string ) {
        return string.replace( rmsPrefix, "ms-" ).replace( rdashAlpha, fcamelCase );
    }
    var acceptData = function( owner ) {

        // Accepts only:
        //  - Node
        //    - Node.ELEMENT_NODE
        //    - Node.DOCUMENT_NODE
        //  - Object
        //    - Any
        return owner.nodeType === 1 || owner.nodeType === 9 || !( +owner.nodeType );
    };




    function Data() {
        this.expando = jQuery.expando + Data.uid++;
    }

    Data.uid = 1;

    Data.prototype = {

        cache: function( owner ) {

            // Check if the owner object already has a cache
            var value = owner[ this.expando ];

            // If not, create one
            if ( !value ) {
                value = {};

                // We can accept data for non-element nodes in modern browsers,
                // but we should not, see #8335.
                // Always return an empty object.
                if ( acceptData( owner ) ) {

                    // If it is a node unlikely to be stringify-ed or looped over
                    // use plain assignment
                    if ( owner.nodeType ) {
                        owner[ this.expando ] = value;

                        // Otherwise secure it in a non-enumerable property
                        // configurable must be true to allow the property to be
                        // deleted when data is removed
                    } else {
                        Object.defineProperty( owner, this.expando, {
                            value: value,
                            configurable: true
                        } );
                    }
                }
            }

            return value;
        },
        set: function( owner, data, value ) {
            var prop,
                cache = this.cache( owner );

            // Handle: [ owner, key, value ] args
            // Always use camelCase key (gh-2257)
            if ( typeof data === "string" ) {
                cache[ camelCase( data ) ] = value;

                // Handle: [ owner, { properties } ] args
            } else {

                // Copy the properties one-by-one to the cache object
                for ( prop in data ) {
                    cache[ camelCase( prop ) ] = data[ prop ];
                }
            }
            return cache;
        },
        get: function( owner, key ) {
            return key === undefined ?
                this.cache( owner ) :

                // Always use camelCase key (gh-2257)
                owner[ this.expando ] && owner[ this.expando ][ camelCase( key ) ];
        },
        access: function( owner, key, value ) {

            // In cases where either:
            //
            //   1. No key was specified
            //   2. A string key was specified, but no value provided
            //
            // Take the "read" path and allow the get method to determine
            // which value to return, respectively either:
            //
            //   1. The entire cache object
            //   2. The data stored at the key
            //
            if ( key === undefined ||
                ( ( key && typeof key === "string" ) && value === undefined ) ) {

                return this.get( owner, key );
            }

            // When the key is not a string, or both a key and value
            // are specified, set or extend (existing objects) with either:
            //
            //   1. An object of properties
            //   2. A key and value
            //
            this.set( owner, key, value );

            // Since the "set" path can have two possible entry points
            // return the expected data based on which path was taken[*]
            return value !== undefined ? value : key;
        },
        remove: function( owner, key ) {
            var i,
                cache = owner[ this.expando ];

            if ( cache === undefined ) {
                return;
            }

            if ( key !== undefined ) {

                // Support array or space separated string of keys
                if ( Array.isArray( key ) ) {

                    // If key is an array of keys...
                    // We always set camelCase keys, so remove that.
                    key = key.map( camelCase );
                } else {
                    key = camelCase( key );

                    // If a key with the spaces exists, use it.
                    // Otherwise, create an array by matching non-whitespace
                    key = key in cache ?
                        [ key ] :
                        ( key.match( rnothtmlwhite ) || [] );
                }

                i = key.length;

                while ( i-- ) {
                    delete cache[ key[ i ] ];
                }
            }

            // Remove the expando if there's no more data
            if ( key === undefined || jQuery.isEmptyObject( cache ) ) {

                // Support: Chrome <=35 - 45
                // Webkit & Blink performance suffers when deleting properties
                // from DOM nodes, so set to undefined instead
                // https://bugs.chromium.org/p/chromium/issues/detail?id=378607 (bug restricted)
                if ( owner.nodeType ) {
                    owner[ this.expando ] = undefined;
                } else {
                    delete owner[ this.expando ];
                }
            }
        },
        hasData: function( owner ) {
            var cache = owner[ this.expando ];
            return cache !== undefined && !jQuery.isEmptyObject( cache );
        }
    };
    var dataPriv = new Data();

    var dataUser = new Data();



//	Implementation Summary
//
//	1. Enforce API surface and semantic compatibility with 1.9.x branch
//	2. Improve the module's maintainability by reducing the storage
//		paths to a single mechanism.
//	3. Use the same single mechanism to support "private" and "user" data.
//	4. _Never_ expose "private" data to user code (TODO: Drop _data, _removeData)
//	5. Avoid exposing implementation details on user objects (eg. expando properties)
//	6. Provide a clear path for implementation upgrade to WeakMap in 2014

    var rbrace = /^(?:\{[\w\W]*\}|\[[\w\W]*\])$/,
        rmultiDash = /[A-Z]/g;

    function getData( data ) {
        if ( data === "true" ) {
            return true;
        }

        if ( data === "false" ) {
            return false;
        }

        if ( data === "null" ) {
            return null;
        }

        // Only convert to a number if it doesn't change the string
        if ( data === +data + "" ) {
            return +data;
        }

        if ( rbrace.test( data ) ) {
            return JSON.parse( data );
        }

        return data;
    }

    function dataAttr( elem, key, data ) {
        var name;

        // If nothing was found internally, try to fetch any
        // data from the HTML5 data-* attribute
        if ( data === undefined && elem.nodeType === 1 ) {
            name = "data-" + key.replace( rmultiDash, "-$&" ).toLowerCase();
            data = elem.getAttribute( name );

            if ( typeof data === "string" ) {
                try {
                    data = getData( data );
                } catch ( e ) {}

                // Make sure we set the data so it isn't changed later
                dataUser.set( elem, key, data );
            } else {
                data = undefined;
            }
        }
        return data;
    }

    jQuery.extend( {
        hasData: function( elem ) {
            return dataUser.hasData( elem ) || dataPriv.hasData( elem );
        },

        data: function( elem, name, data ) {
            return dataUser.access( elem, name, data );
        },

        removeData: function( elem, name ) {
            dataUser.remove( elem, name );
        },

        // TODO: Now that all calls to _data and _removeData have been replaced
        // with direct calls to dataPriv methods, these can be deprecated.
        _data: function( elem, name, data ) {
            return dataPriv.access( elem, name, data );
        },

        _removeData: function( elem, name ) {
            dataPriv.remove( elem, name );
        }
    } );

    jQuery.fn.extend( {
        data: function( key, value ) {
            var i, name, data,
                elem = this[ 0 ],
                attrs = elem && elem.attributes;

            // Gets all values
            if ( key === undefined ) {
                if ( this.length ) {
                    data = dataUser.get( elem );

                    if ( elem.nodeType === 1 && !dataPriv.get( elem, "hasDataAttrs" ) ) {
                        i = attrs.length;
                        while ( i-- ) {

                            // Support: IE 11 only
                            // The attrs elements can be null (#14894)
                            if ( attrs[ i ] ) {
                                name = attrs[ i ].name;
                                if ( name.indexOf( "data-" ) === 0 ) {
                                    name = camelCase( name.slice( 5 ) );
                                    dataAttr( elem, name, data[ name ] );
                                }
                            }
                        }
                        dataPriv.set( elem, "hasDataAttrs", true );
                    }
                }

                return data;
            }

            // Sets multiple values
            if ( typeof key === "object" ) {
                return this.each( function() {
                    dataUser.set( this, key );
                } );
            }

            return access( this, function( value ) {
                var data;

                // The calling jQuery object (element matches) is not empty
                // (and therefore has an element appears at this[ 0 ]) and the
                // `value` parameter was not undefined. An empty jQuery object
                // will result in `undefined` for elem = this[ 0 ] which will
                // throw an exception if an attempt to read a data cache is made.
                if ( elem && value === undefined ) {

                    // Attempt to get data from the cache
                    // The key will always be camelCased in Data
                    data = dataUser.get( elem, key );
                    if ( data !== undefined ) {
                        return data;
                    }

                    // Attempt to "discover" the data in
                    // HTML5 custom data-* attrs
                    data = dataAttr( elem, key );
                    if ( data !== undefined ) {
                        return data;
                    }

                    // We tried really hard, but the data doesn't exist.
                    return;
                }

                // Set the data...
                this.each( function() {

                    // We always store the camelCased key
                    dataUser.set( this, key, value );
                } );
            }, null, value, arguments.length > 1, null, true );
        },

        removeData: function( key ) {
            return this.each( function() {
                dataUser.remove( this, key );
            } );
        }
    } );


    jQuery.extend( {
        queue: function( elem, type, data ) {
            var queue;

            if ( elem ) {
                type = ( type || "fx" ) + "queue";
                queue = dataPriv.get( elem, type );

                // Speed up dequeue by getting out quickly if this is just a lookup
                if ( data ) {
                    if ( !queue || Array.isArray( data ) ) {
                        queue = dataPriv.access( elem, type, jQuery.makeArray( data ) );
                    } else {
                        queue.push( data );
                    }
                }
                return queue || [];
            }
        },

        dequeue: function( elem, type ) {
            type = type || "fx";

            var queue = jQuery.queue( elem, type ),
                startLength = queue.length,
                fn = queue.shift(),
                hooks = jQuery._queueHooks( elem, type ),
                next = function() {
                    jQuery.dequeue( elem, type );
                };

            // If the fx queue is dequeued, always remove the progress sentinel
            if ( fn === "inprogress" ) {
                fn = queue.shift();
                startLength--;
            }

            if ( fn ) {

                // Add a progress sentinel to prevent the fx queue from being
                // automatically dequeued
                if ( type === "fx" ) {
                    queue.unshift( "inprogress" );
                }

                // Clear up the last queue stop function
                delete hooks.stop;
                fn.call( elem, next, hooks );
            }

            if ( !startLength && hooks ) {
                hooks.empty.fire();
            }
        },

        // Not public - generate a queueHooks object, or return the current one
        _queueHooks: function( elem, type ) {
            var key = type + "queueHooks";
            return dataPriv.get( elem, key ) || dataPriv.access( elem, key, {
                empty: jQuery.Callbacks( "once memory" ).add( function() {
                    dataPriv.remove( elem, [ type + "queue", key ] );
                } )
            } );
        }
    } );

    jQuery.fn.extend( {
        queue: function( type, data ) {
            var setter = 2;

            if ( typeof type !== "string" ) {
                data = type;
                type = "fx";
                setter--;
            }

            if ( arguments.length < setter ) {
                return jQuery.queue( this[ 0 ], type );
            }

            return data === undefined ?
                this :
                this.each( function() {
                    var queue = jQuery.queue( this, type, data );

                    // Ensure a hooks for this queue
                    jQuery._queueHooks( this, type );

                    if ( type === "fx" && queue[ 0 ] !== "inprogress" ) {
                        jQuery.dequeue( this, type );
                    }
                } );
        },
        dequeue: function( type ) {
            return this.each( function() {
                jQuery.dequeue( this, type );
            } );
        },
        clearQueue: function( type ) {
            return this.queue( type || "fx", [] );
        },

        // Get a promise resolved when queues of a certain type
        // are emptied (fx is the type by default)
        promise: function( type, obj ) {
            var tmp,
                count = 1,
                defer = jQuery.Deferred(),
                elements = this,
                i = this.length,
                resolve = function() {
                    if ( !( --count ) ) {
                        defer.resolveWith( elements, [ elements ] );
                    }
                };

            if ( typeof type !== "string" ) {
                obj = type;
                type = undefined;
            }
            type = type || "fx";

            while ( i-- ) {
                tmp = dataPriv.get( elements[ i ], type + "queueHooks" );
                if ( tmp && tmp.empty ) {
                    count++;
                    tmp.empty.add( resolve );
                }
            }
            resolve();
            return defer.promise( obj );
        }
    } );
    var pnum = ( /[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/ ).source;

    var rcssNum = new RegExp( "^(?:([+-])=|)(" + pnum + ")([a-z%]*)$", "i" );


    var cssExpand = [ "Top", "Right", "Bottom", "Left" ];

    var documentElement = document.documentElement;



    var isAttached = function( elem ) {
            return jQuery.contains( elem.ownerDocument, elem );
        },
        composed = { composed: true };

    // Support: IE 9 - 11+, Edge 12 - 18+, iOS 10.0 - 10.2 only
    // Check attachment across shadow DOM boundaries when possible (gh-3504)
    // Support: iOS 10.0-10.2 only
    // Early iOS 10 versions support `attachShadow` but not `getRootNode`,
    // leading to errors. We need to check for `getRootNode`.
    if ( documentElement.getRootNode ) {
        isAttached = function( elem ) {
            return jQuery.contains( elem.ownerDocument, elem ) ||
                elem.getRootNode( composed ) === elem.ownerDocument;
        };
    }
    var isHiddenWithinTree = function( elem, el ) {

        // isHiddenWithinTree might be called from jQuery#filter function;
        // in that case, element will be second argument
        elem = el || elem;

        // Inline style trumps all
        return elem.style.display === "none" ||
            elem.style.display === "" &&

            // Otherwise, check computed style
            // Support: Firefox <=43 - 45
            // Disconnected elements can have computed display: none, so first confirm that elem is
            // in the document.
            isAttached( elem ) &&

            jQuery.css( elem, "display" ) === "none";
    };



    function adjustCSS( elem, prop, valueParts, tween ) {
        var adjusted, scale,
            maxIterations = 20,
            currentValue = tween ?
                function() {
                    return tween.cur();
                } :
                function() {
                    return jQuery.css( elem, prop, "" );
                },
            initial = currentValue(),
            unit = valueParts && valueParts[ 3 ] || ( jQuery.cssNumber[ prop ] ? "" : "px" ),

            // Starting value computation is required for potential unit mismatches
            initialInUnit = elem.nodeType &&
                ( jQuery.cssNumber[ prop ] || unit !== "px" && +initial ) &&
                rcssNum.exec( jQuery.css( elem, prop ) );

        if ( initialInUnit && initialInUnit[ 3 ] !== unit ) {

            // Support: Firefox <=54
            // Halve the iteration target value to prevent interference from CSS upper bounds (gh-2144)
            initial = initial / 2;

            // Trust units reported by jQuery.css
            unit = unit || initialInUnit[ 3 ];

            // Iteratively approximate from a nonzero starting point
            initialInUnit = +initial || 1;

            while ( maxIterations-- ) {

                // Evaluate and update our best guess (doubling guesses that zero out).
                // Finish if the scale equals or crosses 1 (making the old*new product non-positive).
                jQuery.style( elem, prop, initialInUnit + unit );
                if ( ( 1 - scale ) * ( 1 - ( scale = currentValue() / initial || 0.5 ) ) <= 0 ) {
                    maxIterations = 0;
                }
                initialInUnit = initialInUnit / scale;

            }

            initialInUnit = initialInUnit * 2;
            jQuery.style( elem, prop, initialInUnit + unit );

            // Make sure we update the tween properties later on
            valueParts = valueParts || [];
        }

        if ( valueParts ) {
            initialInUnit = +initialInUnit || +initial || 0;

            // Apply relative offset (+=/-=) if specified
            adjusted = valueParts[ 1 ] ?
                initialInUnit + ( valueParts[ 1 ] + 1 ) * valueParts[ 2 ] :
                +valueParts[ 2 ];
            if ( tween ) {
                tween.unit = unit;
                tween.start = initialInUnit;
                tween.end = adjusted;
            }
        }
        return adjusted;
    }


    var defaultDisplayMap = {};

    function getDefaultDisplay( elem ) {
        var temp,
            doc = elem.ownerDocument,
            nodeName = elem.nodeName,
            display = defaultDisplayMap[ nodeName ];

        if ( display ) {
            return display;
        }

        temp = doc.body.appendChild( doc.createElement( nodeName ) );
        display = jQuery.css( temp, "display" );

        temp.parentNode.removeChild( temp );

        if ( display === "none" ) {
            display = "block";
        }
        defaultDisplayMap[ nodeName ] = display;

        return display;
    }

    function showHide( elements, show ) {
        var display, elem,
            values = [],
            index = 0,
            length = elements.length;

        // Determine new display value for elements that need to change
        for ( ; index < length; index++ ) {
            elem = elements[ index ];
            if ( !elem.style ) {
                continue;
            }

            display = elem.style.display;
            if ( show ) {

                // Since we force visibility upon cascade-hidden elements, an immediate (and slow)
                // check is required in this first loop unless we have a nonempty display value (either
                // inline or about-to-be-restored)
                if ( display === "none" ) {
                    values[ index ] = dataPriv.get( elem, "display" ) || null;
                    if ( !values[ index ] ) {
                        elem.style.display = "";
                    }
                }
                if ( elem.style.display === "" && isHiddenWithinTree( elem ) ) {
                    values[ index ] = getDefaultDisplay( elem );
                }
            } else {
                if ( display !== "none" ) {
                    values[ index ] = "none";

                    // Remember what we're overwriting
                    dataPriv.set( elem, "display", display );
                }
            }
        }

        // Set the display of the elements in a second loop to avoid constant reflow
        for ( index = 0; index < length; index++ ) {
            if ( values[ index ] != null ) {
                elements[ index ].style.display = values[ index ];
            }
        }

        return elements;
    }

    jQuery.fn.extend( {
        show: function() {
            return showHide( this, true );
        },
        hide: function() {
            return showHide( this );
        },
        toggle: function( state ) {
            if ( typeof state === "boolean" ) {
                return state ? this.show() : this.hide();
            }

            return this.each( function() {
                if ( isHiddenWithinTree( this ) ) {
                    jQuery( this ).show();
                } else {
                    jQuery( this ).hide();
                }
            } );
        }
    } );
    var rcheckableType = ( /^(?:checkbox|radio)$/i );

    var rtagName = ( /<([a-z][^\/\0>\x20\t\r\n\f]*)/i );

    var rscriptType = ( /^$|^module$|\/(?:java|ecma)script/i );



    ( function() {
        var fragment = document.createDocumentFragment(),
            div = fragment.appendChild( document.createElement( "div" ) ),
            input = document.createElement( "input" );

        // Support: Android 4.0 - 4.3 only
        // Check state lost if the name is set (#11217)
        // Support: Windows Web Apps (WWA)
        // `name` and `type` must use .setAttribute for WWA (#14901)
        input.setAttribute( "type", "radio" );
        input.setAttribute( "checked", "checked" );
        input.setAttribute( "name", "t" );

        div.appendChild( input );

        // Support: Android <=4.1 only
        // Older WebKit doesn't clone checked state correctly in fragments
        support.checkClone = div.cloneNode( true ).cloneNode( true ).lastChild.checked;

        // Support: IE <=11 only
        // Make sure textarea (and checkbox) defaultValue is properly cloned
        div.innerHTML = "<textarea>x</textarea>";
        support.noCloneChecked = !!div.cloneNode( true ).lastChild.defaultValue;

        // Support: IE <=9 only
        // IE <=9 replaces <option> tags with their contents when inserted outside of
        // the select element.
        div.innerHTML = "<option></option>";
        support.option = !!div.lastChild;
    } )();


// We have to close these tags to support XHTML (#13200)
    var wrapMap = {

        // XHTML parsers do not magically insert elements in the
        // same way that tag soup parsers do. So we cannot shorten
        // this by omitting <tbody> or other required elements.
        thead: [ 1, "<table>", "</table>" ],
        col: [ 2, "<table><colgroup>", "</colgroup></table>" ],
        tr: [ 2, "<table><tbody>", "</tbody></table>" ],
        td: [ 3, "<table><tbody><tr>", "</tr></tbody></table>" ],

        _default: [ 0, "", "" ]
    };

    wrapMap.tbody = wrapMap.tfoot = wrapMap.colgroup = wrapMap.caption = wrapMap.thead;
    wrapMap.th = wrapMap.td;

// Support: IE <=9 only
    if ( !support.option ) {
        wrapMap.optgroup = wrapMap.option = [ 1, "<select multiple='multiple'>", "</select>" ];
    }


    function getAll( context, tag ) {

        // Support: IE <=9 - 11 only
        // Use typeof to avoid zero-argument method invocation on host objects (#15151)
        var ret;

        if ( typeof context.getElementsByTagName !== "undefined" ) {
            ret = context.getElementsByTagName( tag || "*" );

        } else if ( typeof context.querySelectorAll !== "undefined" ) {
            ret = context.querySelectorAll( tag || "*" );

        } else {
            ret = [];
        }

        if ( tag === undefined || tag && nodeName( context, tag ) ) {
            return jQuery.merge( [ context ], ret );
        }

        return ret;
    }


// Mark scripts as having already been evaluated
    function setGlobalEval( elems, refElements ) {
        var i = 0,
            l = elems.length;

        for ( ; i < l; i++ ) {
            dataPriv.set(
                elems[ i ],
                "globalEval",
                !refElements || dataPriv.get( refElements[ i ], "globalEval" )
            );
        }
    }


    var rhtml = /<|&#?\w+;/;

    function buildFragment( elems, context, scripts, selection, ignored ) {
        var elem, tmp, tag, wrap, attached, j,
            fragment = context.createDocumentFragment(),
            nodes = [],
            i = 0,
            l = elems.length;

        for ( ; i < l; i++ ) {
            elem = elems[ i ];

            if ( elem || elem === 0 ) {

                // Add nodes directly
                if ( toType( elem ) === "object" ) {

                    // Support: Android <=4.0 only, PhantomJS 1 only
                    // push.apply(_, arraylike) throws on ancient WebKit
                    jQuery.merge( nodes, elem.nodeType ? [ elem ] : elem );

                    // Convert non-html into a text node
                } else if ( !rhtml.test( elem ) ) {
                    nodes.push( context.createTextNode( elem ) );

                    // Convert html into DOM nodes
                } else {
                    tmp = tmp || fragment.appendChild( context.createElement( "div" ) );

                    // Deserialize a standard representation
                    tag = ( rtagName.exec( elem ) || [ "", "" ] )[ 1 ].toLowerCase();
                    wrap = wrapMap[ tag ] || wrapMap._default;
                    tmp.innerHTML = wrap[ 1 ] + jQuery.htmlPrefilter( elem ) + wrap[ 2 ];

                    // Descend through wrappers to the right content
                    j = wrap[ 0 ];
                    while ( j-- ) {
                        tmp = tmp.lastChild;
                    }

                    // Support: Android <=4.0 only, PhantomJS 1 only
                    // push.apply(_, arraylike) throws on ancient WebKit
                    jQuery.merge( nodes, tmp.childNodes );

                    // Remember the top-level container
                    tmp = fragment.firstChild;

                    // Ensure the created nodes are orphaned (#12392)
                    tmp.textContent = "";
                }
            }
        }

        // Remove wrapper from fragment
        fragment.textContent = "";

        i = 0;
        while ( ( elem = nodes[ i++ ] ) ) {

            // Skip elements already in the context collection (trac-4087)
            if ( selection && jQuery.inArray( elem, selection ) > -1 ) {
                if ( ignored ) {
                    ignored.push( elem );
                }
                continue;
            }

            attached = isAttached( elem );

            // Append to fragment
            tmp = getAll( fragment.appendChild( elem ), "script" );

            // Preserve script evaluation history
            if ( attached ) {
                setGlobalEval( tmp );
            }

            // Capture executables
            if ( scripts ) {
                j = 0;
                while ( ( elem = tmp[ j++ ] ) ) {
                    if ( rscriptType.test( elem.type || "" ) ) {
                        scripts.push( elem );
                    }
                }
            }
        }

        return fragment;
    }


    var rtypenamespace = /^([^.]*)(?:\.(.+)|)/;

    function returnTrue() {
        return true;
    }

    function returnFalse() {
        return false;
    }

// Support: IE <=9 - 11+
// focus() and blur() are asynchronous, except when they are no-op.
// So expect focus to be synchronous when the element is already active,
// and blur to be synchronous when the element is not already active.
// (focus and blur are always synchronous in other supported browsers,
// this just defines when we can count on it).
    function expectSync( elem, type ) {
        return ( elem === safeActiveElement() ) === ( type === "focus" );
    }

// Support: IE <=9 only
// Accessing document.activeElement can throw unexpectedly
// https://bugs.jquery.com/ticket/13393
    function safeActiveElement() {
        try {
            return document.activeElement;
        } catch ( err ) { }
    }

    function on( elem, types, selector, data, fn, one ) {
        var origFn, type;

        // Types can be a map of types/handlers
        if ( typeof types === "object" ) {

            // ( types-Object, selector, data )
            if ( typeof selector !== "string" ) {

                // ( types-Object, data )
                data = data || selector;
                selector = undefined;
            }
            for ( type in types ) {
                on( elem, type, selector, data, types[ type ], one );
            }
            return elem;
        }

        if ( data == null && fn == null ) {

            // ( types, fn )
            fn = selector;
            data = selector = undefined;
        } else if ( fn == null ) {
            if ( typeof selector === "string" ) {

                // ( types, selector, fn )
                fn = data;
                data = undefined;
            } else {

                // ( types, data, fn )
                fn = data;
                data = selector;
                selector = undefined;
            }
        }
        if ( fn === false ) {
            fn = returnFalse;
        } else if ( !fn ) {
            return elem;
        }

        if ( one === 1 ) {
            origFn = fn;
            fn = function( event ) {

                // Can use an empty set, since event contains the info
                jQuery().off( event );
                return origFn.apply( this, arguments );
            };

            // Use same guid so caller can remove using origFn
            fn.guid = origFn.guid || ( origFn.guid = jQuery.guid++ );
        }
        return elem.each( function() {
            jQuery.event.add( this, types, fn, data, selector );
        } );
    }

    /*
 * Helper functions for managing events -- not part of the public interface.
 * Props to Dean Edwards' addEvent library for many of the ideas.
 */
    jQuery.event = {

        global: {},

        add: function( elem, types, handler, data, selector ) {

            var handleObjIn, eventHandle, tmp,
                events, t, handleObj,
                special, handlers, type, namespaces, origType,
                elemData = dataPriv.get( elem );

            // Only attach events to objects that accept data
            if ( !acceptData( elem ) ) {
                return;
            }

            // Caller can pass in an object of custom data in lieu of the handler
            if ( handler.handler ) {
                handleObjIn = handler;
                handler = handleObjIn.handler;
                selector = handleObjIn.selector;
            }

            // Ensure that invalid selectors throw exceptions at attach time
            // Evaluate against documentElement in case elem is a non-element node (e.g., document)
            if ( selector ) {
                jQuery.find.matchesSelector( documentElement, selector );
            }

            // Make sure that the handler has a unique ID, used to find/remove it later
            if ( !handler.guid ) {
                handler.guid = jQuery.guid++;
            }

            // Init the element's event structure and main handler, if this is the first
            if ( !( events = elemData.events ) ) {
                events = elemData.events = Object.create( null );
            }
            if ( !( eventHandle = elemData.handle ) ) {
                eventHandle = elemData.handle = function( e ) {

                    // Discard the second event of a jQuery.event.trigger() and
                    // when an event is called after a page has unloaded
                    return typeof jQuery !== "undefined" && jQuery.event.triggered !== e.type ?
                        jQuery.event.dispatch.apply( elem, arguments ) : undefined;
                };
            }

            // Handle multiple events separated by a space
            types = ( types || "" ).match( rnothtmlwhite ) || [ "" ];
            t = types.length;
            while ( t-- ) {
                tmp = rtypenamespace.exec( types[ t ] ) || [];
                type = origType = tmp[ 1 ];
                namespaces = ( tmp[ 2 ] || "" ).split( "." ).sort();

                // There *must* be a type, no attaching namespace-only handlers
                if ( !type ) {
                    continue;
                }

                // If event changes its type, use the special event handlers for the changed type
                special = jQuery.event.special[ type ] || {};

                // If selector defined, determine special event api type, otherwise given type
                type = ( selector ? special.delegateType : special.bindType ) || type;

                // Update special based on newly reset type
                special = jQuery.event.special[ type ] || {};

                // handleObj is passed to all event handlers
                handleObj = jQuery.extend( {
                    type: type,
                    origType: origType,
                    data: data,
                    handler: handler,
                    guid: handler.guid,
                    selector: selector,
                    needsContext: selector && jQuery.expr.match.needsContext.test( selector ),
                    namespace: namespaces.join( "." )
                }, handleObjIn );

                // Init the event handler queue if we're the first
                if ( !( handlers = events[ type ] ) ) {
                    handlers = events[ type ] = [];
                    handlers.delegateCount = 0;

                    // Only use addEventListener if the special events handler returns false
                    if ( !special.setup ||
                        special.setup.call( elem, data, namespaces, eventHandle ) === false ) {

                        if ( elem.addEventListener ) {
                            elem.addEventListener( type, eventHandle );
                        }
                    }
                }

                if ( special.add ) {
                    special.add.call( elem, handleObj );

                    if ( !handleObj.handler.guid ) {
                        handleObj.handler.guid = handler.guid;
                    }
                }

                // Add to the element's handler list, delegates in front
                if ( selector ) {
                    handlers.splice( handlers.delegateCount++, 0, handleObj );
                } else {
                    handlers.push( handleObj );
                }

                // Keep track of which events have ever been used, for event optimization
                jQuery.event.global[ type ] = true;
            }

        },

        // Detach an event or set of events from an element
        remove: function( elem, types, handler, selector, mappedTypes ) {

            var j, origCount, tmp,
                events, t, handleObj,
                special, handlers, type, namespaces, origType,
                elemData = dataPriv.hasData( elem ) && dataPriv.get( elem );

            if ( !elemData || !( events = elemData.events ) ) {
                return;
            }

            // Once for each type.namespace in types; type may be omitted
            types = ( types || "" ).match( rnothtmlwhite ) || [ "" ];
            t = types.length;
            while ( t-- ) {
                tmp = rtypenamespace.exec( types[ t ] ) || [];
                type = origType = tmp[ 1 ];
                namespaces = ( tmp[ 2 ] || "" ).split( "." ).sort();

                // Unbind all events (on this namespace, if provided) for the element
                if ( !type ) {
                    for ( type in events ) {
                        jQuery.event.remove( elem, type + types[ t ], handler, selector, true );
                    }
                    continue;
                }

                special = jQuery.event.special[ type ] || {};
                type = ( selector ? special.delegateType : special.bindType ) || type;
                handlers = events[ type ] || [];
                tmp = tmp[ 2 ] &&
                    new RegExp( "(^|\\.)" + namespaces.join( "\\.(?:.*\\.|)" ) + "(\\.|$)" );

                // Remove matching events
                origCount = j = handlers.length;
                while ( j-- ) {
                    handleObj = handlers[ j ];

                    if ( ( mappedTypes || origType === handleObj.origType ) &&
                        ( !handler || handler.guid === handleObj.guid ) &&
                        ( !tmp || tmp.test( handleObj.namespace ) ) &&
                        ( !selector || selector === handleObj.selector ||
                            selector === "**" && handleObj.selector ) ) {
                        handlers.splice( j, 1 );

                        if ( handleObj.selector ) {
                            handlers.delegateCount--;
                        }
                        if ( special.remove ) {
                            special.remove.call( elem, handleObj );
                        }
                    }
                }

                // Remove generic event handler if we removed something and no more handlers exist
                // (avoids potential for endless recursion during removal of special event handlers)
                if ( origCount && !handlers.length ) {
                    if ( !special.teardown ||
                        special.teardown.call( elem, namespaces, elemData.handle ) === false ) {

                        jQuery.removeEvent( elem, type, elemData.handle );
                    }

                    delete events[ type ];
                }
            }

            // Remove data and the expando if it's no longer used
            if ( jQuery.isEmptyObject( events ) ) {
                dataPriv.remove( elem, "handle events" );
            }
        },

        dispatch: function( nativeEvent ) {

            var i, j, ret, matched, handleObj, handlerQueue,
                args = new Array( arguments.length ),

                // Make a writable jQuery.Event from the native event object
                event = jQuery.event.fix( nativeEvent ),

                handlers = (
                    dataPriv.get( this, "events" ) || Object.create( null )
                )[ event.type ] || [],
                special = jQuery.event.special[ event.type ] || {};

            // Use the fix-ed jQuery.Event rather than the (read-only) native event
            args[ 0 ] = event;

            for ( i = 1; i < arguments.length; i++ ) {
                args[ i ] = arguments[ i ];
            }

            event.delegateTarget = this;

            // Call the preDispatch hook for the mapped type, and let it bail if desired
            if ( special.preDispatch && special.preDispatch.call( this, event ) === false ) {
                return;
            }

            // Determine handlers
            handlerQueue = jQuery.event.handlers.call( this, event, handlers );

            // Run delegates first; they may want to stop propagation beneath us
            i = 0;
            while ( ( matched = handlerQueue[ i++ ] ) && !event.isPropagationStopped() ) {
                event.currentTarget = matched.elem;

                j = 0;
                while ( ( handleObj = matched.handlers[ j++ ] ) &&
                !event.isImmediatePropagationStopped() ) {

                    // If the event is namespaced, then each handler is only invoked if it is
                    // specially universal or its namespaces are a superset of the event's.
                    if ( !event.rnamespace || handleObj.namespace === false ||
                        event.rnamespace.test( handleObj.namespace ) ) {

                        event.handleObj = handleObj;
                        event.data = handleObj.data;

                        ret = ( ( jQuery.event.special[ handleObj.origType ] || {} ).handle ||
                            handleObj.handler ).apply( matched.elem, args );

                        if ( ret !== undefined ) {
                            if ( ( event.result = ret ) === false ) {
                                event.preventDefault();
                                event.stopPropagation();
                            }
                        }
                    }
                }
            }

            // Call the postDispatch hook for the mapped type
            if ( special.postDispatch ) {
                special.postDispatch.call( this, event );
            }

            return event.result;
        },

        handlers: function( event, handlers ) {
            var i, handleObj, sel, matchedHandlers, matchedSelectors,
                handlerQueue = [],
                delegateCount = handlers.delegateCount,
                cur = event.target;

            // Find delegate handlers
            if ( delegateCount &&

                // Support: IE <=9
                // Black-hole SVG <use> instance trees (trac-13180)
                cur.nodeType &&

                // Support: Firefox <=42
                // Suppress spec-violating clicks indicating a non-primary pointer button (trac-3861)
                // https://www.w3.org/TR/DOM-Level-3-Events/#event-type-click
                // Support: IE 11 only
                // ...but not arrow key "clicks" of radio inputs, which can have `button` -1 (gh-2343)
                !( event.type === "click" && event.button >= 1 ) ) {

                for ( ; cur !== this; cur = cur.parentNode || this ) {

                    // Don't check non-elements (#13208)
                    // Don't process clicks on disabled elements (#6911, #8165, #11382, #11764)
                    if ( cur.nodeType === 1 && !( event.type === "click" && cur.disabled === true ) ) {
                        matchedHandlers = [];
                        matchedSelectors = {};
                        for ( i = 0; i < delegateCount; i++ ) {
                            handleObj = handlers[ i ];

                            // Don't conflict with Object.prototype properties (#13203)
                            sel = handleObj.selector + " ";

                            if ( matchedSelectors[ sel ] === undefined ) {
                                matchedSelectors[ sel ] = handleObj.needsContext ?
                                    jQuery( sel, this ).index( cur ) > -1 :
                                    jQuery.find( sel, this, null, [ cur ] ).length;
                            }
                            if ( matchedSelectors[ sel ] ) {
                                matchedHandlers.push( handleObj );
                            }
                        }
                        if ( matchedHandlers.length ) {
                            handlerQueue.push( { elem: cur, handlers: matchedHandlers } );
                        }
                    }
                }
            }

            // Add the remaining (directly-bound) handlers
            cur = this;
            if ( delegateCount < handlers.length ) {
                handlerQueue.push( { elem: cur, handlers: handlers.slice( delegateCount ) } );
            }

            return handlerQueue;
        },

        addProp: function( name, hook ) {
            Object.defineProperty( jQuery.Event.prototype, name, {
                enumerable: true,
                configurable: true,

                get: isFunction( hook ) ?
                    function() {
                        if ( this.originalEvent ) {
                            return hook( this.originalEvent );
                        }
                    } :
                    function() {
                        if ( this.originalEvent ) {
                            return this.originalEvent[ name ];
                        }
                    },

                set: function( value ) {
                    Object.defineProperty( this, name, {
                        enumerable: true,
                        configurable: true,
                        writable: true,
                        value: value
                    } );
                }
            } );
        },

        fix: function( originalEvent ) {
            return originalEvent[ jQuery.expando ] ?
                originalEvent :
                new jQuery.Event( originalEvent );
        },

        special: {
            load: {

                // Prevent triggered image.load events from bubbling to window.load
                noBubble: true
            },
            click: {

                // Utilize native event to ensure correct state for checkable inputs
                setup: function( data ) {

                    // For mutual compressibility with _default, replace `this` access with a local var.
                    // `|| data` is dead code meant only to preserve the variable through minification.
                    var el = this || data;

                    // Claim the first handler
                    if ( rcheckableType.test( el.type ) &&
                        el.click && nodeName( el, "input" ) ) {

                        // dataPriv.set( el, "click", ... )
                        leverageNative( el, "click", returnTrue );
                    }

                    // Return false to allow normal processing in the caller
                    return false;
                },
                trigger: function( data ) {

                    // For mutual compressibility with _default, replace `this` access with a local var.
                    // `|| data` is dead code meant only to preserve the variable through minification.
                    var el = this || data;

                    // Force setup before triggering a click
                    if ( rcheckableType.test( el.type ) &&
                        el.click && nodeName( el, "input" ) ) {

                        leverageNative( el, "click" );
                    }

                    // Return non-false to allow normal event-path propagation
                    return true;
                },

                // For cross-browser consistency, suppress native .click() on links
                // Also prevent it if we're currently inside a leveraged native-event stack
                _default: function( event ) {
                    var target = event.target;
                    return rcheckableType.test( target.type ) &&
                        target.click && nodeName( target, "input" ) &&
                        dataPriv.get( target, "click" ) ||
                        nodeName( target, "a" );
                }
            },

            beforeunload: {
                postDispatch: function( event ) {

                    // Support: Firefox 20+
                    // Firefox doesn't alert if the returnValue field is not set.
                    if ( event.result !== undefined && event.originalEvent ) {
                        event.originalEvent.returnValue = event.result;
                    }
                }
            }
        }
    };

// Ensure the presence of an event listener that handles manually-triggered
// synthetic events by interrupting progress until reinvoked in response to
// *native* events that it fires directly, ensuring that state changes have
// already occurred before other listeners are invoked.
    function leverageNative( el, type, expectSync ) {

        // Missing expectSync indicates a trigger call, which must force setup through jQuery.event.add
        if ( !expectSync ) {
            if ( dataPriv.get( el, type ) === undefined ) {
                jQuery.event.add( el, type, returnTrue );
            }
            return;
        }

        // Register the controller as a special universal handler for all event namespaces
        dataPriv.set( el, type, false );
        jQuery.event.add( el, type, {
            namespace: false,
            handler: function( event ) {
                var notAsync, result,
                    saved = dataPriv.get( this, type );

                if ( ( event.isTrigger & 1 ) && this[ type ] ) {

                    // Interrupt processing of the outer synthetic .trigger()ed event
                    // Saved data should be false in such cases, but might be a leftover capture object
                    // from an async native handler (gh-4350)
                    if ( !saved.length ) {

                        // Store arguments for use when handling the inner native event
                        // There will always be at least one argument (an event object), so this array
                        // will not be confused with a leftover capture object.
                        saved = slice.call( arguments );
                        dataPriv.set( this, type, saved );

                        // Trigger the native event and capture its result
                        // Support: IE <=9 - 11+
                        // focus() and blur() are asynchronous
                        notAsync = expectSync( this, type );
                        this[ type ]();
                        result = dataPriv.get( this, type );
                        if ( saved !== result || notAsync ) {
                            dataPriv.set( this, type, false );
                        } else {
                            result = {};
                        }
                        if ( saved !== result ) {

                            // Cancel the outer synthetic event
                            event.stopImmediatePropagation();
                            event.preventDefault();

                            // Support: Chrome 86+
                            // In Chrome, if an element having a focusout handler is blurred by
                            // clicking outside of it, it invokes the handler synchronously. If
                            // that handler calls `.remove()` on the element, the data is cleared,
                            // leaving `result` undefined. We need to guard against this.
                            return result && result.value;
                        }

                        // If this is an inner synthetic event for an event with a bubbling surrogate
                        // (focus or blur), assume that the surrogate already propagated from triggering the
                        // native event and prevent that from happening again here.
                        // This technically gets the ordering wrong w.r.t. to `.trigger()` (in which the
                        // bubbling surrogate propagates *after* the non-bubbling base), but that seems
                        // less bad than duplication.
                    } else if ( ( jQuery.event.special[ type ] || {} ).delegateType ) {
                        event.stopPropagation();
                    }

                    // If this is a native event triggered above, everything is now in order
                    // Fire an inner synthetic event with the original arguments
                } else if ( saved.length ) {

                    // ...and capture the result
                    dataPriv.set( this, type, {
                        value: jQuery.event.trigger(

                            // Support: IE <=9 - 11+
                            // Extend with the prototype to reset the above stopImmediatePropagation()
                            jQuery.extend( saved[ 0 ], jQuery.Event.prototype ),
                            saved.slice( 1 ),
                            this
                        )
                    } );

                    // Abort handling of the native event
                    event.stopImmediatePropagation();
                }
            }
        } );
    }

    jQuery.removeEvent = function( elem, type, handle ) {

        // This "if" is needed for plain objects
        if ( elem.removeEventListener ) {
            elem.removeEventListener( type, handle );
        }
    };

    jQuery.Event = function( src, props ) {

        // Allow instantiation without the 'new' keyword
        if ( !( this instanceof jQuery.Event ) ) {
            return new jQuery.Event( src, props );
        }

        // Event object
        if ( src && src.type ) {
            this.originalEvent = src;
            this.type = src.type;

            // Events bubbling up the document may have been marked as prevented
            // by a handler lower down the tree; reflect the correct value.
            this.isDefaultPrevented = src.defaultPrevented ||
            src.defaultPrevented === undefined &&

            // Support: Android <=2.3 only
            src.returnValue === false ?
                returnTrue :
                returnFalse;

            // Create target properties
            // Support: Safari <=6 - 7 only
            // Target should not be a text node (#504, #13143)
            this.target = ( src.target && src.target.nodeType === 3 ) ?
                src.target.parentNode :
                src.target;

            this.currentTarget = src.currentTarget;
            this.relatedTarget = src.relatedTarget;

            // Event type
        } else {
            this.type = src;
        }

        // Put explicitly provided properties onto the event object
        if ( props ) {
            jQuery.extend( this, props );
        }

        // Create a timestamp if incoming event doesn't have one
        this.timeStamp = src && src.timeStamp || Date.now();

        // Mark it as fixed
        this[ jQuery.expando ] = true;
    };

// jQuery.Event is based on DOM3 Events as specified by the ECMAScript Language Binding
// https://www.w3.org/TR/2003/WD-DOM-Level-3-Events-20030331/ecma-script-binding.html
    jQuery.Event.prototype = {
        constructor: jQuery.Event,
        isDefaultPrevented: returnFalse,
        isPropagationStopped: returnFalse,
        isImmediatePropagationStopped: returnFalse,
        isSimulated: false,

        preventDefault: function() {
            var e = this.originalEvent;

            this.isDefaultPrevented = returnTrue;

            if ( e && !this.isSimulated ) {
                e.preventDefault();
            }
        },
        stopPropagation: function() {
            var e = this.originalEvent;

            this.isPropagationStopped = returnTrue;

            if ( e && !this.isSimulated ) {
                e.stopPropagation();
            }
        },
        stopImmediatePropagation: function() {
            var e = this.originalEvent;

            this.isImmediatePropagationStopped = returnTrue;

            if ( e && !this.isSimulated ) {
                e.stopImmediatePropagation();
            }

            this.stopPropagation();
        }
    };

// Includes all common event props including KeyEvent and MouseEvent specific props
    jQuery.each( {
        altKey: true,
        bubbles: true,
        cancelable: true,
        changedTouches: true,
        ctrlKey: true,
        detail: true,
        eventPhase: true,
        metaKey: true,
        pageX: true,
        pageY: true,
        shiftKey: true,
        view: true,
        "char": true,
        code: true,
        charCode: true,
        key: true,
        keyCode: true,
        button: true,
        buttons: true,
        clientX: true,
        clientY: true,
        offsetX: true,
        offsetY: true,
        pointerId: true,
        pointerType: true,
        screenX: true,
        screenY: true,
        targetTouches: true,
        toElement: true,
        touches: true,
        which: true
    }, jQuery.event.addProp );

    jQuery.each( { focus: "focusin", blur: "focusout" }, function( type, delegateType ) {
        jQuery.event.special[ type ] = {

            // Utilize native event if possible so blur/focus sequence is correct
            setup: function() {

                // Claim the first handler
                // dataPriv.set( this, "focus", ... )
                // dataPriv.set( this, "blur", ... )
                leverageNative( this, type, expectSync );

                // Return false to allow normal processing in the caller
                return false;
            },
            trigger: function() {

                // Force setup before trigger
                leverageNative( this, type );

                // Return non-false to allow normal event-path propagation
                return true;
            },

            // Suppress native focus or blur as it's already being fired
            // in leverageNative.
            _default: function() {
                return true;
            },

            delegateType: delegateType
        };
    } );

// Create mouseenter/leave events using mouseover/out and event-time checks
// so that event delegation works in jQuery.
// Do the same for pointerenter/pointerleave and pointerover/pointerout
//
// Support: Safari 7 only
// Safari sends mouseenter too often; see:
// https://bugs.chromium.org/p/chromium/issues/detail?id=470258
// for the description of the bug (it existed in older Chrome versions as well).
    jQuery.each( {
        mouseenter: "mouseover",
        mouseleave: "mouseout",
        pointerenter: "pointerover",
        pointerleave: "pointerout"
    }, function( orig, fix ) {
        jQuery.event.special[ orig ] = {
            delegateType: fix,
            bindType: fix,

            handle: function( event ) {
                var ret,
                    target = this,
                    related = event.relatedTarget,
                    handleObj = event.handleObj;

                // For mouseenter/leave call the handler if related is outside the target.
                // NB: No relatedTarget if the mouse left/entered the browser window
                if ( !related || ( related !== target && !jQuery.contains( target, related ) ) ) {
                    event.type = handleObj.origType;
                    ret = handleObj.handler.apply( this, arguments );
                    event.type = fix;
                }
                return ret;
            }
        };
    } );

    jQuery.fn.extend( {

        on: function( types, selector, data, fn ) {
            return on( this, types, selector, data, fn );
        },
        one: function( types, selector, data, fn ) {
            return on( this, types, selector, data, fn, 1 );
        },
        off: function( types, selector, fn ) {
            var handleObj, type;
            if ( types && types.preventDefault && types.handleObj ) {

                // ( event )  dispatched jQuery.Event
                handleObj = types.handleObj;
                jQuery( types.delegateTarget ).off(
                    handleObj.namespace ?
                        handleObj.origType + "." + handleObj.namespace :
                        handleObj.origType,
                    handleObj.selector,
                    handleObj.handler
                );
                return this;
            }
            if ( typeof types === "object" ) {

                // ( types-object [, selector] )
                for ( type in types ) {
                    this.off( type, selector, types[ type ] );
                }
                return this;
            }
            if ( selector === false || typeof selector === "function" ) {

                // ( types [, fn] )
                fn = selector;
                selector = undefined;
            }
            if ( fn === false ) {
                fn = returnFalse;
            }
            return this.each( function() {
                jQuery.event.remove( this, types, fn, selector );
            } );
        }
    } );


    var

        // Support: IE <=10 - 11, Edge 12 - 13 only
        // In IE/Edge using regex groups here causes severe slowdowns.
        // See https://connect.microsoft.com/IE/feedback/details/1736512/
        rnoInnerhtml = /<script|<style|<link/i,

        // checked="checked" or checked
        rchecked = /checked\s*(?:[^=]|=\s*.checked.)/i,
        rcleanScript = /^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g;

// Prefer a tbody over its parent table for containing new rows
    function manipulationTarget( elem, content ) {
        if ( nodeName( elem, "table" ) &&
            nodeName( content.nodeType !== 11 ? content : content.firstChild, "tr" ) ) {

            return jQuery( elem ).children( "tbody" )[ 0 ] || elem;
        }

        return elem;
    }

// Replace/restore the type attribute of script elements for safe DOM manipulation
    function disableScript( elem ) {
        elem.type = ( elem.getAttribute( "type" ) !== null ) + "/" + elem.type;
        return elem;
    }
    function restoreScript( elem ) {
        if ( ( elem.type || "" ).slice( 0, 5 ) === "true/" ) {
            elem.type = elem.type.slice( 5 );
        } else {
            elem.removeAttribute( "type" );
        }

        return elem;
    }

    function cloneCopyEvent( src, dest ) {
        var i, l, type, pdataOld, udataOld, udataCur, events;

        if ( dest.nodeType !== 1 ) {
            return;
        }

        // 1. Copy private data: events, handlers, etc.
        if ( dataPriv.hasData( src ) ) {
            pdataOld = dataPriv.get( src );
            events = pdataOld.events;

            if ( events ) {
                dataPriv.remove( dest, "handle events" );

                for ( type in events ) {
                    for ( i = 0, l = events[ type ].length; i < l; i++ ) {
                        jQuery.event.add( dest, type, events[ type ][ i ] );
                    }
                }
            }
        }

        // 2. Copy user data
        if ( dataUser.hasData( src ) ) {
            udataOld = dataUser.access( src );
            udataCur = jQuery.extend( {}, udataOld );

            dataUser.set( dest, udataCur );
        }
    }

// Fix IE bugs, see support tests
    function fixInput( src, dest ) {
        var nodeName = dest.nodeName.toLowerCase();

        // Fails to persist the checked state of a cloned checkbox or radio button.
        if ( nodeName === "input" && rcheckableType.test( src.type ) ) {
            dest.checked = src.checked;

            // Fails to return the selected option to the default selected state when cloning options
        } else if ( nodeName === "input" || nodeName === "textarea" ) {
            dest.defaultValue = src.defaultValue;
        }
    }

    function domManip( collection, args, callback, ignored ) {

        // Flatten any nested arrays
        args = flat( args );

        var fragment, first, scripts, hasScripts, node, doc,
            i = 0,
            l = collection.length,
            iNoClone = l - 1,
            value = args[ 0 ],
            valueIsFunction = isFunction( value );

        // We can't cloneNode fragments that contain checked, in WebKit
        if ( valueIsFunction ||
            ( l > 1 && typeof value === "string" &&
                !support.checkClone && rchecked.test( value ) ) ) {
            return collection.each( function( index ) {
                var self = collection.eq( index );
                if ( valueIsFunction ) {
                    args[ 0 ] = value.call( this, index, self.html() );
                }
                domManip( self, args, callback, ignored );
            } );
        }

        if ( l ) {
            fragment = buildFragment( args, collection[ 0 ].ownerDocument, false, collection, ignored );
            first = fragment.firstChild;

            if ( fragment.childNodes.length === 1 ) {
                fragment = first;
            }

            // Require either new content or an interest in ignored elements to invoke the callback
            if ( first || ignored ) {
                scripts = jQuery.map( getAll( fragment, "script" ), disableScript );
                hasScripts = scripts.length;

                // Use the original fragment for the last item
                // instead of the first because it can end up
                // being emptied incorrectly in certain situations (#8070).
                for ( ; i < l; i++ ) {
                    node = fragment;

                    if ( i !== iNoClone ) {
                        node = jQuery.clone( node, true, true );

                        // Keep references to cloned scripts for later restoration
                        if ( hasScripts ) {

                            // Support: Android <=4.0 only, PhantomJS 1 only
                            // push.apply(_, arraylike) throws on ancient WebKit
                            jQuery.merge( scripts, getAll( node, "script" ) );
                        }
                    }

                    callback.call( collection[ i ], node, i );
                }

                if ( hasScripts ) {
                    doc = scripts[ scripts.length - 1 ].ownerDocument;

                    // Reenable scripts
                    jQuery.map( scripts, restoreScript );

                    // Evaluate executable scripts on first document insertion
                    for ( i = 0; i < hasScripts; i++ ) {
                        node = scripts[ i ];
                        if ( rscriptType.test( node.type || "" ) &&
                            !dataPriv.access( node, "globalEval" ) &&
                            jQuery.contains( doc, node ) ) {

                            if ( node.src && ( node.type || "" ).toLowerCase()  !== "module" ) {

                                // Optional AJAX dependency, but won't run scripts if not present
                                if ( jQuery._evalUrl && !node.noModule ) {
                                    jQuery._evalUrl( node.src, {
                                        nonce: node.nonce || node.getAttribute( "nonce" )
                                    }, doc );
                                }
                            } else {
                                DOMEval( node.textContent.replace( rcleanScript, "" ), node, doc );
                            }
                        }
                    }
                }
            }
        }

        return collection;
    }

    function remove( elem, selector, keepData ) {
        var node,
            nodes = selector ? jQuery.filter( selector, elem ) : elem,
            i = 0;

        for ( ; ( node = nodes[ i ] ) != null; i++ ) {
            if ( !keepData && node.nodeType === 1 ) {
                jQuery.cleanData( getAll( node ) );
            }

            if ( node.parentNode ) {
                if ( keepData && isAttached( node ) ) {
                    setGlobalEval( getAll( node, "script" ) );
                }
                node.parentNode.removeChild( node );
            }
        }

        return elem;
    }

    jQuery.extend( {
        htmlPrefilter: function( html ) {
            return html;
        },

        clone: function( elem, dataAndEvents, deepDataAndEvents ) {
            var i, l, srcElements, destElements,
                clone = elem.cloneNode( true ),
                inPage = isAttached( elem );

            // Fix IE cloning issues
            if ( !support.noCloneChecked && ( elem.nodeType === 1 || elem.nodeType === 11 ) &&
                !jQuery.isXMLDoc( elem ) ) {

                // We eschew Sizzle here for performance reasons: https://jsperf.com/getall-vs-sizzle/2
                destElements = getAll( clone );
                srcElements = getAll( elem );

                for ( i = 0, l = srcElements.length; i < l; i++ ) {
                    fixInput( srcElements[ i ], destElements[ i ] );
                }
            }

            // Copy the events from the original to the clone
            if ( dataAndEvents ) {
                if ( deepDataAndEvents ) {
                    srcElements = srcElements || getAll( elem );
                    destElements = destElements || getAll( clone );

                    for ( i = 0, l = srcElements.length; i < l; i++ ) {
                        cloneCopyEvent( srcElements[ i ], destElements[ i ] );
                    }
                } else {
                    cloneCopyEvent( elem, clone );
                }
            }

            // Preserve script evaluation history
            destElements = getAll( clone, "script" );
            if ( destElements.length > 0 ) {
                setGlobalEval( destElements, !inPage && getAll( elem, "script" ) );
            }

            // Return the cloned set
            return clone;
        },

        cleanData: function( elems ) {
            var data, elem, type,
                special = jQuery.event.special,
                i = 0;

            for ( ; ( elem = elems[ i ] ) !== undefined; i++ ) {
                if ( acceptData( elem ) ) {
                    if ( ( data = elem[ dataPriv.expando ] ) ) {
                        if ( data.events ) {
                            for ( type in data.events ) {
                                if ( special[ type ] ) {
                                    jQuery.event.remove( elem, type );

                                    // This is a shortcut to avoid jQuery.event.remove's overhead
                                } else {
                                    jQuery.removeEvent( elem, type, data.handle );
                                }
                            }
                        }

                        // Support: Chrome <=35 - 45+
                        // Assign undefined instead of using delete, see Data#remove
                        elem[ dataPriv.expando ] = undefined;
                    }
                    if ( elem[ dataUser.expando ] ) {

                        // Support: Chrome <=35 - 45+
                        // Assign undefined instead of using delete, see Data#remove
                        elem[ dataUser.expando ] = undefined;
                    }
                }
            }
        }
    } );

    jQuery.fn.extend( {
        detach: function( selector ) {
            return remove( this, selector, true );
        },

        remove: function( selector ) {
            return remove( this, selector );
        },

        text: function( value ) {
            return access( this, function( value ) {
                return value === undefined ?
                    jQuery.text( this ) :
                    this.empty().each( function() {
                        if ( this.nodeType === 1 || this.nodeType === 11 || this.nodeType === 9 ) {
                            this.textContent = value;
                        }
                    } );
            }, null, value, arguments.length );
        },

        append: function() {
            return domManip( this, arguments, function( elem ) {
                if ( this.nodeType === 1 || this.nodeType === 11 || this.nodeType === 9 ) {
                    var target = manipulationTarget( this, elem );
                    target.appendChild( elem );
                }
            } );
        },

        prepend: function() {
            return domManip( this, arguments, function( elem ) {
                if ( this.nodeType === 1 || this.nodeType === 11 || this.nodeType === 9 ) {
                    var target = manipulationTarget( this, elem );
                    target.insertBefore( elem, target.firstChild );
                }
            } );
        },

        before: function() {
            return domManip( this, arguments, function( elem ) {
                if ( this.parentNode ) {
                    this.parentNode.insertBefore( elem, this );
                }
            } );
        },

        after: function() {
            return domManip( this, arguments, function( elem ) {
                if ( this.parentNode ) {
                    this.parentNode.insertBefore( elem, this.nextSibling );
                }
            } );
        },

        empty: function() {
            var elem,
                i = 0;

            for ( ; ( elem = this[ i ] ) != null; i++ ) {
                if ( elem.nodeType === 1 ) {

                    // Prevent memory leaks
                    jQuery.cleanData( getAll( elem, false ) );

                    // Remove any remaining nodes
                    elem.textContent = "";
                }
            }

            return this;
        },

        clone: function( dataAndEvents, deepDataAndEvents ) {
            dataAndEvents = dataAndEvents == null ? false : dataAndEvents;
            deepDataAndEvents = deepDataAndEvents == null ? dataAndEvents : deepDataAndEvents;

            return this.map( function() {
                return jQuery.clone( this, dataAndEvents, deepDataAndEvents );
            } );
        },

        html: function( value ) {
            return access( this, function( value ) {
                var elem = this[ 0 ] || {},
                    i = 0,
                    l = this.length;

                if ( value === undefined && elem.nodeType === 1 ) {
                    return elem.innerHTML;
                }

                // See if we can take a shortcut and just use innerHTML
                if ( typeof value === "string" && !rnoInnerhtml.test( value ) &&
                    !wrapMap[ ( rtagName.exec( value ) || [ "", "" ] )[ 1 ].toLowerCase() ] ) {

                    value = jQuery.htmlPrefilter( value );

                    try {
                        for ( ; i < l; i++ ) {
                            elem = this[ i ] || {};

                            // Remove element nodes and prevent memory leaks
                            if ( elem.nodeType === 1 ) {
                                jQuery.cleanData( getAll( elem, false ) );
                                elem.innerHTML = value;
                            }
                        }

                        elem = 0;

                        // If using innerHTML throws an exception, use the fallback method
                    } catch ( e ) {}
                }

                if ( elem ) {
                    this.empty().append( value );
                }
            }, null, value, arguments.length );
        },

        replaceWith: function() {
            var ignored = [];

            // Make the changes, replacing each non-ignored context element with the new content
            return domManip( this, arguments, function( elem ) {
                var parent = this.parentNode;

                if ( jQuery.inArray( this, ignored ) < 0 ) {
                    jQuery.cleanData( getAll( this ) );
                    if ( parent ) {
                        parent.replaceChild( elem, this );
                    }
                }

                // Force callback invocation
            }, ignored );
        }
    } );

    jQuery.each( {
        appendTo: "append",
        prependTo: "prepend",
        insertBefore: "before",
        insertAfter: "after",
        replaceAll: "replaceWith"
    }, function( name, original ) {
        jQuery.fn[ name ] = function( selector ) {
            var elems,
                ret = [],
                insert = jQuery( selector ),
                last = insert.length - 1,
                i = 0;

            for ( ; i <= last; i++ ) {
                elems = i === last ? this : this.clone( true );
                jQuery( insert[ i ] )[ original ]( elems );

                // Support: Android <=4.0 only, PhantomJS 1 only
                // .get() because push.apply(_, arraylike) throws on ancient WebKit
                push.apply( ret, elems.get() );
            }

            return this.pushStack( ret );
        };
    } );
    var rnumnonpx = new RegExp( "^(" + pnum + ")(?!px)[a-z%]+$", "i" );

    var getStyles = function( elem ) {

        // Support: IE <=11 only, Firefox <=30 (#15098, #14150)
        // IE throws on elements created in popups
        // FF meanwhile throws on frame elements through "defaultView.getComputedStyle"
        var view = elem.ownerDocument.defaultView;

        if ( !view || !view.opener ) {
            view = window;
        }

        return view.getComputedStyle( elem );
    };

    var swap = function( elem, options, callback ) {
        var ret, name,
            old = {};

        // Remember the old values, and insert the new ones
        for ( name in options ) {
            old[ name ] = elem.style[ name ];
            elem.style[ name ] = options[ name ];
        }

        ret = callback.call( elem );

        // Revert the old values
        for ( name in options ) {
            elem.style[ name ] = old[ name ];
        }

        return ret;
    };


    var rboxStyle = new RegExp( cssExpand.join( "|" ), "i" );



    ( function() {

        // Executing both pixelPosition & boxSizingReliable tests require only one layout
        // so they're executed at the same time to save the second computation.
        function computeStyleTests() {

            // This is a singleton, we need to execute it only once
            if ( !div ) {
                return;
            }

            container.style.cssText = "position:absolute;left:-11111px;width:60px;" +
                "margin-top:1px;padding:0;border:0";
            div.style.cssText =
                "position:relative;display:block;box-sizing:border-box;overflow:scroll;" +
                "margin:auto;border:1px;padding:1px;" +
                "width:60%;top:1%";
            documentElement.appendChild( container ).appendChild( div );

            var divStyle = window.getComputedStyle( div );
            pixelPositionVal = divStyle.top !== "1%";

            // Support: Android 4.0 - 4.3 only, Firefox <=3 - 44
            reliableMarginLeftVal = roundPixelMeasures( divStyle.marginLeft ) === 12;

            // Support: Android 4.0 - 4.3 only, Safari <=9.1 - 10.1, iOS <=7.0 - 9.3
            // Some styles come back with percentage values, even though they shouldn't
            div.style.right = "60%";
            pixelBoxStylesVal = roundPixelMeasures( divStyle.right ) === 36;

            // Support: IE 9 - 11 only
            // Detect misreporting of content dimensions for box-sizing:border-box elements
            boxSizingReliableVal = roundPixelMeasures( divStyle.width ) === 36;

            // Support: IE 9 only
            // Detect overflow:scroll screwiness (gh-3699)
            // Support: Chrome <=64
            // Don't get tricked when zoom affects offsetWidth (gh-4029)
            div.style.position = "absolute";
            scrollboxSizeVal = roundPixelMeasures( div.offsetWidth / 3 ) === 12;

            documentElement.removeChild( container );

            // Nullify the div so it wouldn't be stored in the memory and
            // it will also be a sign that checks already performed
            div = null;
        }

        function roundPixelMeasures( measure ) {
            return Math.round( parseFloat( measure ) );
        }

        var pixelPositionVal, boxSizingReliableVal, scrollboxSizeVal, pixelBoxStylesVal,
            reliableTrDimensionsVal, reliableMarginLeftVal,
            container = document.createElement( "div" ),
            div = document.createElement( "div" );

        // Finish early in limited (non-browser) environments
        if ( !div.style ) {
            return;
        }

        // Support: IE <=9 - 11 only
        // Style of cloned element affects source element cloned (#8908)
        div.style.backgroundClip = "content-box";
        div.cloneNode( true ).style.backgroundClip = "";
        support.clearCloneStyle = div.style.backgroundClip === "content-box";

        jQuery.extend( support, {
            boxSizingReliable: function() {
                computeStyleTests();
                return boxSizingReliableVal;
            },
            pixelBoxStyles: function() {
                computeStyleTests();
                return pixelBoxStylesVal;
            },
            pixelPosition: function() {
                computeStyleTests();
                return pixelPositionVal;
            },
            reliableMarginLeft: function() {
                computeStyleTests();
                return reliableMarginLeftVal;
            },
            scrollboxSize: function() {
                computeStyleTests();
                return scrollboxSizeVal;
            },

            // Support: IE 9 - 11+, Edge 15 - 18+
            // IE/Edge misreport `getComputedStyle` of table rows with width/height
            // set in CSS while `offset*` properties report correct values.
            // Behavior in IE 9 is more subtle than in newer versions & it passes
            // some versions of this test; make sure not to make it pass there!
            //
            // Support: Firefox 70+
            // Only Firefox includes border widths
            // in computed dimensions. (gh-4529)
            reliableTrDimensions: function() {
                var table, tr, trChild, trStyle;
                if ( reliableTrDimensionsVal == null ) {
                    table = document.createElement( "table" );
                    tr = document.createElement( "tr" );
                    trChild = document.createElement( "div" );

                    table.style.cssText = "position:absolute;left:-11111px;border-collapse:separate";
                    tr.style.cssText = "border:1px solid";

                    // Support: Chrome 86+
                    // Height set through cssText does not get applied.
                    // Computed height then comes back as 0.
                    tr.style.height = "1px";
                    trChild.style.height = "9px";

                    // Support: Android 8 Chrome 86+
                    // In our bodyBackground.html iframe,
                    // display for all div elements is set to "inline",
                    // which causes a problem only in Android 8 Chrome 86.
                    // Ensuring the div is display: block
                    // gets around this issue.
                    trChild.style.display = "block";

                    documentElement
                        .appendChild( table )
                        .appendChild( tr )
                        .appendChild( trChild );

                    trStyle = window.getComputedStyle( tr );
                    reliableTrDimensionsVal = ( parseInt( trStyle.height, 10 ) +
                        parseInt( trStyle.borderTopWidth, 10 ) +
                        parseInt( trStyle.borderBottomWidth, 10 ) ) === tr.offsetHeight;

                    documentElement.removeChild( table );
                }
                return reliableTrDimensionsVal;
            }
        } );
    } )();


    function curCSS( elem, name, computed ) {
        var width, minWidth, maxWidth, ret,

            // Support: Firefox 51+
            // Retrieving style before computed somehow
            // fixes an issue with getting wrong values
            // on detached elements
            style = elem.style;

        computed = computed || getStyles( elem );

        // getPropertyValue is needed for:
        //   .css('filter') (IE 9 only, #12537)
        //   .css('--customProperty) (#3144)
        if ( computed ) {
            ret = computed.getPropertyValue( name ) || computed[ name ];

            if ( ret === "" && !isAttached( elem ) ) {
                ret = jQuery.style( elem, name );
            }

            // A tribute to the "awesome hack by Dean Edwards"
            // Android Browser returns percentage for some values,
            // but width seems to be reliably pixels.
            // This is against the CSSOM draft spec:
            // https://drafts.csswg.org/cssom/#resolved-values
            if ( !support.pixelBoxStyles() && rnumnonpx.test( ret ) && rboxStyle.test( name ) ) {

                // Remember the original values
                width = style.width;
                minWidth = style.minWidth;
                maxWidth = style.maxWidth;

                // Put in the new values to get a computed value out
                style.minWidth = style.maxWidth = style.width = ret;
                ret = computed.width;

                // Revert the changed values
                style.width = width;
                style.minWidth = minWidth;
                style.maxWidth = maxWidth;
            }
        }

        return ret !== undefined ?

            // Support: IE <=9 - 11 only
            // IE returns zIndex value as an integer.
            ret + "" :
            ret;
    }


    function addGetHookIf( conditionFn, hookFn ) {

        // Define the hook, we'll check on the first run if it's really needed.
        return {
            get: function() {
                if ( conditionFn() ) {

                    // Hook not needed (or it's not possible to use it due
                    // to missing dependency), remove it.
                    delete this.get;
                    return;
                }

                // Hook needed; redefine it so that the support test is not executed again.
                return ( this.get = hookFn ).apply( this, arguments );
            }
        };
    }


    var cssPrefixes = [ "Webkit", "Moz", "ms" ],
        emptyStyle = document.createElement( "div" ).style,
        vendorProps = {};

// Return a vendor-prefixed property or undefined
    function vendorPropName( name ) {

        // Check for vendor prefixed names
        var capName = name[ 0 ].toUpperCase() + name.slice( 1 ),
            i = cssPrefixes.length;

        while ( i-- ) {
            name = cssPrefixes[ i ] + capName;
            if ( name in emptyStyle ) {
                return name;
            }
        }
    }

// Return a potentially-mapped jQuery.cssProps or vendor prefixed property
    function finalPropName( name ) {
        var final = jQuery.cssProps[ name ] || vendorProps[ name ];

        if ( final ) {
            return final;
        }
        if ( name in emptyStyle ) {
            return name;
        }
        return vendorProps[ name ] = vendorPropName( name ) || name;
    }


    var

        // Swappable if display is none or starts with table
        // except "table", "table-cell", or "table-caption"
        // See here for display values: https://developer.mozilla.org/en-US/docs/CSS/display
        rdisplayswap = /^(none|table(?!-c[ea]).+)/,
        rcustomProp = /^--/,
        cssShow = { position: "absolute", visibility: "hidden", display: "block" },
        cssNormalTransform = {
            letterSpacing: "0",
            fontWeight: "400"
        };

    function setPositiveNumber( _elem, value, subtract ) {

        // Any relative (+/-) values have already been
        // normalized at this point
        var matches = rcssNum.exec( value );
        return matches ?

            // Guard against undefined "subtract", e.g., when used as in cssHooks
            Math.max( 0, matches[ 2 ] - ( subtract || 0 ) ) + ( matches[ 3 ] || "px" ) :
            value;
    }

    function boxModelAdjustment( elem, dimension, box, isBorderBox, styles, computedVal ) {
        var i = dimension === "width" ? 1 : 0,
            extra = 0,
            delta = 0;

        // Adjustment may not be necessary
        if ( box === ( isBorderBox ? "border" : "content" ) ) {
            return 0;
        }

        for ( ; i < 4; i += 2 ) {

            // Both box models exclude margin
            if ( box === "margin" ) {
                delta += jQuery.css( elem, box + cssExpand[ i ], true, styles );
            }

            // If we get here with a content-box, we're seeking "padding" or "border" or "margin"
            if ( !isBorderBox ) {

                // Add padding
                delta += jQuery.css( elem, "padding" + cssExpand[ i ], true, styles );

                // For "border" or "margin", add border
                if ( box !== "padding" ) {
                    delta += jQuery.css( elem, "border" + cssExpand[ i ] + "Width", true, styles );

                    // But still keep track of it otherwise
                } else {
                    extra += jQuery.css( elem, "border" + cssExpand[ i ] + "Width", true, styles );
                }

                // If we get here with a border-box (content + padding + border), we're seeking "content" or
                // "padding" or "margin"
            } else {

                // For "content", subtract padding
                if ( box === "content" ) {
                    delta -= jQuery.css( elem, "padding" + cssExpand[ i ], true, styles );
                }

                // For "content" or "padding", subtract border
                if ( box !== "margin" ) {
                    delta -= jQuery.css( elem, "border" + cssExpand[ i ] + "Width", true, styles );
                }
            }
        }

        // Account for positive content-box scroll gutter when requested by providing computedVal
        if ( !isBorderBox && computedVal >= 0 ) {

            // offsetWidth/offsetHeight is a rounded sum of content, padding, scroll gutter, and border
            // Assuming integer scroll gutter, subtract the rest and round down
            delta += Math.max( 0, Math.ceil(
                elem[ "offset" + dimension[ 0 ].toUpperCase() + dimension.slice( 1 ) ] -
                computedVal -
                delta -
                extra -
                0.5

                // If offsetWidth/offsetHeight is unknown, then we can't determine content-box scroll gutter
                // Use an explicit zero to avoid NaN (gh-3964)
            ) ) || 0;
        }

        return delta;
    }

    function getWidthOrHeight( elem, dimension, extra ) {

        // Start with computed style
        var styles = getStyles( elem ),

            // To avoid forcing a reflow, only fetch boxSizing if we need it (gh-4322).
            // Fake content-box until we know it's needed to know the true value.
            boxSizingNeeded = !support.boxSizingReliable() || extra,
            isBorderBox = boxSizingNeeded &&
                jQuery.css( elem, "boxSizing", false, styles ) === "border-box",
            valueIsBorderBox = isBorderBox,

            val = curCSS( elem, dimension, styles ),
            offsetProp = "offset" + dimension[ 0 ].toUpperCase() + dimension.slice( 1 );

        // Support: Firefox <=54
        // Return a confounding non-pixel value or feign ignorance, as appropriate.
        if ( rnumnonpx.test( val ) ) {
            if ( !extra ) {
                return val;
            }
            val = "auto";
        }


        // Support: IE 9 - 11 only
        // Use offsetWidth/offsetHeight for when box sizing is unreliable.
        // In those cases, the computed value can be trusted to be border-box.
        if ( ( !support.boxSizingReliable() && isBorderBox ||

            // Support: IE 10 - 11+, Edge 15 - 18+
            // IE/Edge misreport `getComputedStyle` of table rows with width/height
            // set in CSS while `offset*` properties report correct values.
            // Interestingly, in some cases IE 9 doesn't suffer from this issue.
            !support.reliableTrDimensions() && nodeName( elem, "tr" ) ||

            // Fall back to offsetWidth/offsetHeight when value is "auto"
            // This happens for inline elements with no explicit setting (gh-3571)
            val === "auto" ||

            // Support: Android <=4.1 - 4.3 only
            // Also use offsetWidth/offsetHeight for misreported inline dimensions (gh-3602)
            !parseFloat( val ) && jQuery.css( elem, "display", false, styles ) === "inline" ) &&

            // Make sure the element is visible & connected
            elem.getClientRects().length ) {

            isBorderBox = jQuery.css( elem, "boxSizing", false, styles ) === "border-box";

            // Where available, offsetWidth/offsetHeight approximate border box dimensions.
            // Where not available (e.g., SVG), assume unreliable box-sizing and interpret the
            // retrieved value as a content box dimension.
            valueIsBorderBox = offsetProp in elem;
            if ( valueIsBorderBox ) {
                val = elem[ offsetProp ];
            }
        }

        // Normalize "" and auto
        val = parseFloat( val ) || 0;

        // Adjust for the element's box model
        return ( val +
            boxModelAdjustment(
                elem,
                dimension,
                extra || ( isBorderBox ? "border" : "content" ),
                valueIsBorderBox,
                styles,

                // Provide the current computed size to request scroll gutter calculation (gh-3589)
                val
            )
        ) + "px";
    }

    jQuery.extend( {

        // Add in style property hooks for overriding the default
        // behavior of getting and setting a style property
        cssHooks: {
            opacity: {
                get: function( elem, computed ) {
                    if ( computed ) {

                        // We should always get a number back from opacity
                        var ret = curCSS( elem, "opacity" );
                        return ret === "" ? "1" : ret;
                    }
                }
            }
        },

        // Don't automatically add "px" to these possibly-unitless properties
        cssNumber: {
            "animationIterationCount": true,
            "columnCount": true,
            "fillOpacity": true,
            "flexGrow": true,
            "flexShrink": true,
            "fontWeight": true,
            "gridArea": true,
            "gridColumn": true,
            "gridColumnEnd": true,
            "gridColumnStart": true,
            "gridRow": true,
            "gridRowEnd": true,
            "gridRowStart": true,
            "lineHeight": true,
            "opacity": true,
            "order": true,
            "orphans": true,
            "widows": true,
            "zIndex": true,
            "zoom": true
        },

        // Add in properties whose names you wish to fix before
        // setting or getting the value
        cssProps: {},

        // Get and set the style property on a DOM Node
        style: function( elem, name, value, extra ) {

            // Don't set styles on text and comment nodes
            if ( !elem || elem.nodeType === 3 || elem.nodeType === 8 || !elem.style ) {
                return;
            }

            // Make sure that we're working with the right name
            var ret, type, hooks,
                origName = camelCase( name ),
                isCustomProp = rcustomProp.test( name ),
                style = elem.style;

            // Make sure that we're working with the right name. We don't
            // want to query the value if it is a CSS custom property
            // since they are user-defined.
            if ( !isCustomProp ) {
                name = finalPropName( origName );
            }

            // Gets hook for the prefixed version, then unprefixed version
            hooks = jQuery.cssHooks[ name ] || jQuery.cssHooks[ origName ];

            // Check if we're setting a value
            if ( value !== undefined ) {
                type = typeof value;

                // Convert "+=" or "-=" to relative numbers (#7345)
                if ( type === "string" && ( ret = rcssNum.exec( value ) ) && ret[ 1 ] ) {
                    value = adjustCSS( elem, name, ret );

                    // Fixes bug #9237
                    type = "number";
                }

                // Make sure that null and NaN values aren't set (#7116)
                if ( value == null || value !== value ) {
                    return;
                }

                // If a number was passed in, add the unit (except for certain CSS properties)
                // The isCustomProp check can be removed in jQuery 4.0 when we only auto-append
                // "px" to a few hardcoded values.
                if ( type === "number" && !isCustomProp ) {
                    value += ret && ret[ 3 ] || ( jQuery.cssNumber[ origName ] ? "" : "px" );
                }

                // background-* props affect original clone's values
                if ( !support.clearCloneStyle && value === "" && name.indexOf( "background" ) === 0 ) {
                    style[ name ] = "inherit";
                }

                // If a hook was provided, use that value, otherwise just set the specified value
                if ( !hooks || !( "set" in hooks ) ||
                    ( value = hooks.set( elem, value, extra ) ) !== undefined ) {

                    if ( isCustomProp ) {
                        style.setProperty( name, value );
                    } else {
                        style[ name ] = value;
                    }
                }

            } else {

                // If a hook was provided get the non-computed value from there
                if ( hooks && "get" in hooks &&
                    ( ret = hooks.get( elem, false, extra ) ) !== undefined ) {

                    return ret;
                }

                // Otherwise just get the value from the style object
                return style[ name ];
            }
        },

        css: function( elem, name, extra, styles ) {
            var val, num, hooks,
                origName = camelCase( name ),
                isCustomProp = rcustomProp.test( name );

            // Make sure that we're working with the right name. We don't
            // want to modify the value if it is a CSS custom property
            // since they are user-defined.
            if ( !isCustomProp ) {
                name = finalPropName( origName );
            }

            // Try prefixed name followed by the unprefixed name
            hooks = jQuery.cssHooks[ name ] || jQuery.cssHooks[ origName ];

            // If a hook was provided get the computed value from there
            if ( hooks && "get" in hooks ) {
                val = hooks.get( elem, true, extra );
            }

            // Otherwise, if a way to get the computed value exists, use that
            if ( val === undefined ) {
                val = curCSS( elem, name, styles );
            }

            // Convert "normal" to computed value
            if ( val === "normal" && name in cssNormalTransform ) {
                val = cssNormalTransform[ name ];
            }

            // Make numeric if forced or a qualifier was provided and val looks numeric
            if ( extra === "" || extra ) {
                num = parseFloat( val );
                return extra === true || isFinite( num ) ? num || 0 : val;
            }

            return val;
        }
    } );

    jQuery.each( [ "height", "width" ], function( _i, dimension ) {
        jQuery.cssHooks[ dimension ] = {
            get: function( elem, computed, extra ) {
                if ( computed ) {

                    // Certain elements can have dimension info if we invisibly show them
                    // but it must have a current display style that would benefit
                    return rdisplayswap.test( jQuery.css( elem, "display" ) ) &&

                    // Support: Safari 8+
                    // Table columns in Safari have non-zero offsetWidth & zero
                    // getBoundingClientRect().width unless display is changed.
                    // Support: IE <=11 only
                    // Running getBoundingClientRect on a disconnected node
                    // in IE throws an error.
                    ( !elem.getClientRects().length || !elem.getBoundingClientRect().width ) ?
                        swap( elem, cssShow, function() {
                            return getWidthOrHeight( elem, dimension, extra );
                        } ) :
                        getWidthOrHeight( elem, dimension, extra );
                }
            },

            set: function( elem, value, extra ) {
                var matches,
                    styles = getStyles( elem ),

                    // Only read styles.position if the test has a chance to fail
                    // to avoid forcing a reflow.
                    scrollboxSizeBuggy = !support.scrollboxSize() &&
                        styles.position === "absolute",

                    // To avoid forcing a reflow, only fetch boxSizing if we need it (gh-3991)
                    boxSizingNeeded = scrollboxSizeBuggy || extra,
                    isBorderBox = boxSizingNeeded &&
                        jQuery.css( elem, "boxSizing", false, styles ) === "border-box",
                    subtract = extra ?
                        boxModelAdjustment(
                            elem,
                            dimension,
                            extra,
                            isBorderBox,
                            styles
                        ) :
                        0;

                // Account for unreliable border-box dimensions by comparing offset* to computed and
                // faking a content-box to get border and padding (gh-3699)
                if ( isBorderBox && scrollboxSizeBuggy ) {
                    subtract -= Math.ceil(
                        elem[ "offset" + dimension[ 0 ].toUpperCase() + dimension.slice( 1 ) ] -
                        parseFloat( styles[ dimension ] ) -
                        boxModelAdjustment( elem, dimension, "border", false, styles ) -
                        0.5
                    );
                }

                // Convert to pixels if value adjustment is needed
                if ( subtract && ( matches = rcssNum.exec( value ) ) &&
                    ( matches[ 3 ] || "px" ) !== "px" ) {

                    elem.style[ dimension ] = value;
                    value = jQuery.css( elem, dimension );
                }

                return setPositiveNumber( elem, value, subtract );
            }
        };
    } );

    jQuery.cssHooks.marginLeft = addGetHookIf( support.reliableMarginLeft,
        function( elem, computed ) {
            if ( computed ) {
                return ( parseFloat( curCSS( elem, "marginLeft" ) ) ||
                    elem.getBoundingClientRect().left -
                    swap( elem, { marginLeft: 0 }, function() {
                        return elem.getBoundingClientRect().left;
                    } )
                ) + "px";
            }
        }
    );

// These hooks are used by animate to expand properties
    jQuery.each( {
        margin: "",
        padding: "",
        border: "Width"
    }, function( prefix, suffix ) {
        jQuery.cssHooks[ prefix + suffix ] = {
            expand: function( value ) {
                var i = 0,
                    expanded = {},

                    // Assumes a single number if not a string
                    parts = typeof value === "string" ? value.split( " " ) : [ value ];

                for ( ; i < 4; i++ ) {
                    expanded[ prefix + cssExpand[ i ] + suffix ] =
                        parts[ i ] || parts[ i - 2 ] || parts[ 0 ];
                }

                return expanded;
            }
        };

        if ( prefix !== "margin" ) {
            jQuery.cssHooks[ prefix + suffix ].set = setPositiveNumber;
        }
    } );

    jQuery.fn.extend( {
        css: function( name, value ) {
            return access( this, function( elem, name, value ) {
                var styles, len,
                    map = {},
                    i = 0;

                if ( Array.isArray( name ) ) {
                    styles = getStyles( elem );
                    len = name.length;

                    for ( ; i < len; i++ ) {
                        map[ name[ i ] ] = jQuery.css( elem, name[ i ], false, styles );
                    }

                    return map;
                }

                return value !== undefined ?
                    jQuery.style( elem, name, value ) :
                    jQuery.css( elem, name );
            }, name, value, arguments.length > 1 );
        }
    } );


    function Tween( elem, options, prop, end, easing ) {
        return new Tween.prototype.init( elem, options, prop, end, easing );
    }
    jQuery.Tween = Tween;

    Tween.prototype = {
        constructor: Tween,
        init: function( elem, options, prop, end, easing, unit ) {
            this.elem = elem;
            this.prop = prop;
            this.easing = easing || jQuery.easing._default;
            this.options = options;
            this.start = this.now = this.cur();
            this.end = end;
            this.unit = unit || ( jQuery.cssNumber[ prop ] ? "" : "px" );
        },
        cur: function() {
            var hooks = Tween.propHooks[ this.prop ];

            return hooks && hooks.get ?
                hooks.get( this ) :
                Tween.propHooks._default.get( this );
        },
        run: function( percent ) {
            var eased,
                hooks = Tween.propHooks[ this.prop ];

            if ( this.options.duration ) {
                this.pos = eased = jQuery.easing[ this.easing ](
                    percent, this.options.duration * percent, 0, 1, this.options.duration
                );
            } else {
                this.pos = eased = percent;
            }
            this.now = ( this.end - this.start ) * eased + this.start;

            if ( this.options.step ) {
                this.options.step.call( this.elem, this.now, this );
            }

            if ( hooks && hooks.set ) {
                hooks.set( this );
            } else {
                Tween.propHooks._default.set( this );
            }
            return this;
        }
    };

    Tween.prototype.init.prototype = Tween.prototype;

    Tween.propHooks = {
        _default: {
            get: function( tween ) {
                var result;

                // Use a property on the element directly when it is not a DOM element,
                // or when there is no matching style property that exists.
                if ( tween.elem.nodeType !== 1 ||
                    tween.elem[ tween.prop ] != null && tween.elem.style[ tween.prop ] == null ) {
                    return tween.elem[ tween.prop ];
                }

                // Passing an empty string as a 3rd parameter to .css will automatically
                // attempt a parseFloat and fallback to a string if the parse fails.
                // Simple values such as "10px" are parsed to Float;
                // complex values such as "rotate(1rad)" are returned as-is.
                result = jQuery.css( tween.elem, tween.prop, "" );

                // Empty strings, null, undefined and "auto" are converted to 0.
                return !result || result === "auto" ? 0 : result;
            },
            set: function( tween ) {

                // Use step hook for back compat.
                // Use cssHook if its there.
                // Use .style if available and use plain properties where available.
                if ( jQuery.fx.step[ tween.prop ] ) {
                    jQuery.fx.step[ tween.prop ]( tween );
                } else if ( tween.elem.nodeType === 1 && (
                    jQuery.cssHooks[ tween.prop ] ||
                    tween.elem.style[ finalPropName( tween.prop ) ] != null ) ) {
                    jQuery.style( tween.elem, tween.prop, tween.now + tween.unit );
                } else {
                    tween.elem[ tween.prop ] = tween.now;
                }
            }
        }
    };

// Support: IE <=9 only
// Panic based approach to setting things on disconnected nodes
    Tween.propHooks.scrollTop = Tween.propHooks.scrollLeft = {
        set: function( tween ) {
            if ( tween.elem.nodeType && tween.elem.parentNode ) {
                tween.elem[ tween.prop ] = tween.now;
            }
        }
    };

    jQuery.easing = {
        linear: function( p ) {
            return p;
        },
        swing: function( p ) {
            return 0.5 - Math.cos( p * Math.PI ) / 2;
        },
        _default: "swing"
    };

    jQuery.fx = Tween.prototype.init;

// Back compat <1.8 extension point
    jQuery.fx.step = {};




    var
        fxNow, inProgress,
        rfxtypes = /^(?:toggle|show|hide)$/,
        rrun = /queueHooks$/;

    function schedule() {
        if ( inProgress ) {
            if ( document.hidden === false && window.requestAnimationFrame ) {
                window.requestAnimationFrame( schedule );
            } else {
                window.setTimeout( schedule, jQuery.fx.interval );
            }

            jQuery.fx.tick();
        }
    }

// Animations created synchronously will run synchronously
    function createFxNow() {
        window.setTimeout( function() {
            fxNow = undefined;
        } );
        return ( fxNow = Date.now() );
    }

// Generate parameters to create a standard animation
    function genFx( type, includeWidth ) {
        var which,
            i = 0,
            attrs = { height: type };

        // If we include width, step value is 1 to do all cssExpand values,
        // otherwise step value is 2 to skip over Left and Right
        includeWidth = includeWidth ? 1 : 0;
        for ( ; i < 4; i += 2 - includeWidth ) {
            which = cssExpand[ i ];
            attrs[ "margin" + which ] = attrs[ "padding" + which ] = type;
        }

        if ( includeWidth ) {
            attrs.opacity = attrs.width = type;
        }

        return attrs;
    }

    function createTween( value, prop, animation ) {
        var tween,
            collection = ( Animation.tweeners[ prop ] || [] ).concat( Animation.tweeners[ "*" ] ),
            index = 0,
            length = collection.length;
        for ( ; index < length; index++ ) {
            if ( ( tween = collection[ index ].call( animation, prop, value ) ) ) {

                // We're done with this property
                return tween;
            }
        }
    }

    function defaultPrefilter( elem, props, opts ) {
        var prop, value, toggle, hooks, oldfire, propTween, restoreDisplay, display,
            isBox = "width" in props || "height" in props,
            anim = this,
            orig = {},
            style = elem.style,
            hidden = elem.nodeType && isHiddenWithinTree( elem ),
            dataShow = dataPriv.get( elem, "fxshow" );

        // Queue-skipping animations hijack the fx hooks
        if ( !opts.queue ) {
            hooks = jQuery._queueHooks( elem, "fx" );
            if ( hooks.unqueued == null ) {
                hooks.unqueued = 0;
                oldfire = hooks.empty.fire;
                hooks.empty.fire = function() {
                    if ( !hooks.unqueued ) {
                        oldfire();
                    }
                };
            }
            hooks.unqueued++;

            anim.always( function() {

                // Ensure the complete handler is called before this completes
                anim.always( function() {
                    hooks.unqueued--;
                    if ( !jQuery.queue( elem, "fx" ).length ) {
                        hooks.empty.fire();
                    }
                } );
            } );
        }

        // Detect show/hide animations
        for ( prop in props ) {
            value = props[ prop ];
            if ( rfxtypes.test( value ) ) {
                delete props[ prop ];
                toggle = toggle || value === "toggle";
                if ( value === ( hidden ? "hide" : "show" ) ) {

                    // Pretend to be hidden if this is a "show" and
                    // there is still data from a stopped show/hide
                    if ( value === "show" && dataShow && dataShow[ prop ] !== undefined ) {
                        hidden = true;

                        // Ignore all other no-op show/hide data
                    } else {
                        continue;
                    }
                }
                orig[ prop ] = dataShow && dataShow[ prop ] || jQuery.style( elem, prop );
            }
        }

        // Bail out if this is a no-op like .hide().hide()
        propTween = !jQuery.isEmptyObject( props );
        if ( !propTween && jQuery.isEmptyObject( orig ) ) {
            return;
        }

        // Restrict "overflow" and "display" styles during box animations
        if ( isBox && elem.nodeType === 1 ) {

            // Support: IE <=9 - 11, Edge 12 - 15
            // Record all 3 overflow attributes because IE does not infer the shorthand
            // from identically-valued overflowX and overflowY and Edge just mirrors
            // the overflowX value there.
            opts.overflow = [ style.overflow, style.overflowX, style.overflowY ];

            // Identify a display type, preferring old show/hide data over the CSS cascade
            restoreDisplay = dataShow && dataShow.display;
            if ( restoreDisplay == null ) {
                restoreDisplay = dataPriv.get( elem, "display" );
            }
            display = jQuery.css( elem, "display" );
            if ( display === "none" ) {
                if ( restoreDisplay ) {
                    display = restoreDisplay;
                } else {

                    // Get nonempty value(s) by temporarily forcing visibility
                    showHide( [ elem ], true );
                    restoreDisplay = elem.style.display || restoreDisplay;
                    display = jQuery.css( elem, "display" );
                    showHide( [ elem ] );
                }
            }

            // Animate inline elements as inline-block
            if ( display === "inline" || display === "inline-block" && restoreDisplay != null ) {
                if ( jQuery.css( elem, "float" ) === "none" ) {

                    // Restore the original display value at the end of pure show/hide animations
                    if ( !propTween ) {
                        anim.done( function() {
                            style.display = restoreDisplay;
                        } );
                        if ( restoreDisplay == null ) {
                            display = style.display;
                            restoreDisplay = display === "none" ? "" : display;
                        }
                    }
                    style.display = "inline-block";
                }
            }
        }

        if ( opts.overflow ) {
            style.overflow = "hidden";
            anim.always( function() {
                style.overflow = opts.overflow[ 0 ];
                style.overflowX = opts.overflow[ 1 ];
                style.overflowY = opts.overflow[ 2 ];
            } );
        }

        // Implement show/hide animations
        propTween = false;
        for ( prop in orig ) {

            // General show/hide setup for this element animation
            if ( !propTween ) {
                if ( dataShow ) {
                    if ( "hidden" in dataShow ) {
                        hidden = dataShow.hidden;
                    }
                } else {
                    dataShow = dataPriv.access( elem, "fxshow", { display: restoreDisplay } );
                }

                // Store hidden/visible for toggle so `.stop().toggle()` "reverses"
                if ( toggle ) {
                    dataShow.hidden = !hidden;
                }

                // Show elements before animating them
                if ( hidden ) {
                    showHide( [ elem ], true );
                }

                /* eslint-disable no-loop-func */

                anim.done( function() {

                    /* eslint-enable no-loop-func */

                    // The final step of a "hide" animation is actually hiding the element
                    if ( !hidden ) {
                        showHide( [ elem ] );
                    }
                    dataPriv.remove( elem, "fxshow" );
                    for ( prop in orig ) {
                        jQuery.style( elem, prop, orig[ prop ] );
                    }
                } );
            }

            // Per-property setup
            propTween = createTween( hidden ? dataShow[ prop ] : 0, prop, anim );
            if ( !( prop in dataShow ) ) {
                dataShow[ prop ] = propTween.start;
                if ( hidden ) {
                    propTween.end = propTween.start;
                    propTween.start = 0;
                }
            }
        }
    }

    function propFilter( props, specialEasing ) {
        var index, name, easing, value, hooks;

        // camelCase, specialEasing and expand cssHook pass
        for ( index in props ) {
            name = camelCase( index );
            easing = specialEasing[ name ];
            value = props[ index ];
            if ( Array.isArray( value ) ) {
                easing = value[ 1 ];
                value = props[ index ] = value[ 0 ];
            }

            if ( index !== name ) {
                props[ name ] = value;
                delete props[ index ];
            }

            hooks = jQuery.cssHooks[ name ];
            if ( hooks && "expand" in hooks ) {
                value = hooks.expand( value );
                delete props[ name ];

                // Not quite $.extend, this won't overwrite existing keys.
                // Reusing 'index' because we have the correct "name"
                for ( index in value ) {
                    if ( !( index in props ) ) {
                        props[ index ] = value[ index ];
                        specialEasing[ index ] = easing;
                    }
                }
            } else {
                specialEasing[ name ] = easing;
            }
        }
    }

    function Animation( elem, properties, options ) {
        var result,
            stopped,
            index = 0,
            length = Animation.prefilters.length,
            deferred = jQuery.Deferred().always( function() {

                // Don't match elem in the :animated selector
                delete tick.elem;
            } ),
            tick = function() {
                if ( stopped ) {
                    return false;
                }
                var currentTime = fxNow || createFxNow(),
                    remaining = Math.max( 0, animation.startTime + animation.duration - currentTime ),

                    // Support: Android 2.3 only
                    // Archaic crash bug won't allow us to use `1 - ( 0.5 || 0 )` (#12497)
                    temp = remaining / animation.duration || 0,
                    percent = 1 - temp,
                    index = 0,
                    length = animation.tweens.length;

                for ( ; index < length; index++ ) {
                    animation.tweens[ index ].run( percent );
                }

                deferred.notifyWith( elem, [ animation, percent, remaining ] );

                // If there's more to do, yield
                if ( percent < 1 && length ) {
                    return remaining;
                }

                // If this was an empty animation, synthesize a final progress notification
                if ( !length ) {
                    deferred.notifyWith( elem, [ animation, 1, 0 ] );
                }

                // Resolve the animation and report its conclusion
                deferred.resolveWith( elem, [ animation ] );
                return false;
            },
            animation = deferred.promise( {
                elem: elem,
                props: jQuery.extend( {}, properties ),
                opts: jQuery.extend( true, {
                    specialEasing: {},
                    easing: jQuery.easing._default
                }, options ),
                originalProperties: properties,
                originalOptions: options,
                startTime: fxNow || createFxNow(),
                duration: options.duration,
                tweens: [],
                createTween: function( prop, end ) {
                    var tween = jQuery.Tween( elem, animation.opts, prop, end,
                        animation.opts.specialEasing[ prop ] || animation.opts.easing );
                    animation.tweens.push( tween );
                    return tween;
                },
                stop: function( gotoEnd ) {
                    var index = 0,

                        // If we are going to the end, we want to run all the tweens
                        // otherwise we skip this part
                        length = gotoEnd ? animation.tweens.length : 0;
                    if ( stopped ) {
                        return this;
                    }
                    stopped = true;
                    for ( ; index < length; index++ ) {
                        animation.tweens[ index ].run( 1 );
                    }

                    // Resolve when we played the last frame; otherwise, reject
                    if ( gotoEnd ) {
                        deferred.notifyWith( elem, [ animation, 1, 0 ] );
                        deferred.resolveWith( elem, [ animation, gotoEnd ] );
                    } else {
                        deferred.rejectWith( elem, [ animation, gotoEnd ] );
                    }
                    return this;
                }
            } ),
            props = animation.props;

        propFilter( props, animation.opts.specialEasing );

        for ( ; index < length; index++ ) {
            result = Animation.prefilters[ index ].call( animation, elem, props, animation.opts );
            if ( result ) {
                if ( isFunction( result.stop ) ) {
                    jQuery._queueHooks( animation.elem, animation.opts.queue ).stop =
                        result.stop.bind( result );
                }
                return result;
            }
        }

        jQuery.map( props, createTween, animation );

        if ( isFunction( animation.opts.start ) ) {
            animation.opts.start.call( elem, animation );
        }

        // Attach callbacks from options
        animation
            .progress( animation.opts.progress )
            .done( animation.opts.done, animation.opts.complete )
            .fail( animation.opts.fail )
            .always( animation.opts.always );

        jQuery.fx.timer(
            jQuery.extend( tick, {
                elem: elem,
                anim: animation,
                queue: animation.opts.queue
            } )
        );

        return animation;
    }

    jQuery.Animation = jQuery.extend( Animation, {

        tweeners: {
            "*": [ function( prop, value ) {
                var tween = this.createTween( prop, value );
                adjustCSS( tween.elem, prop, rcssNum.exec( value ), tween );
                return tween;
            } ]
        },

        tweener: function( props, callback ) {
            if ( isFunction( props ) ) {
                callback = props;
                props = [ "*" ];
            } else {
                props = props.match( rnothtmlwhite );
            }

            var prop,
                index = 0,
                length = props.length;

            for ( ; index < length; index++ ) {
                prop = props[ index ];
                Animation.tweeners[ prop ] = Animation.tweeners[ prop ] || [];
                Animation.tweeners[ prop ].unshift( callback );
            }
        },

        prefilters: [ defaultPrefilter ],

        prefilter: function( callback, prepend ) {
            if ( prepend ) {
                Animation.prefilters.unshift( callback );
            } else {
                Animation.prefilters.push( callback );
            }
        }
    } );

    jQuery.speed = function( speed, easing, fn ) {
        var opt = speed && typeof speed === "object" ? jQuery.extend( {}, speed ) : {
            complete: fn || !fn && easing ||
                isFunction( speed ) && speed,
            duration: speed,
            easing: fn && easing || easing && !isFunction( easing ) && easing
        };

        // Go to the end state if fx are off
        if ( jQuery.fx.off ) {
            opt.duration = 0;

        } else {
            if ( typeof opt.duration !== "number" ) {
                if ( opt.duration in jQuery.fx.speeds ) {
                    opt.duration = jQuery.fx.speeds[ opt.duration ];

                } else {
                    opt.duration = jQuery.fx.speeds._default;
                }
            }
        }

        // Normalize opt.queue - true/undefined/null -> "fx"
        if ( opt.queue == null || opt.queue === true ) {
            opt.queue = "fx";
        }

        // Queueing
        opt.old = opt.complete;

        opt.complete = function() {
            if ( isFunction( opt.old ) ) {
                opt.old.call( this );
            }

            if ( opt.queue ) {
                jQuery.dequeue( this, opt.queue );
            }
        };

        return opt;
    };

    jQuery.fn.extend( {
        fadeTo: function( speed, to, easing, callback ) {

            // Show any hidden elements after setting opacity to 0
            return this.filter( isHiddenWithinTree ).css( "opacity", 0 ).show()

            // Animate to the value specified
                .end().animate( { opacity: to }, speed, easing, callback );
        },
        animate: function( prop, speed, easing, callback ) {
            var empty = jQuery.isEmptyObject( prop ),
                optall = jQuery.speed( speed, easing, callback ),
                doAnimation = function() {

                    // Operate on a copy of prop so per-property easing won't be lost
                    var anim = Animation( this, jQuery.extend( {}, prop ), optall );

                    // Empty animations, or finishing resolves immediately
                    if ( empty || dataPriv.get( this, "finish" ) ) {
                        anim.stop( true );
                    }
                };

            doAnimation.finish = doAnimation;

            return empty || optall.queue === false ?
                this.each( doAnimation ) :
                this.queue( optall.queue, doAnimation );
        },
        stop: function( type, clearQueue, gotoEnd ) {
            var stopQueue = function( hooks ) {
                var stop = hooks.stop;
                delete hooks.stop;
                stop( gotoEnd );
            };

            if ( typeof type !== "string" ) {
                gotoEnd = clearQueue;
                clearQueue = type;
                type = undefined;
            }
            if ( clearQueue ) {
                this.queue( type || "fx", [] );
            }

            return this.each( function() {
                var dequeue = true,
                    index = type != null && type + "queueHooks",
                    timers = jQuery.timers,
                    data = dataPriv.get( this );

                if ( index ) {
                    if ( data[ index ] && data[ index ].stop ) {
                        stopQueue( data[ index ] );
                    }
                } else {
                    for ( index in data ) {
                        if ( data[ index ] && data[ index ].stop && rrun.test( index ) ) {
                            stopQueue( data[ index ] );
                        }
                    }
                }

                for ( index = timers.length; index--; ) {
                    if ( timers[ index ].elem === this &&
                        ( type == null || timers[ index ].queue === type ) ) {

                        timers[ index ].anim.stop( gotoEnd );
                        dequeue = false;
                        timers.splice( index, 1 );
                    }
                }

                // Start the next in the queue if the last step wasn't forced.
                // Timers currently will call their complete callbacks, which
                // will dequeue but only if they were gotoEnd.
                if ( dequeue || !gotoEnd ) {
                    jQuery.dequeue( this, type );
                }
            } );
        },
        finish: function( type ) {
            if ( type !== false ) {
                type = type || "fx";
            }
            return this.each( function() {
                var index,
                    data = dataPriv.get( this ),
                    queue = data[ type + "queue" ],
                    hooks = data[ type + "queueHooks" ],
                    timers = jQuery.timers,
                    length = queue ? queue.length : 0;

                // Enable finishing flag on private data
                data.finish = true;

                // Empty the queue first
                jQuery.queue( this, type, [] );

                if ( hooks && hooks.stop ) {
                    hooks.stop.call( this, true );
                }

                // Look for any active animations, and finish them
                for ( index = timers.length; index--; ) {
                    if ( timers[ index ].elem === this && timers[ index ].queue === type ) {
                        timers[ index ].anim.stop( true );
                        timers.splice( index, 1 );
                    }
                }

                // Look for any animations in the old queue and finish them
                for ( index = 0; index < length; index++ ) {
                    if ( queue[ index ] && queue[ index ].finish ) {
                        queue[ index ].finish.call( this );
                    }
                }

                // Turn off finishing flag
                delete data.finish;
            } );
        }
    } );

    jQuery.each( [ "toggle", "show", "hide" ], function( _i, name ) {
        var cssFn = jQuery.fn[ name ];
        jQuery.fn[ name ] = function( speed, easing, callback ) {
            return speed == null || typeof speed === "boolean" ?
                cssFn.apply( this, arguments ) :
                this.animate( genFx( name, true ), speed, easing, callback );
        };
    } );

// Generate shortcuts for custom animations
    jQuery.each( {
        slideDown: genFx( "show" ),
        slideUp: genFx( "hide" ),
        slideToggle: genFx( "toggle" ),
        fadeIn: { opacity: "show" },
        fadeOut: { opacity: "hide" },
        fadeToggle: { opacity: "toggle" }
    }, function( name, props ) {
        jQuery.fn[ name ] = function( speed, easing, callback ) {
            return this.animate( props, speed, easing, callback );
        };
    } );

    jQuery.timers = [];
    jQuery.fx.tick = function() {
        var timer,
            i = 0,
            timers = jQuery.timers;

        fxNow = Date.now();

        for ( ; i < timers.length; i++ ) {
            timer = timers[ i ];

            // Run the timer and safely remove it when done (allowing for external removal)
            if ( !timer() && timers[ i ] === timer ) {
                timers.splice( i--, 1 );
            }
        }

        if ( !timers.length ) {
            jQuery.fx.stop();
        }
        fxNow = undefined;
    };

    jQuery.fx.timer = function( timer ) {
        jQuery.timers.push( timer );
        jQuery.fx.start();
    };

    jQuery.fx.interval = 13;
    jQuery.fx.start = function() {
        if ( inProgress ) {
            return;
        }

        inProgress = true;
        schedule();
    };

    jQuery.fx.stop = function() {
        inProgress = null;
    };

    jQuery.fx.speeds = {
        slow: 600,
        fast: 200,

        // Default speed
        _default: 400
    };


// Based off of the plugin by Clint Helfers, with permission.
// https://web.archive.org/web/20100324014747/http://blindsignals.com/index.php/2009/07/jquery-delay/
    jQuery.fn.delay = function( time, type ) {
        time = jQuery.fx ? jQuery.fx.speeds[ time ] || time : time;
        type = type || "fx";

        return this.queue( type, function( next, hooks ) {
            var timeout = window.setTimeout( next, time );
            hooks.stop = function() {
                window.clearTimeout( timeout );
            };
        } );
    };


    ( function() {
        var input = document.createElement( "input" ),
            select = document.createElement( "select" ),
            opt = select.appendChild( document.createElement( "option" ) );

        input.type = "checkbox";

        // Support: Android <=4.3 only
        // Default value for a checkbox should be "on"
        support.checkOn = input.value !== "";

        // Support: IE <=11 only
        // Must access selectedIndex to make default options select
        support.optSelected = opt.selected;

        // Support: IE <=11 only
        // An input loses its value after becoming a radio
        input = document.createElement( "input" );
        input.value = "t";
        input.type = "radio";
        support.radioValue = input.value === "t";
    } )();


    var boolHook,
        attrHandle = jQuery.expr.attrHandle;

    jQuery.fn.extend( {
        attr: function( name, value ) {
            return access( this, jQuery.attr, name, value, arguments.length > 1 );
        },

        removeAttr: function( name ) {
            return this.each( function() {
                jQuery.removeAttr( this, name );
            } );
        }
    } );

    jQuery.extend( {
        attr: function( elem, name, value ) {
            var ret, hooks,
                nType = elem.nodeType;

            // Don't get/set attributes on text, comment and attribute nodes
            if ( nType === 3 || nType === 8 || nType === 2 ) {
                return;
            }

            // Fallback to prop when attributes are not supported
            if ( typeof elem.getAttribute === "undefined" ) {
                return jQuery.prop( elem, name, value );
            }

            // Attribute hooks are determined by the lowercase version
            // Grab necessary hook if one is defined
            if ( nType !== 1 || !jQuery.isXMLDoc( elem ) ) {
                hooks = jQuery.attrHooks[ name.toLowerCase() ] ||
                    ( jQuery.expr.match.bool.test( name ) ? boolHook : undefined );
            }

            if ( value !== undefined ) {
                if ( value === null ) {
                    jQuery.removeAttr( elem, name );
                    return;
                }

                if ( hooks && "set" in hooks &&
                    ( ret = hooks.set( elem, value, name ) ) !== undefined ) {
                    return ret;
                }

                elem.setAttribute( name, value + "" );
                return value;
            }

            if ( hooks && "get" in hooks && ( ret = hooks.get( elem, name ) ) !== null ) {
                return ret;
            }

            ret = jQuery.find.attr( elem, name );

            // Non-existent attributes return null, we normalize to undefined
            return ret == null ? undefined : ret;
        },

        attrHooks: {
            type: {
                set: function( elem, value ) {
                    if ( !support.radioValue && value === "radio" &&
                        nodeName( elem, "input" ) ) {
                        var val = elem.value;
                        elem.setAttribute( "type", value );
                        if ( val ) {
                            elem.value = val;
                        }
                        return value;
                    }
                }
            }
        },

        removeAttr: function( elem, value ) {
            var name,
                i = 0,

                // Attribute names can contain non-HTML whitespace characters
                // https://html.spec.whatwg.org/multipage/syntax.html#attributes-2
                attrNames = value && value.match( rnothtmlwhite );

            if ( attrNames && elem.nodeType === 1 ) {
                while ( ( name = attrNames[ i++ ] ) ) {
                    elem.removeAttribute( name );
                }
            }
        }
    } );

// Hooks for boolean attributes
    boolHook = {
        set: function( elem, value, name ) {
            if ( value === false ) {

                // Remove boolean attributes when set to false
                jQuery.removeAttr( elem, name );
            } else {
                elem.setAttribute( name, name );
            }
            return name;
        }
    };

    jQuery.each( jQuery.expr.match.bool.source.match( /\w+/g ), function( _i, name ) {
        var getter = attrHandle[ name ] || jQuery.find.attr;

        attrHandle[ name ] = function( elem, name, isXML ) {
            var ret, handle,
                lowercaseName = name.toLowerCase();

            if ( !isXML ) {

                // Avoid an infinite loop by temporarily removing this function from the getter
                handle = attrHandle[ lowercaseName ];
                attrHandle[ lowercaseName ] = ret;
                ret = getter( elem, name, isXML ) != null ?
                    lowercaseName :
                    null;
                attrHandle[ lowercaseName ] = handle;
            }
            return ret;
        };
    } );




    var rfocusable = /^(?:input|select|textarea|button)$/i,
        rclickable = /^(?:a|area)$/i;

    jQuery.fn.extend( {
        prop: function( name, value ) {
            return access( this, jQuery.prop, name, value, arguments.length > 1 );
        },

        removeProp: function( name ) {
            return this.each( function() {
                delete this[ jQuery.propFix[ name ] || name ];
            } );
        }
    } );

    jQuery.extend( {
        prop: function( elem, name, value ) {
            var ret, hooks,
                nType = elem.nodeType;

            // Don't get/set properties on text, comment and attribute nodes
            if ( nType === 3 || nType === 8 || nType === 2 ) {
                return;
            }

            if ( nType !== 1 || !jQuery.isXMLDoc( elem ) ) {

                // Fix name and attach hooks
                name = jQuery.propFix[ name ] || name;
                hooks = jQuery.propHooks[ name ];
            }

            if ( value !== undefined ) {
                if ( hooks && "set" in hooks &&
                    ( ret = hooks.set( elem, value, name ) ) !== undefined ) {
                    return ret;
                }

                return ( elem[ name ] = value );
            }

            if ( hooks && "get" in hooks && ( ret = hooks.get( elem, name ) ) !== null ) {
                return ret;
            }

            return elem[ name ];
        },

        propHooks: {
            tabIndex: {
                get: function( elem ) {

                    // Support: IE <=9 - 11 only
                    // elem.tabIndex doesn't always return the
                    // correct value when it hasn't been explicitly set
                    // https://web.archive.org/web/20141116233347/http://fluidproject.org/blog/2008/01/09/getting-setting-and-removing-tabindex-values-with-javascript/
                    // Use proper attribute retrieval(#12072)
                    var tabindex = jQuery.find.attr( elem, "tabindex" );

                    if ( tabindex ) {
                        return parseInt( tabindex, 10 );
                    }

                    if (
                        rfocusable.test( elem.nodeName ) ||
                        rclickable.test( elem.nodeName ) &&
                        elem.href
                    ) {
                        return 0;
                    }

                    return -1;
                }
            }
        },

        propFix: {
            "for": "htmlFor",
            "class": "className"
        }
    } );

// Support: IE <=11 only
// Accessing the selectedIndex property
// forces the browser to respect setting selected
// on the option
// The getter ensures a default option is selected
// when in an optgroup
// eslint rule "no-unused-expressions" is disabled for this code
// since it considers such accessions noop
    if ( !support.optSelected ) {
        jQuery.propHooks.selected = {
            get: function( elem ) {

                /* eslint no-unused-expressions: "off" */

                var parent = elem.parentNode;
                if ( parent && parent.parentNode ) {
                    parent.parentNode.selectedIndex;
                }
                return null;
            },
            set: function( elem ) {

                /* eslint no-unused-expressions: "off" */

                var parent = elem.parentNode;
                if ( parent ) {
                    parent.selectedIndex;

                    if ( parent.parentNode ) {
                        parent.parentNode.selectedIndex;
                    }
                }
            }
        };
    }

    jQuery.each( [
        "tabIndex",
        "readOnly",
        "maxLength",
        "cellSpacing",
        "cellPadding",
        "rowSpan",
        "colSpan",
        "useMap",
        "frameBorder",
        "contentEditable"
    ], function() {
        jQuery.propFix[ this.toLowerCase() ] = this;
    } );




    // Strip and collapse whitespace according to HTML spec
    // https://infra.spec.whatwg.org/#strip-and-collapse-ascii-whitespace
    function stripAndCollapse( value ) {
        var tokens = value.match( rnothtmlwhite ) || [];
        return tokens.join( " " );
    }


    function getClass( elem ) {
        return elem.getAttribute && elem.getAttribute( "class" ) || "";
    }

    function classesToArray( value ) {
        if ( Array.isArray( value ) ) {
            return value;
        }
        if ( typeof value === "string" ) {
            return value.match( rnothtmlwhite ) || [];
        }
        return [];
    }

    jQuery.fn.extend( {
        addClass: function( value ) {
            var classes, elem, cur, curValue, clazz, j, finalValue,
                i = 0;

            if ( isFunction( value ) ) {
                return this.each( function( j ) {
                    jQuery( this ).addClass( value.call( this, j, getClass( this ) ) );
                } );
            }

            classes = classesToArray( value );

            if ( classes.length ) {
                while ( ( elem = this[ i++ ] ) ) {
                    curValue = getClass( elem );
                    cur = elem.nodeType === 1 && ( " " + stripAndCollapse( curValue ) + " " );

                    if ( cur ) {
                        j = 0;
                        while ( ( clazz = classes[ j++ ] ) ) {
                            if ( cur.indexOf( " " + clazz + " " ) < 0 ) {
                                cur += clazz + " ";
                            }
                        }

                        // Only assign if different to avoid unneeded rendering.
                        finalValue = stripAndCollapse( cur );
                        if ( curValue !== finalValue ) {
                            elem.setAttribute( "class", finalValue );
                        }
                    }
                }
            }

            return this;
        },

        removeClass: function( value ) {
            var classes, elem, cur, curValue, clazz, j, finalValue,
                i = 0;

            if ( isFunction( value ) ) {
                return this.each( function( j ) {
                    jQuery( this ).removeClass( value.call( this, j, getClass( this ) ) );
                } );
            }

            if ( !arguments.length ) {
                return this.attr( "class", "" );
            }

            classes = classesToArray( value );

            if ( classes.length ) {
                while ( ( elem = this[ i++ ] ) ) {
                    curValue = getClass( elem );

                    // This expression is here for better compressibility (see addClass)
                    cur = elem.nodeType === 1 && ( " " + stripAndCollapse( curValue ) + " " );

                    if ( cur ) {
                        j = 0;
                        while ( ( clazz = classes[ j++ ] ) ) {

                            // Remove *all* instances
                            while ( cur.indexOf( " " + clazz + " " ) > -1 ) {
                                cur = cur.replace( " " + clazz + " ", " " );
                            }
                        }

                        // Only assign if different to avoid unneeded rendering.
                        finalValue = stripAndCollapse( cur );
                        if ( curValue !== finalValue ) {
                            elem.setAttribute( "class", finalValue );
                        }
                    }
                }
            }

            return this;
        },

        toggleClass: function( value, stateVal ) {
            var type = typeof value,
                isValidValue = type === "string" || Array.isArray( value );

            if ( typeof stateVal === "boolean" && isValidValue ) {
                return stateVal ? this.addClass( value ) : this.removeClass( value );
            }

            if ( isFunction( value ) ) {
                return this.each( function( i ) {
                    jQuery( this ).toggleClass(
                        value.call( this, i, getClass( this ), stateVal ),
                        stateVal
                    );
                } );
            }

            return this.each( function() {
                var className, i, self, classNames;

                if ( isValidValue ) {

                    // Toggle individual class names
                    i = 0;
                    self = jQuery( this );
                    classNames = classesToArray( value );

                    while ( ( className = classNames[ i++ ] ) ) {

                        // Check each className given, space separated list
                        if ( self.hasClass( className ) ) {
                            self.removeClass( className );
                        } else {
                            self.addClass( className );
                        }
                    }

                    // Toggle whole class name
                } else if ( value === undefined || type === "boolean" ) {
                    className = getClass( this );
                    if ( className ) {

                        // Store className if set
                        dataPriv.set( this, "__className__", className );
                    }

                    // If the element has a class name or if we're passed `false`,
                    // then remove the whole classname (if there was one, the above saved it).
                    // Otherwise bring back whatever was previously saved (if anything),
                    // falling back to the empty string if nothing was stored.
                    if ( this.setAttribute ) {
                        this.setAttribute( "class",
                            className || value === false ?
                                "" :
                                dataPriv.get( this, "__className__" ) || ""
                        );
                    }
                }
            } );
        },

        hasClass: function( selector ) {
            var className, elem,
                i = 0;

            className = " " + selector + " ";
            while ( ( elem = this[ i++ ] ) ) {
                if ( elem.nodeType === 1 &&
                    ( " " + stripAndCollapse( getClass( elem ) ) + " " ).indexOf( className ) > -1 ) {
                    return true;
                }
            }

            return false;
        }
    } );




    var rreturn = /\r/g;

    jQuery.fn.extend( {
        val: function( value ) {
            var hooks, ret, valueIsFunction,
                elem = this[ 0 ];

            if ( !arguments.length ) {
                if ( elem ) {
                    hooks = jQuery.valHooks[ elem.type ] ||
                        jQuery.valHooks[ elem.nodeName.toLowerCase() ];

                    if ( hooks &&
                        "get" in hooks &&
                        ( ret = hooks.get( elem, "value" ) ) !== undefined
                    ) {
                        return ret;
                    }

                    ret = elem.value;

                    // Handle most common string cases
                    if ( typeof ret === "string" ) {
                        return ret.replace( rreturn, "" );
                    }

                    // Handle cases where value is null/undef or number
                    return ret == null ? "" : ret;
                }

                return;
            }

            valueIsFunction = isFunction( value );

            return this.each( function( i ) {
                var val;

                if ( this.nodeType !== 1 ) {
                    return;
                }

                if ( valueIsFunction ) {
                    val = value.call( this, i, jQuery( this ).val() );
                } else {
                    val = value;
                }

                // Treat null/undefined as ""; convert numbers to string
                if ( val == null ) {
                    val = "";

                } else if ( typeof val === "number" ) {
                    val += "";

                } else if ( Array.isArray( val ) ) {
                    val = jQuery.map( val, function( value ) {
                        return value == null ? "" : value + "";
                    } );
                }

                hooks = jQuery.valHooks[ this.type ] || jQuery.valHooks[ this.nodeName.toLowerCase() ];

                // If set returns undefined, fall back to normal setting
                if ( !hooks || !( "set" in hooks ) || hooks.set( this, val, "value" ) === undefined ) {
                    this.value = val;
                }
            } );
        }
    } );

    jQuery.extend( {
        valHooks: {
            option: {
                get: function( elem ) {

                    var val = jQuery.find.attr( elem, "value" );
                    return val != null ?
                        val :

                        // Support: IE <=10 - 11 only
                        // option.text throws exceptions (#14686, #14858)
                        // Strip and collapse whitespace
                        // https://html.spec.whatwg.org/#strip-and-collapse-whitespace
                        stripAndCollapse( jQuery.text( elem ) );
                }
            },
            select: {
                get: function( elem ) {
                    var value, option, i,
                        options = elem.options,
                        index = elem.selectedIndex,
                        one = elem.type === "select-one",
                        values = one ? null : [],
                        max = one ? index + 1 : options.length;

                    if ( index < 0 ) {
                        i = max;

                    } else {
                        i = one ? index : 0;
                    }

                    // Loop through all the selected options
                    for ( ; i < max; i++ ) {
                        option = options[ i ];

                        // Support: IE <=9 only
                        // IE8-9 doesn't update selected after form reset (#2551)
                        if ( ( option.selected || i === index ) &&

                            // Don't return options that are disabled or in a disabled optgroup
                            !option.disabled &&
                            ( !option.parentNode.disabled ||
                                !nodeName( option.parentNode, "optgroup" ) ) ) {

                            // Get the specific value for the option
                            value = jQuery( option ).val();

                            // We don't need an array for one selects
                            if ( one ) {
                                return value;
                            }

                            // Multi-Selects return an array
                            values.push( value );
                        }
                    }

                    return values;
                },

                set: function( elem, value ) {
                    var optionSet, option,
                        options = elem.options,
                        values = jQuery.makeArray( value ),
                        i = options.length;

                    while ( i-- ) {
                        option = options[ i ];

                        /* eslint-disable no-cond-assign */

                        if ( option.selected =
                            jQuery.inArray( jQuery.valHooks.option.get( option ), values ) > -1
                        ) {
                            optionSet = true;
                        }

                        /* eslint-enable no-cond-assign */
                    }

                    // Force browsers to behave consistently when non-matching value is set
                    if ( !optionSet ) {
                        elem.selectedIndex = -1;
                    }
                    return values;
                }
            }
        }
    } );

// Radios and checkboxes getter/setter
    jQuery.each( [ "radio", "checkbox" ], function() {
        jQuery.valHooks[ this ] = {
            set: function( elem, value ) {
                if ( Array.isArray( value ) ) {
                    return ( elem.checked = jQuery.inArray( jQuery( elem ).val(), value ) > -1 );
                }
            }
        };
        if ( !support.checkOn ) {
            jQuery.valHooks[ this ].get = function( elem ) {
                return elem.getAttribute( "value" ) === null ? "on" : elem.value;
            };
        }
    } );




// Return jQuery for attributes-only inclusion


    support.focusin = "onfocusin" in window;


    var rfocusMorph = /^(?:focusinfocus|focusoutblur)$/,
        stopPropagationCallback = function( e ) {
            e.stopPropagation();
        };

    jQuery.extend( jQuery.event, {

        trigger: function( event, data, elem, onlyHandlers ) {

            var i, cur, tmp, bubbleType, ontype, handle, special, lastElement,
                eventPath = [ elem || document ],
                type = hasOwn.call( event, "type" ) ? event.type : event,
                namespaces = hasOwn.call( event, "namespace" ) ? event.namespace.split( "." ) : [];

            cur = lastElement = tmp = elem = elem || document;

            // Don't do events on text and comment nodes
            if ( elem.nodeType === 3 || elem.nodeType === 8 ) {
                return;
            }

            // focus/blur morphs to focusin/out; ensure we're not firing them right now
            if ( rfocusMorph.test( type + jQuery.event.triggered ) ) {
                return;
            }

            if ( type.indexOf( "." ) > -1 ) {

                // Namespaced trigger; create a regexp to match event type in handle()
                namespaces = type.split( "." );
                type = namespaces.shift();
                namespaces.sort();
            }
            ontype = type.indexOf( ":" ) < 0 && "on" + type;

            // Caller can pass in a jQuery.Event object, Object, or just an event type string
            event = event[ jQuery.expando ] ?
                event :
                new jQuery.Event( type, typeof event === "object" && event );

            // Trigger bitmask: & 1 for native handlers; & 2 for jQuery (always true)
            event.isTrigger = onlyHandlers ? 2 : 3;
            event.namespace = namespaces.join( "." );
            event.rnamespace = event.namespace ?
                new RegExp( "(^|\\.)" + namespaces.join( "\\.(?:.*\\.|)" ) + "(\\.|$)" ) :
                null;

            // Clean up the event in case it is being reused
            event.result = undefined;
            if ( !event.target ) {
                event.target = elem;
            }

            // Clone any incoming data and prepend the event, creating the handler arg list
            data = data == null ?
                [ event ] :
                jQuery.makeArray( data, [ event ] );

            // Allow special events to draw outside the lines
            special = jQuery.event.special[ type ] || {};
            if ( !onlyHandlers && special.trigger && special.trigger.apply( elem, data ) === false ) {
                return;
            }

            // Determine event propagation path in advance, per W3C events spec (#9951)
            // Bubble up to document, then to window; watch for a global ownerDocument var (#9724)
            if ( !onlyHandlers && !special.noBubble && !isWindow( elem ) ) {

                bubbleType = special.delegateType || type;
                if ( !rfocusMorph.test( bubbleType + type ) ) {
                    cur = cur.parentNode;
                }
                for ( ; cur; cur = cur.parentNode ) {
                    eventPath.push( cur );
                    tmp = cur;
                }

                // Only add window if we got to document (e.g., not plain obj or detached DOM)
                if ( tmp === ( elem.ownerDocument || document ) ) {
                    eventPath.push( tmp.defaultView || tmp.parentWindow || window );
                }
            }

            // Fire handlers on the event path
            i = 0;
            while ( ( cur = eventPath[ i++ ] ) && !event.isPropagationStopped() ) {
                lastElement = cur;
                event.type = i > 1 ?
                    bubbleType :
                    special.bindType || type;

                // jQuery handler
                handle = ( dataPriv.get( cur, "events" ) || Object.create( null ) )[ event.type ] &&
                    dataPriv.get( cur, "handle" );
                if ( handle ) {
                    handle.apply( cur, data );
                }

                // Native handler
                handle = ontype && cur[ ontype ];
                if ( handle && handle.apply && acceptData( cur ) ) {
                    event.result = handle.apply( cur, data );
                    if ( event.result === false ) {
                        event.preventDefault();
                    }
                }
            }
            event.type = type;

            // If nobody prevented the default action, do it now
            if ( !onlyHandlers && !event.isDefaultPrevented() ) {

                if ( ( !special._default ||
                    special._default.apply( eventPath.pop(), data ) === false ) &&
                    acceptData( elem ) ) {

                    // Call a native DOM method on the target with the same name as the event.
                    // Don't do default actions on window, that's where global variables be (#6170)
                    if ( ontype && isFunction( elem[ type ] ) && !isWindow( elem ) ) {

                        // Don't re-trigger an onFOO event when we call its FOO() method
                        tmp = elem[ ontype ];

                        if ( tmp ) {
                            elem[ ontype ] = null;
                        }

                        // Prevent re-triggering of the same event, since we already bubbled it above
                        jQuery.event.triggered = type;

                        if ( event.isPropagationStopped() ) {
                            lastElement.addEventListener( type, stopPropagationCallback );
                        }

                        elem[ type ]();

                        if ( event.isPropagationStopped() ) {
                            lastElement.removeEventListener( type, stopPropagationCallback );
                        }

                        jQuery.event.triggered = undefined;

                        if ( tmp ) {
                            elem[ ontype ] = tmp;
                        }
                    }
                }
            }

            return event.result;
        },

        // Piggyback on a donor event to simulate a different one
        // Used only for `focus(in | out)` events
        simulate: function( type, elem, event ) {
            var e = jQuery.extend(
                new jQuery.Event(),
                event,
                {
                    type: type,
                    isSimulated: true
                }
            );

            jQuery.event.trigger( e, null, elem );
        }

    } );

    jQuery.fn.extend( {

        trigger: function( type, data ) {
            return this.each( function() {
                jQuery.event.trigger( type, data, this );
            } );
        },
        triggerHandler: function( type, data ) {
            var elem = this[ 0 ];
            if ( elem ) {
                return jQuery.event.trigger( type, data, elem, true );
            }
        }
    } );


// Support: Firefox <=44
// Firefox doesn't have focus(in | out) events
// Related ticket - https://bugzilla.mozilla.org/show_bug.cgi?id=687787
//
// Support: Chrome <=48 - 49, Safari <=9.0 - 9.1
// focus(in | out) events fire after focus & blur events,
// which is spec violation - http://www.w3.org/TR/DOM-Level-3-Events/#events-focusevent-event-order
// Related ticket - https://bugs.chromium.org/p/chromium/issues/detail?id=449857
    if ( !support.focusin ) {
        jQuery.each( { focus: "focusin", blur: "focusout" }, function( orig, fix ) {

            // Attach a single capturing handler on the document while someone wants focusin/focusout
            var handler = function( event ) {
                jQuery.event.simulate( fix, event.target, jQuery.event.fix( event ) );
            };

            jQuery.event.special[ fix ] = {
                setup: function() {

                    // Handle: regular nodes (via `this.ownerDocument`), window
                    // (via `this.document`) & document (via `this`).
                    var doc = this.ownerDocument || this.document || this,
                        attaches = dataPriv.access( doc, fix );

                    if ( !attaches ) {
                        doc.addEventListener( orig, handler, true );
                    }
                    dataPriv.access( doc, fix, ( attaches || 0 ) + 1 );
                },
                teardown: function() {
                    var doc = this.ownerDocument || this.document || this,
                        attaches = dataPriv.access( doc, fix ) - 1;

                    if ( !attaches ) {
                        doc.removeEventListener( orig, handler, true );
                        dataPriv.remove( doc, fix );

                    } else {
                        dataPriv.access( doc, fix, attaches );
                    }
                }
            };
        } );
    }
    var location = window.location;

    var nonce = { guid: Date.now() };

    var rquery = ( /\?/ );



// Cross-browser xml parsing
    jQuery.parseXML = function( data ) {
        var xml, parserErrorElem;
        if ( !data || typeof data !== "string" ) {
            return null;
        }

        // Support: IE 9 - 11 only
        // IE throws on parseFromString with invalid input.
        try {
            xml = ( new window.DOMParser() ).parseFromString( data, "text/xml" );
        } catch ( e ) {}

        parserErrorElem = xml && xml.getElementsByTagName( "parsererror" )[ 0 ];
        if ( !xml || parserErrorElem ) {
            jQuery.error( "Invalid XML: " + (
                parserErrorElem ?
                    jQuery.map( parserErrorElem.childNodes, function( el ) {
                        return el.textContent;
                    } ).join( "\n" ) :
                    data
            ) );
        }
        return xml;
    };


    var
        rbracket = /\[\]$/,
        rCRLF = /\r?\n/g,
        rsubmitterTypes = /^(?:submit|button|image|reset|file)$/i,
        rsubmittable = /^(?:input|select|textarea|keygen)/i;

    function buildParams( prefix, obj, traditional, add ) {
        var name;

        if ( Array.isArray( obj ) ) {

            // Serialize array item.
            jQuery.each( obj, function( i, v ) {
                if ( traditional || rbracket.test( prefix ) ) {

                    // Treat each array item as a scalar.
                    add( prefix, v );

                } else {

                    // Item is non-scalar (array or object), encode its numeric index.
                    buildParams(
                        prefix + "[" + ( typeof v === "object" && v != null ? i : "" ) + "]",
                        v,
                        traditional,
                        add
                    );
                }
            } );

        } else if ( !traditional && toType( obj ) === "object" ) {

            // Serialize object item.
            for ( name in obj ) {
                buildParams( prefix + "[" + name + "]", obj[ name ], traditional, add );
            }

        } else {

            // Serialize scalar item.
            add( prefix, obj );
        }
    }

// Serialize an array of form elements or a set of
// key/values into a query string
    jQuery.param = function( a, traditional ) {
        var prefix,
            s = [],
            add = function( key, valueOrFunction ) {

                // If value is a function, invoke it and use its return value
                var value = isFunction( valueOrFunction ) ?
                    valueOrFunction() :
                    valueOrFunction;

                s[ s.length ] = encodeURIComponent( key ) + "=" +
                    encodeURIComponent( value == null ? "" : value );
            };

        if ( a == null ) {
            return "";
        }

        // If an array was passed in, assume that it is an array of form elements.
        if ( Array.isArray( a ) || ( a.jquery && !jQuery.isPlainObject( a ) ) ) {

            // Serialize the form elements
            jQuery.each( a, function() {
                add( this.name, this.value );
            } );

        } else {

            // If traditional, encode the "old" way (the way 1.3.2 or older
            // did it), otherwise encode params recursively.
            for ( prefix in a ) {
                buildParams( prefix, a[ prefix ], traditional, add );
            }
        }

        // Return the resulting serialization
        return s.join( "&" );
    };

    jQuery.fn.extend( {
        serialize: function() {
            return jQuery.param( this.serializeArray() );
        },
        serializeArray: function() {
            return this.map( function() {

                // Can add propHook for "elements" to filter or add form elements
                var elements = jQuery.prop( this, "elements" );
                return elements ? jQuery.makeArray( elements ) : this;
            } ).filter( function() {
                var type = this.type;

                // Use .is( ":disabled" ) so that fieldset[disabled] works
                return this.name && !jQuery( this ).is( ":disabled" ) &&
                    rsubmittable.test( this.nodeName ) && !rsubmitterTypes.test( type ) &&
                    ( this.checked || !rcheckableType.test( type ) );
            } ).map( function( _i, elem ) {
                var val = jQuery( this ).val();

                if ( val == null ) {
                    return null;
                }

                if ( Array.isArray( val ) ) {
                    return jQuery.map( val, function( val ) {
                        return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
                    } );
                }

                return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
            } ).get();
        }
    } );


    var
        r20 = /%20/g,
        rhash = /#.*$/,
        rantiCache = /([?&])_=[^&]*/,
        rheaders = /^(.*?):[ \t]*([^\r\n]*)$/mg,

        // #7653, #8125, #8152: local protocol detection
        rlocalProtocol = /^(?:about|app|app-storage|.+-extension|file|res|widget):$/,
        rnoContent = /^(?:GET|HEAD)$/,
        rprotocol = /^\/\//,

        /* Prefilters
	 * 1) They are useful to introduce custom dataTypes (see ajax/jsonp.js for an example)
	 * 2) These are called:
	 *    - BEFORE asking for a transport
	 *    - AFTER param serialization (s.data is a string if s.processData is true)
	 * 3) key is the dataType
	 * 4) the catchall symbol "*" can be used
	 * 5) execution will start with transport dataType and THEN continue down to "*" if needed
	 */
        prefilters = {},

        /* Transports bindings
	 * 1) key is the dataType
	 * 2) the catchall symbol "*" can be used
	 * 3) selection will start with transport dataType and THEN go to "*" if needed
	 */
        transports = {},

        // Avoid comment-prolog char sequence (#10098); must appease lint and evade compression
        allTypes = "*/".concat( "*" ),

        // Anchor tag for parsing the document origin
        originAnchor = document.createElement( "a" );

    originAnchor.href = location.href;

// Base "constructor" for jQuery.ajaxPrefilter and jQuery.ajaxTransport
    function addToPrefiltersOrTransports( structure ) {

        // dataTypeExpression is optional and defaults to "*"
        return function( dataTypeExpression, func ) {

            if ( typeof dataTypeExpression !== "string" ) {
                func = dataTypeExpression;
                dataTypeExpression = "*";
            }

            var dataType,
                i = 0,
                dataTypes = dataTypeExpression.toLowerCase().match( rnothtmlwhite ) || [];

            if ( isFunction( func ) ) {

                // For each dataType in the dataTypeExpression
                while ( ( dataType = dataTypes[ i++ ] ) ) {

                    // Prepend if requested
                    if ( dataType[ 0 ] === "+" ) {
                        dataType = dataType.slice( 1 ) || "*";
                        ( structure[ dataType ] = structure[ dataType ] || [] ).unshift( func );

                        // Otherwise append
                    } else {
                        ( structure[ dataType ] = structure[ dataType ] || [] ).push( func );
                    }
                }
            }
        };
    }

// Base inspection function for prefilters and transports
    function inspectPrefiltersOrTransports( structure, options, originalOptions, jqXHR ) {

        var inspected = {},
            seekingTransport = ( structure === transports );

        function inspect( dataType ) {
            var selected;
            inspected[ dataType ] = true;
            jQuery.each( structure[ dataType ] || [], function( _, prefilterOrFactory ) {
                var dataTypeOrTransport = prefilterOrFactory( options, originalOptions, jqXHR );
                if ( typeof dataTypeOrTransport === "string" &&
                    !seekingTransport && !inspected[ dataTypeOrTransport ] ) {

                    options.dataTypes.unshift( dataTypeOrTransport );
                    inspect( dataTypeOrTransport );
                    return false;
                } else if ( seekingTransport ) {
                    return !( selected = dataTypeOrTransport );
                }
            } );
            return selected;
        }

        return inspect( options.dataTypes[ 0 ] ) || !inspected[ "*" ] && inspect( "*" );
    }

// A special extend for ajax options
// that takes "flat" options (not to be deep extended)
// Fixes #9887
    function ajaxExtend( target, src ) {
        var key, deep,
            flatOptions = jQuery.ajaxSettings.flatOptions || {};

        for ( key in src ) {
            if ( src[ key ] !== undefined ) {
                ( flatOptions[ key ] ? target : ( deep || ( deep = {} ) ) )[ key ] = src[ key ];
            }
        }
        if ( deep ) {
            jQuery.extend( true, target, deep );
        }

        return target;
    }

    /* Handles responses to an ajax request:
 * - finds the right dataType (mediates between content-type and expected dataType)
 * - returns the corresponding response
 */
    function ajaxHandleResponses( s, jqXHR, responses ) {

        var ct, type, finalDataType, firstDataType,
            contents = s.contents,
            dataTypes = s.dataTypes;

        // Remove auto dataType and get content-type in the process
        while ( dataTypes[ 0 ] === "*" ) {
            dataTypes.shift();
            if ( ct === undefined ) {
                ct = s.mimeType || jqXHR.getResponseHeader( "Content-Type" );
            }
        }

        // Check if we're dealing with a known content-type
        if ( ct ) {
            for ( type in contents ) {
                if ( contents[ type ] && contents[ type ].test( ct ) ) {
                    dataTypes.unshift( type );
                    break;
                }
            }
        }

        // Check to see if we have a response for the expected dataType
        if ( dataTypes[ 0 ] in responses ) {
            finalDataType = dataTypes[ 0 ];
        } else {

            // Try convertible dataTypes
            for ( type in responses ) {
                if ( !dataTypes[ 0 ] || s.converters[ type + " " + dataTypes[ 0 ] ] ) {
                    finalDataType = type;
                    break;
                }
                if ( !firstDataType ) {
                    firstDataType = type;
                }
            }

            // Or just use first one
            finalDataType = finalDataType || firstDataType;
        }

        // If we found a dataType
        // We add the dataType to the list if needed
        // and return the corresponding response
        if ( finalDataType ) {
            if ( finalDataType !== dataTypes[ 0 ] ) {
                dataTypes.unshift( finalDataType );
            }
            return responses[ finalDataType ];
        }
    }

    /* Chain conversions given the request and the original response
 * Also sets the responseXXX fields on the jqXHR instance
 */
    function ajaxConvert( s, response, jqXHR, isSuccess ) {
        var conv2, current, conv, tmp, prev,
            converters = {},

            // Work with a copy of dataTypes in case we need to modify it for conversion
            dataTypes = s.dataTypes.slice();

        // Create converters map with lowercased keys
        if ( dataTypes[ 1 ] ) {
            for ( conv in s.converters ) {
                converters[ conv.toLowerCase() ] = s.converters[ conv ];
            }
        }

        current = dataTypes.shift();

        // Convert to each sequential dataType
        while ( current ) {

            if ( s.responseFields[ current ] ) {
                jqXHR[ s.responseFields[ current ] ] = response;
            }

            // Apply the dataFilter if provided
            if ( !prev && isSuccess && s.dataFilter ) {
                response = s.dataFilter( response, s.dataType );
            }

            prev = current;
            current = dataTypes.shift();

            if ( current ) {

                // There's only work to do if current dataType is non-auto
                if ( current === "*" ) {

                    current = prev;

                    // Convert response if prev dataType is non-auto and differs from current
                } else if ( prev !== "*" && prev !== current ) {

                    // Seek a direct converter
                    conv = converters[ prev + " " + current ] || converters[ "* " + current ];

                    // If none found, seek a pair
                    if ( !conv ) {
                        for ( conv2 in converters ) {

                            // If conv2 outputs current
                            tmp = conv2.split( " " );
                            if ( tmp[ 1 ] === current ) {

                                // If prev can be converted to accepted input
                                conv = converters[ prev + " " + tmp[ 0 ] ] ||
                                    converters[ "* " + tmp[ 0 ] ];
                                if ( conv ) {

                                    // Condense equivalence converters
                                    if ( conv === true ) {
                                        conv = converters[ conv2 ];

                                        // Otherwise, insert the intermediate dataType
                                    } else if ( converters[ conv2 ] !== true ) {
                                        current = tmp[ 0 ];
                                        dataTypes.unshift( tmp[ 1 ] );
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    // Apply converter (if not an equivalence)
                    if ( conv !== true ) {

                        // Unless errors are allowed to bubble, catch and return them
                        if ( conv && s.throws ) {
                            response = conv( response );
                        } else {
                            try {
                                response = conv( response );
                            } catch ( e ) {
                                return {
                                    state: "parsererror",
                                    error: conv ? e : "No conversion from " + prev + " to " + current
                                };
                            }
                        }
                    }
                }
            }
        }

        return { state: "success", data: response };
    }

    jQuery.extend( {

        // Counter for holding the number of active queries
        active: 0,

        // Last-Modified header cache for next request
        lastModified: {},
        etag: {},

        ajaxSettings: {
            url: location.href,
            type: "GET",
            isLocal: rlocalProtocol.test( location.protocol ),
            global: true,
            processData: true,
            async: true,
            contentType: "application/x-www-form-urlencoded; charset=UTF-8",

            /*
		timeout: 0,
		data: null,
		dataType: null,
		username: null,
		password: null,
		cache: null,
		throws: false,
		traditional: false,
		headers: {},
		*/

            accepts: {
                "*": allTypes,
                text: "text/plain",
                html: "text/html",
                xml: "application/xml, text/xml",
                json: "application/json, text/javascript"
            },

            contents: {
                xml: /\bxml\b/,
                html: /\bhtml/,
                json: /\bjson\b/
            },

            responseFields: {
                xml: "responseXML",
                text: "responseText",
                json: "responseJSON"
            },

            // Data converters
            // Keys separate source (or catchall "*") and destination types with a single space
            converters: {

                // Convert anything to text
                "* text": String,

                // Text to html (true = no transformation)
                "text html": true,

                // Evaluate text as a json expression
                "text json": JSON.parse,

                // Parse text as xml
                "text xml": jQuery.parseXML
            },

            // For options that shouldn't be deep extended:
            // you can add your own custom options here if
            // and when you create one that shouldn't be
            // deep extended (see ajaxExtend)
            flatOptions: {
                url: true,
                context: true
            }
        },

        // Creates a full fledged settings object into target
        // with both ajaxSettings and settings fields.
        // If target is omitted, writes into ajaxSettings.
        ajaxSetup: function( target, settings ) {
            return settings ?

                // Building a settings object
                ajaxExtend( ajaxExtend( target, jQuery.ajaxSettings ), settings ) :

                // Extending ajaxSettings
                ajaxExtend( jQuery.ajaxSettings, target );
        },

        ajaxPrefilter: addToPrefiltersOrTransports( prefilters ),
        ajaxTransport: addToPrefiltersOrTransports( transports ),

        // Main method
        ajax: function( url, options ) {

            // If url is an object, simulate pre-1.5 signature
            if ( typeof url === "object" ) {
                options = url;
                url = undefined;
            }

            // Force options to be an object
            options = options || {};

            var transport,

                // URL without anti-cache param
                cacheURL,

                // Response headers
                responseHeadersString,
                responseHeaders,

                // timeout handle
                timeoutTimer,

                // Url cleanup var
                urlAnchor,

                // Request state (becomes false upon send and true upon completion)
                completed,

                // To know if global events are to be dispatched
                fireGlobals,

                // Loop variable
                i,

                // uncached part of the url
                uncached,

                // Create the final options object
                s = jQuery.ajaxSetup( {}, options ),

                // Callbacks context
                callbackContext = s.context || s,

                // Context for global events is callbackContext if it is a DOM node or jQuery collection
                globalEventContext = s.context &&
                ( callbackContext.nodeType || callbackContext.jquery ) ?
                    jQuery( callbackContext ) :
                    jQuery.event,

                // Deferreds
                deferred = jQuery.Deferred(),
                completeDeferred = jQuery.Callbacks( "once memory" ),

                // Status-dependent callbacks
                statusCode = s.statusCode || {},

                // Headers (they are sent all at once)
                requestHeaders = {},
                requestHeadersNames = {},

                // Default abort message
                strAbort = "canceled",

                // Fake xhr
                jqXHR = {
                    readyState: 0,

                    // Builds headers hashtable if needed
                    getResponseHeader: function( key ) {
                        var match;
                        if ( completed ) {
                            if ( !responseHeaders ) {
                                responseHeaders = {};
                                while ( ( match = rheaders.exec( responseHeadersString ) ) ) {
                                    responseHeaders[ match[ 1 ].toLowerCase() + " " ] =
                                        ( responseHeaders[ match[ 1 ].toLowerCase() + " " ] || [] )
                                            .concat( match[ 2 ] );
                                }
                            }
                            match = responseHeaders[ key.toLowerCase() + " " ];
                        }
                        return match == null ? null : match.join( ", " );
                    },

                    // Raw string
                    getAllResponseHeaders: function() {
                        return completed ? responseHeadersString : null;
                    },

                    // Caches the header
                    setRequestHeader: function( name, value ) {
                        if ( completed == null ) {
                            name = requestHeadersNames[ name.toLowerCase() ] =
                                requestHeadersNames[ name.toLowerCase() ] || name;
                            requestHeaders[ name ] = value;
                        }
                        return this;
                    },

                    // Overrides response content-type header
                    overrideMimeType: function( type ) {
                        if ( completed == null ) {
                            s.mimeType = type;
                        }
                        return this;
                    },

                    // Status-dependent callbacks
                    statusCode: function( map ) {
                        var code;
                        if ( map ) {
                            if ( completed ) {

                                // Execute the appropriate callbacks
                                jqXHR.always( map[ jqXHR.status ] );
                            } else {

                                // Lazy-add the new callbacks in a way that preserves old ones
                                for ( code in map ) {
                                    statusCode[ code ] = [ statusCode[ code ], map[ code ] ];
                                }
                            }
                        }
                        return this;
                    },

                    // Cancel the request
                    abort: function( statusText ) {
                        var finalText = statusText || strAbort;
                        if ( transport ) {
                            transport.abort( finalText );
                        }
                        done( 0, finalText );
                        return this;
                    }
                };

            // Attach deferreds
            deferred.promise( jqXHR );

            // Add protocol if not provided (prefilters might expect it)
            // Handle falsy url in the settings object (#10093: consistency with old signature)
            // We also use the url parameter if available
            s.url = ( ( url || s.url || location.href ) + "" )
                .replace( rprotocol, location.protocol + "//" );

            // Alias method option to type as per ticket #12004
            s.type = options.method || options.type || s.method || s.type;

            // Extract dataTypes list
            s.dataTypes = ( s.dataType || "*" ).toLowerCase().match( rnothtmlwhite ) || [ "" ];

            // A cross-domain request is in order when the origin doesn't match the current origin.
            if ( s.crossDomain == null ) {
                urlAnchor = document.createElement( "a" );

                // Support: IE <=8 - 11, Edge 12 - 15
                // IE throws exception on accessing the href property if url is malformed,
                // e.g. http://example.com:80x/
                try {
                    urlAnchor.href = s.url;

                    // Support: IE <=8 - 11 only
                    // Anchor's host property isn't correctly set when s.url is relative
                    urlAnchor.href = urlAnchor.href;
                    s.crossDomain = originAnchor.protocol + "//" + originAnchor.host !==
                        urlAnchor.protocol + "//" + urlAnchor.host;
                } catch ( e ) {

                    // If there is an error parsing the URL, assume it is crossDomain,
                    // it can be rejected by the transport if it is invalid
                    s.crossDomain = true;
                }
            }

            // Convert data if not already a string
            if ( s.data && s.processData && typeof s.data !== "string" ) {
                s.data = jQuery.param( s.data, s.traditional );
            }

            // Apply prefilters
            inspectPrefiltersOrTransports( prefilters, s, options, jqXHR );

            // If request was aborted inside a prefilter, stop there
            if ( completed ) {
                return jqXHR;
            }

            // We can fire global events as of now if asked to
            // Don't fire events if jQuery.event is undefined in an AMD-usage scenario (#15118)
            fireGlobals = jQuery.event && s.global;

            // Watch for a new set of requests
            if ( fireGlobals && jQuery.active++ === 0 ) {
                jQuery.event.trigger( "ajaxStart" );
            }

            // Uppercase the type
            s.type = s.type.toUpperCase();

            // Determine if request has content
            s.hasContent = !rnoContent.test( s.type );

            // Save the URL in case we're toying with the If-Modified-Since
            // and/or If-None-Match header later on
            // Remove hash to simplify url manipulation
            cacheURL = s.url.replace( rhash, "" );

            // More options handling for requests with no content
            if ( !s.hasContent ) {

                // Remember the hash so we can put it back
                uncached = s.url.slice( cacheURL.length );

                // If data is available and should be processed, append data to url
                if ( s.data && ( s.processData || typeof s.data === "string" ) ) {
                    cacheURL += ( rquery.test( cacheURL ) ? "&" : "?" ) + s.data;

                    // #9682: remove data so that it's not used in an eventual retry
                    delete s.data;
                }

                // Add or update anti-cache param if needed
                if ( s.cache === false ) {
                    cacheURL = cacheURL.replace( rantiCache, "$1" );
                    uncached = ( rquery.test( cacheURL ) ? "&" : "?" ) + "_=" + ( nonce.guid++ ) +
                        uncached;
                }

                // Put hash and anti-cache on the URL that will be requested (gh-1732)
                s.url = cacheURL + uncached;

                // Change '%20' to '+' if this is encoded form body content (gh-2658)
            } else if ( s.data && s.processData &&
                ( s.contentType || "" ).indexOf( "application/x-www-form-urlencoded" ) === 0 ) {
                s.data = s.data.replace( r20, "+" );
            }

            // Set the If-Modified-Since and/or If-None-Match header, if in ifModified mode.
            if ( s.ifModified ) {
                if ( jQuery.lastModified[ cacheURL ] ) {
                    jqXHR.setRequestHeader( "If-Modified-Since", jQuery.lastModified[ cacheURL ] );
                }
                if ( jQuery.etag[ cacheURL ] ) {
                    jqXHR.setRequestHeader( "If-None-Match", jQuery.etag[ cacheURL ] );
                }
            }

            // Set the correct header, if data is being sent
            if ( s.data && s.hasContent && s.contentType !== false || options.contentType ) {
                jqXHR.setRequestHeader( "Content-Type", s.contentType );
            }

            // Set the Accepts header for the server, depending on the dataType
            jqXHR.setRequestHeader(
                "Accept",
                s.dataTypes[ 0 ] && s.accepts[ s.dataTypes[ 0 ] ] ?
                    s.accepts[ s.dataTypes[ 0 ] ] +
                    ( s.dataTypes[ 0 ] !== "*" ? ", " + allTypes + "; q=0.01" : "" ) :
                    s.accepts[ "*" ]
            );

            // Check for headers option
            for ( i in s.headers ) {
                jqXHR.setRequestHeader( i, s.headers[ i ] );
            }

            // Allow custom headers/mimetypes and early abort
            if ( s.beforeSend &&
                ( s.beforeSend.call( callbackContext, jqXHR, s ) === false || completed ) ) {

                // Abort if not done already and return
                return jqXHR.abort();
            }

            // Aborting is no longer a cancellation
            strAbort = "abort";

            // Install callbacks on deferreds
            completeDeferred.add( s.complete );
            jqXHR.done( s.success );
            jqXHR.fail( s.error );

            // Get transport
            transport = inspectPrefiltersOrTransports( transports, s, options, jqXHR );

            // If no transport, we auto-abort
            if ( !transport ) {
                done( -1, "No Transport" );
            } else {
                jqXHR.readyState = 1;

                // Send global event
                if ( fireGlobals ) {
                    globalEventContext.trigger( "ajaxSend", [ jqXHR, s ] );
                }

                // If request was aborted inside ajaxSend, stop there
                if ( completed ) {
                    return jqXHR;
                }

                // Timeout
                if ( s.async && s.timeout > 0 ) {
                    timeoutTimer = window.setTimeout( function() {
                        jqXHR.abort( "timeout" );
                    }, s.timeout );
                }

                try {
                    completed = false;
                    transport.send( requestHeaders, done );
                } catch ( e ) {

                    // Rethrow post-completion exceptions
                    if ( completed ) {
                        throw e;
                    }

                    // Propagate others as results
                    done( -1, e );
                }
            }

            // Callback for when everything is done
            function done( status, nativeStatusText, responses, headers ) {
                var isSuccess, success, error, response, modified,
                    statusText = nativeStatusText;

                // Ignore repeat invocations
                if ( completed ) {
                    return;
                }

                completed = true;

                // Clear timeout if it exists
                if ( timeoutTimer ) {
                    window.clearTimeout( timeoutTimer );
                }

                // Dereference transport for early garbage collection
                // (no matter how long the jqXHR object will be used)
                transport = undefined;

                // Cache response headers
                responseHeadersString = headers || "";

                // Set readyState
                jqXHR.readyState = status > 0 ? 4 : 0;

                // Determine if successful
                isSuccess = status >= 200 && status < 300 || status === 304;

                // Get response data
                if ( responses ) {
                    response = ajaxHandleResponses( s, jqXHR, responses );
                }

                // Use a noop converter for missing script but not if jsonp
                if ( !isSuccess &&
                    jQuery.inArray( "script", s.dataTypes ) > -1 &&
                    jQuery.inArray( "json", s.dataTypes ) < 0 ) {
                    s.converters[ "text script" ] = function() {};
                }

                // Convert no matter what (that way responseXXX fields are always set)
                response = ajaxConvert( s, response, jqXHR, isSuccess );

                // If successful, handle type chaining
                if ( isSuccess ) {

                    // Set the If-Modified-Since and/or If-None-Match header, if in ifModified mode.
                    if ( s.ifModified ) {
                        modified = jqXHR.getResponseHeader( "Last-Modified" );
                        if ( modified ) {
                            jQuery.lastModified[ cacheURL ] = modified;
                        }
                        modified = jqXHR.getResponseHeader( "etag" );
                        if ( modified ) {
                            jQuery.etag[ cacheURL ] = modified;
                        }
                    }

                    // if no content
                    if ( status === 204 || s.type === "HEAD" ) {
                        statusText = "nocontent";

                        // if not modified
                    } else if ( status === 304 ) {
                        statusText = "notmodified";

                        // If we have data, let's convert it
                    } else {
                        statusText = response.state;
                        success = response.data;
                        error = response.error;
                        isSuccess = !error;
                    }
                } else {

                    // Extract error from statusText and normalize for non-aborts
                    error = statusText;
                    if ( status || !statusText ) {
                        statusText = "error";
                        if ( status < 0 ) {
                            status = 0;
                        }
                    }
                }

                // Set data for the fake xhr object
                jqXHR.status = status;
                jqXHR.statusText = ( nativeStatusText || statusText ) + "";

                // Success/Error
                if ( isSuccess ) {
                    deferred.resolveWith( callbackContext, [ success, statusText, jqXHR ] );
                } else {
                    deferred.rejectWith( callbackContext, [ jqXHR, statusText, error ] );
                }

                // Status-dependent callbacks
                jqXHR.statusCode( statusCode );
                statusCode = undefined;

                if ( fireGlobals ) {
                    globalEventContext.trigger( isSuccess ? "ajaxSuccess" : "ajaxError",
                        [ jqXHR, s, isSuccess ? success : error ] );
                }

                // Complete
                completeDeferred.fireWith( callbackContext, [ jqXHR, statusText ] );

                if ( fireGlobals ) {
                    globalEventContext.trigger( "ajaxComplete", [ jqXHR, s ] );

                    // Handle the global AJAX counter
                    if ( !( --jQuery.active ) ) {
                        jQuery.event.trigger( "ajaxStop" );
                    }
                }
            }

            return jqXHR;
        },

        getJSON: function( url, data, callback ) {
            return jQuery.get( url, data, callback, "json" );
        },

        getScript: function( url, callback ) {
            return jQuery.get( url, undefined, callback, "script" );
        }
    } );

    jQuery.each( [ "get", "post" ], function( _i, method ) {
        jQuery[ method ] = function( url, data, callback, type ) {

            // Shift arguments if data argument was omitted
            if ( isFunction( data ) ) {
                type = type || callback;
                callback = data;
                data = undefined;
            }

            // The url can be an options object (which then must have .url)
            return jQuery.ajax( jQuery.extend( {
                url: url,
                type: method,
                dataType: type,
                data: data,
                success: callback
            }, jQuery.isPlainObject( url ) && url ) );
        };
    } );

    jQuery.ajaxPrefilter( function( s ) {
        var i;
        for ( i in s.headers ) {
            if ( i.toLowerCase() === "content-type" ) {
                s.contentType = s.headers[ i ] || "";
            }
        }
    } );


    jQuery._evalUrl = function( url, options, doc ) {
        return jQuery.ajax( {
            url: url,

            // Make this explicit, since user can override this through ajaxSetup (#11264)
            type: "GET",
            dataType: "script",
            cache: true,
            async: false,
            global: false,

            // Only evaluate the response if it is successful (gh-4126)
            // dataFilter is not invoked for failure responses, so using it instead
            // of the default converter is kludgy but it works.
            converters: {
                "text script": function() {}
            },
            dataFilter: function( response ) {
                jQuery.globalEval( response, options, doc );
            }
        } );
    };


    jQuery.fn.extend( {
        wrapAll: function( html ) {
            var wrap;

            if ( this[ 0 ] ) {
                if ( isFunction( html ) ) {
                    html = html.call( this[ 0 ] );
                }

                // The elements to wrap the target around
                wrap = jQuery( html, this[ 0 ].ownerDocument ).eq( 0 ).clone( true );

                if ( this[ 0 ].parentNode ) {
                    wrap.insertBefore( this[ 0 ] );
                }

                wrap.map( function() {
                    var elem = this;

                    while ( elem.firstElementChild ) {
                        elem = elem.firstElementChild;
                    }

                    return elem;
                } ).append( this );
            }

            return this;
        },

        wrapInner: function( html ) {
            if ( isFunction( html ) ) {
                return this.each( function( i ) {
                    jQuery( this ).wrapInner( html.call( this, i ) );
                } );
            }

            return this.each( function() {
                var self = jQuery( this ),
                    contents = self.contents();

                if ( contents.length ) {
                    contents.wrapAll( html );

                } else {
                    self.append( html );
                }
            } );
        },

        wrap: function( html ) {
            var htmlIsFunction = isFunction( html );

            return this.each( function( i ) {
                jQuery( this ).wrapAll( htmlIsFunction ? html.call( this, i ) : html );
            } );
        },

        unwrap: function( selector ) {
            this.parent( selector ).not( "body" ).each( function() {
                jQuery( this ).replaceWith( this.childNodes );
            } );
            return this;
        }
    } );


    jQuery.expr.pseudos.hidden = function( elem ) {
        return !jQuery.expr.pseudos.visible( elem );
    };
    jQuery.expr.pseudos.visible = function( elem ) {
        return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
    };




    jQuery.ajaxSettings.xhr = function() {
        try {
            return new window.XMLHttpRequest();
        } catch ( e ) {}
    };

    var xhrSuccessStatus = {

            // File protocol always yields status code 0, assume 200
            0: 200,

            // Support: IE <=9 only
            // #1450: sometimes IE returns 1223 when it should be 204
            1223: 204
        },
        xhrSupported = jQuery.ajaxSettings.xhr();

    support.cors = !!xhrSupported && ( "withCredentials" in xhrSupported );
    support.ajax = xhrSupported = !!xhrSupported;

    jQuery.ajaxTransport( function( options ) {
        var callback, errorCallback;

        // Cross domain only allowed if supported through XMLHttpRequest
        if ( support.cors || xhrSupported && !options.crossDomain ) {
            return {
                send: function( headers, complete ) {
                    var i,
                        xhr = options.xhr();

                    xhr.open(
                        options.type,
                        options.url,
                        options.async,
                        options.username,
                        options.password
                    );

                    // Apply custom fields if provided
                    if ( options.xhrFields ) {
                        for ( i in options.xhrFields ) {
                            xhr[ i ] = options.xhrFields[ i ];
                        }
                    }

                    // Override mime type if needed
                    if ( options.mimeType && xhr.overrideMimeType ) {
                        xhr.overrideMimeType( options.mimeType );
                    }

                    // X-Requested-With header
                    // For cross-domain requests, seeing as conditions for a preflight are
                    // akin to a jigsaw puzzle, we simply never set it to be sure.
                    // (it can always be set on a per-request basis or even using ajaxSetup)
                    // For same-domain requests, won't change header if already provided.
                    if ( !options.crossDomain && !headers[ "X-Requested-With" ] ) {
                        headers[ "X-Requested-With" ] = "XMLHttpRequest";
                    }

                    // Set headers
                    for ( i in headers ) {
                        xhr.setRequestHeader( i, headers[ i ] );
                    }

                    // Callback
                    callback = function( type ) {
                        return function() {
                            if ( callback ) {
                                callback = errorCallback = xhr.onload =
                                    xhr.onerror = xhr.onabort = xhr.ontimeout =
                                        xhr.onreadystatechange = null;

                                if ( type === "abort" ) {
                                    xhr.abort();
                                } else if ( type === "error" ) {

                                    // Support: IE <=9 only
                                    // On a manual native abort, IE9 throws
                                    // errors on any property access that is not readyState
                                    if ( typeof xhr.status !== "number" ) {
                                        complete( 0, "error" );
                                    } else {
                                        complete(

                                            // File: protocol always yields status 0; see #8605, #14207
                                            xhr.status,
                                            xhr.statusText
                                        );
                                    }
                                } else {
                                    complete(
                                        xhrSuccessStatus[ xhr.status ] || xhr.status,
                                        xhr.statusText,

                                        // Support: IE <=9 only
                                        // IE9 has no XHR2 but throws on binary (trac-11426)
                                        // For XHR2 non-text, let the caller handle it (gh-2498)
                                        ( xhr.responseType || "text" ) !== "text"  ||
                                        typeof xhr.responseText !== "string" ?
                                            { binary: xhr.response } :
                                            { text: xhr.responseText },
                                        xhr.getAllResponseHeaders()
                                    );
                                }
                            }
                        };
                    };

                    // Listen to events
                    xhr.onload = callback();
                    errorCallback = xhr.onerror = xhr.ontimeout = callback( "error" );

                    // Support: IE 9 only
                    // Use onreadystatechange to replace onabort
                    // to handle uncaught aborts
                    if ( xhr.onabort !== undefined ) {
                        xhr.onabort = errorCallback;
                    } else {
                        xhr.onreadystatechange = function() {

                            // Check readyState before timeout as it changes
                            if ( xhr.readyState === 4 ) {

                                // Allow onerror to be called first,
                                // but that will not handle a native abort
                                // Also, save errorCallback to a variable
                                // as xhr.onerror cannot be accessed
                                window.setTimeout( function() {
                                    if ( callback ) {
                                        errorCallback();
                                    }
                                } );
                            }
                        };
                    }

                    // Create the abort callback
                    callback = callback( "abort" );

                    try {

                        // Do send the request (this may raise an exception)
                        xhr.send( options.hasContent && options.data || null );
                    } catch ( e ) {

                        // #14683: Only rethrow if this hasn't been notified as an error yet
                        if ( callback ) {
                            throw e;
                        }
                    }
                },

                abort: function() {
                    if ( callback ) {
                        callback();
                    }
                }
            };
        }
    } );




// Prevent auto-execution of scripts when no explicit dataType was provided (See gh-2432)
    jQuery.ajaxPrefilter( function( s ) {
        if ( s.crossDomain ) {
            s.contents.script = false;
        }
    } );

// Install script dataType
    jQuery.ajaxSetup( {
        accepts: {
            script: "text/javascript, application/javascript, " +
                "application/ecmascript, application/x-ecmascript"
        },
        contents: {
            script: /\b(?:java|ecma)script\b/
        },
        converters: {
            "text script": function( text ) {
                jQuery.globalEval( text );
                return text;
            }
        }
    } );

// Handle cache's special case and crossDomain
    jQuery.ajaxPrefilter( "script", function( s ) {
        if ( s.cache === undefined ) {
            s.cache = false;
        }
        if ( s.crossDomain ) {
            s.type = "GET";
        }
    } );

// Bind script tag hack transport
    jQuery.ajaxTransport( "script", function( s ) {

        // This transport only deals with cross domain or forced-by-attrs requests
        if ( s.crossDomain || s.scriptAttrs ) {
            var script, callback;
            return {
                send: function( _, complete ) {
                    script = jQuery( "<script>" )
                        .attr( s.scriptAttrs || {} )
                        .prop( { charset: s.scriptCharset, src: s.url } )
                        .on( "load error", callback = function( evt ) {
                            script.remove();
                            callback = null;
                            if ( evt ) {
                                complete( evt.type === "error" ? 404 : 200, evt.type );
                            }
                        } );

                    // Use native DOM manipulation to avoid our domManip AJAX trickery
                    document.head.appendChild( script[ 0 ] );
                },
                abort: function() {
                    if ( callback ) {
                        callback();
                    }
                }
            };
        }
    } );




    var oldCallbacks = [],
        rjsonp = /(=)\?(?=&|$)|\?\?/;

// Default jsonp settings
    jQuery.ajaxSetup( {
        jsonp: "callback",
        jsonpCallback: function() {
            var callback = oldCallbacks.pop() || ( jQuery.expando + "_" + ( nonce.guid++ ) );
            this[ callback ] = true;
            return callback;
        }
    } );

// Detect, normalize options and install callbacks for jsonp requests
    jQuery.ajaxPrefilter( "json jsonp", function( s, originalSettings, jqXHR ) {

        var callbackName, overwritten, responseContainer,
            jsonProp = s.jsonp !== false && ( rjsonp.test( s.url ) ?
                    "url" :
                    typeof s.data === "string" &&
                    ( s.contentType || "" )
                        .indexOf( "application/x-www-form-urlencoded" ) === 0 &&
                    rjsonp.test( s.data ) && "data"
            );

        // Handle iff the expected data type is "jsonp" or we have a parameter to set
        if ( jsonProp || s.dataTypes[ 0 ] === "jsonp" ) {

            // Get callback name, remembering preexisting value associated with it
            callbackName = s.jsonpCallback = isFunction( s.jsonpCallback ) ?
                s.jsonpCallback() :
                s.jsonpCallback;

            // Insert callback into url or form data
            if ( jsonProp ) {
                s[ jsonProp ] = s[ jsonProp ].replace( rjsonp, "$1" + callbackName );
            } else if ( s.jsonp !== false ) {
                s.url += ( rquery.test( s.url ) ? "&" : "?" ) + s.jsonp + "=" + callbackName;
            }

            // Use data converter to retrieve json after script execution
            s.converters[ "script json" ] = function() {
                if ( !responseContainer ) {
                    jQuery.error( callbackName + " was not called" );
                }
                return responseContainer[ 0 ];
            };

            // Force json dataType
            s.dataTypes[ 0 ] = "json";

            // Install callback
            overwritten = window[ callbackName ];
            window[ callbackName ] = function() {
                responseContainer = arguments;
            };

            // Clean-up function (fires after converters)
            jqXHR.always( function() {

                // If previous value didn't exist - remove it
                if ( overwritten === undefined ) {
                    jQuery( window ).removeProp( callbackName );

                    // Otherwise restore preexisting value
                } else {
                    window[ callbackName ] = overwritten;
                }

                // Save back as free
                if ( s[ callbackName ] ) {

                    // Make sure that re-using the options doesn't screw things around
                    s.jsonpCallback = originalSettings.jsonpCallback;

                    // Save the callback name for future use
                    oldCallbacks.push( callbackName );
                }

                // Call if it was a function and we have a response
                if ( responseContainer && isFunction( overwritten ) ) {
                    overwritten( responseContainer[ 0 ] );
                }

                responseContainer = overwritten = undefined;
            } );

            // Delegate to script
            return "script";
        }
    } );




// Support: Safari 8 only
// In Safari 8 documents created via document.implementation.createHTMLDocument
// collapse sibling forms: the second one becomes a child of the first one.
// Because of that, this security measure has to be disabled in Safari 8.
// https://bugs.webkit.org/show_bug.cgi?id=137337
    support.createHTMLDocument = ( function() {
        var body = document.implementation.createHTMLDocument( "" ).body;
        body.innerHTML = "<form></form><form></form>";
        return body.childNodes.length === 2;
    } )();


// Argument "data" should be string of html
// context (optional): If specified, the fragment will be created in this context,
// defaults to document
// keepScripts (optional): If true, will include scripts passed in the html string
    jQuery.parseHTML = function( data, context, keepScripts ) {
        if ( typeof data !== "string" ) {
            return [];
        }
        if ( typeof context === "boolean" ) {
            keepScripts = context;
            context = false;
        }

        var base, parsed, scripts;

        if ( !context ) {

            // Stop scripts or inline event handlers from being executed immediately
            // by using document.implementation
            if ( support.createHTMLDocument ) {
                context = document.implementation.createHTMLDocument( "" );

                // Set the base href for the created document
                // so any parsed elements with URLs
                // are based on the document's URL (gh-2965)
                base = context.createElement( "base" );
                base.href = document.location.href;
                context.head.appendChild( base );
            } else {
                context = document;
            }
        }

        parsed = rsingleTag.exec( data );
        scripts = !keepScripts && [];

        // Single tag
        if ( parsed ) {
            return [ context.createElement( parsed[ 1 ] ) ];
        }

        parsed = buildFragment( [ data ], context, scripts );

        if ( scripts && scripts.length ) {
            jQuery( scripts ).remove();
        }

        return jQuery.merge( [], parsed.childNodes );
    };


    /**
     * Load a url into a page
     */
    jQuery.fn.load = function( url, params, callback ) {
        var selector, type, response,
            self = this,
            off = url.indexOf( " " );

        if ( off > -1 ) {
            selector = stripAndCollapse( url.slice( off ) );
            url = url.slice( 0, off );
        }

        // If it's a function
        if ( isFunction( params ) ) {

            // We assume that it's the callback
            callback = params;
            params = undefined;

            // Otherwise, build a param string
        } else if ( params && typeof params === "object" ) {
            type = "POST";
        }

        // If we have elements to modify, make the request
        if ( self.length > 0 ) {
            jQuery.ajax( {
                url: url,

                // If "type" variable is undefined, then "GET" method will be used.
                // Make value of this field explicit since
                // user can override it through ajaxSetup method
                type: type || "GET",
                dataType: "html",
                data: params
            } ).done( function( responseText ) {

                // Save response for use in complete callback
                response = arguments;

                self.html( selector ?

                    // If a selector was specified, locate the right elements in a dummy div
                    // Exclude scripts to avoid IE 'Permission Denied' errors
                    jQuery( "<div>" ).append( jQuery.parseHTML( responseText ) ).find( selector ) :

                    // Otherwise use the full result
                    responseText );

                // If the request succeeds, this function gets "data", "status", "jqXHR"
                // but they are ignored because response was set above.
                // If it fails, this function gets "jqXHR", "status", "error"
            } ).always( callback && function( jqXHR, status ) {
                self.each( function() {
                    callback.apply( this, response || [ jqXHR.responseText, status, jqXHR ] );
                } );
            } );
        }

        return this;
    };




    jQuery.expr.pseudos.animated = function( elem ) {
        return jQuery.grep( jQuery.timers, function( fn ) {
            return elem === fn.elem;
        } ).length;
    };




    jQuery.offset = {
        setOffset: function( elem, options, i ) {
            var curPosition, curLeft, curCSSTop, curTop, curOffset, curCSSLeft, calculatePosition,
                position = jQuery.css( elem, "position" ),
                curElem = jQuery( elem ),
                props = {};

            // Set position first, in-case top/left are set even on static elem
            if ( position === "static" ) {
                elem.style.position = "relative";
            }

            curOffset = curElem.offset();
            curCSSTop = jQuery.css( elem, "top" );
            curCSSLeft = jQuery.css( elem, "left" );
            calculatePosition = ( position === "absolute" || position === "fixed" ) &&
                ( curCSSTop + curCSSLeft ).indexOf( "auto" ) > -1;

            // Need to be able to calculate position if either
            // top or left is auto and position is either absolute or fixed
            if ( calculatePosition ) {
                curPosition = curElem.position();
                curTop = curPosition.top;
                curLeft = curPosition.left;

            } else {
                curTop = parseFloat( curCSSTop ) || 0;
                curLeft = parseFloat( curCSSLeft ) || 0;
            }

            if ( isFunction( options ) ) {

                // Use jQuery.extend here to allow modification of coordinates argument (gh-1848)
                options = options.call( elem, i, jQuery.extend( {}, curOffset ) );
            }

            if ( options.top != null ) {
                props.top = ( options.top - curOffset.top ) + curTop;
            }
            if ( options.left != null ) {
                props.left = ( options.left - curOffset.left ) + curLeft;
            }

            if ( "using" in options ) {
                options.using.call( elem, props );

            } else {
                curElem.css( props );
            }
        }
    };

    jQuery.fn.extend( {

        // offset() relates an element's border box to the document origin
        offset: function( options ) {

            // Preserve chaining for setter
            if ( arguments.length ) {
                return options === undefined ?
                    this :
                    this.each( function( i ) {
                        jQuery.offset.setOffset( this, options, i );
                    } );
            }

            var rect, win,
                elem = this[ 0 ];

            if ( !elem ) {
                return;
            }

            // Return zeros for disconnected and hidden (display: none) elements (gh-2310)
            // Support: IE <=11 only
            // Running getBoundingClientRect on a
            // disconnected node in IE throws an error
            if ( !elem.getClientRects().length ) {
                return { top: 0, left: 0 };
            }

            // Get document-relative position by adding viewport scroll to viewport-relative gBCR
            rect = elem.getBoundingClientRect();
            win = elem.ownerDocument.defaultView;
            return {
                top: rect.top + win.pageYOffset,
                left: rect.left + win.pageXOffset
            };
        },

        // position() relates an element's margin box to its offset parent's padding box
        // This corresponds to the behavior of CSS absolute positioning
        position: function() {
            if ( !this[ 0 ] ) {
                return;
            }

            var offsetParent, offset, doc,
                elem = this[ 0 ],
                parentOffset = { top: 0, left: 0 };

            // position:fixed elements are offset from the viewport, which itself always has zero offset
            if ( jQuery.css( elem, "position" ) === "fixed" ) {

                // Assume position:fixed implies availability of getBoundingClientRect
                offset = elem.getBoundingClientRect();

            } else {
                offset = this.offset();

                // Account for the *real* offset parent, which can be the document or its root element
                // when a statically positioned element is identified
                doc = elem.ownerDocument;
                offsetParent = elem.offsetParent || doc.documentElement;
                while ( offsetParent &&
                ( offsetParent === doc.body || offsetParent === doc.documentElement ) &&
                jQuery.css( offsetParent, "position" ) === "static" ) {

                    offsetParent = offsetParent.parentNode;
                }
                if ( offsetParent && offsetParent !== elem && offsetParent.nodeType === 1 ) {

                    // Incorporate borders into its offset, since they are outside its content origin
                    parentOffset = jQuery( offsetParent ).offset();
                    parentOffset.top += jQuery.css( offsetParent, "borderTopWidth", true );
                    parentOffset.left += jQuery.css( offsetParent, "borderLeftWidth", true );
                }
            }

            // Subtract parent offsets and element margins
            return {
                top: offset.top - parentOffset.top - jQuery.css( elem, "marginTop", true ),
                left: offset.left - parentOffset.left - jQuery.css( elem, "marginLeft", true )
            };
        },

        // This method will return documentElement in the following cases:
        // 1) For the element inside the iframe without offsetParent, this method will return
        //    documentElement of the parent window
        // 2) For the hidden or detached element
        // 3) For body or html element, i.e. in case of the html node - it will return itself
        //
        // but those exceptions were never presented as a real life use-cases
        // and might be considered as more preferable results.
        //
        // This logic, however, is not guaranteed and can change at any point in the future
        offsetParent: function() {
            return this.map( function() {
                var offsetParent = this.offsetParent;

                while ( offsetParent && jQuery.css( offsetParent, "position" ) === "static" ) {
                    offsetParent = offsetParent.offsetParent;
                }

                return offsetParent || documentElement;
            } );
        }
    } );

// Create scrollLeft and scrollTop methods
    jQuery.each( { scrollLeft: "pageXOffset", scrollTop: "pageYOffset" }, function( method, prop ) {
        var top = "pageYOffset" === prop;

        jQuery.fn[ method ] = function( val ) {
            return access( this, function( elem, method, val ) {

                // Coalesce documents and windows
                var win;
                if ( isWindow( elem ) ) {
                    win = elem;
                } else if ( elem.nodeType === 9 ) {
                    win = elem.defaultView;
                }

                if ( val === undefined ) {
                    return win ? win[ prop ] : elem[ method ];
                }

                if ( win ) {
                    win.scrollTo(
                        !top ? val : win.pageXOffset,
                        top ? val : win.pageYOffset
                    );

                } else {
                    elem[ method ] = val;
                }
            }, method, val, arguments.length );
        };
    } );

// Support: Safari <=7 - 9.1, Chrome <=37 - 49
// Add the top/left cssHooks using jQuery.fn.position
// Webkit bug: https://bugs.webkit.org/show_bug.cgi?id=29084
// Blink bug: https://bugs.chromium.org/p/chromium/issues/detail?id=589347
// getComputedStyle returns percent when specified for top/left/bottom/right;
// rather than make the css module depend on the offset module, just check for it here
    jQuery.each( [ "top", "left" ], function( _i, prop ) {
        jQuery.cssHooks[ prop ] = addGetHookIf( support.pixelPosition,
            function( elem, computed ) {
                if ( computed ) {
                    computed = curCSS( elem, prop );

                    // If curCSS returns percentage, fallback to offset
                    return rnumnonpx.test( computed ) ?
                        jQuery( elem ).position()[ prop ] + "px" :
                        computed;
                }
            }
        );
    } );


// Create innerHeight, innerWidth, height, width, outerHeight and outerWidth methods
    jQuery.each( { Height: "height", Width: "width" }, function( name, type ) {
        jQuery.each( {
            padding: "inner" + name,
            content: type,
            "": "outer" + name
        }, function( defaultExtra, funcName ) {

            // Margin is only for outerHeight, outerWidth
            jQuery.fn[ funcName ] = function( margin, value ) {
                var chainable = arguments.length && ( defaultExtra || typeof margin !== "boolean" ),
                    extra = defaultExtra || ( margin === true || value === true ? "margin" : "border" );

                return access( this, function( elem, type, value ) {
                    var doc;

                    if ( isWindow( elem ) ) {

                        // $( window ).outerWidth/Height return w/h including scrollbars (gh-1729)
                        return funcName.indexOf( "outer" ) === 0 ?
                            elem[ "inner" + name ] :
                            elem.document.documentElement[ "client" + name ];
                    }

                    // Get document width or height
                    if ( elem.nodeType === 9 ) {
                        doc = elem.documentElement;

                        // Either scroll[Width/Height] or offset[Width/Height] or client[Width/Height],
                        // whichever is greatest
                        return Math.max(
                            elem.body[ "scroll" + name ], doc[ "scroll" + name ],
                            elem.body[ "offset" + name ], doc[ "offset" + name ],
                            doc[ "client" + name ]
                        );
                    }

                    return value === undefined ?

                        // Get width or height on the element, requesting but not forcing parseFloat
                        jQuery.css( elem, type, extra ) :

                        // Set width or height on the element
                        jQuery.style( elem, type, value, extra );
                }, type, chainable ? margin : undefined, chainable );
            };
        } );
    } );


    jQuery.each( [
        "ajaxStart",
        "ajaxStop",
        "ajaxComplete",
        "ajaxError",
        "ajaxSuccess",
        "ajaxSend"
    ], function( _i, type ) {
        jQuery.fn[ type ] = function( fn ) {
            return this.on( type, fn );
        };
    } );




    jQuery.fn.extend( {

        bind: function( types, data, fn ) {
            return this.on( types, null, data, fn );
        },
        unbind: function( types, fn ) {
            return this.off( types, null, fn );
        },

        delegate: function( selector, types, data, fn ) {
            return this.on( types, selector, data, fn );
        },
        undelegate: function( selector, types, fn ) {

            // ( namespace ) or ( selector, types [, fn] )
            return arguments.length === 1 ?
                this.off( selector, "**" ) :
                this.off( types, selector || "**", fn );
        },

        hover: function( fnOver, fnOut ) {
            return this.mouseenter( fnOver ).mouseleave( fnOut || fnOver );
        }
    } );

    jQuery.each(
        ( "blur focus focusin focusout resize scroll click dblclick " +
            "mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave " +
            "change select submit keydown keypress keyup contextmenu" ).split( " " ),
        function( _i, name ) {

            // Handle event binding
            jQuery.fn[ name ] = function( data, fn ) {
                return arguments.length > 0 ?
                    this.on( name, null, data, fn ) :
                    this.trigger( name );
            };
        }
    );




// Support: Android <=4.0 only
// Make sure we trim BOM and NBSP
    var rtrim = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;

// Bind a function to a context, optionally partially applying any
// arguments.
// jQuery.proxy is deprecated to promote standards (specifically Function#bind)
// However, it is not slated for removal any time soon
    jQuery.proxy = function( fn, context ) {
        var tmp, args, proxy;

        if ( typeof context === "string" ) {
            tmp = fn[ context ];
            context = fn;
            fn = tmp;
        }

        // Quick check to determine if target is callable, in the spec
        // this throws a TypeError, but we will just return undefined.
        if ( !isFunction( fn ) ) {
            return undefined;
        }

        // Simulated bind
        args = slice.call( arguments, 2 );
        proxy = function() {
            return fn.apply( context || this, args.concat( slice.call( arguments ) ) );
        };

        // Set the guid of unique handler to the same of original handler, so it can be removed
        proxy.guid = fn.guid = fn.guid || jQuery.guid++;

        return proxy;
    };

    jQuery.holdReady = function( hold ) {
        if ( hold ) {
            jQuery.readyWait++;
        } else {
            jQuery.ready( true );
        }
    };
    jQuery.isArray = Array.isArray;
    jQuery.parseJSON = JSON.parse;
    jQuery.nodeName = nodeName;
    jQuery.isFunction = isFunction;
    jQuery.isWindow = isWindow;
    jQuery.camelCase = camelCase;
    jQuery.type = toType;

    jQuery.now = Date.now;

    jQuery.isNumeric = function( obj ) {

        // As of jQuery 3.0, isNumeric is limited to
        // strings and numbers (primitives or objects)
        // that can be coerced to finite numbers (gh-2662)
        var type = jQuery.type( obj );
        return ( type === "number" || type === "string" ) &&

            // parseFloat NaNs numeric-cast false positives ("")
            // ...but misinterprets leading-number strings, particularly hex literals ("0x...")
            // subtraction forces infinities to NaN
            !isNaN( obj - parseFloat( obj ) );
    };

    jQuery.trim = function( text ) {
        return text == null ?
            "" :
            ( text + "" ).replace( rtrim, "" );
    };



// Register as a named AMD module, since jQuery can be concatenated with other
// files that may use define, but not via a proper concatenation script that
// understands anonymous AMD modules. A named AMD is safest and most robust
// way to register. Lowercase jquery is used because AMD module names are
// derived from file names, and jQuery is normally delivered in a lowercase
// file name. Do this after creating the global so that if an AMD module wants
// to call noConflict to hide this version of jQuery, it will work.

// Note that for maximum portability, libraries that are not jQuery should
// declare themselves as anonymous modules, and avoid setting a global if an
// AMD loader is present. jQuery is a special case. For more information, see
// https://github.com/jrburke/requirejs/wiki/Updating-existing-libraries#wiki-anon

    if ( typeof define === "function" && define.amd ) {
        define( "jquery", [], function() {
            return jQuery;
        } );
    }




    var

        // Map over jQuery in case of overwrite
        _jQuery = window.jQuery,

        // Map over the $ in case of overwrite
        _$ = window.$;

    jQuery.noConflict = function( deep ) {
        if ( window.$ === jQuery ) {
            window.$ = _$;
        }

        if ( deep && window.jQuery === jQuery ) {
            window.jQuery = _jQuery;
        }

        return jQuery;
    };

// Expose jQuery and $ identifiers, even in AMD
// (#7102#comment:10, https://github.com/jquery/jquery/pull/557)
// and CommonJS for browser emulators (#13566)
    if ( typeof noGlobal === "undefined" ) {
        window.jQuery = window.$ = jQuery;
    }




    return jQuery;
} );
/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options = $.extend({}, options); // clone object since it's unexpected behavior if the expired property were changed
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // NOTE Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};
// JQuery URL Parser plugin - https://github.com/allmarkedup/jQuery-URL-Parser
// Written by Mark Perkins, mark@allmarkedup.com
// License: http://unlicense.org/ (i.e. do what you want with it!)

;(function($) {

    var tag2attr = {
        a       : 'href',
        img     : 'src',
        form    : 'action',
        base    : 'href',
        script  : 'src',
        iframe  : 'src',
        link    : 'href'
    },

    key = ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","fragment"], // keys available to query

    aliases = { "anchor" : "fragment" }, // aliases for backwards compatability

    parser = {
        strict  : /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,  //less intuitive, more accurate to the specs
        loose   :  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/ // more intuitive, fails on relative paths and deviates from specs
    },

    querystring_parser = /(?:^|&|;)([^&=;]*)=?([^&;]*)/g, // supports both ampersand and semicolon-delimted query string key/value pairs

    fragment_parser = /(?:^|&|;)([^&=;]*)=?([^&;]*)/g; // supports both ampersand and semicolon-delimted fragment key/value pairs

    function parseUri( url, strictMode )
    {
        var str = decodeURI( url ),
            res   = parser[ strictMode || false ? "strict" : "loose" ].exec( str ),
            uri = { attr : {}, param : {}, seg : {} },
            i   = 14;

        while ( i-- )
        {
            uri.attr[ key[i] ] = res[i] || "";
        }

        // build query and fragment parameters

        uri.param['query'] = {};
        uri.param['fragment'] = {};

        uri.attr['query'].replace( querystring_parser, function ( $0, $1, $2 ){
            if ($1)
            {
                uri.param['query'][$1] = $2;
            }
        });

        uri.attr['fragment'].replace( fragment_parser, function ( $0, $1, $2 ){
            if ($1)
            {
                uri.param['fragment'][$1] = $2;
            }
        });

        // split path and fragement into segments

        uri.seg['path'] = uri.attr.path.replace(/^\/+|\/+$/g,'').split('/');

        uri.seg['fragment'] = uri.attr.fragment.replace(/^\/+|\/+$/g,'').split('/');

        // compile a 'base' domain attribute

        uri.attr['base'] = uri.attr.host ? uri.attr.protocol+"://"+uri.attr.host + (uri.attr.port ? ":"+uri.attr.port : '') : '';

        return uri;
    };

    function getAttrName( elm )
    {
        var tn = elm.tagName;
        if ( tn !== undefined ) return tag2attr[tn.toLowerCase()];
        return tn;
    }

    $.fn.url = function( strictMode )
    {
        var url = '';

        if ( this.length )
        {
            url = $(this).attr( getAttrName(this[0]) ) || '';
        }

        return $.url({ url : url, strict : strictMode });
    };

    $.url = function( opts )
    {
        var url     = '',
            strict  = false;

        if ( typeof opts === 'string' )
        {
            url = opts;
        }
        else
        {
            opts = opts || {};
            strict = opts.strict || strict;
            url = opts.url === undefined ? window.location.toString() : opts.url;
        }

        return {

            data : parseUri(url, strict),

            // get various attributes from the URI
            attr : function( attr )
            {
                attr = aliases[attr] || attr;
                return attr !== undefined ? this.data.attr[attr] : this.data.attr;
            },

            // return query string parameters
            param : function( param )
            {
                return param !== undefined ? this.data.param.query[param] : this.data.param.query;
            },

            // return fragment parameters
            fparam : function( param )
            {
                return param !== undefined ? this.data.param.fragment[param] : this.data.param.fragment;
            },

            // return path segments
            segment : function( seg )
            {
                if ( seg === undefined )
                {
                    return this.data.seg.path;
                }
                else
                {
                    seg = seg < 0 ? this.data.seg.path.length + seg : seg - 1; // negative segments count from the end
                    return this.data.seg.path[seg];
                }
            },

            // return fragment segments
            fsegment : function( seg )
            {
                if ( seg === undefined )
                {
                    return this.data.seg.fragment;
                }
                else
                {
                    seg = seg < 0 ? this.data.seg.fragment.length + seg : seg - 1; // negative segments count from the end
                    return this.data.seg.fragment[seg];
                }
            },

            replace : function( param, value )
            {
                var sParamPattern = eval('/(&|\\?)'+param+'=[a-zA-Z0-9\_\-\|\+\^\#\@\$\%\*\/\;\:]+/');
                var sIsParamPattern = /\?[a-zA-Z0-9_-]+/;
                if (sIsParamPattern.test(url) === true) {
                    if (sParamPattern.test(url) === true) return url.replace(sParamPattern, '$1'+param+'='+value);
                    return url + '&'+param+'='+value;
                } else {
                    if (sParamPattern.test(url) === true) return url.replace(sParamPattern, '$1'+param+'='+value);
                    return url + '?'+param+'='+value;
                }

            }

        };

    };

})(jQuery);
(function($) {
    $.fn.setValue = function(value) {
        if ($(this).is('SELECT') === true) {
            $(this).find('option[value="'+ value +'"]').prop('selected', true);
        } else {
            $(this).val(value);
        }
    };
})(jQuery);
var EC$ = window.jQuery;
(function($, jQuery) {
    // .uuid 복원
    if (typeof jQuery.uuid == 'undefined') {
        jQuery.uuid = 0;
    }

    // .curCSS 복원
    if ( !jQuery.curCSS ) {
        jQuery.curCSS = jQuery.css;
    }

    // .browser 복원
    if ( !$.browser ) {
        $.uaMatch = function( ua ) {
            ua = ua.toLowerCase();

            var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
                /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
                /(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
                /(msie) ([\w.]+)/.exec( ua ) ||
                ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
                [];

            return {
                browser: match[ 1 ] || "",
                version: match[ 2 ] || "0"
            };
        };

        matched = $.uaMatch( navigator.userAgent );
        browser = {};

        if ( matched.browser ) {
            browser[ matched.browser ] = true;
            browser.version = matched.version;
        }

        // Chrome is Webkit, but Webkit is also Safari.
        if ( browser.chrome ) {
            browser.webkit = true;
        } else if ( browser.webkit ) {
            browser.safari = true;
        }

        $.browser = browser;
    }

    // $.escapeSelector Polyfill
    if ( !$.escapeSelector ) {
        $.escapeSelector = function( sel ) {
            var rcssescape = /([\0-\x1f\x7f]|^-?\d)|^-$|[^\0-\x1f\x7f-\uFFFF\w-]/g;
            var fcssescape = function( ch, asCodePoint ) {
                if ( asCodePoint ) {

                    // U+0000 NULL becomes U+FFFD REPLACEMENT CHARACTER
                    if ( ch === "\0" ) {
                        return "\uFFFD";
                    }

                    // Control characters and (dependent upon position) numbers get escaped as code points
                    return ch.slice( 0, -1 ) + "\\" + ch.charCodeAt( ch.length - 1 ).toString( 16 ) + " ";
                }

                // Other potentially-special ASCII characters get backslash-escaped
                return "\\" + ch;
            };

            return ( sel + "" ).replace( rcssescape, fcssescape );
        };
    }
})($, jQuery);
(function(global) {if (global.moment) {global.moment_original = global.moment;}
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):e.moment=t()}(this,function(){"use strict";var H;function _(){return H.apply(null,arguments)}function y(e){return e instanceof Array||"[object Array]"===Object.prototype.toString.call(e)}function F(e){return null!=e&&"[object Object]"===Object.prototype.toString.call(e)}function c(e,t){return Object.prototype.hasOwnProperty.call(e,t)}function L(e){if(Object.getOwnPropertyNames)return 0===Object.getOwnPropertyNames(e).length;for(var t in e)if(c(e,t))return;return 1}function g(e){return void 0===e}function w(e){return"number"==typeof e||"[object Number]"===Object.prototype.toString.call(e)}function V(e){return e instanceof Date||"[object Date]"===Object.prototype.toString.call(e)}function G(e,t){for(var n=[],s=e.length,i=0;i<s;++i)n.push(t(e[i],i));return n}function E(e,t){for(var n in t)c(t,n)&&(e[n]=t[n]);return c(t,"toString")&&(e.toString=t.toString),c(t,"valueOf")&&(e.valueOf=t.valueOf),e}function l(e,t,n,s){return Wt(e,t,n,s,!0).utc()}function p(e){return null==e._pf&&(e._pf={empty:!1,unusedTokens:[],unusedInput:[],overflow:-2,charsLeftOver:0,nullInput:!1,invalidEra:null,invalidMonth:null,invalidFormat:!1,userInvalidated:!1,iso:!1,parsedDateParts:[],era:null,meridiem:null,rfc2822:!1,weekdayMismatch:!1}),e._pf}function A(e){var t,n,s=e._d&&!isNaN(e._d.getTime());return s&&(t=p(e),n=j.call(t.parsedDateParts,function(e){return null!=e}),s=t.overflow<0&&!t.empty&&!t.invalidEra&&!t.invalidMonth&&!t.invalidWeekday&&!t.weekdayMismatch&&!t.nullInput&&!t.invalidFormat&&!t.userInvalidated&&(!t.meridiem||t.meridiem&&n),e._strict)&&(s=s&&0===t.charsLeftOver&&0===t.unusedTokens.length&&void 0===t.bigHour),null!=Object.isFrozen&&Object.isFrozen(e)?s:(e._isValid=s,e._isValid)}function I(e){var t=l(NaN);return null!=e?E(p(t),e):p(t).userInvalidated=!0,t}var j=Array.prototype.some||function(e){for(var t=Object(this),n=t.length>>>0,s=0;s<n;s++)if(s in t&&e.call(this,t[s],s,t))return!0;return!1},Z=_.momentProperties=[],z=!1;function q(e,t){var n,s,i,r=Z.length;if(g(t._isAMomentObject)||(e._isAMomentObject=t._isAMomentObject),g(t._i)||(e._i=t._i),g(t._f)||(e._f=t._f),g(t._l)||(e._l=t._l),g(t._strict)||(e._strict=t._strict),g(t._tzm)||(e._tzm=t._tzm),g(t._isUTC)||(e._isUTC=t._isUTC),g(t._offset)||(e._offset=t._offset),g(t._pf)||(e._pf=p(t)),g(t._locale)||(e._locale=t._locale),0<r)for(n=0;n<r;n++)g(i=t[s=Z[n]])||(e[s]=i);return e}function $(e){q(this,e),this._d=new Date(null!=e._d?e._d.getTime():NaN),this.isValid()||(this._d=new Date(NaN)),!1===z&&(z=!0,_.updateOffset(this),z=!1)}function k(e){return e instanceof $||null!=e&&null!=e._isAMomentObject}function B(e){!1===_.suppressDeprecationWarnings&&"undefined"!=typeof console&&console.warn&&console.warn("Deprecation warning: "+e)}function e(r,a){var o=!0;return E(function(){if(null!=_.deprecationHandler&&_.deprecationHandler(null,r),o){for(var e,t,n=[],s=arguments.length,i=0;i<s;i++){if(e="","object"==typeof arguments[i]){for(t in e+="\n["+i+"] ",arguments[0])c(arguments[0],t)&&(e+=t+": "+arguments[0][t]+", ");e=e.slice(0,-2)}else e=arguments[i];n.push(e)}B(r+"\nArguments: "+Array.prototype.slice.call(n).join("")+"\n"+(new Error).stack),o=!1}return a.apply(this,arguments)},a)}var J={};function Q(e,t){null!=_.deprecationHandler&&_.deprecationHandler(e,t),J[e]||(B(t),J[e]=!0)}function a(e){return"undefined"!=typeof Function&&e instanceof Function||"[object Function]"===Object.prototype.toString.call(e)}function X(e,t){var n,s=E({},e);for(n in t)c(t,n)&&(F(e[n])&&F(t[n])?(s[n]={},E(s[n],e[n]),E(s[n],t[n])):null!=t[n]?s[n]=t[n]:delete s[n]);for(n in e)c(e,n)&&!c(t,n)&&F(e[n])&&(s[n]=E({},s[n]));return s}function K(e){null!=e&&this.set(e)}_.suppressDeprecationWarnings=!1,_.deprecationHandler=null;var ee=Object.keys||function(e){var t,n=[];for(t in e)c(e,t)&&n.push(t);return n};function r(e,t,n){var s=""+Math.abs(e);return(0<=e?n?"+":"":"-")+Math.pow(10,Math.max(0,t-s.length)).toString().substr(1)+s}var te=/(\[[^\[]*\])|(\\)?([Hh]mm(ss)?|Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|Qo?|N{1,5}|YYYYYY|YYYYY|YYYY|YY|y{2,4}|yo?|gg(ggg?)?|GG(GGG?)?|e|E|a|A|hh?|HH?|kk?|mm?|ss?|S{1,9}|x|X|zz?|ZZ?|.)/g,ne=/(\[[^\[]*\])|(\\)?(LTS|LT|LL?L?L?|l{1,4})/g,se={},ie={};function s(e,t,n,s){var i="string"==typeof s?function(){return this[s]()}:s;e&&(ie[e]=i),t&&(ie[t[0]]=function(){return r(i.apply(this,arguments),t[1],t[2])}),n&&(ie[n]=function(){return this.localeData().ordinal(i.apply(this,arguments),e)})}function re(e,t){return e.isValid()?(t=ae(t,e.localeData()),se[t]=se[t]||function(s){for(var e,i=s.match(te),t=0,r=i.length;t<r;t++)ie[i[t]]?i[t]=ie[i[t]]:i[t]=(e=i[t]).match(/\[[\s\S]/)?e.replace(/^\[|\]$/g,""):e.replace(/\\/g,"");return function(e){for(var t="",n=0;n<r;n++)t+=a(i[n])?i[n].call(e,s):i[n];return t}}(t),se[t](e)):e.localeData().invalidDate()}function ae(e,t){var n=5;function s(e){return t.longDateFormat(e)||e}for(ne.lastIndex=0;0<=n&&ne.test(e);)e=e.replace(ne,s),ne.lastIndex=0,--n;return e}var oe={D:"date",dates:"date",date:"date",d:"day",days:"day",day:"day",e:"weekday",weekdays:"weekday",weekday:"weekday",E:"isoWeekday",isoweekdays:"isoWeekday",isoweekday:"isoWeekday",DDD:"dayOfYear",dayofyears:"dayOfYear",dayofyear:"dayOfYear",h:"hour",hours:"hour",hour:"hour",ms:"millisecond",milliseconds:"millisecond",millisecond:"millisecond",m:"minute",minutes:"minute",minute:"minute",M:"month",months:"month",month:"month",Q:"quarter",quarters:"quarter",quarter:"quarter",s:"second",seconds:"second",second:"second",gg:"weekYear",weekyears:"weekYear",weekyear:"weekYear",GG:"isoWeekYear",isoweekyears:"isoWeekYear",isoweekyear:"isoWeekYear",w:"week",weeks:"week",week:"week",W:"isoWeek",isoweeks:"isoWeek",isoweek:"isoWeek",y:"year",years:"year",year:"year"};function o(e){return"string"==typeof e?oe[e]||oe[e.toLowerCase()]:void 0}function ue(e){var t,n,s={};for(n in e)c(e,n)&&(t=o(n))&&(s[t]=e[n]);return s}var le={date:9,day:11,weekday:11,isoWeekday:11,dayOfYear:4,hour:13,millisecond:16,minute:14,month:8,quarter:7,second:15,weekYear:1,isoWeekYear:1,week:5,isoWeek:5,year:1};var de=/\d/,t=/\d\d/,he=/\d{3}/,ce=/\d{4}/,fe=/[+-]?\d{6}/,n=/\d\d?/,me=/\d\d\d\d?/,_e=/\d\d\d\d\d\d?/,ye=/\d{1,3}/,ge=/\d{1,4}/,we=/[+-]?\d{1,6}/,pe=/\d+/,ke=/[+-]?\d+/,Me=/Z|[+-]\d\d:?\d\d/gi,ve=/Z|[+-]\d\d(?::?\d\d)?/gi,i=/[0-9]{0,256}['a-z\u00A0-\u05FF\u0700-\uD7FF\uF900-\uFDCF\uFDF0-\uFF07\uFF10-\uFFEF]{1,256}|[\u0600-\u06FF\/]{1,256}(\s*?[\u0600-\u06FF]{1,256}){1,2}/i,u=/^[1-9]\d?/,d=/^([1-9]\d|\d)/;function h(e,n,s){Ye[e]=a(n)?n:function(e,t){return e&&s?s:n}}function De(e,t){return c(Ye,e)?Ye[e](t._strict,t._locale):new RegExp(f(e.replace("\\","").replace(/\\(\[)|\\(\])|\[([^\]\[]*)\]|\\(.)/g,function(e,t,n,s,i){return t||n||s||i})))}function f(e){return e.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&")}function m(e){return e<0?Math.ceil(e)||0:Math.floor(e)}function M(e){var e=+e,t=0;return t=0!=e&&isFinite(e)?m(e):t}var Ye={},Se={};function v(e,n){var t,s,i=n;for("string"==typeof e&&(e=[e]),w(n)&&(i=function(e,t){t[n]=M(e)}),s=e.length,t=0;t<s;t++)Se[e[t]]=i}function Oe(e,i){v(e,function(e,t,n,s){n._w=n._w||{},i(e,n._w,n,s)})}function be(e){return e%4==0&&e%100!=0||e%400==0}var D=0,Y=1,S=2,O=3,b=4,T=5,Te=6,xe=7,Ne=8;function We(e){return be(e)?366:365}s("Y",0,0,function(){var e=this.year();return e<=9999?r(e,4):"+"+e}),s(0,["YY",2],0,function(){return this.year()%100}),s(0,["YYYY",4],0,"year"),s(0,["YYYYY",5],0,"year"),s(0,["YYYYYY",6,!0],0,"year"),h("Y",ke),h("YY",n,t),h("YYYY",ge,ce),h("YYYYY",we,fe),h("YYYYYY",we,fe),v(["YYYYY","YYYYYY"],D),v("YYYY",function(e,t){t[D]=2===e.length?_.parseTwoDigitYear(e):M(e)}),v("YY",function(e,t){t[D]=_.parseTwoDigitYear(e)}),v("Y",function(e,t){t[D]=parseInt(e,10)}),_.parseTwoDigitYear=function(e){return M(e)+(68<M(e)?1900:2e3)};var x,Pe=Re("FullYear",!0);function Re(t,n){return function(e){return null!=e?(Ue(this,t,e),_.updateOffset(this,n),this):Ce(this,t)}}function Ce(e,t){if(!e.isValid())return NaN;var n=e._d,s=e._isUTC;switch(t){case"Milliseconds":return s?n.getUTCMilliseconds():n.getMilliseconds();case"Seconds":return s?n.getUTCSeconds():n.getSeconds();case"Minutes":return s?n.getUTCMinutes():n.getMinutes();case"Hours":return s?n.getUTCHours():n.getHours();case"Date":return s?n.getUTCDate():n.getDate();case"Day":return s?n.getUTCDay():n.getDay();case"Month":return s?n.getUTCMonth():n.getMonth();case"FullYear":return s?n.getUTCFullYear():n.getFullYear();default:return NaN}}function Ue(e,t,n){var s,i,r;if(e.isValid()&&!isNaN(n)){switch(s=e._d,i=e._isUTC,t){case"Milliseconds":return i?s.setUTCMilliseconds(n):s.setMilliseconds(n);case"Seconds":return i?s.setUTCSeconds(n):s.setSeconds(n);case"Minutes":return i?s.setUTCMinutes(n):s.setMinutes(n);case"Hours":return i?s.setUTCHours(n):s.setHours(n);case"Date":return i?s.setUTCDate(n):s.setDate(n);case"FullYear":break;default:return}t=n,r=e.month(),e=29!==(e=e.date())||1!==r||be(t)?e:28,i?s.setUTCFullYear(t,r,e):s.setFullYear(t,r,e)}}function He(e,t){var n;return isNaN(e)||isNaN(t)?NaN:(n=(t%(n=12)+n)%n,e+=(t-n)/12,1==n?be(e)?29:28:31-n%7%2)}x=Array.prototype.indexOf||function(e){for(var t=0;t<this.length;++t)if(this[t]===e)return t;return-1},s("M",["MM",2],"Mo",function(){return this.month()+1}),s("MMM",0,0,function(e){return this.localeData().monthsShort(this,e)}),s("MMMM",0,0,function(e){return this.localeData().months(this,e)}),h("M",n,u),h("MM",n,t),h("MMM",function(e,t){return t.monthsShortRegex(e)}),h("MMMM",function(e,t){return t.monthsRegex(e)}),v(["M","MM"],function(e,t){t[Y]=M(e)-1}),v(["MMM","MMMM"],function(e,t,n,s){s=n._locale.monthsParse(e,s,n._strict);null!=s?t[Y]=s:p(n).invalidMonth=e});var Fe="January_February_March_April_May_June_July_August_September_October_November_December".split("_"),Le="Jan_Feb_Mar_Apr_May_Jun_Jul_Aug_Sep_Oct_Nov_Dec".split("_"),Ve=/D[oD]?(\[[^\[\]]*\]|\s)+MMMM?/,Ge=i,Ee=i;function Ae(e,t){if(e.isValid()){if("string"==typeof t)if(/^\d+$/.test(t))t=M(t);else if(!w(t=e.localeData().monthsParse(t)))return;var n=(n=e.date())<29?n:Math.min(n,He(e.year(),t));e._isUTC?e._d.setUTCMonth(t,n):e._d.setMonth(t,n)}}function Ie(e){return null!=e?(Ae(this,e),_.updateOffset(this,!0),this):Ce(this,"Month")}function je(){function e(e,t){return t.length-e.length}for(var t,n,s=[],i=[],r=[],a=0;a<12;a++)n=l([2e3,a]),t=f(this.monthsShort(n,"")),n=f(this.months(n,"")),s.push(t),i.push(n),r.push(n),r.push(t);s.sort(e),i.sort(e),r.sort(e),this._monthsRegex=new RegExp("^("+r.join("|")+")","i"),this._monthsShortRegex=this._monthsRegex,this._monthsStrictRegex=new RegExp("^("+i.join("|")+")","i"),this._monthsShortStrictRegex=new RegExp("^("+s.join("|")+")","i")}function Ze(e,t,n,s,i,r,a){var o;return e<100&&0<=e?(o=new Date(e+400,t,n,s,i,r,a),isFinite(o.getFullYear())&&o.setFullYear(e)):o=new Date(e,t,n,s,i,r,a),o}function ze(e){var t;return e<100&&0<=e?((t=Array.prototype.slice.call(arguments))[0]=e+400,t=new Date(Date.UTC.apply(null,t)),isFinite(t.getUTCFullYear())&&t.setUTCFullYear(e)):t=new Date(Date.UTC.apply(null,arguments)),t}function qe(e,t,n){n=7+t-n;return n-(7+ze(e,0,n).getUTCDay()-t)%7-1}function $e(e,t,n,s,i){var r,t=1+7*(t-1)+(7+n-s)%7+qe(e,s,i),n=t<=0?We(r=e-1)+t:t>We(e)?(r=e+1,t-We(e)):(r=e,t);return{year:r,dayOfYear:n}}function Be(e,t,n){var s,i,r=qe(e.year(),t,n),r=Math.floor((e.dayOfYear()-r-1)/7)+1;return r<1?s=r+N(i=e.year()-1,t,n):r>N(e.year(),t,n)?(s=r-N(e.year(),t,n),i=e.year()+1):(i=e.year(),s=r),{week:s,year:i}}function N(e,t,n){var s=qe(e,t,n),t=qe(e+1,t,n);return(We(e)-s+t)/7}s("w",["ww",2],"wo","week"),s("W",["WW",2],"Wo","isoWeek"),h("w",n,u),h("ww",n,t),h("W",n,u),h("WW",n,t),Oe(["w","ww","W","WW"],function(e,t,n,s){t[s.substr(0,1)]=M(e)});function Je(e,t){return e.slice(t,7).concat(e.slice(0,t))}s("d",0,"do","day"),s("dd",0,0,function(e){return this.localeData().weekdaysMin(this,e)}),s("ddd",0,0,function(e){return this.localeData().weekdaysShort(this,e)}),s("dddd",0,0,function(e){return this.localeData().weekdays(this,e)}),s("e",0,0,"weekday"),s("E",0,0,"isoWeekday"),h("d",n),h("e",n),h("E",n),h("dd",function(e,t){return t.weekdaysMinRegex(e)}),h("ddd",function(e,t){return t.weekdaysShortRegex(e)}),h("dddd",function(e,t){return t.weekdaysRegex(e)}),Oe(["dd","ddd","dddd"],function(e,t,n,s){s=n._locale.weekdaysParse(e,s,n._strict);null!=s?t.d=s:p(n).invalidWeekday=e}),Oe(["d","e","E"],function(e,t,n,s){t[s]=M(e)});var Qe="Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),Xe="Sun_Mon_Tue_Wed_Thu_Fri_Sat".split("_"),Ke="Su_Mo_Tu_We_Th_Fr_Sa".split("_"),et=i,tt=i,nt=i;function st(){function e(e,t){return t.length-e.length}for(var t,n,s,i=[],r=[],a=[],o=[],u=0;u<7;u++)s=l([2e3,1]).day(u),t=f(this.weekdaysMin(s,"")),n=f(this.weekdaysShort(s,"")),s=f(this.weekdays(s,"")),i.push(t),r.push(n),a.push(s),o.push(t),o.push(n),o.push(s);i.sort(e),r.sort(e),a.sort(e),o.sort(e),this._weekdaysRegex=new RegExp("^("+o.join("|")+")","i"),this._weekdaysShortRegex=this._weekdaysRegex,this._weekdaysMinRegex=this._weekdaysRegex,this._weekdaysStrictRegex=new RegExp("^("+a.join("|")+")","i"),this._weekdaysShortStrictRegex=new RegExp("^("+r.join("|")+")","i"),this._weekdaysMinStrictRegex=new RegExp("^("+i.join("|")+")","i")}function it(){return this.hours()%12||12}function rt(e,t){s(e,0,0,function(){return this.localeData().meridiem(this.hours(),this.minutes(),t)})}function at(e,t){return t._meridiemParse}s("H",["HH",2],0,"hour"),s("h",["hh",2],0,it),s("k",["kk",2],0,function(){return this.hours()||24}),s("hmm",0,0,function(){return""+it.apply(this)+r(this.minutes(),2)}),s("hmmss",0,0,function(){return""+it.apply(this)+r(this.minutes(),2)+r(this.seconds(),2)}),s("Hmm",0,0,function(){return""+this.hours()+r(this.minutes(),2)}),s("Hmmss",0,0,function(){return""+this.hours()+r(this.minutes(),2)+r(this.seconds(),2)}),rt("a",!0),rt("A",!1),h("a",at),h("A",at),h("H",n,d),h("h",n,u),h("k",n,u),h("HH",n,t),h("hh",n,t),h("kk",n,t),h("hmm",me),h("hmmss",_e),h("Hmm",me),h("Hmmss",_e),v(["H","HH"],O),v(["k","kk"],function(e,t,n){e=M(e);t[O]=24===e?0:e}),v(["a","A"],function(e,t,n){n._isPm=n._locale.isPM(e),n._meridiem=e}),v(["h","hh"],function(e,t,n){t[O]=M(e),p(n).bigHour=!0}),v("hmm",function(e,t,n){var s=e.length-2;t[O]=M(e.substr(0,s)),t[b]=M(e.substr(s)),p(n).bigHour=!0}),v("hmmss",function(e,t,n){var s=e.length-4,i=e.length-2;t[O]=M(e.substr(0,s)),t[b]=M(e.substr(s,2)),t[T]=M(e.substr(i)),p(n).bigHour=!0}),v("Hmm",function(e,t,n){var s=e.length-2;t[O]=M(e.substr(0,s)),t[b]=M(e.substr(s))}),v("Hmmss",function(e,t,n){var s=e.length-4,i=e.length-2;t[O]=M(e.substr(0,s)),t[b]=M(e.substr(s,2)),t[T]=M(e.substr(i))});i=Re("Hours",!0);var ot,ut={calendar:{sameDay:"[Today at] LT",nextDay:"[Tomorrow at] LT",nextWeek:"dddd [at] LT",lastDay:"[Yesterday at] LT",lastWeek:"[Last] dddd [at] LT",sameElse:"L"},longDateFormat:{LTS:"h:mm:ss A",LT:"h:mm A",L:"MM/DD/YYYY",LL:"MMMM D, YYYY",LLL:"MMMM D, YYYY h:mm A",LLLL:"dddd, MMMM D, YYYY h:mm A"},invalidDate:"Invalid date",ordinal:"%d",dayOfMonthOrdinalParse:/\d{1,2}/,relativeTime:{future:"in %s",past:"%s ago",s:"a few seconds",ss:"%d seconds",m:"a minute",mm:"%d minutes",h:"an hour",hh:"%d hours",d:"a day",dd:"%d days",w:"a week",ww:"%d weeks",M:"a month",MM:"%d months",y:"a year",yy:"%d years"},months:Fe,monthsShort:Le,week:{dow:0,doy:6},weekdays:Qe,weekdaysMin:Ke,weekdaysShort:Xe,meridiemParse:/[ap]\.?m?\.?/i},W={},lt={};function dt(e){return e&&e.toLowerCase().replace("_","-")}function ht(e){for(var t,n,s,i,r=0;r<e.length;){for(t=(i=dt(e[r]).split("-")).length,n=(n=dt(e[r+1]))?n.split("-"):null;0<t;){if(s=ct(i.slice(0,t).join("-")))return s;if(n&&n.length>=t&&function(e,t){for(var n=Math.min(e.length,t.length),s=0;s<n;s+=1)if(e[s]!==t[s])return s;return n}(i,n)>=t-1)break;t--}r++}return ot}function ct(t){var e,n;if(void 0===W[t]&&"undefined"!=typeof module&&module&&module.exports&&(n=t)&&n.match("^[^/\\\\]*$"))try{e=ot._abbr,require("./locale/"+t),ft(e)}catch(e){W[t]=null}return W[t]}function ft(e,t){return e&&((t=g(t)?P(e):mt(e,t))?ot=t:"undefined"!=typeof console&&console.warn&&console.warn("Locale "+e+" not found. Did you forget to load it?")),ot._abbr}function mt(e,t){if(null===t)return delete W[e],null;var n,s=ut;if(t.abbr=e,null!=W[e])Q("defineLocaleOverride","use moment.updateLocale(localeName, config) to change an existing locale. moment.defineLocale(localeName, config) should only be used for creating a new locale See http://momentjs.com/guides/#/warnings/define-locale/ for more info."),s=W[e]._config;else if(null!=t.parentLocale)if(null!=W[t.parentLocale])s=W[t.parentLocale]._config;else{if(null==(n=ct(t.parentLocale)))return lt[t.parentLocale]||(lt[t.parentLocale]=[]),lt[t.parentLocale].push({name:e,config:t}),null;s=n._config}return W[e]=new K(X(s,t)),lt[e]&&lt[e].forEach(function(e){mt(e.name,e.config)}),ft(e),W[e]}function P(e){var t;if(!(e=e&&e._locale&&e._locale._abbr?e._locale._abbr:e))return ot;if(!y(e)){if(t=ct(e))return t;e=[e]}return ht(e)}function _t(e){var t=e._a;return t&&-2===p(e).overflow&&(t=t[Y]<0||11<t[Y]?Y:t[S]<1||t[S]>He(t[D],t[Y])?S:t[O]<0||24<t[O]||24===t[O]&&(0!==t[b]||0!==t[T]||0!==t[Te])?O:t[b]<0||59<t[b]?b:t[T]<0||59<t[T]?T:t[Te]<0||999<t[Te]?Te:-1,p(e)._overflowDayOfYear&&(t<D||S<t)&&(t=S),p(e)._overflowWeeks&&-1===t&&(t=xe),p(e)._overflowWeekday&&-1===t&&(t=Ne),p(e).overflow=t),e}var yt=/^\s*((?:[+-]\d{6}|\d{4})-(?:\d\d-\d\d|W\d\d-\d|W\d\d|\d\d\d|\d\d))(?:(T| )(\d\d(?::\d\d(?::\d\d(?:[.,]\d+)?)?)?)([+-]\d\d(?::?\d\d)?|\s*Z)?)?$/,gt=/^\s*((?:[+-]\d{6}|\d{4})(?:\d\d\d\d|W\d\d\d|W\d\d|\d\d\d|\d\d|))(?:(T| )(\d\d(?:\d\d(?:\d\d(?:[.,]\d+)?)?)?)([+-]\d\d(?::?\d\d)?|\s*Z)?)?$/,wt=/Z|[+-]\d\d(?::?\d\d)?/,pt=[["YYYYYY-MM-DD",/[+-]\d{6}-\d\d-\d\d/],["YYYY-MM-DD",/\d{4}-\d\d-\d\d/],["GGGG-[W]WW-E",/\d{4}-W\d\d-\d/],["GGGG-[W]WW",/\d{4}-W\d\d/,!1],["YYYY-DDD",/\d{4}-\d{3}/],["YYYY-MM",/\d{4}-\d\d/,!1],["YYYYYYMMDD",/[+-]\d{10}/],["YYYYMMDD",/\d{8}/],["GGGG[W]WWE",/\d{4}W\d{3}/],["GGGG[W]WW",/\d{4}W\d{2}/,!1],["YYYYDDD",/\d{7}/],["YYYYMM",/\d{6}/,!1],["YYYY",/\d{4}/,!1]],kt=[["HH:mm:ss.SSSS",/\d\d:\d\d:\d\d\.\d+/],["HH:mm:ss,SSSS",/\d\d:\d\d:\d\d,\d+/],["HH:mm:ss",/\d\d:\d\d:\d\d/],["HH:mm",/\d\d:\d\d/],["HHmmss.SSSS",/\d\d\d\d\d\d\.\d+/],["HHmmss,SSSS",/\d\d\d\d\d\d,\d+/],["HHmmss",/\d\d\d\d\d\d/],["HHmm",/\d\d\d\d/],["HH",/\d\d/]],Mt=/^\/?Date\((-?\d+)/i,vt=/^(?:(Mon|Tue|Wed|Thu|Fri|Sat|Sun),?\s)?(\d{1,2})\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s(\d{2,4})\s(\d\d):(\d\d)(?::(\d\d))?\s(?:(UT|GMT|[ECMP][SD]T)|([Zz])|([+-]\d{4}))$/,Dt={UT:0,GMT:0,EDT:-240,EST:-300,CDT:-300,CST:-360,MDT:-360,MST:-420,PDT:-420,PST:-480};function Yt(e){var t,n,s,i,r,a,o=e._i,u=yt.exec(o)||gt.exec(o),o=pt.length,l=kt.length;if(u){for(p(e).iso=!0,t=0,n=o;t<n;t++)if(pt[t][1].exec(u[1])){i=pt[t][0],s=!1!==pt[t][2];break}if(null==i)e._isValid=!1;else{if(u[3]){for(t=0,n=l;t<n;t++)if(kt[t][1].exec(u[3])){r=(u[2]||" ")+kt[t][0];break}if(null==r)return void(e._isValid=!1)}if(s||null==r){if(u[4]){if(!wt.exec(u[4]))return void(e._isValid=!1);a="Z"}e._f=i+(r||"")+(a||""),xt(e)}else e._isValid=!1}}else e._isValid=!1}function St(e,t,n,s,i,r){e=[function(e){e=parseInt(e,10);{if(e<=49)return 2e3+e;if(e<=999)return 1900+e}return e}(e),Le.indexOf(t),parseInt(n,10),parseInt(s,10),parseInt(i,10)];return r&&e.push(parseInt(r,10)),e}function Ot(e){var t,n,s=vt.exec(e._i.replace(/\([^()]*\)|[\n\t]/g," ").replace(/(\s\s+)/g," ").replace(/^\s\s*/,"").replace(/\s\s*$/,""));s?(t=St(s[4],s[3],s[2],s[5],s[6],s[7]),function(e,t,n){if(!e||Xe.indexOf(e)===new Date(t[0],t[1],t[2]).getDay())return 1;p(n).weekdayMismatch=!0,n._isValid=!1}(s[1],t,e)&&(e._a=t,e._tzm=(t=s[8],n=s[9],s=s[10],t?Dt[t]:n?0:60*(((t=parseInt(s,10))-(n=t%100))/100)+n),e._d=ze.apply(null,e._a),e._d.setUTCMinutes(e._d.getUTCMinutes()-e._tzm),p(e).rfc2822=!0)):e._isValid=!1}function bt(e,t,n){return null!=e?e:null!=t?t:n}function Tt(e){var t,n,s,i,r,a,o,u,l,d,h,c=[];if(!e._d){for(s=e,i=new Date(_.now()),n=s._useUTC?[i.getUTCFullYear(),i.getUTCMonth(),i.getUTCDate()]:[i.getFullYear(),i.getMonth(),i.getDate()],e._w&&null==e._a[S]&&null==e._a[Y]&&(null!=(i=(s=e)._w).GG||null!=i.W||null!=i.E?(u=1,l=4,r=bt(i.GG,s._a[D],Be(R(),1,4).year),a=bt(i.W,1),((o=bt(i.E,1))<1||7<o)&&(d=!0)):(u=s._locale._week.dow,l=s._locale._week.doy,h=Be(R(),u,l),r=bt(i.gg,s._a[D],h.year),a=bt(i.w,h.week),null!=i.d?((o=i.d)<0||6<o)&&(d=!0):null!=i.e?(o=i.e+u,(i.e<0||6<i.e)&&(d=!0)):o=u),a<1||a>N(r,u,l)?p(s)._overflowWeeks=!0:null!=d?p(s)._overflowWeekday=!0:(h=$e(r,a,o,u,l),s._a[D]=h.year,s._dayOfYear=h.dayOfYear)),null!=e._dayOfYear&&(i=bt(e._a[D],n[D]),(e._dayOfYear>We(i)||0===e._dayOfYear)&&(p(e)._overflowDayOfYear=!0),d=ze(i,0,e._dayOfYear),e._a[Y]=d.getUTCMonth(),e._a[S]=d.getUTCDate()),t=0;t<3&&null==e._a[t];++t)e._a[t]=c[t]=n[t];for(;t<7;t++)e._a[t]=c[t]=null==e._a[t]?2===t?1:0:e._a[t];24===e._a[O]&&0===e._a[b]&&0===e._a[T]&&0===e._a[Te]&&(e._nextDay=!0,e._a[O]=0),e._d=(e._useUTC?ze:Ze).apply(null,c),r=e._useUTC?e._d.getUTCDay():e._d.getDay(),null!=e._tzm&&e._d.setUTCMinutes(e._d.getUTCMinutes()-e._tzm),e._nextDay&&(e._a[O]=24),e._w&&void 0!==e._w.d&&e._w.d!==r&&(p(e).weekdayMismatch=!0)}}function xt(e){if(e._f===_.ISO_8601)Yt(e);else if(e._f===_.RFC_2822)Ot(e);else{e._a=[],p(e).empty=!0;for(var t,n,s,i,r,a=""+e._i,o=a.length,u=0,l=ae(e._f,e._locale).match(te)||[],d=l.length,h=0;h<d;h++)n=l[h],(t=(a.match(De(n,e))||[])[0])&&(0<(s=a.substr(0,a.indexOf(t))).length&&p(e).unusedInput.push(s),a=a.slice(a.indexOf(t)+t.length),u+=t.length),ie[n]?(t?p(e).empty=!1:p(e).unusedTokens.push(n),s=n,r=e,null!=(i=t)&&c(Se,s)&&Se[s](i,r._a,r,s)):e._strict&&!t&&p(e).unusedTokens.push(n);p(e).charsLeftOver=o-u,0<a.length&&p(e).unusedInput.push(a),e._a[O]<=12&&!0===p(e).bigHour&&0<e._a[O]&&(p(e).bigHour=void 0),p(e).parsedDateParts=e._a.slice(0),p(e).meridiem=e._meridiem,e._a[O]=function(e,t,n){if(null==n)return t;return null!=e.meridiemHour?e.meridiemHour(t,n):null!=e.isPM?((e=e.isPM(n))&&t<12&&(t+=12),t=e||12!==t?t:0):t}(e._locale,e._a[O],e._meridiem),null!==(o=p(e).era)&&(e._a[D]=e._locale.erasConvertYear(o,e._a[D])),Tt(e),_t(e)}}function Nt(e){var t,n,s,i=e._i,r=e._f;if(e._locale=e._locale||P(e._l),null===i||void 0===r&&""===i)return I({nullInput:!0});if("string"==typeof i&&(e._i=i=e._locale.preparse(i)),k(i))return new $(_t(i));if(V(i))e._d=i;else if(y(r)){var a,o,u,l,d,h,c=e,f=!1,m=c._f.length;if(0===m)p(c).invalidFormat=!0,c._d=new Date(NaN);else{for(l=0;l<m;l++)d=0,h=!1,a=q({},c),null!=c._useUTC&&(a._useUTC=c._useUTC),a._f=c._f[l],xt(a),A(a)&&(h=!0),d=(d+=p(a).charsLeftOver)+10*p(a).unusedTokens.length,p(a).score=d,f?d<u&&(u=d,o=a):(null==u||d<u||h)&&(u=d,o=a,h)&&(f=!0);E(c,o||a)}}else if(r)xt(e);else if(g(r=(i=e)._i))i._d=new Date(_.now());else V(r)?i._d=new Date(r.valueOf()):"string"==typeof r?(n=i,null!==(t=Mt.exec(n._i))?n._d=new Date(+t[1]):(Yt(n),!1===n._isValid&&(delete n._isValid,Ot(n),!1===n._isValid)&&(delete n._isValid,n._strict?n._isValid=!1:_.createFromInputFallback(n)))):y(r)?(i._a=G(r.slice(0),function(e){return parseInt(e,10)}),Tt(i)):F(r)?(t=i)._d||(s=void 0===(n=ue(t._i)).day?n.date:n.day,t._a=G([n.year,n.month,s,n.hour,n.minute,n.second,n.millisecond],function(e){return e&&parseInt(e,10)}),Tt(t)):w(r)?i._d=new Date(r):_.createFromInputFallback(i);return A(e)||(e._d=null),e}function Wt(e,t,n,s,i){var r={};return!0!==t&&!1!==t||(s=t,t=void 0),!0!==n&&!1!==n||(s=n,n=void 0),(F(e)&&L(e)||y(e)&&0===e.length)&&(e=void 0),r._isAMomentObject=!0,r._useUTC=r._isUTC=i,r._l=n,r._i=e,r._f=t,r._strict=s,(i=new $(_t(Nt(i=r))))._nextDay&&(i.add(1,"d"),i._nextDay=void 0),i}function R(e,t,n,s){return Wt(e,t,n,s,!1)}_.createFromInputFallback=e("value provided is not in a recognized RFC2822 or ISO format. moment construction falls back to js Date(), which is not reliable across all browsers and versions. Non RFC2822/ISO date formats are discouraged. Please refer to http://momentjs.com/guides/#/warnings/js-date/ for more info.",function(e){e._d=new Date(e._i+(e._useUTC?" UTC":""))}),_.ISO_8601=function(){},_.RFC_2822=function(){};me=e("moment().min is deprecated, use moment.max instead. http://momentjs.com/guides/#/warnings/min-max/",function(){var e=R.apply(null,arguments);return this.isValid()&&e.isValid()?e<this?this:e:I()}),_e=e("moment().max is deprecated, use moment.min instead. http://momentjs.com/guides/#/warnings/min-max/",function(){var e=R.apply(null,arguments);return this.isValid()&&e.isValid()?this<e?this:e:I()});function Pt(e,t){var n,s;if(!(t=1===t.length&&y(t[0])?t[0]:t).length)return R();for(n=t[0],s=1;s<t.length;++s)t[s].isValid()&&!t[s][e](n)||(n=t[s]);return n}var Rt=["year","quarter","month","week","day","hour","minute","second","millisecond"];function Ct(e){var e=ue(e),t=e.year||0,n=e.quarter||0,s=e.month||0,i=e.week||e.isoWeek||0,r=e.day||0,a=e.hour||0,o=e.minute||0,u=e.second||0,l=e.millisecond||0;this._isValid=function(e){var t,n,s=!1,i=Rt.length;for(t in e)if(c(e,t)&&(-1===x.call(Rt,t)||null!=e[t]&&isNaN(e[t])))return!1;for(n=0;n<i;++n)if(e[Rt[n]]){if(s)return!1;parseFloat(e[Rt[n]])!==M(e[Rt[n]])&&(s=!0)}return!0}(e),this._milliseconds=+l+1e3*u+6e4*o+1e3*a*60*60,this._days=+r+7*i,this._months=+s+3*n+12*t,this._data={},this._locale=P(),this._bubble()}function Ut(e){return e instanceof Ct}function Ht(e){return e<0?-1*Math.round(-1*e):Math.round(e)}function Ft(e,n){s(e,0,0,function(){var e=this.utcOffset(),t="+";return e<0&&(e=-e,t="-"),t+r(~~(e/60),2)+n+r(~~e%60,2)})}Ft("Z",":"),Ft("ZZ",""),h("Z",ve),h("ZZ",ve),v(["Z","ZZ"],function(e,t,n){n._useUTC=!0,n._tzm=Vt(ve,e)});var Lt=/([\+\-]|\d\d)/gi;function Vt(e,t){var t=(t||"").match(e);return null===t?null:0===(t=60*(e=((t[t.length-1]||[])+"").match(Lt)||["-",0,0])[1]+M(e[2]))?0:"+"===e[0]?t:-t}function Gt(e,t){var n;return t._isUTC?(t=t.clone(),n=(k(e)||V(e)?e:R(e)).valueOf()-t.valueOf(),t._d.setTime(t._d.valueOf()+n),_.updateOffset(t,!1),t):R(e).local()}function Et(e){return-Math.round(e._d.getTimezoneOffset())}function At(){return!!this.isValid()&&this._isUTC&&0===this._offset}_.updateOffset=function(){};var It=/^(-|\+)?(?:(\d*)[. ])?(\d+):(\d+)(?::(\d+)(\.\d*)?)?$/,jt=/^(-|\+)?P(?:([-+]?[0-9,.]*)Y)?(?:([-+]?[0-9,.]*)M)?(?:([-+]?[0-9,.]*)W)?(?:([-+]?[0-9,.]*)D)?(?:T(?:([-+]?[0-9,.]*)H)?(?:([-+]?[0-9,.]*)M)?(?:([-+]?[0-9,.]*)S)?)?$/;function C(e,t){var n,s=e;return Ut(e)?s={ms:e._milliseconds,d:e._days,M:e._months}:w(e)||!isNaN(+e)?(s={},t?s[t]=+e:s.milliseconds=+e):(t=It.exec(e))?(n="-"===t[1]?-1:1,s={y:0,d:M(t[S])*n,h:M(t[O])*n,m:M(t[b])*n,s:M(t[T])*n,ms:M(Ht(1e3*t[Te]))*n}):(t=jt.exec(e))?(n="-"===t[1]?-1:1,s={y:Zt(t[2],n),M:Zt(t[3],n),w:Zt(t[4],n),d:Zt(t[5],n),h:Zt(t[6],n),m:Zt(t[7],n),s:Zt(t[8],n)}):null==s?s={}:"object"==typeof s&&("from"in s||"to"in s)&&(t=function(e,t){var n;if(!e.isValid()||!t.isValid())return{milliseconds:0,months:0};t=Gt(t,e),e.isBefore(t)?n=zt(e,t):((n=zt(t,e)).milliseconds=-n.milliseconds,n.months=-n.months);return n}(R(s.from),R(s.to)),(s={}).ms=t.milliseconds,s.M=t.months),n=new Ct(s),Ut(e)&&c(e,"_locale")&&(n._locale=e._locale),Ut(e)&&c(e,"_isValid")&&(n._isValid=e._isValid),n}function Zt(e,t){e=e&&parseFloat(e.replace(",","."));return(isNaN(e)?0:e)*t}function zt(e,t){var n={};return n.months=t.month()-e.month()+12*(t.year()-e.year()),e.clone().add(n.months,"M").isAfter(t)&&--n.months,n.milliseconds=+t-+e.clone().add(n.months,"M"),n}function qt(s,i){return function(e,t){var n;return null===t||isNaN(+t)||(Q(i,"moment()."+i+"(period, number) is deprecated. Please use moment()."+i+"(number, period). See http://momentjs.com/guides/#/warnings/add-inverted-param/ for more info."),n=e,e=t,t=n),$t(this,C(e,t),s),this}}function $t(e,t,n,s){var i=t._milliseconds,r=Ht(t._days),t=Ht(t._months);e.isValid()&&(s=null==s||s,t&&Ae(e,Ce(e,"Month")+t*n),r&&Ue(e,"Date",Ce(e,"Date")+r*n),i&&e._d.setTime(e._d.valueOf()+i*n),s)&&_.updateOffset(e,r||t)}C.fn=Ct.prototype,C.invalid=function(){return C(NaN)};Fe=qt(1,"add"),Qe=qt(-1,"subtract");function Bt(e){return"string"==typeof e||e instanceof String}function Jt(e){return k(e)||V(e)||Bt(e)||w(e)||function(t){var e=y(t),n=!1;e&&(n=0===t.filter(function(e){return!w(e)&&Bt(t)}).length);return e&&n}(e)||function(e){var t,n,s=F(e)&&!L(e),i=!1,r=["years","year","y","months","month","M","days","day","d","dates","date","D","hours","hour","h","minutes","minute","m","seconds","second","s","milliseconds","millisecond","ms"],a=r.length;for(t=0;t<a;t+=1)n=r[t],i=i||c(e,n);return s&&i}(e)||null==e}function Qt(e,t){var n,s;return e.date()<t.date()?-Qt(t,e):-((n=12*(t.year()-e.year())+(t.month()-e.month()))+(t-(s=e.clone().add(n,"months"))<0?(t-s)/(s-e.clone().add(n-1,"months")):(t-s)/(e.clone().add(1+n,"months")-s)))||0}function Xt(e){return void 0===e?this._locale._abbr:(null!=(e=P(e))&&(this._locale=e),this)}_.defaultFormat="YYYY-MM-DDTHH:mm:ssZ",_.defaultFormatUtc="YYYY-MM-DDTHH:mm:ss[Z]";Ke=e("moment().lang() is deprecated. Instead, use moment().localeData() to get the language configuration. Use moment().locale() to change languages.",function(e){return void 0===e?this.localeData():this.locale(e)});function Kt(){return this._locale}var en=126227808e5;function tn(e,t){return(e%t+t)%t}function nn(e,t,n){return e<100&&0<=e?new Date(e+400,t,n)-en:new Date(e,t,n).valueOf()}function sn(e,t,n){return e<100&&0<=e?Date.UTC(e+400,t,n)-en:Date.UTC(e,t,n)}function rn(e,t){return t.erasAbbrRegex(e)}function an(){for(var e,t,n,s=[],i=[],r=[],a=[],o=this.eras(),u=0,l=o.length;u<l;++u)e=f(o[u].name),t=f(o[u].abbr),n=f(o[u].narrow),i.push(e),s.push(t),r.push(n),a.push(e),a.push(t),a.push(n);this._erasRegex=new RegExp("^("+a.join("|")+")","i"),this._erasNameRegex=new RegExp("^("+i.join("|")+")","i"),this._erasAbbrRegex=new RegExp("^("+s.join("|")+")","i"),this._erasNarrowRegex=new RegExp("^("+r.join("|")+")","i")}function on(e,t){s(0,[e,e.length],0,t)}function un(e,t,n,s,i){var r;return null==e?Be(this,s,i).year:(r=N(e,s,i),function(e,t,n,s,i){e=$e(e,t,n,s,i),t=ze(e.year,0,e.dayOfYear);return this.year(t.getUTCFullYear()),this.month(t.getUTCMonth()),this.date(t.getUTCDate()),this}.call(this,e,t=r<t?r:t,n,s,i))}s("N",0,0,"eraAbbr"),s("NN",0,0,"eraAbbr"),s("NNN",0,0,"eraAbbr"),s("NNNN",0,0,"eraName"),s("NNNNN",0,0,"eraNarrow"),s("y",["y",1],"yo","eraYear"),s("y",["yy",2],0,"eraYear"),s("y",["yyy",3],0,"eraYear"),s("y",["yyyy",4],0,"eraYear"),h("N",rn),h("NN",rn),h("NNN",rn),h("NNNN",function(e,t){return t.erasNameRegex(e)}),h("NNNNN",function(e,t){return t.erasNarrowRegex(e)}),v(["N","NN","NNN","NNNN","NNNNN"],function(e,t,n,s){s=n._locale.erasParse(e,s,n._strict);s?p(n).era=s:p(n).invalidEra=e}),h("y",pe),h("yy",pe),h("yyy",pe),h("yyyy",pe),h("yo",function(e,t){return t._eraYearOrdinalRegex||pe}),v(["y","yy","yyy","yyyy"],D),v(["yo"],function(e,t,n,s){var i;n._locale._eraYearOrdinalRegex&&(i=e.match(n._locale._eraYearOrdinalRegex)),n._locale.eraYearOrdinalParse?t[D]=n._locale.eraYearOrdinalParse(e,i):t[D]=parseInt(e,10)}),s(0,["gg",2],0,function(){return this.weekYear()%100}),s(0,["GG",2],0,function(){return this.isoWeekYear()%100}),on("gggg","weekYear"),on("ggggg","weekYear"),on("GGGG","isoWeekYear"),on("GGGGG","isoWeekYear"),h("G",ke),h("g",ke),h("GG",n,t),h("gg",n,t),h("GGGG",ge,ce),h("gggg",ge,ce),h("GGGGG",we,fe),h("ggggg",we,fe),Oe(["gggg","ggggg","GGGG","GGGGG"],function(e,t,n,s){t[s.substr(0,2)]=M(e)}),Oe(["gg","GG"],function(e,t,n,s){t[s]=_.parseTwoDigitYear(e)}),s("Q",0,"Qo","quarter"),h("Q",de),v("Q",function(e,t){t[Y]=3*(M(e)-1)}),s("D",["DD",2],"Do","date"),h("D",n,u),h("DD",n,t),h("Do",function(e,t){return e?t._dayOfMonthOrdinalParse||t._ordinalParse:t._dayOfMonthOrdinalParseLenient}),v(["D","DD"],S),v("Do",function(e,t){t[S]=M(e.match(n)[0])});ge=Re("Date",!0);s("DDD",["DDDD",3],"DDDo","dayOfYear"),h("DDD",ye),h("DDDD",he),v(["DDD","DDDD"],function(e,t,n){n._dayOfYear=M(e)}),s("m",["mm",2],0,"minute"),h("m",n,d),h("mm",n,t),v(["m","mm"],b);var ln,ce=Re("Minutes",!1),we=(s("s",["ss",2],0,"second"),h("s",n,d),h("ss",n,t),v(["s","ss"],T),Re("Seconds",!1));for(s("S",0,0,function(){return~~(this.millisecond()/100)}),s(0,["SS",2],0,function(){return~~(this.millisecond()/10)}),s(0,["SSS",3],0,"millisecond"),s(0,["SSSS",4],0,function(){return 10*this.millisecond()}),s(0,["SSSSS",5],0,function(){return 100*this.millisecond()}),s(0,["SSSSSS",6],0,function(){return 1e3*this.millisecond()}),s(0,["SSSSSSS",7],0,function(){return 1e4*this.millisecond()}),s(0,["SSSSSSSS",8],0,function(){return 1e5*this.millisecond()}),s(0,["SSSSSSSSS",9],0,function(){return 1e6*this.millisecond()}),h("S",ye,de),h("SS",ye,t),h("SSS",ye,he),ln="SSSS";ln.length<=9;ln+="S")h(ln,pe);function dn(e,t){t[Te]=M(1e3*("0."+e))}for(ln="S";ln.length<=9;ln+="S")v(ln,dn);fe=Re("Milliseconds",!1),s("z",0,0,"zoneAbbr"),s("zz",0,0,"zoneName");u=$.prototype;function hn(e){return e}u.add=Fe,u.calendar=function(e,t){1===arguments.length&&(arguments[0]?Jt(arguments[0])?(e=arguments[0],t=void 0):function(e){for(var t=F(e)&&!L(e),n=!1,s=["sameDay","nextDay","lastDay","nextWeek","lastWeek","sameElse"],i=0;i<s.length;i+=1)n=n||c(e,s[i]);return t&&n}(arguments[0])&&(t=arguments[0],e=void 0):t=e=void 0);var e=e||R(),n=Gt(e,this).startOf("day"),n=_.calendarFormat(this,n)||"sameElse",t=t&&(a(t[n])?t[n].call(this,e):t[n]);return this.format(t||this.localeData().calendar(n,this,R(e)))},u.clone=function(){return new $(this)},u.diff=function(e,t,n){var s,i,r;if(!this.isValid())return NaN;if(!(s=Gt(e,this)).isValid())return NaN;switch(i=6e4*(s.utcOffset()-this.utcOffset()),t=o(t)){case"year":r=Qt(this,s)/12;break;case"month":r=Qt(this,s);break;case"quarter":r=Qt(this,s)/3;break;case"second":r=(this-s)/1e3;break;case"minute":r=(this-s)/6e4;break;case"hour":r=(this-s)/36e5;break;case"day":r=(this-s-i)/864e5;break;case"week":r=(this-s-i)/6048e5;break;default:r=this-s}return n?r:m(r)},u.endOf=function(e){var t,n;if(void 0!==(e=o(e))&&"millisecond"!==e&&this.isValid()){switch(n=this._isUTC?sn:nn,e){case"year":t=n(this.year()+1,0,1)-1;break;case"quarter":t=n(this.year(),this.month()-this.month()%3+3,1)-1;break;case"month":t=n(this.year(),this.month()+1,1)-1;break;case"week":t=n(this.year(),this.month(),this.date()-this.weekday()+7)-1;break;case"isoWeek":t=n(this.year(),this.month(),this.date()-(this.isoWeekday()-1)+7)-1;break;case"day":case"date":t=n(this.year(),this.month(),this.date()+1)-1;break;case"hour":t=this._d.valueOf(),t+=36e5-tn(t+(this._isUTC?0:6e4*this.utcOffset()),36e5)-1;break;case"minute":t=this._d.valueOf(),t+=6e4-tn(t,6e4)-1;break;case"second":t=this._d.valueOf(),t+=1e3-tn(t,1e3)-1;break}this._d.setTime(t),_.updateOffset(this,!0)}return this},u.format=function(e){return e=e||(this.isUtc()?_.defaultFormatUtc:_.defaultFormat),e=re(this,e),this.localeData().postformat(e)},u.from=function(e,t){return this.isValid()&&(k(e)&&e.isValid()||R(e).isValid())?C({to:this,from:e}).locale(this.locale()).humanize(!t):this.localeData().invalidDate()},u.fromNow=function(e){return this.from(R(),e)},u.to=function(e,t){return this.isValid()&&(k(e)&&e.isValid()||R(e).isValid())?C({from:this,to:e}).locale(this.locale()).humanize(!t):this.localeData().invalidDate()},u.toNow=function(e){return this.to(R(),e)},u.get=function(e){return a(this[e=o(e)])?this[e]():this},u.invalidAt=function(){return p(this).overflow},u.isAfter=function(e,t){return e=k(e)?e:R(e),!(!this.isValid()||!e.isValid())&&("millisecond"===(t=o(t)||"millisecond")?this.valueOf()>e.valueOf():e.valueOf()<this.clone().startOf(t).valueOf())},u.isBefore=function(e,t){return e=k(e)?e:R(e),!(!this.isValid()||!e.isValid())&&("millisecond"===(t=o(t)||"millisecond")?this.valueOf()<e.valueOf():this.clone().endOf(t).valueOf()<e.valueOf())},u.isBetween=function(e,t,n,s){return e=k(e)?e:R(e),t=k(t)?t:R(t),!!(this.isValid()&&e.isValid()&&t.isValid())&&("("===(s=s||"()")[0]?this.isAfter(e,n):!this.isBefore(e,n))&&(")"===s[1]?this.isBefore(t,n):!this.isAfter(t,n))},u.isSame=function(e,t){var e=k(e)?e:R(e);return!(!this.isValid()||!e.isValid())&&("millisecond"===(t=o(t)||"millisecond")?this.valueOf()===e.valueOf():(e=e.valueOf(),this.clone().startOf(t).valueOf()<=e&&e<=this.clone().endOf(t).valueOf()))},u.isSameOrAfter=function(e,t){return this.isSame(e,t)||this.isAfter(e,t)},u.isSameOrBefore=function(e,t){return this.isSame(e,t)||this.isBefore(e,t)},u.isValid=function(){return A(this)},u.lang=Ke,u.locale=Xt,u.localeData=Kt,u.max=_e,u.min=me,u.parsingFlags=function(){return E({},p(this))},u.set=function(e,t){if("object"==typeof e)for(var n=function(e){var t,n=[];for(t in e)c(e,t)&&n.push({unit:t,priority:le[t]});return n.sort(function(e,t){return e.priority-t.priority}),n}(e=ue(e)),s=n.length,i=0;i<s;i++)this[n[i].unit](e[n[i].unit]);else if(a(this[e=o(e)]))return this[e](t);return this},u.startOf=function(e){var t,n;if(void 0!==(e=o(e))&&"millisecond"!==e&&this.isValid()){switch(n=this._isUTC?sn:nn,e){case"year":t=n(this.year(),0,1);break;case"quarter":t=n(this.year(),this.month()-this.month()%3,1);break;case"month":t=n(this.year(),this.month(),1);break;case"week":t=n(this.year(),this.month(),this.date()-this.weekday());break;case"isoWeek":t=n(this.year(),this.month(),this.date()-(this.isoWeekday()-1));break;case"day":case"date":t=n(this.year(),this.month(),this.date());break;case"hour":t=this._d.valueOf(),t-=tn(t+(this._isUTC?0:6e4*this.utcOffset()),36e5);break;case"minute":t=this._d.valueOf(),t-=tn(t,6e4);break;case"second":t=this._d.valueOf(),t-=tn(t,1e3);break}this._d.setTime(t),_.updateOffset(this,!0)}return this},u.subtract=Qe,u.toArray=function(){var e=this;return[e.year(),e.month(),e.date(),e.hour(),e.minute(),e.second(),e.millisecond()]},u.toObject=function(){var e=this;return{years:e.year(),months:e.month(),date:e.date(),hours:e.hours(),minutes:e.minutes(),seconds:e.seconds(),milliseconds:e.milliseconds()}},u.toDate=function(){return new Date(this.valueOf())},u.toISOString=function(e){var t;return this.isValid()?(t=(e=!0!==e)?this.clone().utc():this).year()<0||9999<t.year()?re(t,e?"YYYYYY-MM-DD[T]HH:mm:ss.SSS[Z]":"YYYYYY-MM-DD[T]HH:mm:ss.SSSZ"):a(Date.prototype.toISOString)?e?this.toDate().toISOString():new Date(this.valueOf()+60*this.utcOffset()*1e3).toISOString().replace("Z",re(t,"Z")):re(t,e?"YYYY-MM-DD[T]HH:mm:ss.SSS[Z]":"YYYY-MM-DD[T]HH:mm:ss.SSSZ"):null},u.inspect=function(){var e,t,n;return this.isValid()?(t="moment",e="",this.isLocal()||(t=0===this.utcOffset()?"moment.utc":"moment.parseZone",e="Z"),t="["+t+'("]',n=0<=this.year()&&this.year()<=9999?"YYYY":"YYYYYY",this.format(t+n+"-MM-DD[T]HH:mm:ss.SSS"+(e+'[")]'))):"moment.invalid(/* "+this._i+" */)"},"undefined"!=typeof Symbol&&null!=Symbol.for&&(u[Symbol.for("nodejs.util.inspect.custom")]=function(){return"Moment<"+this.format()+">"}),u.toJSON=function(){return this.isValid()?this.toISOString():null},u.toString=function(){return this.clone().locale("en").format("ddd MMM DD YYYY HH:mm:ss [GMT]ZZ")},u.unix=function(){return Math.floor(this.valueOf()/1e3)},u.valueOf=function(){return this._d.valueOf()-6e4*(this._offset||0)},u.creationData=function(){return{input:this._i,format:this._f,locale:this._locale,isUTC:this._isUTC,strict:this._strict}},u.eraName=function(){for(var e,t=this.localeData().eras(),n=0,s=t.length;n<s;++n){if(e=this.clone().startOf("day").valueOf(),t[n].since<=e&&e<=t[n].until)return t[n].name;if(t[n].until<=e&&e<=t[n].since)return t[n].name}return""},u.eraNarrow=function(){for(var e,t=this.localeData().eras(),n=0,s=t.length;n<s;++n){if(e=this.clone().startOf("day").valueOf(),t[n].since<=e&&e<=t[n].until)return t[n].narrow;if(t[n].until<=e&&e<=t[n].since)return t[n].narrow}return""},u.eraAbbr=function(){for(var e,t=this.localeData().eras(),n=0,s=t.length;n<s;++n){if(e=this.clone().startOf("day").valueOf(),t[n].since<=e&&e<=t[n].until)return t[n].abbr;if(t[n].until<=e&&e<=t[n].since)return t[n].abbr}return""},u.eraYear=function(){for(var e,t,n=this.localeData().eras(),s=0,i=n.length;s<i;++s)if(e=n[s].since<=n[s].until?1:-1,t=this.clone().startOf("day").valueOf(),n[s].since<=t&&t<=n[s].until||n[s].until<=t&&t<=n[s].since)return(this.year()-_(n[s].since).year())*e+n[s].offset;return this.year()},u.year=Pe,u.isLeapYear=function(){return be(this.year())},u.weekYear=function(e){return un.call(this,e,this.week(),this.weekday()+this.localeData()._week.dow,this.localeData()._week.dow,this.localeData()._week.doy)},u.isoWeekYear=function(e){return un.call(this,e,this.isoWeek(),this.isoWeekday(),1,4)},u.quarter=u.quarters=function(e){return null==e?Math.ceil((this.month()+1)/3):this.month(3*(e-1)+this.month()%3)},u.month=Ie,u.daysInMonth=function(){return He(this.year(),this.month())},u.week=u.weeks=function(e){var t=this.localeData().week(this);return null==e?t:this.add(7*(e-t),"d")},u.isoWeek=u.isoWeeks=function(e){var t=Be(this,1,4).week;return null==e?t:this.add(7*(e-t),"d")},u.weeksInYear=function(){var e=this.localeData()._week;return N(this.year(),e.dow,e.doy)},u.weeksInWeekYear=function(){var e=this.localeData()._week;return N(this.weekYear(),e.dow,e.doy)},u.isoWeeksInYear=function(){return N(this.year(),1,4)},u.isoWeeksInISOWeekYear=function(){return N(this.isoWeekYear(),1,4)},u.date=ge,u.day=u.days=function(e){var t,n,s;return this.isValid()?(t=Ce(this,"Day"),null!=e?(n=e,s=this.localeData(),e="string"!=typeof n?n:isNaN(n)?"number"==typeof(n=s.weekdaysParse(n))?n:null:parseInt(n,10),this.add(e-t,"d")):t):null!=e?this:NaN},u.weekday=function(e){var t;return this.isValid()?(t=(this.day()+7-this.localeData()._week.dow)%7,null==e?t:this.add(e-t,"d")):null!=e?this:NaN},u.isoWeekday=function(e){var t,n;return this.isValid()?null!=e?(t=e,n=this.localeData(),n="string"==typeof t?n.weekdaysParse(t)%7||7:isNaN(t)?null:t,this.day(this.day()%7?n:n-7)):this.day()||7:null!=e?this:NaN},u.dayOfYear=function(e){var t=Math.round((this.clone().startOf("day")-this.clone().startOf("year"))/864e5)+1;return null==e?t:this.add(e-t,"d")},u.hour=u.hours=i,u.minute=u.minutes=ce,u.second=u.seconds=we,u.millisecond=u.milliseconds=fe,u.utcOffset=function(e,t,n){var s,i=this._offset||0;if(!this.isValid())return null!=e?this:NaN;if(null==e)return this._isUTC?i:Et(this);if("string"==typeof e){if(null===(e=Vt(ve,e)))return this}else Math.abs(e)<16&&!n&&(e*=60);return!this._isUTC&&t&&(s=Et(this)),this._offset=e,this._isUTC=!0,null!=s&&this.add(s,"m"),i!==e&&(!t||this._changeInProgress?$t(this,C(e-i,"m"),1,!1):this._changeInProgress||(this._changeInProgress=!0,_.updateOffset(this,!0),this._changeInProgress=null)),this},u.utc=function(e){return this.utcOffset(0,e)},u.local=function(e){return this._isUTC&&(this.utcOffset(0,e),this._isUTC=!1,e)&&this.subtract(Et(this),"m"),this},u.parseZone=function(){var e;return null!=this._tzm?this.utcOffset(this._tzm,!1,!0):"string"==typeof this._i&&(null!=(e=Vt(Me,this._i))?this.utcOffset(e):this.utcOffset(0,!0)),this},u.hasAlignedHourOffset=function(e){return!!this.isValid()&&(e=e?R(e).utcOffset():0,(this.utcOffset()-e)%60==0)},u.isDST=function(){return this.utcOffset()>this.clone().month(0).utcOffset()||this.utcOffset()>this.clone().month(5).utcOffset()},u.isLocal=function(){return!!this.isValid()&&!this._isUTC},u.isUtcOffset=function(){return!!this.isValid()&&this._isUTC},u.isUtc=At,u.isUTC=At,u.zoneAbbr=function(){return this._isUTC?"UTC":""},u.zoneName=function(){return this._isUTC?"Coordinated Universal Time":""},u.dates=e("dates accessor is deprecated. Use date instead.",ge),u.months=e("months accessor is deprecated. Use month instead",Ie),u.years=e("years accessor is deprecated. Use year instead",Pe),u.zone=e("moment().zone is deprecated, use moment().utcOffset instead. http://momentjs.com/guides/#/warnings/zone/",function(e,t){return null!=e?(this.utcOffset(e="string"!=typeof e?-e:e,t),this):-this.utcOffset()}),u.isDSTShifted=e("isDSTShifted is deprecated. See http://momentjs.com/guides/#/warnings/dst-shifted/ for more information",function(){var e,t;return g(this._isDSTShifted)&&(q(e={},this),(e=Nt(e))._a?(t=(e._isUTC?l:R)(e._a),this._isDSTShifted=this.isValid()&&0<function(e,t,n){for(var s=Math.min(e.length,t.length),i=Math.abs(e.length-t.length),r=0,a=0;a<s;a++)(n&&e[a]!==t[a]||!n&&M(e[a])!==M(t[a]))&&r++;return r+i}(e._a,t.toArray())):this._isDSTShifted=!1),this._isDSTShifted});d=K.prototype;function cn(e,t,n,s){var i=P(),s=l().set(s,t);return i[n](s,e)}function fn(e,t,n){if(w(e)&&(t=e,e=void 0),e=e||"",null!=t)return cn(e,t,n,"month");for(var s=[],i=0;i<12;i++)s[i]=cn(e,i,n,"month");return s}function mn(e,t,n,s){t=("boolean"==typeof e?w(t)&&(n=t,t=void 0):(t=e,e=!1,w(n=t)&&(n=t,t=void 0)),t||"");var i,r=P(),a=e?r._week.dow:0,o=[];if(null!=n)return cn(t,(n+a)%7,s,"day");for(i=0;i<7;i++)o[i]=cn(t,(i+a)%7,s,"day");return o}d.calendar=function(e,t,n){return a(e=this._calendar[e]||this._calendar.sameElse)?e.call(t,n):e},d.longDateFormat=function(e){var t=this._longDateFormat[e],n=this._longDateFormat[e.toUpperCase()];return t||!n?t:(this._longDateFormat[e]=n.match(te).map(function(e){return"MMMM"===e||"MM"===e||"DD"===e||"dddd"===e?e.slice(1):e}).join(""),this._longDateFormat[e])},d.invalidDate=function(){return this._invalidDate},d.ordinal=function(e){return this._ordinal.replace("%d",e)},d.preparse=hn,d.postformat=hn,d.relativeTime=function(e,t,n,s){var i=this._relativeTime[n];return a(i)?i(e,t,n,s):i.replace(/%d/i,e)},d.pastFuture=function(e,t){return a(e=this._relativeTime[0<e?"future":"past"])?e(t):e.replace(/%s/i,t)},d.set=function(e){var t,n;for(n in e)c(e,n)&&(a(t=e[n])?this[n]=t:this["_"+n]=t);this._config=e,this._dayOfMonthOrdinalParseLenient=new RegExp((this._dayOfMonthOrdinalParse.source||this._ordinalParse.source)+"|"+/\d{1,2}/.source)},d.eras=function(e,t){for(var n,s=this._eras||P("en")._eras,i=0,r=s.length;i<r;++i){switch(typeof s[i].since){case"string":n=_(s[i].since).startOf("day"),s[i].since=n.valueOf();break}switch(typeof s[i].until){case"undefined":s[i].until=1/0;break;case"string":n=_(s[i].until).startOf("day").valueOf(),s[i].until=n.valueOf();break}}return s},d.erasParse=function(e,t,n){var s,i,r,a,o,u=this.eras();for(e=e.toUpperCase(),s=0,i=u.length;s<i;++s)if(r=u[s].name.toUpperCase(),a=u[s].abbr.toUpperCase(),o=u[s].narrow.toUpperCase(),n)switch(t){case"N":case"NN":case"NNN":if(a===e)return u[s];break;case"NNNN":if(r===e)return u[s];break;case"NNNNN":if(o===e)return u[s];break}else if(0<=[r,a,o].indexOf(e))return u[s]},d.erasConvertYear=function(e,t){var n=e.since<=e.until?1:-1;return void 0===t?_(e.since).year():_(e.since).year()+(t-e.offset)*n},d.erasAbbrRegex=function(e){return c(this,"_erasAbbrRegex")||an.call(this),e?this._erasAbbrRegex:this._erasRegex},d.erasNameRegex=function(e){return c(this,"_erasNameRegex")||an.call(this),e?this._erasNameRegex:this._erasRegex},d.erasNarrowRegex=function(e){return c(this,"_erasNarrowRegex")||an.call(this),e?this._erasNarrowRegex:this._erasRegex},d.months=function(e,t){return e?(y(this._months)?this._months:this._months[(this._months.isFormat||Ve).test(t)?"format":"standalone"])[e.month()]:y(this._months)?this._months:this._months.standalone},d.monthsShort=function(e,t){return e?(y(this._monthsShort)?this._monthsShort:this._monthsShort[Ve.test(t)?"format":"standalone"])[e.month()]:y(this._monthsShort)?this._monthsShort:this._monthsShort.standalone},d.monthsParse=function(e,t,n){var s,i;if(this._monthsParseExact)return function(e,t,n){var s,i,r,e=e.toLocaleLowerCase();if(!this._monthsParse)for(this._monthsParse=[],this._longMonthsParse=[],this._shortMonthsParse=[],s=0;s<12;++s)r=l([2e3,s]),this._shortMonthsParse[s]=this.monthsShort(r,"").toLocaleLowerCase(),this._longMonthsParse[s]=this.months(r,"").toLocaleLowerCase();return n?"MMM"===t?-1!==(i=x.call(this._shortMonthsParse,e))?i:null:-1!==(i=x.call(this._longMonthsParse,e))?i:null:"MMM"===t?-1!==(i=x.call(this._shortMonthsParse,e))||-1!==(i=x.call(this._longMonthsParse,e))?i:null:-1!==(i=x.call(this._longMonthsParse,e))||-1!==(i=x.call(this._shortMonthsParse,e))?i:null}.call(this,e,t,n);for(this._monthsParse||(this._monthsParse=[],this._longMonthsParse=[],this._shortMonthsParse=[]),s=0;s<12;s++){if(i=l([2e3,s]),n&&!this._longMonthsParse[s]&&(this._longMonthsParse[s]=new RegExp("^"+this.months(i,"").replace(".","")+"$","i"),this._shortMonthsParse[s]=new RegExp("^"+this.monthsShort(i,"").replace(".","")+"$","i")),n||this._monthsParse[s]||(i="^"+this.months(i,"")+"|^"+this.monthsShort(i,""),this._monthsParse[s]=new RegExp(i.replace(".",""),"i")),n&&"MMMM"===t&&this._longMonthsParse[s].test(e))return s;if(n&&"MMM"===t&&this._shortMonthsParse[s].test(e))return s;if(!n&&this._monthsParse[s].test(e))return s}},d.monthsRegex=function(e){return this._monthsParseExact?(c(this,"_monthsRegex")||je.call(this),e?this._monthsStrictRegex:this._monthsRegex):(c(this,"_monthsRegex")||(this._monthsRegex=Ee),this._monthsStrictRegex&&e?this._monthsStrictRegex:this._monthsRegex)},d.monthsShortRegex=function(e){return this._monthsParseExact?(c(this,"_monthsRegex")||je.call(this),e?this._monthsShortStrictRegex:this._monthsShortRegex):(c(this,"_monthsShortRegex")||(this._monthsShortRegex=Ge),this._monthsShortStrictRegex&&e?this._monthsShortStrictRegex:this._monthsShortRegex)},d.week=function(e){return Be(e,this._week.dow,this._week.doy).week},d.firstDayOfYear=function(){return this._week.doy},d.firstDayOfWeek=function(){return this._week.dow},d.weekdays=function(e,t){return t=y(this._weekdays)?this._weekdays:this._weekdays[e&&!0!==e&&this._weekdays.isFormat.test(t)?"format":"standalone"],!0===e?Je(t,this._week.dow):e?t[e.day()]:t},d.weekdaysMin=function(e){return!0===e?Je(this._weekdaysMin,this._week.dow):e?this._weekdaysMin[e.day()]:this._weekdaysMin},d.weekdaysShort=function(e){return!0===e?Je(this._weekdaysShort,this._week.dow):e?this._weekdaysShort[e.day()]:this._weekdaysShort},d.weekdaysParse=function(e,t,n){var s,i;if(this._weekdaysParseExact)return function(e,t,n){var s,i,r,e=e.toLocaleLowerCase();if(!this._weekdaysParse)for(this._weekdaysParse=[],this._shortWeekdaysParse=[],this._minWeekdaysParse=[],s=0;s<7;++s)r=l([2e3,1]).day(s),this._minWeekdaysParse[s]=this.weekdaysMin(r,"").toLocaleLowerCase(),this._shortWeekdaysParse[s]=this.weekdaysShort(r,"").toLocaleLowerCase(),this._weekdaysParse[s]=this.weekdays(r,"").toLocaleLowerCase();return n?"dddd"===t?-1!==(i=x.call(this._weekdaysParse,e))?i:null:"ddd"===t?-1!==(i=x.call(this._shortWeekdaysParse,e))?i:null:-1!==(i=x.call(this._minWeekdaysParse,e))?i:null:"dddd"===t?-1!==(i=x.call(this._weekdaysParse,e))||-1!==(i=x.call(this._shortWeekdaysParse,e))||-1!==(i=x.call(this._minWeekdaysParse,e))?i:null:"ddd"===t?-1!==(i=x.call(this._shortWeekdaysParse,e))||-1!==(i=x.call(this._weekdaysParse,e))||-1!==(i=x.call(this._minWeekdaysParse,e))?i:null:-1!==(i=x.call(this._minWeekdaysParse,e))||-1!==(i=x.call(this._weekdaysParse,e))||-1!==(i=x.call(this._shortWeekdaysParse,e))?i:null}.call(this,e,t,n);for(this._weekdaysParse||(this._weekdaysParse=[],this._minWeekdaysParse=[],this._shortWeekdaysParse=[],this._fullWeekdaysParse=[]),s=0;s<7;s++){if(i=l([2e3,1]).day(s),n&&!this._fullWeekdaysParse[s]&&(this._fullWeekdaysParse[s]=new RegExp("^"+this.weekdays(i,"").replace(".","\\.?")+"$","i"),this._shortWeekdaysParse[s]=new RegExp("^"+this.weekdaysShort(i,"").replace(".","\\.?")+"$","i"),this._minWeekdaysParse[s]=new RegExp("^"+this.weekdaysMin(i,"").replace(".","\\.?")+"$","i")),this._weekdaysParse[s]||(i="^"+this.weekdays(i,"")+"|^"+this.weekdaysShort(i,"")+"|^"+this.weekdaysMin(i,""),this._weekdaysParse[s]=new RegExp(i.replace(".",""),"i")),n&&"dddd"===t&&this._fullWeekdaysParse[s].test(e))return s;if(n&&"ddd"===t&&this._shortWeekdaysParse[s].test(e))return s;if(n&&"dd"===t&&this._minWeekdaysParse[s].test(e))return s;if(!n&&this._weekdaysParse[s].test(e))return s}},d.weekdaysRegex=function(e){return this._weekdaysParseExact?(c(this,"_weekdaysRegex")||st.call(this),e?this._weekdaysStrictRegex:this._weekdaysRegex):(c(this,"_weekdaysRegex")||(this._weekdaysRegex=et),this._weekdaysStrictRegex&&e?this._weekdaysStrictRegex:this._weekdaysRegex)},d.weekdaysShortRegex=function(e){return this._weekdaysParseExact?(c(this,"_weekdaysRegex")||st.call(this),e?this._weekdaysShortStrictRegex:this._weekdaysShortRegex):(c(this,"_weekdaysShortRegex")||(this._weekdaysShortRegex=tt),this._weekdaysShortStrictRegex&&e?this._weekdaysShortStrictRegex:this._weekdaysShortRegex)},d.weekdaysMinRegex=function(e){return this._weekdaysParseExact?(c(this,"_weekdaysRegex")||st.call(this),e?this._weekdaysMinStrictRegex:this._weekdaysMinRegex):(c(this,"_weekdaysMinRegex")||(this._weekdaysMinRegex=nt),this._weekdaysMinStrictRegex&&e?this._weekdaysMinStrictRegex:this._weekdaysMinRegex)},d.isPM=function(e){return"p"===(e+"").toLowerCase().charAt(0)},d.meridiem=function(e,t,n){return 11<e?n?"pm":"PM":n?"am":"AM"},ft("en",{eras:[{since:"0001-01-01",until:1/0,offset:1,name:"Anno Domini",narrow:"AD",abbr:"AD"},{since:"0000-12-31",until:-1/0,offset:1,name:"Before Christ",narrow:"BC",abbr:"BC"}],dayOfMonthOrdinalParse:/\d{1,2}(th|st|nd|rd)/,ordinal:function(e){var t=e%10;return e+(1===M(e%100/10)?"th":1==t?"st":2==t?"nd":3==t?"rd":"th")}}),_.lang=e("moment.lang is deprecated. Use moment.locale instead.",ft),_.langData=e("moment.langData is deprecated. Use moment.localeData instead.",P);var _n=Math.abs;function yn(e,t,n,s){t=C(t,n);return e._milliseconds+=s*t._milliseconds,e._days+=s*t._days,e._months+=s*t._months,e._bubble()}function gn(e){return e<0?Math.floor(e):Math.ceil(e)}function wn(e){return 4800*e/146097}function pn(e){return 146097*e/4800}function kn(e){return function(){return this.as(e)}}de=kn("ms"),t=kn("s"),ye=kn("m"),he=kn("h"),Fe=kn("d"),_e=kn("w"),me=kn("M"),Qe=kn("Q"),i=kn("y"),ce=de;function Mn(e){return function(){return this.isValid()?this._data[e]:NaN}}var we=Mn("milliseconds"),fe=Mn("seconds"),ge=Mn("minutes"),Pe=Mn("hours"),d=Mn("days"),vn=Mn("months"),Dn=Mn("years");var Yn=Math.round,Sn={ss:44,s:45,m:45,h:22,d:26,w:null,M:11};function On(e,t,n,s){var i=C(e).abs(),r=Yn(i.as("s")),a=Yn(i.as("m")),o=Yn(i.as("h")),u=Yn(i.as("d")),l=Yn(i.as("M")),d=Yn(i.as("w")),i=Yn(i.as("y")),r=(r<=n.ss?["s",r]:r<n.s&&["ss",r])||(a<=1?["m"]:a<n.m&&["mm",a])||(o<=1?["h"]:o<n.h&&["hh",o])||(u<=1?["d"]:u<n.d&&["dd",u]);return(r=(r=null!=n.w?r||(d<=1?["w"]:d<n.w&&["ww",d]):r)||(l<=1?["M"]:l<n.M&&["MM",l])||(i<=1?["y"]:["yy",i]))[2]=t,r[3]=0<+e,r[4]=s,function(e,t,n,s,i){return i.relativeTime(t||1,!!n,e,s)}.apply(null,r)}var bn=Math.abs;function Tn(e){return(0<e)-(e<0)||+e}function xn(){var e,t,n,s,i,r,a,o,u,l,d;return this.isValid()?(e=bn(this._milliseconds)/1e3,t=bn(this._days),n=bn(this._months),(o=this.asSeconds())?(s=m(e/60),i=m(s/60),e%=60,s%=60,r=m(n/12),n%=12,a=e?e.toFixed(3).replace(/\.?0+$/,""):"",u=Tn(this._months)!==Tn(o)?"-":"",l=Tn(this._days)!==Tn(o)?"-":"",d=Tn(this._milliseconds)!==Tn(o)?"-":"",(o<0?"-":"")+"P"+(r?u+r+"Y":"")+(n?u+n+"M":"")+(t?l+t+"D":"")+(i||s||e?"T":"")+(i?d+i+"H":"")+(s?d+s+"M":"")+(e?d+a+"S":"")):"P0D"):this.localeData().invalidDate()}var U=Ct.prototype;return U.isValid=function(){return this._isValid},U.abs=function(){var e=this._data;return this._milliseconds=_n(this._milliseconds),this._days=_n(this._days),this._months=_n(this._months),e.milliseconds=_n(e.milliseconds),e.seconds=_n(e.seconds),e.minutes=_n(e.minutes),e.hours=_n(e.hours),e.months=_n(e.months),e.years=_n(e.years),this},U.add=function(e,t){return yn(this,e,t,1)},U.subtract=function(e,t){return yn(this,e,t,-1)},U.as=function(e){if(!this.isValid())return NaN;var t,n,s=this._milliseconds;if("month"===(e=o(e))||"quarter"===e||"year"===e)switch(t=this._days+s/864e5,n=this._months+wn(t),e){case"month":return n;case"quarter":return n/3;case"year":return n/12}else switch(t=this._days+Math.round(pn(this._months)),e){case"week":return t/7+s/6048e5;case"day":return t+s/864e5;case"hour":return 24*t+s/36e5;case"minute":return 1440*t+s/6e4;case"second":return 86400*t+s/1e3;case"millisecond":return Math.floor(864e5*t)+s;default:throw new Error("Unknown unit "+e)}},U.asMilliseconds=de,U.asSeconds=t,U.asMinutes=ye,U.asHours=he,U.asDays=Fe,U.asWeeks=_e,U.asMonths=me,U.asQuarters=Qe,U.asYears=i,U.valueOf=ce,U._bubble=function(){var e=this._milliseconds,t=this._days,n=this._months,s=this._data;return 0<=e&&0<=t&&0<=n||e<=0&&t<=0&&n<=0||(e+=864e5*gn(pn(n)+t),n=t=0),s.milliseconds=e%1e3,e=m(e/1e3),s.seconds=e%60,e=m(e/60),s.minutes=e%60,e=m(e/60),s.hours=e%24,t+=m(e/24),n+=e=m(wn(t)),t-=gn(pn(e)),e=m(n/12),n%=12,s.days=t,s.months=n,s.years=e,this},U.clone=function(){return C(this)},U.get=function(e){return e=o(e),this.isValid()?this[e+"s"]():NaN},U.milliseconds=we,U.seconds=fe,U.minutes=ge,U.hours=Pe,U.days=d,U.weeks=function(){return m(this.days()/7)},U.months=vn,U.years=Dn,U.humanize=function(e,t){var n,s;return this.isValid()?(n=!1,s=Sn,"object"==typeof e&&(t=e,e=!1),"boolean"==typeof e&&(n=e),"object"==typeof t&&(s=Object.assign({},Sn,t),null!=t.s)&&null==t.ss&&(s.ss=t.s-1),e=this.localeData(),t=On(this,!n,s,e),n&&(t=e.pastFuture(+this,t)),e.postformat(t)):this.localeData().invalidDate()},U.toISOString=xn,U.toString=xn,U.toJSON=xn,U.locale=Xt,U.localeData=Kt,U.toIsoString=e("toIsoString() is deprecated. Please use toISOString() instead (notice the capitals)",xn),U.lang=Ke,s("X",0,0,"unix"),s("x",0,0,"valueOf"),h("x",ke),h("X",/[+-]?\d+(\.\d{1,3})?/),v("X",function(e,t,n){n._d=new Date(1e3*parseFloat(e))}),v("x",function(e,t,n){n._d=new Date(M(e))}),_.version="2.30.1",H=R,_.fn=u,_.min=function(){return Pt("isBefore",[].slice.call(arguments,0))},_.max=function(){return Pt("isAfter",[].slice.call(arguments,0))},_.now=function(){return Date.now?Date.now():+new Date},_.utc=l,_.unix=function(e){return R(1e3*e)},_.months=function(e,t){return fn(e,t,"months")},_.isDate=V,_.locale=ft,_.invalid=I,_.duration=C,_.isMoment=k,_.weekdays=function(e,t,n){return mn(e,t,n,"weekdays")},_.parseZone=function(){return R.apply(null,arguments).parseZone()},_.localeData=P,_.isDuration=Ut,_.monthsShort=function(e,t){return fn(e,t,"monthsShort")},_.weekdaysMin=function(e,t,n){return mn(e,t,n,"weekdaysMin")},_.defineLocale=mt,_.updateLocale=function(e,t){var n,s;return null!=t?(s=ut,null!=W[e]&&null!=W[e].parentLocale?W[e].set(X(W[e]._config,t)):(t=X(s=null!=(n=ct(e))?n._config:s,t),null==n&&(t.abbr=e),(s=new K(t)).parentLocale=W[e],W[e]=s),ft(e)):null!=W[e]&&(null!=W[e].parentLocale?(W[e]=W[e].parentLocale,e===ft()&&ft(e)):null!=W[e]&&delete W[e]),W[e]},_.locales=function(){return ee(W)},_.weekdaysShort=function(e,t,n){return mn(e,t,n,"weekdaysShort")},_.normalizeUnits=o,_.relativeTimeRounding=function(e){return void 0===e?Yn:"function"==typeof e&&(Yn=e,!0)},_.relativeTimeThreshold=function(e,t){return void 0!==Sn[e]&&(void 0===t?Sn[e]:(Sn[e]=t,"s"===e&&(Sn.ss=t-1),!0))},_.calendarFormat=function(e,t){return(e=e.diff(t,"days",!0))<-6?"sameElse":e<-1?"lastWeek":e<0?"lastDay":e<1?"sameDay":e<2?"nextDay":e<7?"nextWeek":"sameElse"},_.prototype=u,_.HTML5_FMT={DATETIME_LOCAL:"YYYY-MM-DDTHH:mm",DATETIME_LOCAL_SECONDS:"YYYY-MM-DDTHH:mm:ss",DATETIME_LOCAL_MS:"YYYY-MM-DDTHH:mm:ss.SSS",DATE:"YYYY-MM-DD",TIME:"HH:mm",TIME_SECONDS:"HH:mm:ss",TIME_MS:"HH:mm:ss.SSS",WEEK:"GGGG-[W]WW",MONTH:"YYYY-MM"},_});
!function(a,i){"use strict";"object"==typeof module&&module.exports?module.exports=i(require("moment")):"function"==typeof define&&define.amd?define(["moment"],i):i(a.moment)}(this,function(o){"use strict";void 0===o.version&&o.default&&(o=o.default);var i,s={},c={},A={},u={},m={},a=(o&&"string"==typeof o.version||y("Moment Timezone requires Moment.js. See https://momentjs.com/timezone/docs/#/use-it/browser/"),o.version.split(".")),r=+a[0],e=+a[1];function n(a){return 96<a?a-87:64<a?a-29:a-48}function t(a){var i=0,r=a.split("."),e=r[0],o=r[1]||"",c=1,A=0,r=1;for(45===a.charCodeAt(0)&&(r=-(i=1));i<e.length;i++)A=60*A+n(e.charCodeAt(i));for(i=0;i<o.length;i++)c/=60,A+=n(o.charCodeAt(i))*c;return A*r}function l(a){for(var i=0;i<a.length;i++)a[i]=t(a[i])}function f(a,i){for(var r=[],e=0;e<i.length;e++)r[e]=a[i[e]];return r}function p(a){for(var a=a.split("|"),i=a[2].split(" "),r=a[3].split(""),e=a[4].split(" "),o=(l(i),l(r),l(e),e),c=r.length,A=0;A<c;A++)o[A]=Math.round((o[A-1]||0)+6e4*o[A]);return o[c-1]=1/0,{name:a[0],abbrs:f(a[1].split(" "),r),offsets:f(i,r),untils:e,population:0|a[5]}}function M(a){a&&this._set(p(a))}function b(a,i){this.name=a,this.zones=i}function h(a){var i=a.toTimeString(),r=i.match(/\([a-z ]+\)/i);"GMT"===(r=r&&r[0]?(r=r[0].match(/[A-Z]/g))?r.join(""):void 0:(r=i.match(/[A-Z]{3,5}/g))?r[0]:void 0)&&(r=void 0),this.at=+a,this.abbr=r,this.offset=a.getTimezoneOffset()}function d(a){this.zone=a,this.offsetScore=0,this.abbrScore=0}function E(){for(var a,i,r,e=(new Date).getFullYear()-2,o=new h(new Date(e,0,1)),c=o.offset,A=[o],n=1;n<48;n++)(r=new Date(e,n,1).getTimezoneOffset())!==c&&(a=function(a,i){for(var r;r=6e4*((i.at-a.at)/12e4|0);)(r=new h(new Date(a.at+r))).offset===a.offset?a=r:i=r;return a}(o,i=new h(new Date(e,n,1))),A.push(a),A.push(new h(new Date(a.at+6e4))),o=i,c=r);for(n=0;n<4;n++)A.push(new h(new Date(e+n,0,1))),A.push(new h(new Date(e+n,6,1)));return A}function g(a,i){return a.offsetScore!==i.offsetScore?a.offsetScore-i.offsetScore:a.abbrScore!==i.abbrScore?a.abbrScore-i.abbrScore:a.zone.population!==i.zone.population?i.zone.population-a.zone.population:i.zone.name.localeCompare(a.zone.name)}function P(){try{var a=Intl.DateTimeFormat().resolvedOptions().timeZone;if(a&&3<a.length){var i=u[S(a)];if(i)return i;y("Moment Timezone found "+a+" from the Intl api, but did not have that data loaded.")}}catch(a){}for(var r,e,o=E(),c=o.length,A=function(a){for(var i,r,e,o=a.length,c={},A=[],n={},t=0;t<o;t++)if(r=a[t].offset,!n.hasOwnProperty(r)){for(i in e=m[r]||{})e.hasOwnProperty(i)&&(c[i]=!0);n[r]=!0}for(t in c)c.hasOwnProperty(t)&&A.push(u[t]);return A}(o),n=[],t=0;t<A.length;t++){for(r=new d(z(A[t])),e=0;e<c;e++)r.scoreOffsetAt(o[e]);n.push(r)}return n.sort(g),0<n.length?n[0].zone.name:void 0}function S(a){return(a||"").toLowerCase().replace(/\//g,"_")}function T(a){var i,r,e,o;for("string"==typeof a&&(a=[a]),i=0;i<a.length;i++){o=S(r=(e=a[i].split("|"))[0]),s[o]=a[i],u[o]=r,A=c=t=n=void 0;var c,A,n=o,t=e[2].split(" ");for(l(t),c=0;c<t.length;c++)A=t[c],m[A]=m[A]||{},m[A][n]=!0}}function z(a,i){a=S(a);var r=s[a];return r instanceof M?r:"string"==typeof r?(r=new M(r),s[a]=r):c[a]&&i!==z&&(i=z(c[a],z))?((r=s[a]=new M)._set(i),r.name=u[a],r):null}function k(a){var i,r,e,o;for("string"==typeof a&&(a=[a]),i=0;i<a.length;i++)e=S((r=a[i].split("|"))[0]),o=S(r[1]),c[e]=o,u[e]=r[0],c[o]=e,u[o]=r[1]}function _(a){T(a.zones),k(a.links);var i,r,e,o=a.countries;if(o&&o.length)for(i=0;i<o.length;i++)r=(e=o[i].split("|"))[0].toUpperCase(),e=e[1].split(" "),A[r]=new b(r,e);L.dataVersion=a.version}function C(a){return C.didShowError||(C.didShowError=!0,y("moment.tz.zoneExists('"+a+"') has been deprecated in favor of !moment.tz.zone('"+a+"')")),!!z(a)}function B(a){var i="X"===a._f||"x"===a._f;return!(!a._a||void 0!==a._tzm||i)}function y(a){"undefined"!=typeof console&&"function"==typeof console.error&&console.error(a)}function L(a){var i=Array.prototype.slice.call(arguments,0,-1),r=arguments[arguments.length-1],i=o.utc.apply(null,i);return!o.isMoment(a)&&B(i)&&(a=z(r))&&i.add(a.parse(i),"minutes"),i.tz(r),i}(r<2||2==r&&e<6)&&y("Moment Timezone requires Moment.js >= 2.6.0. You are using Moment.js "+o.version+". See momentjs.com"),M.prototype={_set:function(a){this.name=a.name,this.abbrs=a.abbrs,this.untils=a.untils,this.offsets=a.offsets,this.population=a.population},_index:function(a){a=function(a,i){var r,e=i.length;if(a<i[0])return 0;if(1<e&&i[e-1]===1/0&&a>=i[e-2])return e-1;if(a>=i[e-1])return-1;for(var o=0,c=e-1;1<c-o;)i[r=Math.floor((o+c)/2)]<=a?o=r:c=r;return c}(+a,this.untils);if(0<=a)return a},countries:function(){var i=this.name;return Object.keys(A).filter(function(a){return-1!==A[a].zones.indexOf(i)})},parse:function(a){for(var i,r,e,o=+a,c=this.offsets,A=this.untils,n=A.length-1,t=0;t<n;t++)if(i=c[t],r=c[t+1],e=c[t&&t-1],i<r&&L.moveAmbiguousForward?i=r:e<i&&L.moveInvalidForward&&(i=e),o<A[t]-6e4*i)return c[t];return c[n]},abbr:function(a){return this.abbrs[this._index(a)]},offset:function(a){return y("zone.offset has been deprecated in favor of zone.utcOffset"),this.offsets[this._index(a)]},utcOffset:function(a){return this.offsets[this._index(a)]}},d.prototype.scoreOffsetAt=function(a){this.offsetScore+=Math.abs(this.zone.utcOffset(a.at)-a.offset),this.zone.abbr(a.at).replace(/[^A-Z]/g,"")!==a.abbr&&this.abbrScore++},L.version="0.5.45",L.dataVersion="",L._zones=s,L._links=c,L._names=u,L._countries=A,L.add=T,L.link=k,L.load=_,L.zone=z,L.zoneExists=C,L.guess=function(a){return i=i&&!a?i:P()},L.names=function(){var a,i=[];for(a in u)u.hasOwnProperty(a)&&(s[a]||s[c[a]])&&u[a]&&i.push(u[a]);return i.sort()},L.Zone=M,L.unpack=p,L.unpackBase60=t,L.needsOffset=B,L.moveInvalidForward=!0,L.moveAmbiguousForward=!1,L.countries=function(){return Object.keys(A)},L.zonesForCountry=function(a,i){var r;return r=(r=a).toUpperCase(),(a=A[r]||null)?(r=a.zones.sort(),i?r.map(function(a){return{name:a,offset:z(a).utcOffset(new Date)}}):r):null};var D,a=o.fn;function N(a){return function(){return this._z?this._z.abbr(this):a.call(this)}}function O(a){return function(){return this._z=null,a.apply(this,arguments)}}o.tz=L,o.defaultZone=null,o.updateOffset=function(a,i){var r,e=o.defaultZone;void 0===a._z&&(e&&B(a)&&!a._isUTC&&a.isValid()&&(a._d=o.utc(a._a)._d,a.utc().add(e.parse(a),"minutes")),a._z=e),a._z&&(e=a._z.utcOffset(a),Math.abs(e)<16&&(e/=60),void 0!==a.utcOffset?(r=a._z,a.utcOffset(-e,i),a._z=r):a.zone(e,i))},a.tz=function(a,i){if(a){if("string"!=typeof a)throw new Error("Time zone name must be a string, got "+a+" ["+typeof a+"]");return this._z=z(a),this._z?o.updateOffset(this,i):y("Moment Timezone has no data for "+a+". See http://momentjs.com/timezone/docs/#/data-loading/."),this}if(this._z)return this._z.name},a.zoneName=N(a.zoneName),a.zoneAbbr=N(a.zoneAbbr),a.utc=O(a.utc),a.local=O(a.local),a.utcOffset=(D=a.utcOffset,function(){return 0<arguments.length&&(this._z=null),D.apply(this,arguments)}),o.tz.setDefault=function(a){return(r<2||2==r&&e<9)&&y("Moment Timezone setDefault() requires Moment.js >= 2.9.0. You are using Moment.js "+o.version+"."),o.defaultZone=a?z(a):null,o};a=o.momentProperties;return"[object Array]"===Object.prototype.toString.call(a)?(a.push("_z"),a.push("_a")):a&&(a._z=null),_({version:"2024a",zones:["Africa/Abidjan|GMT|0|0||48e5","Africa/Nairobi|EAT|-30|0||47e5","Africa/Algiers|CET|-10|0||26e5","Africa/Lagos|WAT|-10|0||17e6","Africa/Khartoum|CAT|-20|0||51e5","Africa/Cairo|EET EEST|-20 -30|010101010101010|29NW0 1cL0 1cN0 1fz0 1a10 1fz0 1a10 1fz0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0|15e6","Africa/Casablanca|+01 +00|-10 0|010101010101010101010101|208q0 e00 2600 gM0 2600 e00 2600 gM0 2600 e00 28M0 e00 2600 gM0 2600 e00 28M0 e00 2600 gM0 2600 e00 2600|32e5","Europe/Paris|CET CEST|-10 -20|01010101010101010101010|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 11A0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|11e6","Africa/Johannesburg|SAST|-20|0||84e5","Africa/Juba|EAT CAT|-30 -20|01|24nx0|","Africa/Sao_Tome|WAT GMT|-10 0|01|1XiN0|","Africa/Tripoli|EET|-20|0||11e5","America/Adak|HST HDT|a0 90|01010101010101010101010|1XKc0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|326","America/Anchorage|AKST AKDT|90 80|01010101010101010101010|1XKb0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|30e4","America/Santo_Domingo|AST|40|0||29e5","America/Fortaleza|-03|30|0||34e5","America/Asuncion|-03 -04|30 40|01010101010101010101010|1XPD0 1ip0 17b0 1ip0 19X0 1fB0 19X0 1fB0 19X0 1fB0 19X0 1ip0 17b0 1ip0 17b0 1ip0 19X0 1fB0 19X0 1fB0 19X0 1ip0|28e5","America/Panama|EST|50|0||15e5","America/Mexico_City|CST CDT|60 50|010101010|1XVk0 1lb0 14p0 1lb0 14p0 1nX0 11B0 1nX0|20e6","America/Managua|CST|60|0||22e5","America/Caracas|-04|40|0||29e5","America/Lima|-05|50|0||11e6","America/Denver|MST MDT|70 60|01010101010101010101010|1XK90 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|26e5","America/Campo_Grande|-03 -04|30 40|01|1XBD0|77e4","America/Chicago|CST CDT|60 50|01010101010101010101010|1XK80 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|92e5","America/Chihuahua|MST MDT CST|70 60 60|010101012|1XVl0 1lb0 14p0 1lb0 14p0 1nX0 11B0 1nX0|81e4","America/Ciudad_Juarez|MST MDT CST|70 60 60|010101012010101010101010|1XK90 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1wn0 cm0 EP0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|","America/Phoenix|MST|70|0||42e5","America/Whitehorse|PST PDT MST|80 70 70|01012|1XKa0 1zb0 Op0 1z90|23e3","America/New_York|EST EDT|50 40|01010101010101010101010|1XK70 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|21e6","America/Los_Angeles|PST PDT|80 70|01010101010101010101010|1XKa0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|15e6","America/Halifax|AST ADT|40 30|01010101010101010101010|1XK60 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|39e4","America/Godthab|-03 -02 -01|30 20 10|0101010101212121212121|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 2so0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|17e3","America/Havana|CST CDT|50 40|01010101010101010101010|1XK50 1zc0 Oo0 1zc0 Rc0 1zc0 Oo0 1zc0 Oo0 1zc0 Oo0 1zc0 Oo0 1zc0 Oo0 1zc0 Rc0 1zc0 Oo0 1zc0 Oo0 1zc0|21e5","America/Mazatlan|MST MDT|70 60|010101010|1XVl0 1lb0 14p0 1lb0 14p0 1nX0 11B0 1nX0|44e4","America/Metlakatla|PST AKST AKDT|80 90 80|012121212121212121212121|1Xqy0 jB0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|14e2","America/Miquelon|-03 -02|30 20|01010101010101010101010|1XK50 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|61e2","America/Noronha|-02|20|0||30e2","America/Ojinaga|MST MDT CST CDT|70 60 60 50|01010101232323232323232|1XK90 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1wn0 Rc0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|23e3","America/Santiago|-03 -04|30 40|01010101010101010101010|1XVf0 11B0 1nX0 11B0 1nX0 11B0 1nX0 14p0 1lb0 11B0 1qL0 11B0 1nX0 11B0 1nX0 11B0 1nX0 11B0 1nX0 11B0 1qL0 WN0|62e5","America/Sao_Paulo|-02 -03|20 30|01|1XBC0|20e6","America/Scoresbysund|-01 +00 -02|10 0 20|0101010101020202020202|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 2pA0 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|452","America/St_Johns|NST NDT|3u 2u|01010101010101010101010|1XK5u 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0|11e4","Antarctica/Casey|+11 +08|-b0 -80|0101010101|1XME0 1kr0 12l0 1o01 14kX 1lf1 14kX 1lf1 13bX|10","Asia/Bangkok|+07|-70|0||15e6","Asia/Vladivostok|+10|-a0|0||60e4","Australia/Sydney|AEDT AEST|-b0 -a0|01010101010101010101010|1XV40 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1fA0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1fA0|40e5","Asia/Tashkent|+05|-50|0||23e5","Pacific/Auckland|NZDT NZST|-d0 -c0|01010101010101010101010|1XV20 1a00 1fA0 1a00 1fA0 1a00 1fA0 1a00 1fA0 1a00 1io0 1a00 1fA0 1a00 1fA0 1a00 1fA0 1a00 1fA0 1a00 1fA0 1cM0|14e5","Europe/Istanbul|+03|-30|0||13e6","Antarctica/Troll|+00 +02|0 -20|01010101010101010101010|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 11A0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|40","Antarctica/Vostok|+07 +05|-70 -50|01|2bnv0|25","Asia/Almaty|+06 +05|-60 -50|01|2bR60|15e5","Asia/Amman|EET EEST +03|-20 -30 -30|010101012|1XRy0 1o00 11A0 1qM0 WM0 1qM0 LA0 1C00|25e5","Asia/Kamchatka|+12|-c0|0||18e4","Asia/Dubai|+04|-40|0||39e5","Asia/Beirut|EET EEST|-20 -30|01010101010101010101010|1XSm0 1nX0 11B0 1nX0 11B0 1qL0 WN0 1qL0 WN0 1qL0 11B0 1nX0 11B0 1nX0 11B0 1nX0 11B0 1qL0 WN0 1qL0 WN0 1qL0|22e5","Asia/Dhaka|+06|-60|0||16e6","Asia/Kuala_Lumpur|+08|-80|0||71e5","Asia/Kolkata|IST|-5u|0||15e6","Asia/Chita|+09|-90|0||33e4","Asia/Shanghai|CST|-80|0||23e6","Asia/Colombo|+0530|-5u|0||22e5","Asia/Damascus|EET EEST +03|-20 -30 -30|010101012|1XRy0 1nX0 11B0 1qL0 WN0 1qL0 WN0 1qL0|26e5","Europe/Athens|EET EEST|-20 -30|01010101010101010101010|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 11A0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|35e5","Asia/Gaza|EET EEST|-20 -30|01010101010101010101010|1XRy0 1on0 11B0 1o00 11A0 1qo0 XA0 1qp0 1cN0 1cL0 1a10 1fz0 17d0 1in0 11B0 1nX0 11B0 1qL0 WN0 1qL0 WN0 1qL0|18e5","Asia/Hong_Kong|HKT|-80|0||73e5","Asia/Jakarta|WIB|-70|0||31e6","Asia/Jayapura|WIT|-90|0||26e4","Asia/Jerusalem|IST IDT|-20 -30|01010101010101010101010|1XRA0 1oL0 10N0 1oL0 10N0 1rz0 W10 1rz0 W10 1rz0 10N0 1oL0 10N0 1oL0 10N0 1oL0 10N0 1rz0 W10 1rz0 W10 1rz0|81e4","Asia/Kabul|+0430|-4u|0||46e5","Asia/Karachi|PKT|-50|0||24e6","Asia/Kathmandu|+0545|-5J|0||12e5","Asia/Sakhalin|+11|-b0|0||58e4","Asia/Makassar|WITA|-80|0||15e5","Asia/Manila|PST|-80|0||24e6","Asia/Seoul|KST|-90|0||23e6","Asia/Rangoon|+0630|-6u|0||48e5","Asia/Tehran|+0330 +0430|-3u -4u|010101010|1XOIu 1dz0 1cp0 1dz0 1cN0 1dz0 1cp0 1dz0|14e6","Asia/Tokyo|JST|-90|0||38e6","Atlantic/Azores|-01 +00|10 0|01010101010101010101010|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 11A0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|25e4","Europe/Lisbon|WET WEST|0 -10|01010101010101010101010|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 11A0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|27e5","Atlantic/Cape_Verde|-01|10|0||50e4","Australia/Adelaide|ACDT ACST|-au -9u|01010101010101010101010|1XV4u 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1fA0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1fA0|11e5","Australia/Brisbane|AEST|-a0|0||20e5","Australia/Darwin|ACST|-9u|0||12e4","Australia/Eucla|+0845|-8J|0||368","Australia/Lord_Howe|+11 +1030|-b0 -au|01010101010101010101010|1XV30 1cMu 1cLu 1cMu 1cLu 1cMu 1cLu 1cMu 1cLu 1cMu 1fzu 1cMu 1cLu 1cMu 1cLu 1cMu 1cLu 1cMu 1cLu 1cMu 1cLu 1fAu|347","Australia/Perth|AWST|-80|0||18e5","Pacific/Easter|-05 -06|50 60|01010101010101010101010|1XVf0 11B0 1nX0 11B0 1nX0 11B0 1nX0 14p0 1lb0 11B0 1qL0 11B0 1nX0 11B0 1nX0 11B0 1nX0 11B0 1nX0 11B0 1qL0 WN0|30e2","Europe/Dublin|GMT IST|0 -10|01010101010101010101010|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 11A0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|12e5","Etc/GMT-1|+01|-10|0||","Pacific/Tongatapu|+13|-d0|0||75e3","Pacific/Kiritimati|+14|-e0|0||51e2","Etc/GMT-2|+02|-20|0||","Pacific/Tahiti|-10|a0|0||18e4","Pacific/Niue|-11|b0|0||12e2","Etc/GMT+12|-12|c0|0||","Pacific/Galapagos|-06|60|0||25e3","Etc/GMT+7|-07|70|0||","Pacific/Pitcairn|-08|80|0||56","Pacific/Gambier|-09|90|0||125","Etc/UTC|UTC|0|0||","Europe/London|GMT BST|0 -10|01010101010101010101010|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 11A0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|10e6","Europe/Chisinau|EET EEST|-20 -30|01010101010101010101010|1XSo0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 11A0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|67e4","Europe/Moscow|MSK|-30|0||16e6","Europe/Volgograd|+04 MSK|-40 -30|01|249a0|10e5","Pacific/Honolulu|HST|a0|0||37e4","MET|MET MEST|-10 -20|01010101010101010101010|1XSp0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0 11A0 1o00 11A0 1o00 11A0 1o00 11A0 1qM0 WM0 1qM0 WM0 1qM0|","Pacific/Chatham|+1345 +1245|-dJ -cJ|01010101010101010101010|1XV20 1a00 1fA0 1a00 1fA0 1a00 1fA0 1a00 1fA0 1a00 1io0 1a00 1fA0 1a00 1fA0 1a00 1fA0 1a00 1fA0 1a00 1fA0 1cM0|600","Pacific/Apia|+14 +13|-e0 -d0|010101|1XV20 1a00 1fA0 1a00 1fA0|37e3","Pacific/Fiji|+13 +12|-d0 -c0|010101|1Xnq0 20o0 pc0 2hc0 bc0|88e4","Pacific/Guam|ChST|-a0|0||17e4","Pacific/Marquesas|-0930|9u|0||86e2","Pacific/Pago_Pago|SST|b0|0||37e2","Pacific/Norfolk|+11 +12|-b0 -c0|0101010101010101010101|219P0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1fA0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1cM0 1fA0|25e4"],links:["Africa/Abidjan|Africa/Accra","Africa/Abidjan|Africa/Bamako","Africa/Abidjan|Africa/Banjul","Africa/Abidjan|Africa/Bissau","Africa/Abidjan|Africa/Conakry","Africa/Abidjan|Africa/Dakar","Africa/Abidjan|Africa/Freetown","Africa/Abidjan|Africa/Lome","Africa/Abidjan|Africa/Monrovia","Africa/Abidjan|Africa/Nouakchott","Africa/Abidjan|Africa/Ouagadougou","Africa/Abidjan|Africa/Timbuktu","Africa/Abidjan|America/Danmarkshavn","Africa/Abidjan|Atlantic/Reykjavik","Africa/Abidjan|Atlantic/St_Helena","Africa/Abidjan|Etc/GMT","Africa/Abidjan|Etc/GMT+0","Africa/Abidjan|Etc/GMT-0","Africa/Abidjan|Etc/GMT0","Africa/Abidjan|Etc/Greenwich","Africa/Abidjan|GMT","Africa/Abidjan|GMT+0","Africa/Abidjan|GMT-0","Africa/Abidjan|GMT0","Africa/Abidjan|Greenwich","Africa/Abidjan|Iceland","Africa/Algiers|Africa/Tunis","Africa/Cairo|Egypt","Africa/Casablanca|Africa/El_Aaiun","Africa/Johannesburg|Africa/Maseru","Africa/Johannesburg|Africa/Mbabane","Africa/Khartoum|Africa/Blantyre","Africa/Khartoum|Africa/Bujumbura","Africa/Khartoum|Africa/Gaborone","Africa/Khartoum|Africa/Harare","Africa/Khartoum|Africa/Kigali","Africa/Khartoum|Africa/Lubumbashi","Africa/Khartoum|Africa/Lusaka","Africa/Khartoum|Africa/Maputo","Africa/Khartoum|Africa/Windhoek","Africa/Lagos|Africa/Bangui","Africa/Lagos|Africa/Brazzaville","Africa/Lagos|Africa/Douala","Africa/Lagos|Africa/Kinshasa","Africa/Lagos|Africa/Libreville","Africa/Lagos|Africa/Luanda","Africa/Lagos|Africa/Malabo","Africa/Lagos|Africa/Ndjamena","Africa/Lagos|Africa/Niamey","Africa/Lagos|Africa/Porto-Novo","Africa/Nairobi|Africa/Addis_Ababa","Africa/Nairobi|Africa/Asmara","Africa/Nairobi|Africa/Asmera","Africa/Nairobi|Africa/Dar_es_Salaam","Africa/Nairobi|Africa/Djibouti","Africa/Nairobi|Africa/Kampala","Africa/Nairobi|Africa/Mogadishu","Africa/Nairobi|Indian/Antananarivo","Africa/Nairobi|Indian/Comoro","Africa/Nairobi|Indian/Mayotte","Africa/Tripoli|Europe/Kaliningrad","Africa/Tripoli|Libya","America/Adak|America/Atka","America/Adak|US/Aleutian","America/Anchorage|America/Juneau","America/Anchorage|America/Nome","America/Anchorage|America/Sitka","America/Anchorage|America/Yakutat","America/Anchorage|US/Alaska","America/Campo_Grande|America/Cuiaba","America/Caracas|America/Boa_Vista","America/Caracas|America/Guyana","America/Caracas|America/La_Paz","America/Caracas|America/Manaus","America/Caracas|America/Porto_Velho","America/Caracas|Brazil/West","America/Caracas|Etc/GMT+4","America/Chicago|America/Indiana/Knox","America/Chicago|America/Indiana/Tell_City","America/Chicago|America/Knox_IN","America/Chicago|America/Matamoros","America/Chicago|America/Menominee","America/Chicago|America/North_Dakota/Beulah","America/Chicago|America/North_Dakota/Center","America/Chicago|America/North_Dakota/New_Salem","America/Chicago|America/Rainy_River","America/Chicago|America/Rankin_Inlet","America/Chicago|America/Resolute","America/Chicago|America/Winnipeg","America/Chicago|CST6CDT","America/Chicago|Canada/Central","America/Chicago|US/Central","America/Chicago|US/Indiana-Starke","America/Denver|America/Boise","America/Denver|America/Cambridge_Bay","America/Denver|America/Edmonton","America/Denver|America/Inuvik","America/Denver|America/Shiprock","America/Denver|America/Yellowknife","America/Denver|Canada/Mountain","America/Denver|MST7MDT","America/Denver|Navajo","America/Denver|US/Mountain","America/Fortaleza|America/Araguaina","America/Fortaleza|America/Argentina/Buenos_Aires","America/Fortaleza|America/Argentina/Catamarca","America/Fortaleza|America/Argentina/ComodRivadavia","America/Fortaleza|America/Argentina/Cordoba","America/Fortaleza|America/Argentina/Jujuy","America/Fortaleza|America/Argentina/La_Rioja","America/Fortaleza|America/Argentina/Mendoza","America/Fortaleza|America/Argentina/Rio_Gallegos","America/Fortaleza|America/Argentina/Salta","America/Fortaleza|America/Argentina/San_Juan","America/Fortaleza|America/Argentina/San_Luis","America/Fortaleza|America/Argentina/Tucuman","America/Fortaleza|America/Argentina/Ushuaia","America/Fortaleza|America/Bahia","America/Fortaleza|America/Belem","America/Fortaleza|America/Buenos_Aires","America/Fortaleza|America/Catamarca","America/Fortaleza|America/Cayenne","America/Fortaleza|America/Cordoba","America/Fortaleza|America/Jujuy","America/Fortaleza|America/Maceio","America/Fortaleza|America/Mendoza","America/Fortaleza|America/Montevideo","America/Fortaleza|America/Paramaribo","America/Fortaleza|America/Punta_Arenas","America/Fortaleza|America/Recife","America/Fortaleza|America/Rosario","America/Fortaleza|America/Santarem","America/Fortaleza|Antarctica/Palmer","America/Fortaleza|Antarctica/Rothera","America/Fortaleza|Atlantic/Stanley","America/Fortaleza|Etc/GMT+3","America/Godthab|America/Nuuk","America/Halifax|America/Glace_Bay","America/Halifax|America/Goose_Bay","America/Halifax|America/Moncton","America/Halifax|America/Thule","America/Halifax|Atlantic/Bermuda","America/Halifax|Canada/Atlantic","America/Havana|Cuba","America/Lima|America/Bogota","America/Lima|America/Eirunepe","America/Lima|America/Guayaquil","America/Lima|America/Porto_Acre","America/Lima|America/Rio_Branco","America/Lima|Brazil/Acre","America/Lima|Etc/GMT+5","America/Los_Angeles|America/Ensenada","America/Los_Angeles|America/Santa_Isabel","America/Los_Angeles|America/Tijuana","America/Los_Angeles|America/Vancouver","America/Los_Angeles|Canada/Pacific","America/Los_Angeles|Mexico/BajaNorte","America/Los_Angeles|PST8PDT","America/Los_Angeles|US/Pacific","America/Managua|America/Belize","America/Managua|America/Costa_Rica","America/Managua|America/El_Salvador","America/Managua|America/Guatemala","America/Managua|America/Regina","America/Managua|America/Swift_Current","America/Managua|America/Tegucigalpa","America/Managua|Canada/Saskatchewan","America/Mazatlan|Mexico/BajaSur","America/Mexico_City|America/Bahia_Banderas","America/Mexico_City|America/Merida","America/Mexico_City|America/Monterrey","America/Mexico_City|Mexico/General","America/New_York|America/Detroit","America/New_York|America/Fort_Wayne","America/New_York|America/Grand_Turk","America/New_York|America/Indiana/Indianapolis","America/New_York|America/Indiana/Marengo","America/New_York|America/Indiana/Petersburg","America/New_York|America/Indiana/Vevay","America/New_York|America/Indiana/Vincennes","America/New_York|America/Indiana/Winamac","America/New_York|America/Indianapolis","America/New_York|America/Iqaluit","America/New_York|America/Kentucky/Louisville","America/New_York|America/Kentucky/Monticello","America/New_York|America/Louisville","America/New_York|America/Montreal","America/New_York|America/Nassau","America/New_York|America/Nipigon","America/New_York|America/Pangnirtung","America/New_York|America/Port-au-Prince","America/New_York|America/Thunder_Bay","America/New_York|America/Toronto","America/New_York|Canada/Eastern","America/New_York|EST5EDT","America/New_York|US/East-Indiana","America/New_York|US/Eastern","America/New_York|US/Michigan","America/Noronha|Atlantic/South_Georgia","America/Noronha|Brazil/DeNoronha","America/Noronha|Etc/GMT+2","America/Panama|America/Atikokan","America/Panama|America/Cancun","America/Panama|America/Cayman","America/Panama|America/Coral_Harbour","America/Panama|America/Jamaica","America/Panama|EST","America/Panama|Jamaica","America/Phoenix|America/Creston","America/Phoenix|America/Dawson_Creek","America/Phoenix|America/Fort_Nelson","America/Phoenix|America/Hermosillo","America/Phoenix|MST","America/Phoenix|US/Arizona","America/Santiago|Chile/Continental","America/Santo_Domingo|America/Anguilla","America/Santo_Domingo|America/Antigua","America/Santo_Domingo|America/Aruba","America/Santo_Domingo|America/Barbados","America/Santo_Domingo|America/Blanc-Sablon","America/Santo_Domingo|America/Curacao","America/Santo_Domingo|America/Dominica","America/Santo_Domingo|America/Grenada","America/Santo_Domingo|America/Guadeloupe","America/Santo_Domingo|America/Kralendijk","America/Santo_Domingo|America/Lower_Princes","America/Santo_Domingo|America/Marigot","America/Santo_Domingo|America/Martinique","America/Santo_Domingo|America/Montserrat","America/Santo_Domingo|America/Port_of_Spain","America/Santo_Domingo|America/Puerto_Rico","America/Santo_Domingo|America/St_Barthelemy","America/Santo_Domingo|America/St_Kitts","America/Santo_Domingo|America/St_Lucia","America/Santo_Domingo|America/St_Thomas","America/Santo_Domingo|America/St_Vincent","America/Santo_Domingo|America/Tortola","America/Santo_Domingo|America/Virgin","America/Sao_Paulo|Brazil/East","America/St_Johns|Canada/Newfoundland","America/Whitehorse|America/Dawson","America/Whitehorse|Canada/Yukon","Asia/Almaty|Asia/Qostanay","Asia/Bangkok|Antarctica/Davis","Asia/Bangkok|Asia/Barnaul","Asia/Bangkok|Asia/Ho_Chi_Minh","Asia/Bangkok|Asia/Hovd","Asia/Bangkok|Asia/Krasnoyarsk","Asia/Bangkok|Asia/Novokuznetsk","Asia/Bangkok|Asia/Novosibirsk","Asia/Bangkok|Asia/Phnom_Penh","Asia/Bangkok|Asia/Saigon","Asia/Bangkok|Asia/Tomsk","Asia/Bangkok|Asia/Vientiane","Asia/Bangkok|Etc/GMT-7","Asia/Bangkok|Indian/Christmas","Asia/Chita|Asia/Dili","Asia/Chita|Asia/Khandyga","Asia/Chita|Asia/Yakutsk","Asia/Chita|Etc/GMT-9","Asia/Chita|Pacific/Palau","Asia/Dhaka|Asia/Bishkek","Asia/Dhaka|Asia/Dacca","Asia/Dhaka|Asia/Kashgar","Asia/Dhaka|Asia/Omsk","Asia/Dhaka|Asia/Thimbu","Asia/Dhaka|Asia/Thimphu","Asia/Dhaka|Asia/Urumqi","Asia/Dhaka|Etc/GMT-6","Asia/Dhaka|Indian/Chagos","Asia/Dubai|Asia/Baku","Asia/Dubai|Asia/Muscat","Asia/Dubai|Asia/Tbilisi","Asia/Dubai|Asia/Yerevan","Asia/Dubai|Etc/GMT-4","Asia/Dubai|Europe/Astrakhan","Asia/Dubai|Europe/Samara","Asia/Dubai|Europe/Saratov","Asia/Dubai|Europe/Ulyanovsk","Asia/Dubai|Indian/Mahe","Asia/Dubai|Indian/Mauritius","Asia/Dubai|Indian/Reunion","Asia/Gaza|Asia/Hebron","Asia/Hong_Kong|Hongkong","Asia/Jakarta|Asia/Pontianak","Asia/Jerusalem|Asia/Tel_Aviv","Asia/Jerusalem|Israel","Asia/Kamchatka|Asia/Anadyr","Asia/Kamchatka|Etc/GMT-12","Asia/Kamchatka|Kwajalein","Asia/Kamchatka|Pacific/Funafuti","Asia/Kamchatka|Pacific/Kwajalein","Asia/Kamchatka|Pacific/Majuro","Asia/Kamchatka|Pacific/Nauru","Asia/Kamchatka|Pacific/Tarawa","Asia/Kamchatka|Pacific/Wake","Asia/Kamchatka|Pacific/Wallis","Asia/Kathmandu|Asia/Katmandu","Asia/Kolkata|Asia/Calcutta","Asia/Kuala_Lumpur|Asia/Brunei","Asia/Kuala_Lumpur|Asia/Choibalsan","Asia/Kuala_Lumpur|Asia/Irkutsk","Asia/Kuala_Lumpur|Asia/Kuching","Asia/Kuala_Lumpur|Asia/Singapore","Asia/Kuala_Lumpur|Asia/Ulaanbaatar","Asia/Kuala_Lumpur|Asia/Ulan_Bator","Asia/Kuala_Lumpur|Etc/GMT-8","Asia/Kuala_Lumpur|Singapore","Asia/Makassar|Asia/Ujung_Pandang","Asia/Rangoon|Asia/Yangon","Asia/Rangoon|Indian/Cocos","Asia/Sakhalin|Asia/Magadan","Asia/Sakhalin|Asia/Srednekolymsk","Asia/Sakhalin|Etc/GMT-11","Asia/Sakhalin|Pacific/Bougainville","Asia/Sakhalin|Pacific/Efate","Asia/Sakhalin|Pacific/Guadalcanal","Asia/Sakhalin|Pacific/Kosrae","Asia/Sakhalin|Pacific/Noumea","Asia/Sakhalin|Pacific/Pohnpei","Asia/Sakhalin|Pacific/Ponape","Asia/Seoul|Asia/Pyongyang","Asia/Seoul|ROK","Asia/Shanghai|Asia/Chongqing","Asia/Shanghai|Asia/Chungking","Asia/Shanghai|Asia/Harbin","Asia/Shanghai|Asia/Macao","Asia/Shanghai|Asia/Macau","Asia/Shanghai|Asia/Taipei","Asia/Shanghai|PRC","Asia/Shanghai|ROC","Asia/Tashkent|Antarctica/Mawson","Asia/Tashkent|Asia/Aqtau","Asia/Tashkent|Asia/Aqtobe","Asia/Tashkent|Asia/Ashgabat","Asia/Tashkent|Asia/Ashkhabad","Asia/Tashkent|Asia/Atyrau","Asia/Tashkent|Asia/Dushanbe","Asia/Tashkent|Asia/Oral","Asia/Tashkent|Asia/Qyzylorda","Asia/Tashkent|Asia/Samarkand","Asia/Tashkent|Asia/Yekaterinburg","Asia/Tashkent|Etc/GMT-5","Asia/Tashkent|Indian/Kerguelen","Asia/Tashkent|Indian/Maldives","Asia/Tehran|Iran","Asia/Tokyo|Japan","Asia/Vladivostok|Antarctica/DumontDUrville","Asia/Vladivostok|Asia/Ust-Nera","Asia/Vladivostok|Etc/GMT-10","Asia/Vladivostok|Pacific/Chuuk","Asia/Vladivostok|Pacific/Port_Moresby","Asia/Vladivostok|Pacific/Truk","Asia/Vladivostok|Pacific/Yap","Atlantic/Cape_Verde|Etc/GMT+1","Australia/Adelaide|Australia/Broken_Hill","Australia/Adelaide|Australia/South","Australia/Adelaide|Australia/Yancowinna","Australia/Brisbane|Australia/Lindeman","Australia/Brisbane|Australia/Queensland","Australia/Darwin|Australia/North","Australia/Lord_Howe|Australia/LHI","Australia/Perth|Australia/West","Australia/Sydney|Antarctica/Macquarie","Australia/Sydney|Australia/ACT","Australia/Sydney|Australia/Canberra","Australia/Sydney|Australia/Currie","Australia/Sydney|Australia/Hobart","Australia/Sydney|Australia/Melbourne","Australia/Sydney|Australia/NSW","Australia/Sydney|Australia/Tasmania","Australia/Sydney|Australia/Victoria","Etc/UTC|Etc/UCT","Etc/UTC|Etc/Universal","Etc/UTC|Etc/Zulu","Etc/UTC|UCT","Etc/UTC|UTC","Etc/UTC|Universal","Etc/UTC|Zulu","Europe/Athens|Asia/Famagusta","Europe/Athens|Asia/Nicosia","Europe/Athens|EET","Europe/Athens|Europe/Bucharest","Europe/Athens|Europe/Helsinki","Europe/Athens|Europe/Kiev","Europe/Athens|Europe/Kyiv","Europe/Athens|Europe/Mariehamn","Europe/Athens|Europe/Nicosia","Europe/Athens|Europe/Riga","Europe/Athens|Europe/Sofia","Europe/Athens|Europe/Tallinn","Europe/Athens|Europe/Uzhgorod","Europe/Athens|Europe/Vilnius","Europe/Athens|Europe/Zaporozhye","Europe/Chisinau|Europe/Tiraspol","Europe/Dublin|Eire","Europe/Istanbul|Antarctica/Syowa","Europe/Istanbul|Asia/Aden","Europe/Istanbul|Asia/Baghdad","Europe/Istanbul|Asia/Bahrain","Europe/Istanbul|Asia/Istanbul","Europe/Istanbul|Asia/Kuwait","Europe/Istanbul|Asia/Qatar","Europe/Istanbul|Asia/Riyadh","Europe/Istanbul|Etc/GMT-3","Europe/Istanbul|Europe/Minsk","Europe/Istanbul|Turkey","Europe/Lisbon|Atlantic/Canary","Europe/Lisbon|Atlantic/Faeroe","Europe/Lisbon|Atlantic/Faroe","Europe/Lisbon|Atlantic/Madeira","Europe/Lisbon|Portugal","Europe/Lisbon|WET","Europe/London|Europe/Belfast","Europe/London|Europe/Guernsey","Europe/London|Europe/Isle_of_Man","Europe/London|Europe/Jersey","Europe/London|GB","Europe/London|GB-Eire","Europe/Moscow|Europe/Kirov","Europe/Moscow|Europe/Simferopol","Europe/Moscow|W-SU","Europe/Paris|Africa/Ceuta","Europe/Paris|Arctic/Longyearbyen","Europe/Paris|Atlantic/Jan_Mayen","Europe/Paris|CET","Europe/Paris|Europe/Amsterdam","Europe/Paris|Europe/Andorra","Europe/Paris|Europe/Belgrade","Europe/Paris|Europe/Berlin","Europe/Paris|Europe/Bratislava","Europe/Paris|Europe/Brussels","Europe/Paris|Europe/Budapest","Europe/Paris|Europe/Busingen","Europe/Paris|Europe/Copenhagen","Europe/Paris|Europe/Gibraltar","Europe/Paris|Europe/Ljubljana","Europe/Paris|Europe/Luxembourg","Europe/Paris|Europe/Madrid","Europe/Paris|Europe/Malta","Europe/Paris|Europe/Monaco","Europe/Paris|Europe/Oslo","Europe/Paris|Europe/Podgorica","Europe/Paris|Europe/Prague","Europe/Paris|Europe/Rome","Europe/Paris|Europe/San_Marino","Europe/Paris|Europe/Sarajevo","Europe/Paris|Europe/Skopje","Europe/Paris|Europe/Stockholm","Europe/Paris|Europe/Tirane","Europe/Paris|Europe/Vaduz","Europe/Paris|Europe/Vatican","Europe/Paris|Europe/Vienna","Europe/Paris|Europe/Warsaw","Europe/Paris|Europe/Zagreb","Europe/Paris|Europe/Zurich","Europe/Paris|Poland","Pacific/Auckland|Antarctica/McMurdo","Pacific/Auckland|Antarctica/South_Pole","Pacific/Auckland|NZ","Pacific/Chatham|NZ-CHAT","Pacific/Easter|Chile/EasterIsland","Pacific/Galapagos|Etc/GMT+6","Pacific/Gambier|Etc/GMT+9","Pacific/Guam|Pacific/Saipan","Pacific/Honolulu|HST","Pacific/Honolulu|Pacific/Johnston","Pacific/Honolulu|US/Hawaii","Pacific/Kiritimati|Etc/GMT-14","Pacific/Niue|Etc/GMT+11","Pacific/Pago_Pago|Pacific/Midway","Pacific/Pago_Pago|Pacific/Samoa","Pacific/Pago_Pago|US/Samoa","Pacific/Pitcairn|Etc/GMT+8","Pacific/Tahiti|Etc/GMT+10","Pacific/Tahiti|Pacific/Rarotonga","Pacific/Tongatapu|Etc/GMT-13","Pacific/Tongatapu|Pacific/Enderbury","Pacific/Tongatapu|Pacific/Fakaofo","Pacific/Tongatapu|Pacific/Kanton"],countries:["AD|Europe/Andorra","AE|Asia/Dubai","AF|Asia/Kabul","AG|America/Puerto_Rico America/Antigua","AI|America/Puerto_Rico America/Anguilla","AL|Europe/Tirane","AM|Asia/Yerevan","AO|Africa/Lagos Africa/Luanda","AQ|Antarctica/Casey Antarctica/Davis Antarctica/Mawson Antarctica/Palmer Antarctica/Rothera Antarctica/Troll Antarctica/Vostok Pacific/Auckland Pacific/Port_Moresby Asia/Riyadh Antarctica/McMurdo Antarctica/DumontDUrville Antarctica/Syowa","AR|America/Argentina/Buenos_Aires America/Argentina/Cordoba America/Argentina/Salta America/Argentina/Jujuy America/Argentina/Tucuman America/Argentina/Catamarca America/Argentina/La_Rioja America/Argentina/San_Juan America/Argentina/Mendoza America/Argentina/San_Luis America/Argentina/Rio_Gallegos America/Argentina/Ushuaia","AS|Pacific/Pago_Pago","AT|Europe/Vienna","AU|Australia/Lord_Howe Antarctica/Macquarie Australia/Hobart Australia/Melbourne Australia/Sydney Australia/Broken_Hill Australia/Brisbane Australia/Lindeman Australia/Adelaide Australia/Darwin Australia/Perth Australia/Eucla","AW|America/Puerto_Rico America/Aruba","AX|Europe/Helsinki Europe/Mariehamn","AZ|Asia/Baku","BA|Europe/Belgrade Europe/Sarajevo","BB|America/Barbados","BD|Asia/Dhaka","BE|Europe/Brussels","BF|Africa/Abidjan Africa/Ouagadougou","BG|Europe/Sofia","BH|Asia/Qatar Asia/Bahrain","BI|Africa/Maputo Africa/Bujumbura","BJ|Africa/Lagos Africa/Porto-Novo","BL|America/Puerto_Rico America/St_Barthelemy","BM|Atlantic/Bermuda","BN|Asia/Kuching Asia/Brunei","BO|America/La_Paz","BQ|America/Puerto_Rico America/Kralendijk","BR|America/Noronha America/Belem America/Fortaleza America/Recife America/Araguaina America/Maceio America/Bahia America/Sao_Paulo America/Campo_Grande America/Cuiaba America/Santarem America/Porto_Velho America/Boa_Vista America/Manaus America/Eirunepe America/Rio_Branco","BS|America/Toronto America/Nassau","BT|Asia/Thimphu","BW|Africa/Maputo Africa/Gaborone","BY|Europe/Minsk","BZ|America/Belize","CA|America/St_Johns America/Halifax America/Glace_Bay America/Moncton America/Goose_Bay America/Toronto America/Iqaluit America/Winnipeg America/Resolute America/Rankin_Inlet America/Regina America/Swift_Current America/Edmonton America/Cambridge_Bay America/Inuvik America/Dawson_Creek America/Fort_Nelson America/Whitehorse America/Dawson America/Vancouver America/Panama America/Puerto_Rico America/Phoenix America/Blanc-Sablon America/Atikokan America/Creston","CC|Asia/Yangon Indian/Cocos","CD|Africa/Maputo Africa/Lagos Africa/Kinshasa Africa/Lubumbashi","CF|Africa/Lagos Africa/Bangui","CG|Africa/Lagos Africa/Brazzaville","CH|Europe/Zurich","CI|Africa/Abidjan","CK|Pacific/Rarotonga","CL|America/Santiago America/Punta_Arenas Pacific/Easter","CM|Africa/Lagos Africa/Douala","CN|Asia/Shanghai Asia/Urumqi","CO|America/Bogota","CR|America/Costa_Rica","CU|America/Havana","CV|Atlantic/Cape_Verde","CW|America/Puerto_Rico America/Curacao","CX|Asia/Bangkok Indian/Christmas","CY|Asia/Nicosia Asia/Famagusta","CZ|Europe/Prague","DE|Europe/Zurich Europe/Berlin Europe/Busingen","DJ|Africa/Nairobi Africa/Djibouti","DK|Europe/Berlin Europe/Copenhagen","DM|America/Puerto_Rico America/Dominica","DO|America/Santo_Domingo","DZ|Africa/Algiers","EC|America/Guayaquil Pacific/Galapagos","EE|Europe/Tallinn","EG|Africa/Cairo","EH|Africa/El_Aaiun","ER|Africa/Nairobi Africa/Asmara","ES|Europe/Madrid Africa/Ceuta Atlantic/Canary","ET|Africa/Nairobi Africa/Addis_Ababa","FI|Europe/Helsinki","FJ|Pacific/Fiji","FK|Atlantic/Stanley","FM|Pacific/Kosrae Pacific/Port_Moresby Pacific/Guadalcanal Pacific/Chuuk Pacific/Pohnpei","FO|Atlantic/Faroe","FR|Europe/Paris","GA|Africa/Lagos Africa/Libreville","GB|Europe/London","GD|America/Puerto_Rico America/Grenada","GE|Asia/Tbilisi","GF|America/Cayenne","GG|Europe/London Europe/Guernsey","GH|Africa/Abidjan Africa/Accra","GI|Europe/Gibraltar","GL|America/Nuuk America/Danmarkshavn America/Scoresbysund America/Thule","GM|Africa/Abidjan Africa/Banjul","GN|Africa/Abidjan Africa/Conakry","GP|America/Puerto_Rico America/Guadeloupe","GQ|Africa/Lagos Africa/Malabo","GR|Europe/Athens","GS|Atlantic/South_Georgia","GT|America/Guatemala","GU|Pacific/Guam","GW|Africa/Bissau","GY|America/Guyana","HK|Asia/Hong_Kong","HN|America/Tegucigalpa","HR|Europe/Belgrade Europe/Zagreb","HT|America/Port-au-Prince","HU|Europe/Budapest","ID|Asia/Jakarta Asia/Pontianak Asia/Makassar Asia/Jayapura","IE|Europe/Dublin","IL|Asia/Jerusalem","IM|Europe/London Europe/Isle_of_Man","IN|Asia/Kolkata","IO|Indian/Chagos","IQ|Asia/Baghdad","IR|Asia/Tehran","IS|Africa/Abidjan Atlantic/Reykjavik","IT|Europe/Rome","JE|Europe/London Europe/Jersey","JM|America/Jamaica","JO|Asia/Amman","JP|Asia/Tokyo","KE|Africa/Nairobi","KG|Asia/Bishkek","KH|Asia/Bangkok Asia/Phnom_Penh","KI|Pacific/Tarawa Pacific/Kanton Pacific/Kiritimati","KM|Africa/Nairobi Indian/Comoro","KN|America/Puerto_Rico America/St_Kitts","KP|Asia/Pyongyang","KR|Asia/Seoul","KW|Asia/Riyadh Asia/Kuwait","KY|America/Panama America/Cayman","KZ|Asia/Almaty Asia/Qyzylorda Asia/Qostanay Asia/Aqtobe Asia/Aqtau Asia/Atyrau Asia/Oral","LA|Asia/Bangkok Asia/Vientiane","LB|Asia/Beirut","LC|America/Puerto_Rico America/St_Lucia","LI|Europe/Zurich Europe/Vaduz","LK|Asia/Colombo","LR|Africa/Monrovia","LS|Africa/Johannesburg Africa/Maseru","LT|Europe/Vilnius","LU|Europe/Brussels Europe/Luxembourg","LV|Europe/Riga","LY|Africa/Tripoli","MA|Africa/Casablanca","MC|Europe/Paris Europe/Monaco","MD|Europe/Chisinau","ME|Europe/Belgrade Europe/Podgorica","MF|America/Puerto_Rico America/Marigot","MG|Africa/Nairobi Indian/Antananarivo","MH|Pacific/Tarawa Pacific/Kwajalein Pacific/Majuro","MK|Europe/Belgrade Europe/Skopje","ML|Africa/Abidjan Africa/Bamako","MM|Asia/Yangon","MN|Asia/Ulaanbaatar Asia/Hovd Asia/Choibalsan","MO|Asia/Macau","MP|Pacific/Guam Pacific/Saipan","MQ|America/Martinique","MR|Africa/Abidjan Africa/Nouakchott","MS|America/Puerto_Rico America/Montserrat","MT|Europe/Malta","MU|Indian/Mauritius","MV|Indian/Maldives","MW|Africa/Maputo Africa/Blantyre","MX|America/Mexico_City America/Cancun America/Merida America/Monterrey America/Matamoros America/Chihuahua America/Ciudad_Juarez America/Ojinaga America/Mazatlan America/Bahia_Banderas America/Hermosillo America/Tijuana","MY|Asia/Kuching Asia/Singapore Asia/Kuala_Lumpur","MZ|Africa/Maputo","NA|Africa/Windhoek","NC|Pacific/Noumea","NE|Africa/Lagos Africa/Niamey","NF|Pacific/Norfolk","NG|Africa/Lagos","NI|America/Managua","NL|Europe/Brussels Europe/Amsterdam","NO|Europe/Berlin Europe/Oslo","NP|Asia/Kathmandu","NR|Pacific/Nauru","NU|Pacific/Niue","NZ|Pacific/Auckland Pacific/Chatham","OM|Asia/Dubai Asia/Muscat","PA|America/Panama","PE|America/Lima","PF|Pacific/Tahiti Pacific/Marquesas Pacific/Gambier","PG|Pacific/Port_Moresby Pacific/Bougainville","PH|Asia/Manila","PK|Asia/Karachi","PL|Europe/Warsaw","PM|America/Miquelon","PN|Pacific/Pitcairn","PR|America/Puerto_Rico","PS|Asia/Gaza Asia/Hebron","PT|Europe/Lisbon Atlantic/Madeira Atlantic/Azores","PW|Pacific/Palau","PY|America/Asuncion","QA|Asia/Qatar","RE|Asia/Dubai Indian/Reunion","RO|Europe/Bucharest","RS|Europe/Belgrade","RU|Europe/Kaliningrad Europe/Moscow Europe/Simferopol Europe/Kirov Europe/Volgograd Europe/Astrakhan Europe/Saratov Europe/Ulyanovsk Europe/Samara Asia/Yekaterinburg Asia/Omsk Asia/Novosibirsk Asia/Barnaul Asia/Tomsk Asia/Novokuznetsk Asia/Krasnoyarsk Asia/Irkutsk Asia/Chita Asia/Yakutsk Asia/Khandyga Asia/Vladivostok Asia/Ust-Nera Asia/Magadan Asia/Sakhalin Asia/Srednekolymsk Asia/Kamchatka Asia/Anadyr","RW|Africa/Maputo Africa/Kigali","SA|Asia/Riyadh","SB|Pacific/Guadalcanal","SC|Asia/Dubai Indian/Mahe","SD|Africa/Khartoum","SE|Europe/Berlin Europe/Stockholm","SG|Asia/Singapore","SH|Africa/Abidjan Atlantic/St_Helena","SI|Europe/Belgrade Europe/Ljubljana","SJ|Europe/Berlin Arctic/Longyearbyen","SK|Europe/Prague Europe/Bratislava","SL|Africa/Abidjan Africa/Freetown","SM|Europe/Rome Europe/San_Marino","SN|Africa/Abidjan Africa/Dakar","SO|Africa/Nairobi Africa/Mogadishu","SR|America/Paramaribo","SS|Africa/Juba","ST|Africa/Sao_Tome","SV|America/El_Salvador","SX|America/Puerto_Rico America/Lower_Princes","SY|Asia/Damascus","SZ|Africa/Johannesburg Africa/Mbabane","TC|America/Grand_Turk","TD|Africa/Ndjamena","TF|Asia/Dubai Indian/Maldives Indian/Kerguelen","TG|Africa/Abidjan Africa/Lome","TH|Asia/Bangkok","TJ|Asia/Dushanbe","TK|Pacific/Fakaofo","TL|Asia/Dili","TM|Asia/Ashgabat","TN|Africa/Tunis","TO|Pacific/Tongatapu","TR|Europe/Istanbul","TT|America/Puerto_Rico America/Port_of_Spain","TV|Pacific/Tarawa Pacific/Funafuti","TW|Asia/Taipei","TZ|Africa/Nairobi Africa/Dar_es_Salaam","UA|Europe/Simferopol Europe/Kyiv","UG|Africa/Nairobi Africa/Kampala","UM|Pacific/Pago_Pago Pacific/Tarawa Pacific/Midway Pacific/Wake","US|America/New_York America/Detroit America/Kentucky/Louisville America/Kentucky/Monticello America/Indiana/Indianapolis America/Indiana/Vincennes America/Indiana/Winamac America/Indiana/Marengo America/Indiana/Petersburg America/Indiana/Vevay America/Chicago America/Indiana/Tell_City America/Indiana/Knox America/Menominee America/North_Dakota/Center America/North_Dakota/New_Salem America/North_Dakota/Beulah America/Denver America/Boise America/Phoenix America/Los_Angeles America/Anchorage America/Juneau America/Sitka America/Metlakatla America/Yakutat America/Nome America/Adak Pacific/Honolulu","UY|America/Montevideo","UZ|Asia/Samarkand Asia/Tashkent","VA|Europe/Rome Europe/Vatican","VC|America/Puerto_Rico America/St_Vincent","VE|America/Caracas","VG|America/Puerto_Rico America/Tortola","VI|America/Puerto_Rico America/St_Thomas","VN|Asia/Bangkok Asia/Ho_Chi_Minh","VU|Pacific/Efate","WF|Pacific/Tarawa Pacific/Wallis","WS|Pacific/Apia","YE|Asia/Riyadh Asia/Aden","YT|Africa/Nairobi Indian/Mayotte","ZA|Africa/Johannesburg","ZM|Africa2/Maputo Africa/Lusaka","ZW|Africa/Maputo Africa/Harare"]}),o});
global.EC_GLOBAL_MOMENT = global.moment; global.moment = global.moment_original || (delete global.moment, undefined);
})(typeof window !== 'undefined' ? window : global);
CAFE24.GLOBAL_DATETIME = (function() {
    var getDateTimeInfo = function (sInfo, mDefault) {
       if (typeof window.CAFE24.GLOBAL_DATETIME_INFO === 'object' && sInfo in window.CAFE24.GLOBAL_DATETIME_INFO) {
           return window.CAFE24.GLOBAL_DATETIME_INFO[sInfo];
       }

       return mDefault;
    };
    var oConstants = getDateTimeInfo('oConstants', {});
    var oOptions = getDateTimeInfo('oOptions', {});
    var oPolicies = getDateTimeInfo('oPolicies', {});
    var sOverrideTimezone = getDateTimeInfo('sOverrideTimezone', '');
    var sMomentNamespace = getDateTimeInfo('sMomentNamespace', '');

    var fMomentLoaded = function() {
        var bMomentLoaded = !!window[sMomentNamespace];
        var bMomentTZLoaded = false;
        if (bMomentLoaded) {
            bMomentTZLoaded = !!window[sMomentNamespace].tz;
        }

        return bMomentLoaded && bMomentTZLoaded;
    };

    var fMomentWrapper = function() {
        return window[sMomentNamespace];
    };

    var fShallowMerge = function(oTarget, oSource) {
        oSource = oSource || {};
        for (var sKey in oSource) {
            if (oSource.hasOwnProperty(sKey)) {
                oTarget[sKey] = oSource[sKey];
            }
        }

        return oTarget;
    };

    var getFormatFromFlag = function(oOptions, iFlag, bOpposite) {
        if (bOpposite) {
            switch (iFlag) {
                case 1:
                    return oOptions[oConstants.IN_DATE_FORMAT];
                case 2:
                    return oOptions[oConstants.IN_TIME_FORMAT];
                default:
                    return oOptions[oConstants.IN_FORMAT];
            }
        }

        switch (iFlag) {
            case 1:
                return oOptions[oConstants.OUT_DATE_FORMAT];
            case 2:
                return oOptions[oConstants.OUT_TIME_FORMAT];
            default:
                return oOptions[oConstants.OUT_FORMAT];
        }
    };

    return {
        'const': oConstants,

        init: function(fCallback) {
            if (fMomentLoaded()) {
                if (typeof fCallback === 'function') {
                    fCallback();
                }

                return;
            }

            var oScript = document.createElement('script');
            oScript.type = 'text/javascript';
            oScript.async = true;
            oScript.src = '/ind-script/moment.php?convert={$sConvert}';
            oScript.onload = oScript.onreadystatechange = function () {
                fMomentWrapper().defaultFormat = oOptions[oConstants.OUT_FORMAT];
                fMomentWrapper().tz.setDefault(oOptions[oConstants.IN_ZONE]);

                if (typeof fCallback === 'function') {
                    fCallback();
                }
            };

            var oFirstScript = document.getElementsByTagName('script')[0];
            oFirstScript.parentNode.insertBefore(oScript, oFirstScript);
        },

        initPromise: function() {
            if (!window.Promise) {
                return;
            }

            return new Promise(function(resolve) {
                this.init(resolve);
            }.bind(this));
        },

        isLoaded: function() {
            return fMomentLoaded();
        },

        setOptions: function(oNewOptions) {
            if (typeof oNewOptions === 'object') {
                for (var sKey in oNewOptions) {
                    if (oNewOptions.hasOwnProperty(sKey) && oOptions.hasOwnProperty(sKey)) {
                        oOptions[sKey] = oNewOptions[sKey];
                    }
                }
            }

            return this;
        },

        now: function(mOptions, iFlag) {
            if (fMomentLoaded() === false) {
                return Math.floor(new Date().getTime() / 1000);
            }

            var oFormatOptions = this.getOptions(mOptions);
            return fMomentWrapper()()
                .tz(oFormatOptions.outZone)
                .format(getFormatFromFlag(oFormatOptions, iFlag));
        },

        format: function(sTime, mOptions, iFlag) {
            if (fMomentLoaded() === false) {
                return sTime;
            }

            var oFormatOptions = this.getOptions(mOptions);
            return fMomentWrapper()
                .tz(sTime, oFormatOptions.inZone)
                .tz(oFormatOptions.outZone)
                .format(getFormatFromFlag(oFormatOptions, iFlag));
        },

        parse: function(sTime, mOptions) {
            if (fMomentLoaded() === false) {
                return sTime;
            }

            var oParseOptions = this.getOptions(mOptions);
            return fMomentWrapper().tz((sTime || new Date()), oParseOptions.inZone).tz(oParseOptions.outZone);
        },

        getOptions: function(mOptions, iFlag) {
            mOptions = mOptions || {};

            var oMergedOptions = fShallowMerge({}, oOptions);
            if (typeof mOptions === 'string' && oPolicies[mOptions]) {
                oMergedOptions = fShallowMerge(oMergedOptions, oPolicies[mOptions]);
            } else if (typeof mOptions === 'object') {
                oMergedOptions = fShallowMerge(oMergedOptions, mOptions);
            }

            if (sOverrideTimezone) {
                if ((typeof mOptions === 'string' && mOptions === 'shop') || (typeof mOptions === 'object' && !mOptions[oConstants.OUT_ZONE])) {
                    oMergedOptions[oConstants.OUT_ZONE] = sOverrideTimezone;
                }
            }

            return oMergedOptions;
        },

        getRevertOptions: function(mOptions) {
            var oCurrentOptions = this.getOptions(mOptions);
            var oMergedOptions = fShallowMerge({}, oOptions);
            oMergedOptions[oConstants.IN_ZONE] = oCurrentOptions[oConstants.OUT_ZONE];
            oMergedOptions[oConstants.IN_FORMAT] = oCurrentOptions[oConstants.OUT_FORMAT];
            oMergedOptions[oConstants.IN_DATE_FORMAT] = oCurrentOptions[oConstants.OUT_DATE_FORMAT];
            oMergedOptions[oConstants.IN_TIME_FORMAT] = oCurrentOptions[oConstants.OUT_TIME_FORMAT];

            return oMergedOptions;
        },

        today: function(sTime, mOptions, iFlag) {
            if (fMomentLoaded() === false) {
                throw new Error('MomentJS didnt initialize');
            }

            mOptions = mOptions || 'shop';
            var oRevertOptions = this.getRevertOptions(mOptions);
            var oToday;
            if (!sTime || sTime === 'now') {
                oToday = this.parse('', mOptions);
            } else {
                iFlag = iFlag || oConstants.IN_FORMAT_ALL || 3;
                oToday = fMomentWrapper().tz(sTime, getFormatFromFlag(oRevertOptions, iFlag, true), oRevertOptions[oConstants.IN_ZONE]);
                if (oToday.isValid() === false) {
                    var oStandardDateRegex = new RegExp(oConstants.STANDARD_DATE_REGEX.replace(/\//g, ''));
                    if (oStandardDateRegex.test(sTime) === true) {
                        oToday = fMomentWrapper().tz(sTime, oRevertOptions[oConstants.IN_ZONE]);
                    } else {
                        oToday = fMomentWrapper()();
                    }
                }
            }

            var oStartOfDay = oToday.clone().startOf('day');
            var oEndOfDay = oToday.clone().endOf('day');

            var sStartOfDayInSeoul = oStartOfDay.tz(oConstants.SEOUL).format(oConstants.FULL_TIME);
            var sEndOfDayInSeoul = oEndOfDay.tz(oConstants.SEOUL).format(oConstants.FULL_TIME);

            return [sStartOfDayInSeoul, sEndOfDayInSeoul];
        },

        parseFromFormat: function(sTime, mOptions, iFlag) {
            if (fMomentLoaded() === false) {
                return sTime;
            }

            mOptions = mOptions || 'shop';
            iFlag = iFlag || oConstants.IN_FORMAT_ALL || 3;

            var oRevertOptions = {};
            if (typeof mOptions === 'string') {
                oRevertOptions = this.getRevertOptions(mOptions);
            } else {
                oRevertOptions = this.getOptions(mOptions);
            }

            return fMomentWrapper()(sTime, getFormatFromFlag(oRevertOptions, iFlag, true));
        }
    };
})();

var EC_GLOBAL_DATETIME = CAFE24.getDeprecatedNamespace('EC_GLOBAL_DATETIME');

!function() {
    'use strict';
    var re = {
        not_string: /[^s]/,
        not_bool: /[^t]/,
        not_type: /[^T]/,
        not_primitive: /[^v]/,
        number: /[diefg]/,
        numeric_arg: /[bcdiefguxX]/,
        json: /[j]/,
        not_json: /[^j]/,
        text: /^[^\x25]+/,
        modulo: /^\x25{2}/,
        placeholder: /^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-gijostTuvxX])/,
        key: /^([a-z_][a-z_\d]*)/i,
        key_access: /^\.([a-z_][a-z_\d]*)/i,
        index_access: /^\[(\d+)\]/,
        sign: /^[\+\-]/
    };

    function sprintf(key) {
        try {
            // `arguments` is not an array, but should be fine for this call
            return sprintf_format(sprintf_parse(key), arguments);
        } catch (e) {
            return key;
        }
    }

    function vsprintf(fmt, argv) {
        return sprintf.apply(null, [fmt].concat(argv || []));
    }

    function sprintf_format(parse_tree, argv) {
        var cursor = 1, tree_length = parse_tree.length, arg, output = '', i, k, ph, pad, pad_character, pad_length, is_positive, sign;
        for (i = 0; i < tree_length; i++) {
            if (typeof parse_tree[i] === 'string') {
                output += parse_tree[i];
            }
            else if (typeof parse_tree[i] === 'object') {
                ph = parse_tree[i]; // convenience purposes only
                if (ph.keys) { // keyword argument
                    arg = argv[cursor];
                    for (k = 0; k < ph.keys.length; k++) {
                        if (arg == undefined) {
                            throw new Error(sprintf('[sprintf] Cannot access property "%s" of undefined value "%s"', ph.keys[k], ph.keys[k-1]));
                        }
                        arg = arg[ph.keys[k]];
                    }
                }
                else if (ph.param_no) { // positional argument (explicit)
                    arg = argv[ph.param_no];
                }
                else { // positional argument (implicit)
                    arg = argv[cursor++];
                }

                if (re.not_type.test(ph.type) && re.not_primitive.test(ph.type) && arg instanceof Function) {
                    arg = arg();
                }

                if (re.numeric_arg.test(ph.type) && (typeof arg !== 'number' && isNaN(arg))) {
                    throw new TypeError(sprintf('[sprintf] expecting number but found %T', arg));
                }

                if (re.number.test(ph.type)) {
                    is_positive = arg >= 0;
                }

                switch (ph.type) {
                    case 'b':
                        arg = parseInt(arg, 10).toString(2);
                        break;
                    case 'c':
                        arg = String.fromCharCode(parseInt(arg, 10));
                        break;
                    case 'd':
                    case 'i':
                        arg = parseInt(arg, 10);
                        break;
                    case 'j':
                        arg = JSON.stringify(arg, null, ph.width ? parseInt(ph.width) : 0);
                        break;
                    case 'e':
                        arg = ph.precision ? parseFloat(arg).toExponential(ph.precision) : parseFloat(arg).toExponential();
                        break;
                    case 'f':
                        arg = ph.precision ? parseFloat(arg).toFixed(ph.precision) : parseFloat(arg);
                        break;
                    case 'g':
                        arg = ph.precision ? String(Number(arg.toPrecision(ph.precision))) : parseFloat(arg);
                        break;
                    case 'o':
                        arg = (parseInt(arg, 10) >>> 0).toString(8);
                        break;
                    case 's':
                        arg = String(arg);
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg);
                        break;
                    case 't':
                        arg = String(!!arg);
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg);
                        break;
                    case 'T':
                        arg = Object.prototype.toString.call(arg).slice(8, -1).toLowerCase();
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg);
                        break;
                    case 'u':
                        arg = parseInt(arg, 10) >>> 0;
                        break;
                    case 'v':
                        arg = arg.valueOf();
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg);
                        break;
                    case 'x':
                        arg = (parseInt(arg, 10) >>> 0).toString(16);
                        break;
                    case 'X':
                        arg = (parseInt(arg, 10) >>> 0).toString(16).toUpperCase();
                        break;
                }
                if (re.json.test(ph.type)) {
                    output += arg;
                }
                else {
                    if (re.number.test(ph.type) && (!is_positive || ph.sign)) {
                        sign = is_positive ? '+' : '-';
                        arg = arg.toString().replace(re.sign, '');
                    }
                    else {
                        sign = '';
                    }
                    pad_character = ph.pad_char ? ph.pad_char === '0' ? '0' : ph.pad_char.charAt(1) : ' ';
                    pad_length = ph.width - (sign + arg).length;
                    pad = ph.width ? (pad_length > 0 ? pad_character.repeat(pad_length) : '') : '';
                    output += ph.align ? sign + arg + pad : (pad_character === '0' ? sign + pad + arg : pad + sign + arg);
                }
            }
        }
        return output;
    }

    var sprintf_cache = Object.create(null);

    function sprintf_parse(fmt) {
        if (sprintf_cache[fmt]) {
            return sprintf_cache[fmt];
        }

        var _fmt = fmt, match, parse_tree = [], arg_names = 0;
        while (_fmt) {
            if ((match = re.text.exec(_fmt)) !== null) {
                parse_tree.push(match[0]);
            }
            else if ((match = re.modulo.exec(_fmt)) !== null) {
                parse_tree.push('%');
            }
            else if ((match = re.placeholder.exec(_fmt)) !== null) {
                if (match[2]) {
                    arg_names |= 1;
                    var field_list = [], replacement_field = match[2], field_match = [];
                    if ((field_match = re.key.exec(replacement_field)) !== null) {
                        field_list.push(field_match[1]);
                        while ((replacement_field = replacement_field.substring(field_match[0].length)) !== '') {
                            if ((field_match = re.key_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1]);
                            }
                            else if ((field_match = re.index_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1]);
                            }
                            else {
                                throw new SyntaxError('[sprintf] failed to parse named argument key');
                            }
                        }
                    }
                    else {
                        throw new SyntaxError('[sprintf] failed to parse named argument key');
                    }
                    match[2] = field_list;
                }
                else {
                    arg_names |= 2;
                }
                if (arg_names === 3) {
                    throw new Error('[sprintf] mixing positional and named placeholders is not (yet) supported');
                }
                parse_tree.push(
                    {
                        placeholder: match[0],
                        param_no: match[1],
                        keys: match[2],
                        sign: match[3],
                        pad_char: match[4],
                        align: match[5],
                        width: match[6],
                        precision: match[7],
                        type: match[8]
                    }
                );
            } else {
                throw new SyntaxError('[sprintf] unexpected placeholder');
            }
            _fmt = _fmt.substring(match[0].length);
        }
        return sprintf_cache[fmt] = parse_tree;
    }

    /**
     * export to either browser or node.js
     */
    /* eslint-disable quote-props */
    if (typeof exports !== 'undefined') {
        exports['sprintf'] = sprintf;
        exports['vsprintf'] = vsprintf;
    }
    if (typeof window !== 'undefined') {
        window['sprintf'] = sprintf;
        window['vsprintf'] = vsprintf;

        if (typeof define === 'function' && define['amd']) {
            define(function() {
                return {
                    'sprintf': sprintf,
                    'vsprintf': vsprintf
                };
            });
        }
    }
    /* eslint-enable quote-props */
}();

/*
 * 각개체 별 항목 컨트롤 을 위해서 차후 확장을 고려 하여 별도로 추출
 * 
 */
var secondZipcodeHidden = function() {
    //Front Page 우편번호 2번째 엘레멘트 리스트
    var secondZipcodeElementId = ["postcode2", "rzipcode2", "ozipcode2", "zip2", "address_zip2"];
    for (var i in secondZipcodeElementId) {
        try {
            document.getElementById(secondZipcodeElementId[i]).style.display = "none";
        } catch (e) { }
    }

    // 구디자인 회원 가입수정 zip2 제거
    try {
        document.frm.zip2.style.display = "none";
    } catch (e) { }

    // 구디자인 배송목록 zip2 제거
    try {
        document.addr_set.rcv_zipcode2.style.display = "none";
    } catch (e) { }

    // 구디자인 주문서 작성 zip2 제거
    try {
        document.frm.rzipcode2.style.display = "none";
        document.frm.ozipcode2.style.display = "none";
    } catch (e) { }

    // 구디자인 세금계산서 신청약식 zip2 제거
    try {
        document.frm.mall_zipcode2.style.display = "none";
    } catch (e) { }
};

secondZipcodeHidden();

CAFE24.PLUSAPP_BRIDGE = (function() {
    var bUsePlusAppBridge = false;

    return {
        /**
         * 해당 메소드를 사용해야 플러스앱에 데이터 전달 가능
         */
        setBridgeFunction: function () {
            bUsePlusAppBridge = true;
        },

        /**
         * 플러스앱에 여러 데이터 전달
         * @param oBridgeData JSON 타입의 데이터
         */
        sendBridgeData: function (oBridgeData) {
            if (bUsePlusAppBridge && typeof (UsePlusAppBridge) !== undefined) {
                var browserInfo = navigator.userAgent;
                try {
                    if (browserInfo.indexOf("Cafe24Plus") > -1) {
                        var sBridgeData = JSON.stringify(oBridgeData);

                        // Flutter 데이터 전송
                        if (window.flutter_inappwebview && window.flutter_inappwebview.callHandler) {
                            window.flutter_inappwebview.callHandler("sendDatasToApp", sBridgeData);
                            return;
                        } else {
                            if (window.flutter_inappwebview && window.flutter_inappwebview._callHandler) {
                                window.flutter_inappwebview._callHandler("sendDatasToApp", sBridgeData);
                                return;
                            }
                        }

                        if (browserInfo.indexOf("android") > -1) {
                            if (window.PlusAppBridge.hasOwnProperty('sendDatasToApp') === true) {
                                window.PlusAppBridge.sendDatasToApp(sBridgeData);
                            }
                        } else if (typeof (webkit.messageHandlers.sendDatasToApp) !== 'undefined') {
                            webkit.messageHandlers.sendDatasToApp.postMessage(sBridgeData);
                        }
                    }
                } catch (e) {}
            }
        },

        /**
         * 상품 번호 반환
         * @param sCheckedProduct
         */
        getProductNo: function (sCheckedProduct) {
            return sCheckedProduct.split(':')[0];
        },

        /**
         * serialize 된 Form을 Json Object 포맷으로 변경
         * @param sParam
         */
        unserialize: function (sParam) {
            var objParam, aParam;
            aParam = sParam.replace(/\?/, "").split("&");
            objParam = {};
            EC$.each(aParam, function(iKey, sValue) {
                var aValue = sValue.split('=');
                return objParam[aValue[0]] = aValue[1];
            });
            return objParam;
        },

        /**
         * 장바구니 등록 이벤트시 데이터 전송 처리
         * @param oParam
         */
        addBasket: function (oParam) {
            var oData = {
                type: 'basket',
                raw_data: oParam
            };

            CAFE24.PLUSAPP_BRIDGE.sendBridgeData(oData);
        },

        /**
         * 위시리스트 등록 이벤트시 데이터 전송 처리
         * @param iProductNo
         */
        addWishList: function (iProductNo) {
            var oData = {
                type: 'wish',
                raw_data: {
                    product_no: iProductNo
                }
            };

            CAFE24.PLUSAPP_BRIDGE.sendBridgeData(oData);
        },

        /**
         * SNS 링크 공유하기 이벤트시 데이터 전송 처리
         * @param {string} sMedia 소셜 미디어
         * @param {int} iProductNo 상품번호
         */
        shareSocialLink: function (sMedia, iProductNo) {
            var oData = {
                type: 'share',
                raw_data: {
                    method: sMedia,
                    product_no: iProductNo,
                }
            };

            CAFE24.PLUSAPP_BRIDGE.sendBridgeData(oData);
        },

        /**
         * 시리얼 쿠폰 등록에 성공시 데이터 전송 처리
         * @param string sCouponCode 시리얼 쿠폰 코드
         */
        addSerialCoupon: function (sCouponCode) {
            var oData = {
                type: 'coupon',
                raw_data: {
                    coupon_code: sCouponCode
                }
            };

            CAFE24.PLUSAPP_BRIDGE.sendBridgeData(oData);
        },

        /**
         * 주문 완료 후 데이터 전송 처리
         * @param object oParam 주문 완료 데이터
         */
        addOrderResult: function (oParam) {
            CAFE24.PLUSAPP_BRIDGE.sendBridgeData(oParam);
        },

        /**
         * 프론트 주문서에서 결제 시작시 데이터 전송 처리
         * @param object oParam 결제 시작 데이터 (결제 금액, 통화 정보)
         */
        addBeginCheckout: function (oParam) {
            var oData = {
                type: 'begin_checkout',
                raw_data: oParam
            };

            CAFE24.PLUSAPP_BRIDGE.sendBridgeData(oData);
        }
    };
})();

var EC_PlusAppBridge = CAFE24.getDeprecatedNamespace('PLUSAPP_BRIDGE');

CAFE24.UTIL = CAFE24.UTIL || {};

// $.parseJSON 대체
CAFE24.UTIL.parseJSON = function(data) {
    if (typeof data !== "string" || !data) {
        return null;
    }
    return JSON.parse(data.trim());
};

// $.trim 대체
CAFE24.UTIL.trim = function(text) {
    var trim = String.prototype.trim;

    return text == null ? "" : trim.call(text);
};

// $.browser 대체
CAFE24.UTIL.browser = (function() {
    var uaMatch = function(ua) {
        ua = ua.toLowerCase();

        var match = /(chrome)[ \/]([\w.]+)/.exec(ua) ||
            /(webkit)[ \/]([\w.]+)/.exec(ua) ||
            /(opera)(?:.*version|)[ \/]([\w.]+)/.exec(ua) ||
            /(msie) ([\w.]+)/.exec(ua) ||
            ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(ua) ||
            [];

        return {
            browser: match[1] || "",
            version: match[2] || "0"
        };
    };

    matched = uaMatch(navigator.userAgent);
    browser = {};

    if (matched.browser) {
        browser[matched.browser] = true;
        browser.version = matched.version;
    }

    // Chrome is Webkit, but Webkit is also Safari.
    if (browser.chrome) {
        browser.webkit = true;
    } else if (browser.webkit) {
        browser.safari = true;
    }
    return browser;
})();


CAFE24.UTIL.topWindowJquery = function(selector) {
    if (window.top) {
        if (window.top.EC$) {
            return window.top.EC$(selector);
        }
        if (window.top.$) {
            return window.top.$(selector);
        }
    }
    return false;
};

CAFE24.UTIL.parentWindowJquery = function(selector) {
    if (window.parent) {
        if (window.parent.EC$) {
            return window.parent.EC$(selector);
        }
        if (window.parent.$) {
            return window.parent.$(selector);
        }
    }
    return false;
};

CAFE24.UTIL.openerWindowJquery = function(selector) {
    if (window.opener) {
        if (window.opener.EC$) {
            return window.opener.EC$(selector);
        }
        if (window.opener.$) {
            return window.opener.$(selector);
        }
    }
    return false;
};

var EC_UTIL = CAFE24.getDeprecatedNamespace('EC_UTIL');

/* 사용자 안내띠 버튼 리사이징 */
EC$(function() {
    window.addEventListener("resize", handleEvent);
    window.addEventListener('scroll', handleEvent);

    handleEvent();

    function handleEvent(){
        var targetEl = document.querySelectorAll('.certifyBox');
        var bodyWidth = document.body.offsetWidth,
            winWidth = window.innerWidth-16,
            rightNum = 48;

        for(var i=0; i<targetEl.length; i++){
            var btnElChk = targetEl[i].querySelector('.btnTxt span');
            if(btnElChk){
                var btnWidth = btnElChk.offsetWidth;
                if(winWidth < 600) winWidth = 600;
                var left = (winWidth-(btnWidth+rightNum)+window.scrollX),
                    cul = bodyWidth - winWidth;

                if(bodyWidth > winWidth && window.scrollX <= cul) {
                    targetEl[i].querySelector('.btnTxt').style.left = left + "px";
                }else{
                    targetEl[i].querySelector('.btnTxt').style.left = "";
                }
            }
        }
    }
});
EC$(function () {
    var oAgent = navigator.userAgent.toLowerCase();
    if (!EC$.cookie('ec_ipad_device') || /[TF]/.exec(EC$.cookie('ec_ipad_device')) == undefined) {
        if ((oAgent.indexOf('macintosh') != -1 && 'ontouchend' in document) === true) {
            EC$.cookie('ec_ipad_device', 'T', {path: '/'});
        } else { EC$.cookie('ec_ipad_device', 'F', {path: '/'}); }
    }
});

CAFE24.FRONT_XANS_INTERPRETER = (function() {
    // 변수 정규표현식
    var XANS_VAR_FULL_NAME_REGEXP = '\\{\\$([a-z0-9_\\.]+)(?:[\\s]*[\\|][\\s]*([a-z0-9]+)[\\s]*[:]?((?:[^\\{\\}]+)*))?\\}';

    // 템플릿에서 모든 변수를 찾기 위한 정규식
    var regexpFindSDEVarFullName = new RegExp(XANS_VAR_FULL_NAME_REGEXP, 'ig');

    // '{$var_name|display}'과 같은 문자열에서 변수명과 모디파이어를 분리하기 위한 정규식
    var regexpSDEVarFullname = new RegExp('^' + XANS_VAR_FULL_NAME_REGEXP + '$', 'i');

    // 모디파이어
    var aSDEModifier = {
        display: function(sVar)
        {
            if (sVar) {
                return '';
            } else {
                return 'displaynone';
            }
        },
        numberformat: function(sVar)
        {
            if (isFinite(sVar)) {
                return number_format(sVar);
            } else {
                return '';
            }
        },
        striptag: function(sVar)
        {
            return sVar.replace(/(<([^>]+)>)/ig, '');
        }
    };

    /**
     * 숫자를 3자리씩 콤마(,)로 끊어서 문자열로 변환하여 리턴합니다.
     * @param string sNumber 숫자
     * @returns {string} 콤마 반영된 문자열
     */
    function number_format(sNumber)
    {
        // 3자리씩 ,로 끊어서 리턴
        var sNumber = String(parseInt(sNumber));
        var regexp = /^(-?[0-9]+)([0-9]{3})($|\.|,)/;
        while (regexp.test(sNumber)) {
            sNumber = sNumber.replace(regexp, "$1,$2$3");
        }
        return sNumber;
    }

    /**
     * 전체 변수명에서 실제 변수명과 모디파이어 등을 분리하여 리턴합니다.
     * @param string sVarFullName '{$var_name|display}' 형태의 전체 변수명
     * @returns {{var_name: *, modifire: *}}
     */
    function parseVariableInfo(sVarFullName)
    {
        var aMatches = sVarFullName.match(regexpSDEVarFullname);

        return {
            var_name: aMatches[1],
            modifire: aMatches[2]
        };
    }

    /**
     * XANS 템플릿에서 변수를 반영하여 리턴합니다.
     * @param string sTemplate 템플릿 (HTML)
     * @param array aVars 변수 리스트
     * @return string 완성된 HTML
     */
    function fetch(sTemplate, aVars)
    {
        var aHtml = sTemplate.split('<!--#-->');
        var sHtml = '';

        EC$(aHtml).each(function(iIndex, sModuleHtml) {
            if (iIndex < 1 || (iIndex % 2) !== 1) {
                sHtml += convertHtmlVars(sModuleHtml, aVars);
            } else {
                var oObj = EC$(sModuleHtml);
                var sChildNode = EC$('<div>').append(oObj.children().first().clone()).html();
                if (/<!--\$--><!--@-->([\s\S]+)<!--\$-->/gm.test(sModuleHtml) === true) {
                    sChildNode = /<!--\$--><!--@-->([\s\S]+)<!--\$-->/gm.exec(sModuleHtml)[1].split('<!--@-->')[0];
                }
                var sModuleClass = EC$(oObj).attr('class');
                var sModuleName = ucfirst(sModuleClass.match(/xans-product-([^- ]+)/)[1]);

                if (typeof(aVars['@' + sModuleName]) === 'object') {
                    var s = '';
                    EC$(aVars['@' + sModuleName]).each(function(i, aData) {
                        s += convertHtmlVars(sChildNode, aData);
                    });

                    if (s !== '') {
                        sHtml += EC$('<div>').append(oObj.html(s).clone()).html();
                    }
                }
            }
        });

        return sHtml;
    }

    function ucfirst(sString)
    {
        if (typeof(sString) !== 'string') {
            return '';
        }
        return sString.substring(0, 1).toUpperCase() + sString.substring(1).toLowerCase();
    }

    function convertHtmlVars(sTemplate, aVars)
    {
        return sTemplate.replace(regexpFindSDEVarFullName, function(sVarFullName) {
            var aVarInfo = parseVariableInfo(sVarFullName);

            var sValue = '';
            if (aVars[aVarInfo.var_name] || aVars[aVarInfo.var_name] === 0) {
                sValue = aVars[aVarInfo.var_name];
            }

            if (aVarInfo.modifire !== undefined && aSDEModifier.hasOwnProperty(aVarInfo.modifire) === true) {
                return aSDEModifier[aVarInfo.modifire](sValue);

            } else {
                return sValue;

            }
        });
    }

    /**
     * XANS 템플릿에서 변수 리스트를 얻어서 리턴합니다.
     * @param string sTemplate 템플릿 (HTML)
     * @return array 변수 리스트 (ex: ['{$var_name}', '{$var_name|display}'])
     */
    function getVariables(sTemplate)
    {
        return sTemplate.match(regexpFindSDEVarFullName);
    }

    return {
        getVariables: getVariables,
        parseVariableInfo: parseVariableInfo,
        fetch: fetch
    };
})();

var EC_FRONT_XANS_INTERPRETER = CAFE24.getDeprecatedNamespace('EC_FRONT_XANS_INTERPRETER');

CAFE24.FRONT_XANS_TEMPLATE = (function() {
    // 모듈별 템플릿
    var aModuleTemplates = {};

    /**
     * 모듈별 템플릿을 셋팅합니다.
     * @param string sModuleName 모듈명 (xans-product-listmain-1)
     * @param string sModuleTemplate 모듈 템플릿
     */
    function setTemplate(sModuleName, sModuleTemplate)
    {
        aModuleTemplates[sModuleName] = sModuleTemplate;

        if (/^xans-product-list|^xans-product-hashtaglist/.test(sModuleName) === true) {
            var sTemplateForVDOM = getTemplateForVDOM(sModuleName);
            var $li = EC$(sTemplateForVDOM).find('li').first();
            var sLiHTMLForVDOM = EC$('<ul>').append($li).html();

            // 해시태그 모듈에 대한 별도 캐싱 처리
            if (/^xans-product-hashtaglist/.test(sModuleName) === true) {
                aModuleTemplates[sModuleName] = convertVDomHtmlToHTML(sLiHTMLForVDOM);
            } else {
                // oMobileDomData를 여전히 사용중인 사용자js와의 호환성을 위한 예외처리 - ECHOSTING-142586
                window.oMobileDomData = {
                    dom: convertVDomHtmlToHTML(sLiHTMLForVDOM),
                    data: CAFE24.FRONT_XANS_INTERPRETER.getVariables(sLiHTMLForVDOM)
                };
            }
        }
    }

    /**
     * 모듈별 템플릿을 가져옵니다.
     * @param string sModuleName 모듈명
     * @return string 모듈별 템플릿
     */
    function getTemplate(sModuleName)
    {
        if (aModuleTemplates.hasOwnProperty(sModuleName)) {
            return aModuleTemplates[sModuleName];
        } else {
            return undefined;
        }
    }

    /**
     * Virtual DOM 에서 사용할 모듈별 템플릿을 가져옵니다.
     * @param string sModuleName 모듈명
     * @return string 모듈별 템플릿
     */
    function getTemplateForVDOM(sModuleName)
    {
        var sTemplate = getTemplate(sModuleName) || '';

        // src 속성에 대해 "//:0" 처리해줍니다.
        var sTemplateForVDOM = sTemplate.replace(/(\s+src\s*=\s*["'])/g, '$1//:0#xansjs');

        return sTemplateForVDOM;
    }

    /**
     * "Virtual DOM"용 HTML을 일반 HTML로 변환하여 리턴합니다.
     * @param string sTemplateForVDOM "Virtual DOM"용 사용한 템플릿 HTML
     * @return string 일반 HTML
     */
    function convertVDomHtmlToHTML(sTemplateForVDOM)
    {
        // src 속성에서 "//:0#xansjs"를 삭제합니다.
        var sTemplate = sTemplateForVDOM.replace(/(\s+src\s*=\s*["'])\/\/:0#xansjs/g, '$1');

        return sTemplate;
    }

    return {
        setTemplate: setTemplate,
        getTemplate: getTemplate,
        getTemplateForVDOM: getTemplateForVDOM,
        convertVDomHtmlToHTML: convertVDomHtmlToHTML
    };
})();

var EC_FRONT_XANS_TEMPLATE = CAFE24.getDeprecatedNamespace('EC_FRONT_XANS_TEMPLATE');

/**
 * 모바일 전용 Util
 */
CAFE24.MOBILE_UTIL = {
    /*
     * get li
     */
    convertNode: function(node) {
        return CAFE24.FRONT_XANS_INTERPRETER.fetch(oMobileDomData.dom, node);
    },

    /*
     * set default img
     */
    setDefaultImage: function() {
        EC$(".thumbnail img,img.ThumbImage,img.BigImage").each(function($i,$item) {
            var $img = new Image();
            $img.onerror = function () {
                    $item.src="//img.echosting.cafe24.com/thumb/img_product_big.gif";
            };
            $img.src = this.src;
        });
    },

    /*
     * get ajax url
     */
    getAjaxUrl: function(sModule) {
        var aAjax = [];

        aAjax['xans-product-listnormal'] = '/exec/front/Product/ApiProductNormal';
        aAjax['xans-product-listmain'] = '/exec/front/Product/ApiProductMain';

        return aAjax[sModule];
    },

    /*
     * set param
     */
    setAjaxParam: function(aData, sModule) {
        var aParam = [];

        if (typeof(aData['cate_no']) === 'number' && aData['cate_no'] > 0) { aParam.push('cate_no=' + aData['cate_no']); }
        if (typeof(aData['display_group']) === 'number' && aData['display_group'] > 0) { aParam.push('display_group=' + aData['display_group']); }
        if (typeof(aData['sort_method']) === 'number' && aData['sort_method'] > 0) { aParam.push('sort_method=' + aData['sort_method']); }
        if (typeof(aData['supplier_code']) === 'string' && aData['supplier_code'] !== '') { aParam.push('supplier_code=' + aData['supplier_code']); }
        if (typeof(aData['ec_soldout_display']) === 'string' && aData['ec_soldout_display'] !== '') { aParam.push('ec_soldout_display=' + aData['ec_soldout_display']); }
        aParam.push('page=' + aData['page']);
        aParam.push('bInitMore=' + aData['bInitMore']);
        aParam.push('count=' + aData['count']);

        return this.getAjaxUrl(sModule) + '?' + aParam.join('&');
    }
};

var EC_MOBILE_UTIL = CAFE24.getDeprecatedNamespace('EC_MOBILE_UTIL');

/*
 * Swipe 1.0
 *
 * Brad Birdsall, Prime
 * Copyright 2011, Licensed GPL & MIT
 *
*/
window.SwipeClient = function(element, options) {
    // return immediately if element doesn't exist
    if (! element) {
        return null;
    }

    var _this = this;

    // retreive options
    this.options = options || {};
    this.index = this.options.startSlide || 0;
    this.speed = this.options.speed || 300;
    this.callback = this.options.callback || function() {};
    this.delay = this.options.auto || 0;
    this.postback = this.options.postback || true;

    // 캐싱 사용유무 (기본값으로 이미 넘어오지만 그래도 no로 한번 더 저장)
    this.cache = this.options.cache || 'no';

    // 현재 슬라이드 개별 모듈의 순서(인덱스)를 저장하기 위해 저장 (상품번호, 카테코리 번호 등으로 조합된 상품별 유니크 값)
    this.storageId = this.options.elementId || '';

    // reference dom elements
    this.container = element;
    this.element = this.container.getElementsByTagName('ul')[0]; // the slide pane

    // static css
    this.container.style.overflow = 'hidden';
    this.element.style.listStyle = 'none';
    this.element.style.margin = 0;

    // trigger slider initialization
    this.setup();

    // begin auto slideshow
    this.begin();

    // add event listeners
    if (this.element.addEventListener) {
        this.element.addEventListener('touchstart', this, false);
        this.element.addEventListener('touchmove', this, false);
        this.element.addEventListener('touchend', this, false);
        this.element.addEventListener('touchcancel', this, false);
        this.element.addEventListener('webkitTransitionEnd', this, false);
        this.element.addEventListener('msTransitionEnd', this, false);
        this.element.addEventListener('oTransitionEnd', this, false);
        this.element.addEventListener('transitionend', this, false);

        window.addEventListener('resize', this, false);
    }
};

SwipeClient.prototype = {
    setup: function() {
        // get and measure amt of slides
        this.slides = this.element.children;
        this.length = this.slides.length;

        // return immediately if their are less than two slides
        if (this.length < 2) {
            return null;
        }

        // determine width of each slide
        this.width = Math.ceil(('getBoundingClientRect' in this.container) ? this.container.getBoundingClientRect().width : this.container.offsetWidth);

        // Fix width for Android WebView (i.e. PhoneGap)
        if (this.width === 0 && typeof window.getComputedStyle === 'function') {
            this.width = window.getComputedStyle(this.container, null).width.replace('px','');
        }

        // return immediately if measurement fails
        if (! this.width) {
            return null;
        }

        // hide slider element but keep positioning during setup
        var origVisibility = this.container.style.visibility;

        this.container.style.visibility = 'hidden';

        // dynamic css
        this.element.style.width = Math.ceil(this.slides.length * this.width) + 'px';

        var index = this.slides.length;

        while (index--) {
            var el = this.slides[index];

            el.style.width = this.width + 'px';
            el.style.display = 'table-cell';
            el.style.verticalAlign = 'top';
        }

        // set start position and force translate to remove initial flickering

        // 캐싱 사용중일 경우에만 처리
        if (this.cache === 'yes') {
            // 저장된 세선 스토리지 읽어와 처리
            // 각 스와이프의 개별 모듈에 해당되는 세션 스토리지 값
            // NaN 보다는 parseInt(null)로 명확하게 구분
            var iStorageIndexData = parseInt(null);

            // 상품 상세페이지에서 생성된 세션 스토리지 키
            var sStorageDetailName = 'sStorageDetail';

            // 상품 상세페이지에서 생성된 세션 스토리지 값 (Unix Timestamp)
            // NaN 보다는 parseInt(null)로 명확하게 구분
            var iStorageDetailData = parseInt(null);

            // 현재 시간 Unix Timstamp
            var iNowTime = Math.floor(new Date().getTime() / 1000);

            // 세션 스토리지 유지 시간
            var iSessionTime = 60 * 5;

            // 값 할당 (int)
            try {
                iStorageIndexData = parseInt(sessionStorage.getItem(this.storageId));
                iStorageDetailData = parseInt(sessionStorage.getItem(sStorageDetailName));
            } catch (e) {
            }

            // 저장된 값(추가 이미지)이 삭제된 경우 빈 페이지로 스와이프 되므로, 저장된 인덱스에 해당되는 이미지가 없는 경우는 세션 스토리지 삭제
            if (typeof($S.aButton[iStorageIndexData]) === 'undefined') {
                // 할당된 값 초기화
                iStorageIndexData = parseInt(null);

                // 실제 세션 스토리지에서도 삭제
                try {
                    sessionStorage.removeItem(this.storageId);
                } catch (e) {
                }
            }

            // 값이 있다면 moveTab을 해야 Circle(페이징 원)까지 변경됨
            // 상세페이지에서 생성된 세션 스토리지가 특정 시간이 경과하지 않은 경우에만 처리
            // 만약 모듈에서 상품번호 등의 정보(this.storageId)를 가져오지 못한 경우에는 처리하지 않음
            if (this.storageId !== '' && isNaN(iStorageIndexData) === false && isNaN(iStorageDetailData) === false && iStorageDetailData + iSessionTime >= iNowTime) {
                // 실제 이동 처리
                this.moveTab(iStorageIndexData, 0);
            } else {
                this.slide(this.index, 0);
            }
        } else {
            this.slide(this.index, 0);
        }

        this.container.style.visibility = origVisibility;
    },

    slide: function(index, duration) {
        // if useing ajax load
        try {
            if (oMobileSliderData.sPictorialLoad === true) {
                if ($S.iAjax === index + 1 && $S.bAjax === true) {
                    $S.callAjax();
                }
            }
        } catch (e) {}

        var style = this.element.style;

        // fallback to default speed
        if (duration == undefined) {
          duration = this.speed;
        }

        // set duration speed (0 represents 1-to-1 scrolling)
        style.webkitTransitionDuration = style.MozTransitionDuration = style.msTransitionDuration = style.OTransitionDuration = style.transitionDuration = duration + 'ms';

        // translate to given index position
        style.MozTransform = style.webkitTransform = 'translate3d(' + -(index * this.width) + 'px, 0, 0)';
        style.msTransform = style.OTransform = 'translateX(' + -(index * this.width) + 'px)';

        // set new index to allow for expression arguments
        this.index = index;

        // 현재 모듈의 인덱스를 세선 스토리지에 저장
        // 캐시 사용중이며 인덱스가 있으면서 저장할 스토리지 ID 값이 있는 경우에만 처리
        if (this.cache === 'yes' && isNaN(this.index) === false && this.storageId !== '') {
            try {
                sessionStorage.setItem(this.storageId, this.index);
            } catch (e) {
            }
        }
    },

    getPos: function() {
        // return current index position
        return this.index;
    },

    prev: function(delay, postback) {
        // cancel next scheduled automatic transition, if any
        this.delay = delay || 0;
        this.postback = (postback == undefined) ? true : postback;

        clearTimeout(this.interval);

        if (this.index) {
            this.slide(this.index - 1, this.speed);
        } else {
            if (this.postback !== false) {
                this.slide(this.length - 1, this.speed); //if first slide return to end
            }
        }
    },

    next: function(delay, postback) {
        // cancel next scheduled automatic transition, if any
        this.delay = delay || 0;
        this.postback = (postback == undefined) ? true : postback;

        clearTimeout(this.interval);

        if (this.index < this.length - 1) {
            this.slide(this.index + 1, this.speed);
        } else {
            if (this.postback !== false) {
                this.slide(0, this.speed); //if last slide return to start
            }
        }
    },

    moveTab: function(iPage, delay, oTarget) {
        // control current tab action
        // 모바일 상품상세에서 slide영역을 다시 원복함
        this.index = iPage;
        this.delay = delay || 0;

        clearTimeout(this.interval);

        this.slide(this.index, this.speed);

        if (typeof CAFE24.SHOP_FRONT_NEW_OPTION_EXTRA_IMAGE !== 'undefined') {
            CAFE24.SHOP_FRONT_NEW_OPTION_EXTRA_IMAGE.setSwipeImage('', true, iPage, oTarget);
        }
    },

    begin: function() {
        var _this = this;

        this.interval = (this.delay) ? setTimeout(function() {
            _this.next(_this.delay);
        }, this.delay) : 0;
    },

    stop: function() {
      this.delay = 0;

      clearTimeout(this.interval);
    },

    resume: function() {
      this.delay = this.options.auto || 0;
      this.begin();
    },

    setLength: function(expand) {
        this.length = expand;
    },

    handleEvent: function(e) {
        switch (e.type) {
            case 'touchstart':
                this.onTouchStart(e);
                break;
            case 'touchmove':
                this.onTouchMove(e);
                break;
            case 'touchcancel':
            case 'touchend':
                this.onTouchEnd(e);
                break;
            case 'webkitTransitionEnd':
            case 'msTransitionEnd':
            case 'oTransitionEnd':
            case 'transitionend':
                this.transitionEnd(e);
                break;
            case 'resize':
                this.setup();
                break;
        }
    },

    transitionEnd: function(e) {
      if (this.delay) {
          this.begin();
      }

      this.callback(e, this.index, this.slides[this.index], this);
    },

    onTouchStart: function(e) {
        this.start = {
          // get touch coordinates for delta calculations in onTouchMove
          pageX: e.touches[0].pageX,
          pageY: e.touches[0].pageY,

          // set initial timestamp of touch sequence
          time: Number(new Date())
        };

        // used for testing first onTouchMove event
        this.isScrolling = undefined;

        // reset deltaX
        this.deltaX = 0;

        // set transition time to 0 for 1-to-1 touch movement
        this.element.style.MozTransitionDuration = this.element.style.webkitTransitionDuration = 0;

        e.stopPropagation();
    },

    onTouchMove: function(e) {
        // ensure swiping with one touch and not pinching
        if (e.touches.length > 1 || e.scale && e.scale !== 1) {
            return;
        }

        this.deltaX = e.touches[0].pageX - this.start.pageX;

        // determine if scrolling test has run - one time test
        if (typeof this.isScrolling === 'undefined') {
            this.isScrolling = !! (this.isScrolling || Math.abs(this.deltaX) < Math.abs(e.touches[0].pageY - this.start.pageY));
        }

        // if user is not trying to scroll vertically
        if (! this.isScrolling) {
            // prevent native scrolling
            e.preventDefault();

            // cancel slideshow
            clearTimeout(this.interval);

            // increase resistance if first or last slide
            this.deltaX =
            this.deltaX /
            ((! this.index && this.deltaX > 0 // if first slide and sliding left
            || this.index == this.length - 1 // or if last slide and sliding right
            && this.deltaX < 0 // and if sliding at all
            ) ?
            (Math.abs(this.deltaX) / this.width + 1) // determine resistance level
            : 1); // no resistance if false

            // translate immediately 1-to-1
            this.element.style.MozTransform = this.element.style.webkitTransform = 'translate3d(' + (this.deltaX - this.index * this.width) + 'px,0,0)';

            e.stopPropagation();
        }
    },

    onTouchEnd: function(e) {
        // determine if slide attempt triggers next/prev slide
        var isValidSlide =
            Number(new Date()) - this.start.time < 250 // if slide duration is less than 250ms
            && Math.abs(this.deltaX) > 20 // and if slide amt is greater than 20px
            || Math.abs(this.deltaX) > this.width/2, // or if slide amt is greater than half the width

        // determine if slide attempt is past start and end
        isPastBounds =
            ! this.index && this.deltaX > 0 // if first slide and slide amt is greater than 0
            || this.index == this.length - 1 && this.deltaX < 0; // or if last slide and slide amt is less than 0

        // if not scrolling vertically
        if (! this.isScrolling) {
            // call slide function with slide end value based on isValidSlide and isPastBounds tests
            this.slide(this.index + (isValidSlide && ! isPastBounds ? (this.deltaX < 0 ? 1 : -1) : 0), this.speed);
        }

        e.stopPropagation();
    }
};

/**
 * 모바일 상품 더보기 모듈
 * @package app/Mobile
 * @subpackage Front/Disp/Product
 * @version 2.2
 *
 *
 * version 2.2 변경사항
 * 1. cache = yes 설정으로 더보기 리스트 유지
 * 2. 사용자 html 수정 유무에 상관없이 리스팅
 * 3. api에 $review_cnt 추가
 */
var $M = {
    /*
     * current module name
     */
    sModule: 'xans-product-listnormal',
    /*
     * current module name
     */
    sMore: 'xans-product-listmore',

    /*
     * 더보기 버튼에 대한 중복 실행을 막기 위한 flag
     */
    bLoading: false,

    /*
     * 모듈별로 object 값
     */
    oModuleLoading: {},

    /*
     * init
     */
    init: function() {
        if (this.sModule == 'xans-product-listnormal') {
            // 일반상품에 대해 더보기 기능 적용시 페이징 모듈 자동 삭제
            EC$('.xans-product-normalpaging').remove();
        }
    },
    /*
     * show more
     * @param int iActive 모듈 key
     * @param int iDisplayGroup 추천/신상품 분류
     * @param int iCategoryNo 카테고리 번호
     * @param int iCount 주석변수 상품 수
     * @param int iSortMethod 정렬방법
     * @param string sSupplierCode 공급사코드
     * @param bool bInitMore 더보기 기능 초기화 여부
     * @param string 품절상품 표시 여부
     */
    displayMore: function(iActive, iDisplayGroup, iCategoryNo, iCount, iSortMethod, bCache, sSupplierCode, bInitMore, sSoldoutDisplay) {

        if (this.oModuleLoading[iDisplayGroup] === true) {
            // 로딩 중에는 실행 안함
            return;
        }

        var EC_MORE = (function() {
            var sTargetModuleName,
                sLiTemplate,
                sFirstLiTemplate,
                $moreButton,
                $currentPageText,
                sCurrentPageCookieName,
                iRequestPageNum;

            /**
             * 추가될 상품 정보가 append될 모듈명
             * @returns {string}
             */
            function getTargetModuleName()
            {
                if (iActive > 0) {
                    return $M.sModule + '-' + iActive;
                } else {
                    return $M.sModule;
                }
            }
            /**
             * LI 템플릿을 리턴합니다.
             * @returns {string}
             */
            function getLiTemplate(sPos)
            {
                var sModuleHtmlForVDOM = CAFE24.FRONT_XANS_TEMPLATE.getTemplateForVDOM(sTargetModuleName);
                var oLiObejct = EC$(sModuleHtmlForVDOM).find('ul').first().children('li');
                // 재고플러스 사용시 재고현황 API 로드여부 attribute값 삭제
                oLiObejct.find('[module="ec-product-wms-stock-manage"]').removeAttr('hasLoaded');
                var $li = EC$.fn[sPos].apply(oLiObejct);
                var sLiHtmlForVDOM = EC$('<ul>').append($li).html();

                return CAFE24.FRONT_XANS_TEMPLATE.convertVDomHtmlToHTML(sLiHtmlForVDOM);
            }
            /**
             * "더보기" 버튼 모듈
             * @returns {jQuery}
             */
            function getMoreButtonElement()
            {
                if (iActive > 0) {
                    return EC$('.' + $M.sMore + '-' + iActive);
                } else {
                    return EC$('.' + $M.sMore);
                }
            }
            /**
             * "현재페이지 표시" 영역
             * @returns {jQuery}
             */
            function getCurrentPageTextElement()
            {
                if (iDisplayGroup > 1) {
                    return EC$('#more_current_page_' + iDisplayGroup);
                } else {
                    return EC$('#more_current_page');
                }
            }
            /**
             * "캐쉬된 현재페이지" 쿠키명
             * @returns {string}
             */
            function getCachedCurrentPageCookieName()
            {
                var aCookieName = ['mobile_more_current_page'];
                if (iCategoryNo > 0) {
                    aCookieName.push(iCategoryNo);
                }
                if (iDisplayGroup > 1) {
                    aCookieName.push(iDisplayGroup);
                }
                return aCookieName.join('_');
            }
            /**
             * 요청할 페이지 번호를 구하여 리턴합니다.
             * @return int
             *      "더보기 유지 기능 사용" + "더보기 초기화"인 경우 현재 쿠키에 저장된 페이지 번호
             *      그 외에는 다음에 가져올 페이지 번호
             */
            function getRequestPageNum()
            {
                if (bCache === true && bInitMore === true) {
                    // "더보기 유지 기능 사용" + "더보기 초기화"인 경우
                    var sCookieCurrentPage = EC$.cookie(sCurrentPageCookieName);

                    if (sCookieCurrentPage) {
                        return parseInt(sCookieCurrentPage, 10);
                    } else {
                        return 1;
                    }

                } else {
                    // 그 외
                    var iCurrentPage = $moreButton.data('current_page');

                    if (iCurrentPage === undefined) {
                        iCurrentPage = 1;
                    }

                    return iCurrentPage + 1;
                }
            }
            /**
             * 다음 페이지 상품 정보를 가져올 수 있는 ajax URL을 리턴합니다.
             * @returns string
             */
            function getAjaxUrl()
            {

                var aParam = {
                    cate_no: iCategoryNo,
                    display_group: iDisplayGroup,
                    supplier_code: sSupplierCode,
                    sort_method: iSortMethod,
                    page: iRequestPageNum,
                    count: iCount,
                    bInitMore: (bInitMore === true) ? 'T' : 'F',
                    ec_soldout_display: sSoldoutDisplay
                };

                return CAFE24.MOBILE_UTIL.setAjaxParam(aParam, $M.sModule);
            }
            /**
             * 다음 페이지 상품 정보를 UL Element에 추가해줍니다.
             * @param array aData 상품 정보
             */
            function appendMoreData(aData)
            {
                EC_MORE.hideMoreButton();

                var aHtml = [];
                var sTemplate = sLiTemplate;
                EC$(aData).each(function(iIndex, aVar) {
                    if (iIndex === 0) {
                        sTemplate = sFirstLiTemplate;
                    } else {
                        sTemplate = sLiTemplate;
                    }
                    aHtml.push(CAFE24.FRONT_XANS_INTERPRETER.fetch(sTemplate, aVar));
                });
                var sHtml = aHtml.join('');

                EC$('.' + sTargetModuleName).each(function() {
                    EC$(this).find('ul').first().append(sHtml);
                });

                // 재고현황 API 로드
                if (typeof CAFE24.SHOP_FRONT_NEW_PRODUCT_WMS_STOCK_STATUS !== 'undefined') {
                    CAFE24.SHOP_FRONT_NEW_PRODUCT_WMS_STOCK_STATUS.loadStock('ec-product-wms-stock-manage');
                }

                $currentPageText.text(iRequestPageNum);
                $moreButton.data('current_page', iRequestPageNum);

                // 캐시 기능 사용이면 쿠키에 현재 페이지 저장
                if (bCache === true) {
                    EC$.cookie(sCurrentPageCookieName, iRequestPageNum, { expires: 1 });
                }
            }
            /**
             * '더보기' 버튼을 숨김 처리합니다.
             */
            function hideMoreButton()
            {
                $moreButton.hide();
            }

            /**
             * '더보기' 버튼을 노출 처리합니다.
             */
            function showMoreButton()
            {
                $moreButton.show();
            }

            /**
             * Ajax 요청 여부를 리턴합니다.
             * @return bool true이면 ajax 요청, false이면 ajax 요청 안함
             */
            function isCallAjax()
            {
                if (bInitMore === true && iRequestPageNum <= 1) {
                    // 더보기 유지 기능 동작이고 iRequestPageNum 값이 1이하이면 요청 안함
                    return false;
                }

                return true;
            }

            function setMoreAction(data)
            {
                if (data.rtn_data.end === true) {
                    EC_MORE.hideMoreButton();
                }
                else {
                    setTimeout(EC_MORE.showMoreButton,300);
                }

                if (data.is_new_product === true) {
                    CAFE24.SHOP_FRONT_REVIEW_TALK_REVIEW_COUNT.setReviewTalkCnt();
                }
            }

            sTargetModuleName = getTargetModuleName();
            sFirstLiTemplate = getLiTemplate('first');
            sLiTemplate = getLiTemplate('last');
            $moreButton = getMoreButtonElement();
            $currentPageText = getCurrentPageTextElement();
            sCurrentPageCookieName = getCachedCurrentPageCookieName();
            iRequestPageNum = getRequestPageNum();

            return {
                isCallAjax: isCallAjax,
                getAjaxUrl: getAjaxUrl,
                appendMoreData: appendMoreData,
                hideMoreButton: hideMoreButton,
                showMoreButton: showMoreButton,
                setMoreAction: setMoreAction
            };
        })();

        // ajax
        if (EC_MORE.isCallAjax() === true) {

            var aParamData = {};
            if (EC$('#ec-product-searchdata-form').length > 0) {

                EC$('#ec-product-searchdata-catenum').val(iCategoryNo);
                CAFE24.FRONT_PRODUCT_SEARCH_DATA.setSearchPriceData();

                EC$('#ec-product-searchdata-form .ec-product-searchdata-form:checked').each(function(idx) {
                    var sValues = decodeURIComponent(EC$(this).val());
                    if (EC$(this).val() !== sValues) {
                        EC$(this).val(encodeURIComponent(sValues));
                    } else {
                        EC$(this).val(encodeURIComponent(EC$(this).val()));
                    }
                });

                EC$('#ec-product-searchdata-form .ec-product-searchdata-form.ec_search_selected').each(function() {
                    if (EC$(this).prop('type') !== 'checkbox') {
                        EC$('<input>').attr({type: 'hidden',name: 'search_form[option_data][]',value: encodeURIComponent(EC$(this).attr('sValue'))}).appendTo('#ec-product-searchdata-form');
                    }
                });

                aParamData = EC$('#ec-product-searchdata-form').serialize();


            }

            var iGetCategory = iCategoryNo;
            var iGetDisplay = iDisplayGroup;

            if (iGetCategory === 0) {
                iGetCategory = 1;
            }

            if (iGetDisplay === 0) {
                iGetDisplay = 1;
            }

            // 저장된 세선 스토리지 읽어와 처리
            // 각 더보기의 개별 모듈에 해당되는 세션 스토리지 키
            var sStorageListName = 'sStorageList_' + iGetCategory + '_' + iGetDisplay;

            // 각 더보기의 개별 모듈에 해당되는 세션 스토리지 값
            var sStorageListData = null;

            // 상품 상세페이지에서 생성된 세션 스토리지 키
            var sStorageDetailName = 'sStorageDetail';

            // 상품 상세페이지에서 생성된 세션 스토리지 값 (Unix Timestamp)
            var sStorageDetailData = null;

            // 현재 시간 Unix Timstamp
            var iNowTime = Math.floor(new Date().getTime() / 1000);

            // 세션 스토리지 유지 시간
            var iSessionTime = 60 * 5;

            try {
                sStorageListData = sessionStorage.getItem(sStorageListName);
                sStorageDetailData = sessionStorage.getItem(sStorageDetailName);
            } catch (e) {
            }

            // 상세페이지에서 생성된 세션 스토리지가 특정 시간이 경과하지 않은 경우에만 캐싱 데이터 사용
            if (sStorageDetailData !== null && parseInt(sStorageDetailData) + iSessionTime >= iNowTime) {
                if (bInitMore === true && sStorageListData !== null) {
                    var oReturnData = JSON.parse(sStorageListData);

                    EC_MORE.appendMoreData(oReturnData.rtn_data.data);
                    EC_MORE.setMoreAction(oReturnData);

                    return;
                }
            }

            EC$.ajax({
                type: 'get',
                url: EC_MORE.getAjaxUrl(),
                data: aParamData,
                dataType: 'json',
                success: function(data) {
                    if (data.rtn_code === '1000') {
                        EC_MORE.appendMoreData(data.rtn_data.data);
                        EC_MORE.setMoreAction(data);

                        // 초기 구동이 아니면서 세션 스토리지에 데이터가 있는 경우에는 append
                        if (bInitMore === false && sStorageListData !== null) {
                            data.rtn_data.data = JSON.parse(sStorageListData).rtn_data.data.concat(data.rtn_data.data);
                        }

                        // 최종 생성된 데이터 세션 스토리지에 저장
                        try {
                            sessionStorage.setItem(sStorageListName, JSON.stringify(data));
                            ReferenceCurrencyPrice.init();
                        } catch (e) {
                        }
                        if (CAPP_ASYNC_METHODS.hasOwnProperty('Soldouticon') === true && CAPP_ASYNC_METHODS["Soldouticon"].isUse() === true) {
                            CAPP_ASYNC_METHODS["Soldouticon"].execute();
                        }
                    } else {
                        alert('상품을 추가로 더 불러오는 과정에 문제가 발생했습니다. 지속적으로 발생할 경우 운영자에게 문의하세요.');

                        EC_MORE.hideMoreButton();

                        return false;
                    }
                },
                error: function (xhr, status, error) {
                    return false;
                },
                beforeSend: function () {
                    $M.oModuleLoading[iDisplayGroup] = true;
                },
                complete: function () {
                    $M.oModuleLoading[iDisplayGroup] = false;
                }
            });
        }
    } ,
    setDisplayPageMore: function(iActive, iDisplayGroup, iCategoryNo, iCount, iSortMethod, bCache, sSupplierCode, bInitMore, sSoldoutDisplay) {
        this.displayMore(iActive, iDisplayGroup, iCategoryNo, iCount, iSortMethod, bCache, sSupplierCode, true, sSoldoutDisplay);
    }
};

/**
 * 모바일 상품 스와이프 모듈
 * @package app/Shop
 * @subpackage Front/Disp/Product
 * @since 2014. 2. 12.
 * @update 2014. 5. 29.
 * @version 2.2
 *
 * 2.2 개선사항
 * 1. 데이터 ajax 로딩 추가
 * 2. multi, single 형태 추가
 */
var $S = {
    /*
     * current module name
     */
    sModule: 'xans-product-listmain',

    /*
     * swipe action name
     */
    sModuleSwipe: '',

    /*
     * mode
     */
    sMode: 'multi',

    /*
     * slider
     */
    bSlider: false,

    /*
     * swipeable
     */
    sSwipeable: 'yes',

    /*
     * sParam
     */
    sParam: '',

    /*
     * grid
     */
    sGrid: 'grid3',

    /*
     * grid array
     */
    aGrid: {'grid2': 2, 'grid3': 3, 'grid4': 4, 'grid5': 5},

    /*
     * start slide
     */
    iStart: 0,

    /*
     * page
     */
    iPage: 1,

    /*
     * page block
     */
    iPageBlock: 3,

    /*
     * line
     */
    iLine: 1,

    /*
     * limit circle
     */
    iLimit: 9,

    /*
     * active div
     */
    iActive: 0,

    /*
     * save li element
     */
    aElement: [],

    /*
     * save circle element
     */
    aButton: [],

    /*
     * product class
     */
    $product: null,

    /*
     * product ul
     */
    $productModule: null,

    /*
     * product list li
     */
    $productList: null,

    /*
     * ajax loading
     */
    bAjax: true,

    /*
     * generate dom
     */
    bGenerate: false,

    /*
     * ajax count
     */
    iAjax: 0,

    /*
     * auto slide interval
     */
    iAutoSlideInterval: 0,

    /*
     *  paging ui
     */
    sPagingType: 'circle',

    iProductTotal: 0,

    /*
     * cache 사용여부
     */
    sCache: 'no',

    /*
     * init
     */
    init: function() {
        // set param
        this.setParam();

         // set obejct
        this.setObject();

        // set block
        this.setBlock();

        // validate
        if (this.validate() === false) return;

         // generate
        this.generate();

        // load swipe
        this.load();
    },

    /*
     * set param
     */
    setParam: function() {
        try
        {
            this.sModuleSwipe = this.sModule.replace(/-/g, "_");
            this.iAjax = oMobileSliderData.iSliderLimit;
        }
        catch (e) { }
    },

    /*
     * set block
     */
    setBlock: function() {
        // set block num
        this.iPageBlock = (this.sMode == 'multi') ? this.iLine * this.aGrid[this.sGrid] : 1;
    },

    /*
     * set obejct
     */
    setObject: function() {
        try
        {
            // current module class
            this.sActiveProduct = this.iActive > 0 ? this.sModule + '-'+this.iActive : this.sModule;

            // div
            this.$product = $('.' + this.sActiveProduct);

            // div > ul
            this.$productModule = this.$product.find('ul').first();
            this.$productModule.css('webkit-backface-visibility', 'hidden');

            // div > ul > li > ul > li
            this.$productList = this.$productModule.find('>li');
        }
        catch (e) { }
    },

    /*
     * validate
     */
    validate: function() {
        // not use swipe
        if (this.sSwipeable != 'yes') return false;

        // empty ul
        if (this.$productModule.length < 1) return false;

        // empty li
        if (this.$productList.length < 1) return false;

        // mobilemaincategory-slider exception
        if (this.$productModule.find('.afterNone').length > 0) return true;

        // no condition swipe
        if (this.$productList.length <= this.iPageBlock) return false;
    },

    /*
     * ganerate swipe single dom
     */
    generate: function() {
        if (this.sMode == 'single') { this.generateSingle(); }
        else { this.generateMulti(); }
    },

    /*
     * prepare for element
     */
    prepare: function() {
        var $prepare = {
            /*
             * reset element and circle
             */
            reset: function() {
                $S.aElement = [];
                $S.aButton = [];
            },

            /*
             * set target id
             */
            setId: function() {
                $S.$product.attr('id', $S.sModule + '-slider-' + $S.iActive);
            }
        };
        $prepare.reset();
        $prepare.setId();
    },

    /*
     * ganerate swipe single dom
     */
    generateSingle: function() {
        // prepare
        this.prepare();

        // make li > ul > li
        for (var i=0; i<this.$productList.length; i++) {
            this.makeButton(i);
        }

        // call pagenate
        this.makePagenate();
    },


    /*
     * reset grid
     */
    resetGrid: function() {
        for (var sKey in this.aGrid) {
            if (this.$productModule.hasClass(sKey) === true) { this.$productModule.empty().removeClass(sKey); }
        }
    },

    /*
     * ganerate swipe multi dom
     */
    generateMulti: function() {
        // prepare
        this.prepare();

        if (this.bGenerate === false) return;

        // save li
        this.$productList.each(function() { $S.aElement.push($(this).clone(true)); });

        // delete li and grid2, gird3, grid4
        this.resetGrid();

        this.iProductTotal = this.aElement.length;
        this.iTotalPage = Math.ceil(this.iProductTotal / this.iPageBlock);

        // make li > ul > li
        for (var i=0, k=1, j=0; i<this.iProductTotal; i++, k++)
        {
            // templete for li > ul
            var $template = (j == 0) ? $("<li>", { html: $("<ul>", {'class': this.sGrid}) }) : $('<li>', { html: $("<ul>", {'class': this.sGrid}), css: {'display': 'none'} });

            // add li > ul
            if (k == 1)
            {
                this.$productModule.append($template);
                // <  현재페이지 / 총페이지 >
                if (this.sPagingType !== 'number') {
                    this.makeButton(j);
                }
            }

            // add li > ul > li
            this.$product.children('ul').children('li').eq(j).children('ul').append(this.aElement[i]);

            // see block
            if (k == this.iPageBlock)
            {
                k = 0;
                j++;
            }
        }

        if (this.sPagingType === 'number') {
            this.makeNumber();
        }
        // not necessary pagenate
        if (i < (parseInt(this.iPageBlock) + 1)) return;

        // call pagenate
        this.makePagenate();
    },

    makeButton: function(iCnt) {
        // ECQAINT-14112 롤링 및 넘버링 타입은 '모바일 환경설정'의 기본 사양에 따라 최대 갯수 5개로 설정
        if (this.sPagingType == 'rolling' || this.sPagingType == 'numbering') {
            this.iLimit = 5;
        } else if (this.sPagingType !== 'circle') {
            this.iLimit = 4;
        }

        var iNum = iCnt + 1,
        sSelected = (iCnt == 0) ? 'selected' : '',
        iPage = Math.ceil(iNum / this.iLimit),
        sLimitStyle = (iNum > this.iLimit) ? 'style="display:none"' : '',
        sName = this.sActiveProduct + '_page_'+iPage+'_'+iNum;

        if (this.sPagingType === 'fix') {
            var sSelected = (iCnt == 0) ? 'this' : 'other';
            this.divPaginateName = 'typeList';
            this.aButton.push('<li name="'+sName+'" ' + sLimitStyle +'><a class="' + sSelected + '" onclick="$' + this.sModuleSwipe + '_slider_' + this.iActive + '.moveTab(' + iCnt + ', ' + this.iAutoSlideInterval +');return false;">' + iNum +'</a></li>');
        } else if (this.sPagingType === 'numbering') {
            this.divPaginateName = 'typeNumber';
        } else if (this.sPagingType === 'rolling') {
            this.divPaginateName = 'typeRoll';
            this.aButton.push('<li class="' + sSelected + '" name="' + sName + '"><a href="#none" onclick="$' + this.sModuleSwipe + '_slider_' + this.iActive + '.moveTab(' + iCnt + ', ' + this.iAutoSlideInterval +');return false;">' + iNum + '</a></li>');
        } else {
            // circle
            this.divPaginateName = 'typeSwipe';
            this.aButton.push('<button name="'+sName+'" type="button" ' + sLimitStyle +' class="circle  ' + sSelected + '" onclick="$' + this.sModuleSwipe + '_slider_' + this.iActive + '.moveTab(' + iCnt + ', ' + this.iAutoSlideInterval +', $(this));return false;"><span>' + iNum +'번째 리스트</span></button>');
        }
    },

    /*
     * make fix number
     */
    makeNumber: function() {
        this.divPaginateName = 'typeTotal';
        sName = this.sModule+'-'+this.iActive+'_page';
        this.aButton.push('<span name="' + sName + '" class="page"><strong>1</strong> / <span>' + this.iTotalPage + '</span></span>');
    },

    /*
     * make pagenation
     */
    makePagenate: function() {
        var sSwipeId = this.sModule + '-swipe-button-' + this.iActive;
        var sPaginateStyle = '';
        var aBtn = [];
        if (this.sPagingType === 'fix') {
            aBtn.push('<p class="prev" onclick="$' + this.sModuleSwipe + '_slider_' + this.iActive + '.prev();return false;"><a href="#none"><span>이전 페이지</span></a></p>');
            aBtn.push('<ol id='+ sSwipeId +'>'+this.aButton.join('')+'</ol>');
            aBtn.push('<p class="next" onclick="$' + this.sModuleSwipe + '_slider_' + this.iActive + '.next();return false;"><a href="#none"><span>다음 페이지</span></a></p>');
        } else if (this.sPagingType === 'numbering') {
            aBtn.push('<p><strong>1</strong> / <span>' + this.$productList.length + '</span></p>');
        } else if (this.sPagingType === 'rolling') {
            sPaginateStyle = 'position:static;';
            aBtn.push('<ol id='+ sSwipeId +' class="grid' + this.$productList.length + '">'+this.aButton.join('')+'</ol>');
        } else if (this.sPagingType === 'number') {
            aBtn.push('<p class="prev" onclick="$' + this.sModuleSwipe + '_slider_' + this.iActive + '.prev();return false;"><a href="#none"><span>이전 페이지</span></a></p>');
            aBtn.push(this.aButton.join(''));
            aBtn.push('<p class="next" onclick="$' + this.sModuleSwipe + '_slider_' + this.iActive + '.next();return false;"><a href="#none"><span>다음 페이지</span></a></p>');
        } else {
            aBtn.push('<button type="button" class="prev" onclick="$' + this.sModuleSwipe + '_slider_' + this.iActive + '.prev();return false;"><span>이전 리스트</span></button>');
            aBtn.push('<span id="' + sSwipeId + '">' + this.aButton.join('') +'</span>');
            aBtn.push('<button type="button" class="next" onclick="$' + this.sModuleSwipe + '_slider_' + this.iActive + '.next();return false;"><span>다음 리스트</span></button>');
        }

        var sPaginateForm = '<div class="paginate ec-base-paginate '+this.divPaginateName+'" style="' + sPaginateStyle + '">' + aBtn.join('') + '</div>';

        if ($S.bSlider === false) { this.$product.append(sPaginateForm); }
    },

    /*
     * call ajax
     */
    callAjax: function() {
        this.iPage++;
        this.setAjaxParam();

        var $ajaxLoadContainer = {
            load: function() {
                var $load = '<div class="loading"><img src="//img.echosting.cafe24.com/design/skin/mobile/img_loading.gif" alt="" /></div>',
                    $dimmed = '<div class="dimmed"></div>';
                $('body').append($load + $dimmed);
            },
            remove: function() {
                $('body').find('div.loading, div.dimmed').remove();
            }
        };

        $.ajax({
            type: "get",
            url: this.sParam,
            dataType: "json",
            success: function(data) {
                try {
                    if (data.rtn_code == '1000') {
                        $(data.rtn_data.data).each(function(index, node) {
                            // new index
                            var newElementIndex = $S.iAjax + index;
                            // append new element
                            $S.$productModule.append(CAFE24.MOBILE_UTIL.convertNode(node));
                            // set onerror img
                            CAFE24.MOBILE_UTIL.setDefaultImage();
                            // element setting
                            $S.$product.find('li').eq(newElementIndex).css({
                                width: $(window).width() + 'px',
                                display: 'table-cell',
                                'vertical-align': 'top'
                            }).bind('click', function() { globalPictorlControl($S.$product); });
                        });

                        var len = data.rtn_data.data.length, currentSlider = '$' + $S.sModuleSwipe + '_slider_' + $S.iActive;

                        // set swipe scale
                        $S.$productModule.width($S.$productModule.width() + ($(window).width() * len));
                        // add ajax condition
                        $S.iAjax += len;
                        // stop ajax control
                        if (len < oMobileSliderData.iSliderLimit) { $S.bAjax = false; }
                    } else {
                        alert('상품을 추가로 더 불러오는 과정에 문제가 발생했습니다. 지속적으로 발생할 경우 운영자에게 문의하세요.');
                        return false;
                    }
                } catch (e) {
                    $ajaxLoadContainer.remove();
                }
            },
            error: function(xhr,status,error) {
                //alert('네크워크나 상품API연동에 문제가 있습니다. 지속적으로 발생할 경우 운영자에게 문의하세요.');
                return false;
            },
            beforeSend: function() {
                $ajaxLoadContainer.load();
            },
            complete: function() {
                $ajaxLoadContainer.remove();
                // set swipe module length && excute next slider
                var currentSlider = '$' + $S.sModuleSwipe + '_slider_' + $S.iActive;
                eval(currentSlider + '.setLength(' + $S.iAjax + ');');
            }
        });
    },

    /*
     * set Param
     */
    setAjaxParam: function() {
        var aParam = [];

        aParam['cate_no'] = oMobileSliderData.iCategoryNo;
        aParam['page'] = this.iPage;
        aParam['count'] = oMobileSliderData.iSliderLimit;

        this.sParam = CAFE24.MOBILE_UTIL.setAjaxParam(aParam, this.sModule);
    },

    /*
     * load swipe js
     */
    load: function() {
        try
        {
            var aSwipeVars = [],
                $swipe = document.getElementById('' + this.sModule + '-slider-' + this.iActive + ''),
                $now = this.$product.find('div.swipePage').find('span.now');

            if (this.sPagingType !== 'circle') {
                this.$product.find('.typeSwipe .prev, .typeSwipe .next').show();
            }

            // 상품의 고유한 값(상품 번호 및 카테고리 번호 등)을 지정
            // 이렇게 하지 않으면 스와이프 모듈을 하나로 인식하여 처리되기 때문
            var sProductInfo = '';

            // try-catch로 모듈의 상품 정보를 불러와서 처리하고, 정보가 없으면 처리하지 않음
            try {
                sProductInfo = this.$productModule.find('li').first().attr('data-param').replace(/\?/gi, '').replace(/\&\=/gi, '_');
            } catch (e) {
            }

            aSwipeVars.push('$' + this.sModuleSwipe + '_slider_' + this.iActive + ' = new SwipeClient($swipe, {');
            aSwipeVars.push('    startSlide: ' + this.iStart + ',');
            aSwipeVars.push('    auto: ' + this.iAutoSlideInterval + ',');
            aSwipeVars.push('    cache: \'' + this.sCache + '\',');
            aSwipeVars.push('    elementId: \'' + this.sModuleSwipe + '_slider_' + sProductInfo + '\',');
            aSwipeVars.push('    callback: function(e, pos, ele, obj) {');
            aSwipeVars.push('        if (obj.container.id == "xans-layout-mobilemaincategory-slider-0") { globalCategorySetUi(mode = "init", pos); }');
            aSwipeVars.push('        try { if (globalPictorialLoad === true) { globalPictorialSetUi($S.$product, pos) } } catch(e) {}');
            aSwipeVars.push('        var iSelectedPos = pos + 1;');
            aSwipeVars.push('        if ($S.bSlider === true) { $now.text(iSelectedPos); }');
            aSwipeVars.push(this.getSwipeButtonDisplay());
            aSwipeVars.push('    }');
            aSwipeVars.push('});');

            eval(aSwipeVars.join(''));
        }
        catch (e) { }
    },

    getSwipeButtonDisplay: function() {
        var sSelected = 'selected';
        var sChildSelector = '';
        if (this.sPagingType === 'fix') {
            sSelected = 'this';
            sChildSelector = ' > a';
        }

        var sSwipeVars = '';
        if (this.sPagingType === 'number') {
            sSwipeVars = '        $("[name^=\'' + this.sActiveProduct + '_page\'] > strong").text(iSelectedPos);';
        } else if (this.sPagingType === 'numbering') {
            sSwipeVars = '$(".' + this.divPaginateName + ' > p > strong").text(iSelectedPos);';
        } else if (this.sPagingType === 'rolling') {
            sSwipeVars = '         $("[name^=\'' + this.sActiveProduct + '_page\']").removeClass(\'' + sSelected + '\');';
            sSwipeVars += '        $("[name=\'' + this.sActiveProduct + '_page_1_"+iSelectedPos+"\']").addClass(\'' + sSelected + '\');';
        } else {
            sSwipeVars = '         var iPage = pos === ' + this.iTotalPage + ' ? Math.ceil(' + this.iTotalPage + ' / $S.iLimit) : Math.ceil(iSelectedPos / $S.iLimit);';

            /*
                ECHOSTING-251668 show/hide 조건 삭제

                sSwipeVars += '        if ((pos % $S.iLimit === 0) || (iSelectedPos % $S.iLimit === 0 || iSelectedPos === ' + this.iTotalPage + ')) {';
                sSwipeVars += '        $("[name^=\'' + this.sActiveProduct + '_page\']").hide();';
                sSwipeVars += '        $("[name^=\'' + this.sActiveProduct + '_page_"+iPage+"\']").show();';
                sSwipeVars += '        }';
                sSwipeVars += '        $("[name^=\'' + this.sActiveProduct + '_page\']' + sChildSelector + '").removeClass(\'' + sSelected + '\');';
                sSwipeVars += '        $("[name=\'' + this.sActiveProduct + '_page_"+iPage+"_"+iSelectedPos+"\']' + sChildSelector + '").addClass(\'' + sSelected + '\');';
            */

            sSwipeVars += '        $("[name^=\'' + this.sActiveProduct + '_page\']").hide();';
            sSwipeVars += '        $("[name^=\'' + this.sActiveProduct + '_page_"+iPage+"\']").show();';
            sSwipeVars += '        $("[name^=\'' + this.sActiveProduct + '_page\']' + sChildSelector + '").removeClass(\'' + sSelected + '\');';
            sSwipeVars += '        $("[name=\'' + this.sActiveProduct + '_page_"+iPage+"_"+iSelectedPos+"\']' + sChildSelector + '").addClass(\'' + sSelected + '\');';
        }
        return sSwipeVars;
    }

};

EC$(function() {
    sAttribute = 'ec-data-src';
    if (EC$('img['+sAttribute+']').length > 0) {
        CAFE24.lazyload();
    }
});

/**
 * IntersectionObserver와 MutationObserver를 이용한 Lazyload
 * @constructor
 */
CAFE24.lazyload = function() {
    var oConfig = {
        rootMargin: '0px 0px 50px 0px',
        threshold: 0
    };

    var placeholder = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGP6zwAAAgcBApocMXEAAAAASUVORK5CYII=';
    EC$('img['+sAttribute+']').attr('src', placeholder);

    /**
     * IntersectionObserver에 객체를 등록하는 메소드
     * @type {IntersectionObserver|*}
     */
    var oIntersection = new IntersectionObserver(function (oEntry, self) {
        var iEntryLength = oEntry.length;
        for (var i = 0; i < iEntryLength; i++) {
            if (oEntry[i].target.tagName !== 'IMG') {
                continue;
            }
            if (oEntry[i].target.hasAttribute(sAttribute) === false) {
                continue;
            }
            oEntry[i].target.src = placeholder;
            if (oEntry[i].isIntersecting) {
                preloadImage(oEntry[i].target);
                self.unobserve(oEntry[i].target);
            }
        }
        // 상품목록에 블럭형 레이이아웃이 존재할때만 이미지 로딩 이후 호출
        if (typeof resizeContent === 'function') {
            resizeContent();
        }
    }, oConfig);

    /**
     * MutationObserver에 객체를 등록하는 메소드
     * @type {MutationObserver}
     */
    var oMutation = new MutationObserver(function (oMutationElement) {
        var iMutationLength = oMutationElement.length;
        for (var i = 0; i < iMutationLength; i++) {
            if (oMutationElement[i].addedNodes.length > 0) {
                oMutationElement[i].addedNodes.forEach(function (currnetValue) {
                    registIntersection(currnetValue);
                });
            }
        }
    });

    /**
     * Mutation에서 검출된 객체를 Intersection으로 등록하는 메소드
     * @param oParentElement
     */
    function registIntersection(oParentElement) {
        if (typeof oParentElement.querySelectorAll !== 'function') {
            return;
        }
        var oImage = oParentElement.querySelectorAll('img['+sAttribute+']');
        for (var i=0; i < oImage.length; i++) {
            oIntersection.observe(oImage[i]);
        }
    }

    /**
     * 실제 이미지 교체 메소드
     * @param img
     */
    function preloadImage(img) {
        var src = img.getAttribute(sAttribute);
        if (!src) return;
        img.src = src;
        img.removeAttribute(sAttribute);
    }

    var oNodeList = document.body.childNodes;
    var oMutationConfig = {
        childList: true,
        subtree: true
    };
    var iNodeListLength = oNodeList.length;
    if (iNodeListLength === 0) {
        return;
    }
    for (var i = 0; i < iNodeListLength; i++) {
        registIntersection(oNodeList[i]);
        oMutation.observe(oNodeList[i], oMutationConfig);
    }
};

var EC_lazyload = CAFE24.lazyload;

/*!
 * Shim for MutationObserver interface
 * Author: Graeme Yeates (github.com/megawac)
 * Repository: https://github.com/megawac/MutationObserver.js
 * License: WTFPL V2, 2004 (wtfpl.net).
 * Though credit and staring the repo will make me feel pretty, you can modify and redistribute as you please.
 * Attempts to follow spec (https://www.w3.org/TR/dom/#mutation-observers) as closely as possible for native javascript
 * See https://github.com/WebKit/webkit/blob/master/Source/WebCore/dom/MutationObserver.cpp for current webkit source c++ implementation
 */

/**
 * prefix bugs:
 - https://bugs.webkit.org/show_bug.cgi?id=85161
 - https://bugzilla.mozilla.org/show_bug.cgi?id=749920
 * Don't use WebKitMutationObserver as Safari (6.0.5-6.1) use a buggy implementation
 */
window.MutationObserver = window.MutationObserver || (function(undefined) {
        "use strict";
        /**
         * @param {function(Array.<MutationRecord>, MutationObserver)} listener
         * @constructor
         */
        function MutationObserver(listener) {
            /**
             * @type {Array.<Object>}
             * @private
             */
            this._watched = [];
            /** @private */
            this._listener = listener;
        }

        /**
         * Start a recursive timeout function to check all items being observed for mutations
         * @type {MutationObserver} observer
         * @private
         */
        function startMutationChecker(observer) {
            (function check() {
                var mutations = observer.takeRecords();

                if (mutations.length) { // fire away
                    // calling the listener with context is not spec but currently consistent with FF and WebKit
                    observer._listener(mutations, observer);
                }
                /** @private */
                observer._timeout = setTimeout(check, MutationObserver._period);
            })();
        }

        /**
         * Period to check for mutations (~32 times/sec)
         * @type {number}
         * @expose
         */
        MutationObserver._period = 30;

        /**
         * Exposed API
         * @expose
         * @final
         */
        MutationObserver.prototype = {
            /**
             * see https://dom.spec.whatwg.org/#dom-mutationobserver-observe
             * not going to throw here but going to follow the current spec config sets
             * @param {Node|null} $target
             * @param {Object|null} config : MutationObserverInit configuration dictionary
             * @expose
             * @return undefined
             */
            observe: function($target, config) {
                /**
                 * Using slightly different names so closure can go ham
                 * @type {!Object} : A custom mutation config
                 */
                var settings = {
                    attr: !! (config.attributes || config.attributeFilter || config.attributeOldValue),

                    // some browsers enforce that subtree must be set with childList, attributes or characterData.
                    // We don't care as spec doesn't specify this rule.
                    kids: !! config.childList,
                    descendents: !! config.subtree,
                    charData: !! (config.characterData || config.characterDataOldValue)
                };

                var watched = this._watched;

                // remove already observed target element from pool
                for (var i = 0; i < watched.length; i++) {
                    if (watched[i].tar === $target) watched.splice(i, 1);
                }

                if (config.attributeFilter) {
                    /**
                     * converts to a {key: true} dict for faster lookup
                     * @type {Object.<String,Boolean>}
                     */
                    settings.afilter = reduce(config.attributeFilter, function(a, b) {
                        a[b] = true;
                        return a;
                    }, {});
                }

                watched.push({
                    tar: $target,
                    fn: createMutationSearcher($target, settings)
                });

                // reconnect if not connected
                if (!this._timeout) {
                    startMutationChecker(this);
                }
            },

            /**
             * Finds mutations since last check and empties the "record queue" i.e. mutations will only be found once
             * @expose
             * @return {Array.<MutationRecord>}
             */
            takeRecords: function() {
                var mutations = [];
                var watched = this._watched;

                for (var i = 0; i < watched.length; i++) {
                    watched[i].fn(mutations);
                }

                return mutations;
            },

            /**
             * @expose
             * @return undefined
             */
            disconnect: function() {
                this._watched = []; // clear the stuff being observed
                clearTimeout(this._timeout); // ready for garbage collection
                /** @private */
                this._timeout = null;
            }
        };

        /**
         * Simple MutationRecord pseudoclass. No longer exposing as its not fully compliant
         * @param {Object} data
         * @return {Object} a MutationRecord
         */
        function MutationRecord(data) {
            var settings = { // technically these should be on proto so hasOwnProperty will return false for non explicitly props
                type: null,
                target: null,
                addedNodes: [],
                removedNodes: [],
                previousSibling: null,
                nextSibling: null,
                attributeName: null,
                attributeNamespace: null,
                oldValue: null
            };
            for (var prop in data) {
                if (has(settings, prop) && data[prop] !== undefined) settings[prop] = data[prop];
            }
            return settings;
        }

        /**
         * Creates a func to find all the mutations
         *
         * @param {Node} $target
         * @param {!Object} config : A custom mutation config
         */
        function createMutationSearcher($target, config) {
            /** type {Elestuct} */
            var $oldstate = clone($target, config); // create the cloned datastructure

            /**
             * consumes array of mutations we can push to
             *
             * @param {Array.<MutationRecord>} mutations
             */
            return function(mutations) {
                var olen = mutations.length, dirty;

                if (config.charData && $target.nodeType === 3 && $target.nodeValue !== $oldstate.charData) {
                    mutations.push(new MutationRecord({
                        type: "characterData",
                        target: $target,
                        oldValue: $oldstate.charData
                    }));
                }

                // Alright we check base level changes in attributes... easy
                if (config.attr && $oldstate.attr) {
                    findAttributeMutations(mutations, $target, $oldstate.attr, config.afilter);
                }

                // check childlist or subtree for mutations
                if (config.kids || config.descendents) {
                    dirty = searchSubtree(mutations, $target, $oldstate, config);
                }

                // reclone data structure if theres changes
                if (dirty || mutations.length !== olen) {
                    /** type {Elestuct} */
                    $oldstate = clone($target, config);
                }
            };
        }

        /* attributes + attributeFilter helpers */

        // Check if the environment has the attribute bug (#4) which cause
        // element.attributes.style to always be null.
        var hasAttributeBug = document.createElement("i");
        hasAttributeBug.style.top = 0;
        hasAttributeBug = hasAttributeBug.attributes.style.value != "null";

        /**
         * Gets an attribute value in an environment without attribute bug
         *
         * @param {Node} el
         * @param {Attr} attr
         * @return {String} an attribute value
         */
        function getAttributeSimple(el, attr) {
            // There is a potential for a warning to occur here if the attribute is a
            // custom attribute in IE<9 with a custom .toString() method. This is
            // just a warning and doesn't affect execution (see #21)
            return attr.value;
        }

        /**
         * Gets an attribute value with special hack for style attribute (see #4)
         *
         * @param {Node} el
         * @param {Attr} attr
         * @return {String} an attribute value
         */
        function getAttributeWithStyleHack(el, attr) {
            // As with getAttributeSimple there is a potential warning for custom attribtues in IE7.
            return attr.name !== "style" ? attr.value : el.style.cssText;
        }

        var getAttributeValue = hasAttributeBug ? getAttributeSimple : getAttributeWithStyleHack;

        /**
         * fast helper to check to see if attributes object of an element has changed
         * doesnt handle the textnode case
         *
         * @param {Array.<MutationRecord>} mutations
         * @param {Node} $target
         * @param {Object.<string, string>} $oldstate : Custom attribute clone data structure from clone
         * @param {Object} filter
         */
        function findAttributeMutations(mutations, $target, $oldstate, filter) {
            var checked = {};
            var attributes = $target.attributes;
            var attr;
            var name;
            var i = attributes.length;
            while (i--) {
                attr = attributes[i];
                name = attr.name;
                if (!filter || has(filter, name)) {
                    if (getAttributeValue($target, attr) !== $oldstate[name]) {
                        // The pushing is redundant but gzips very nicely
                        mutations.push(MutationRecord({
                            type: "attributes",
                            target: $target,
                            attributeName: name,
                            oldValue: $oldstate[name],
                            attributeNamespace: attr.namespaceURI // in ie<8 it incorrectly will return undefined
                        }));
                    }
                    checked[name] = true;
                }
            }
            for (name in $oldstate) {
                if (!(checked[name])) {
                    mutations.push(MutationRecord({
                        target: $target,
                        type: "attributes",
                        attributeName: name,
                        oldValue: $oldstate[name]
                    }));
                }
            }
        }

        /**
         * searchSubtree: array of mutations so far, element, element clone, bool
         * synchronous dfs comparision of two nodes
         * This function is applied to any observed element with childList or subtree specified
         * Sorry this is kind of confusing as shit, tried to comment it a bit...
         * codereview.stackexchange.com/questions/38351 discussion of an earlier version of this func
         *
         * @param {Array} mutations
         * @param {Node} $target
         * @param {!Object} $oldstate : A custom cloned node from clone()
         * @param {!Object} config : A custom mutation config
         */
        function searchSubtree(mutations, $target, $oldstate, config) {
            // Track if the tree is dirty and has to be recomputed (#14).
            var dirty;
            /*
             * Helper to identify node rearrangment and stuff...
             * There is no gaurentee that the same node will be identified for both added and removed nodes
             * if the positions have been shuffled.
             * conflicts array will be emptied by end of operation
             */
            function resolveConflicts(conflicts, node, $kids, $oldkids, numAddedNodes) {
                // the distance between the first conflicting node and the last
                var distance = conflicts.length - 1;
                // prevents same conflict being resolved twice consider when two nodes switch places.
                // only one should be given a mutation event (note -~ is used as a math.ceil shorthand)
                var counter = -~((distance - numAddedNodes) / 2);
                var $cur;
                var oldstruct;
                var conflict;
                while ((conflict = conflicts.pop())) {
                    $cur = $kids[conflict.i];
                    oldstruct = $oldkids[conflict.j];

                    // attempt to determine if there was node rearrangement... won't gaurentee all matches
                    // also handles case where added/removed nodes cause nodes to be identified as conflicts
                    if (config.kids && counter && Math.abs(conflict.i - conflict.j) >= distance) {
                        mutations.push(MutationRecord({
                            type: "childList",
                            target: node,
                            addedNodes: [$cur],
                            removedNodes: [$cur],
                            // haha don't rely on this please
                            nextSibling: $cur.nextSibling,
                            previousSibling: $cur.previousSibling
                        }));
                        counter--; // found conflict
                    }

                    // Alright we found the resorted nodes now check for other types of mutations
                    if (config.attr && oldstruct.attr) findAttributeMutations(mutations, $cur, oldstruct.attr, config.afilter);
                    if (config.charData && $cur.nodeType === 3 && $cur.nodeValue !== oldstruct.charData) {
                        mutations.push(MutationRecord({
                            type: "characterData",
                            target: $cur,
                            oldValue: oldstruct.charData
                        }));
                    }
                    // now look @ subtree
                    if (config.descendents) findMutations($cur, oldstruct);
                }
            }

            /**
             * Main worker. Finds and adds mutations if there are any
             * @param {Node} node
             * @param {!Object} old : A cloned data structure using internal clone
             */
            function findMutations(node, old) {
                var $kids = node.childNodes;
                var $oldkids = old.kids;
                var klen = $kids.length;
                // $oldkids will be undefined for text and comment nodes
                var olen = $oldkids ? $oldkids.length : 0;
                // if (!olen && !klen) return; // both empty; clearly no changes

                // we delay the intialization of these for marginal performance in the expected case (actually quite signficant on large subtrees when these would be otherwise unused)
                // map of checked element of ids to prevent registering the same conflict twice
                var map;
                // array of potential conflicts (ie nodes that may have been re arranged)
                var conflicts;
                var id; // element id from getElementId helper
                var idx; // index of a moved or inserted element

                var oldstruct;
                // current and old nodes
                var $cur;
                var $old;
                // track the number of added nodes so we can resolve conflicts more accurately
                var numAddedNodes = 0;

                // iterate over both old and current child nodes at the same time
                var i = 0, j = 0;
                // while there is still anything left in $kids or $oldkids (same as i < $kids.length || j < $oldkids.length;)
                while (i < klen || j < olen) {
                    // current and old nodes at the indexs
                    $cur = $kids[i];
                    oldstruct = $oldkids[j];
                    $old = oldstruct && oldstruct.node;

                    if ($cur === $old) { // expected case - optimized for this case
                        // check attributes as specified by config
                        if (config.attr && oldstruct.attr) /* oldstruct.attr instead of textnode check */findAttributeMutations(mutations, $cur, oldstruct.attr, config.afilter);
                        // check character data if node is a comment or textNode and it's being observed
                        if (config.charData && oldstruct.charData !== undefined && $cur.nodeValue !== oldstruct.charData) {
                            mutations.push(MutationRecord({
                                type: "characterData",
                                target: $cur,
                                oldValue: oldstruct.charData
                            }));
                        }

                        // resolve conflicts; it will be undefined if there are no conflicts - otherwise an array
                        if (conflicts) resolveConflicts(conflicts, node, $kids, $oldkids, numAddedNodes);

                        // recurse on next level of children. Avoids the recursive call when there are no children left to iterate
                        if (config.descendents && ($cur.childNodes.length || oldstruct.kids && oldstruct.kids.length)) findMutations($cur, oldstruct);

                        i++;
                        j++;
                    } else { // (uncommon case) lookahead until they are the same again or the end of children
                        dirty = true;
                        if (!map) { // delayed initalization (big perf benefit)
                            map = {};
                            conflicts = [];
                        }
                        if ($cur) {
                            // check id is in the location map otherwise do a indexOf search
                            if (!(map[id = getElementId($cur)])) { // to prevent double checking
                                // mark id as found
                                map[id] = true;
                                // custom indexOf using comparitor checking oldkids[i].node === $cur
                                if ((idx = indexOfCustomNode($oldkids, $cur, j)) === -1) {
                                    if (config.kids) {
                                        mutations.push(MutationRecord({
                                            type: "childList",
                                            target: node,
                                            addedNodes: [$cur], // $cur is a new node
                                            nextSibling: $cur.nextSibling,
                                            previousSibling: $cur.previousSibling
                                        }));
                                        numAddedNodes++;
                                    }
                                } else {
                                    conflicts.push({ // add conflict
                                        i: i,
                                        j: idx
                                    });
                                }
                            }
                            i++;
                        }

                        if ($old &&
                            // special case: the changes may have been resolved: i and j appear congurent so we can continue using the expected case
                            $old !== $kids[i]
                        ) {
                            if (!(map[id = getElementId($old)])) {
                                map[id] = true;
                                if ((idx = indexOf($kids, $old, i)) === -1) {
                                    if (config.kids) {
                                        mutations.push(MutationRecord({
                                            type: "childList",
                                            target: old.node,
                                            removedNodes: [$old],
                                            nextSibling: $oldkids[j + 1], // praise no indexoutofbounds exception
                                            previousSibling: $oldkids[j - 1]
                                        }));
                                        numAddedNodes--;
                                    }
                                } else {
                                    conflicts.push({
                                        i: idx,
                                        j: j
                                    });
                                }
                            }
                            j++;
                        }
                    }// end uncommon case
                }// end loop

                // resolve any remaining conflicts
                if (conflicts) resolveConflicts(conflicts, node, $kids, $oldkids, numAddedNodes);
            }
            findMutations($target, $oldstate);
            return dirty;
        }

        /**
         * Utility
         * Cones a element into a custom data structure designed for comparision. https://gist.github.com/megawac/8201012
         *
         * @param {Node} $target
         * @param {!Object} config : A custom mutation config
         * @return {!Object} : Cloned data structure
         */
        function clone($target, config) {
            var recurse = true; // set true so childList we'll always check the first level
            return (function copy($target) {
                var elestruct = {
                    /** @type {Node} */
                    node: $target
                };

                // Store current character data of target text or comment node if the config requests
                // those properties to be observed.
                if (config.charData && ($target.nodeType === 3 || $target.nodeType === 8)) {
                    elestruct.charData = $target.nodeValue;
                }
                // its either a element, comment, doc frag or document node
                else {
                    // Add attr only if subtree is specified or top level and avoid if
                    // attributes is a document object (#13).
                    if (config.attr && recurse && $target.nodeType === 1) {
                        /**
                         * clone live attribute list to an object structure {name: val}
                         * @type {Object.<string, string>}
                         */
                        elestruct.attr = reduce($target.attributes, function(memo, attr) {
                            if (!config.afilter || config.afilter[attr.name]) {
                                memo[attr.name] = getAttributeValue($target, attr);
                            }
                            return memo;
                        }, {});
                    }

                    // whether we should iterate the children of $target node
                    if (recurse && ((config.kids || config.charData) || (config.attr && config.descendents))) {
                        /** @type {Array.<!Object>} : Array of custom clone */
                        elestruct.kids = map($target.childNodes, copy);
                    }

                    recurse = config.descendents;
                }
                return elestruct;
            })($target);
        }

        /**
         * indexOf an element in a collection of custom nodes
         *
         * @param {NodeList} set
         * @param {!Object} $node : A custom cloned node
         * @param {number} idx : index to start the loop
         * @return {number}
         */
        function indexOfCustomNode(set, $node, idx) {
            return indexOf(set, $node, idx, JSCompiler_renameProperty("node"));
        }

        // using a non id (eg outerHTML or nodeValue) is extremely naive and will run into issues with nodes that may appear the same like <li></li>
        var counter = 1; // don't use 0 as id (falsy)
        /** @const */
        var expando = "mo_id";

        /**
         * Attempt to uniquely id an element for hashing. We could optimize this for legacy browsers but it hopefully wont be called enough to be a concern
         *
         * @param {Node} $ele
         * @return {(string|number)}
         */
        function getElementId($ele) {
            try {
                return $ele.id || ($ele[expando] = $ele[expando] || counter++);
            } catch (o_O) { // ie <8 will throw if you set an unknown property on a text node
                try {
                    return $ele.nodeValue; // naive
                } catch (shitie) { // when text node is removed: https://gist.github.com/megawac/8355978 :(
                    return counter++;
                }
            }
        }

        /**
         * **map** Apply a mapping function to each item of a set
         * @param {Array|NodeList} set
         * @param {Function} iterator
         */
        function map(set, iterator) {
            var results = [];
            for (var index = 0; index < set.length; index++) {
                results[index] = iterator(set[index], index, set);
            }
            return results;
        }

        /**
         * **Reduce** builds up a single result from a list of values
         * @param {Array|NodeList|NamedNodeMap} set
         * @param {Function} iterator
         * @param {*} [memo] Initial value of the memo.
         */
        function reduce(set, iterator, memo) {
            for (var index = 0; index < set.length; index++) {
                memo = iterator(memo, set[index], index, set);
            }
            return memo;
        }

        /**
         * **indexOf** find index of item in collection.
         * @param {Array|NodeList} set
         * @param {Object} item
         * @param {number} idx
         * @param {string} [prop] Property on set item to compare to item
         */
        function indexOf(set, item, idx, prop) {
            for (/*idx = ~~idx*/; idx < set.length; idx++) {// start idx is always given as this is internal
                if ((prop ? set[idx][prop] : set[idx]) === item) return idx;
            }
            return -1;
        }

        /**
         * @param {Object} obj
         * @param {(string|number)} prop
         * @return {boolean}
         */
        function has(obj, prop) {
            return obj[prop] !== undefined; // will be nicely inlined by gcc
        }

        // GCC hack see https://stackoverflow.com/a/23202438/1517919
        function JSCompiler_renameProperty(a) {
            return a;
        }

        return MutationObserver;
    })(void 0);

/**
 * Copyright 2016 Google Inc. All Rights Reserved.
 *
 * Licensed under the W3C SOFTWARE AND DOCUMENT NOTICE AND LICENSE.
 *
 *  https://www.w3.org/Consortium/Legal/2015/copyright-software-and-document
 *
 */
(function(window, document) {
    'use strict';

// Exits early if all IntersectionObserver and IntersectionObserverEntry
// features are natively supported.
    if ('IntersectionObserver' in window &&
        'IntersectionObserverEntry' in window &&
        'intersectionRatio' in window.IntersectionObserverEntry.prototype) {

        // Minimal polyfill for Edge 15's lack of `isIntersecting`
        // See: https://github.com/w3c/IntersectionObserver/issues/211
        if (!('isIntersecting' in window.IntersectionObserverEntry.prototype)) {
            Object.defineProperty(window.IntersectionObserverEntry.prototype,
                'isIntersecting', {
                    get: function () {
                        return this.intersectionRatio > 0;
                    }
                });
        }
        return;
    }

    /**
     * An IntersectionObserver registry. This registry exists to hold a strong
     * reference to IntersectionObserver instances currently observering a target
     * element. Without this registry, instances without another reference may be
     * garbage collected.
     */
    var registry = [];


    /**
     * Creates the global IntersectionObserverEntry constructor.
     * https://w3c.github.io/IntersectionObserver/#intersection-observer-entry
     * @param {Object} entry A dictionary of instance properties.
     * @constructor
     */
    function IntersectionObserverEntry(entry) {
        this.time = entry.time;
        this.target = entry.target;
        this.rootBounds = entry.rootBounds;
        this.boundingClientRect = entry.boundingClientRect;
        this.intersectionRect = entry.intersectionRect || getEmptyRect();
        this.isIntersecting = !!entry.intersectionRect;

        // Calculates the intersection ratio.
        var targetRect = this.boundingClientRect;
        var targetArea = targetRect.width * targetRect.height;
        var intersectionRect = this.intersectionRect;
        var intersectionArea = intersectionRect.width * intersectionRect.height;

        // Sets intersection ratio.
        if (targetArea) {
            this.intersectionRatio = intersectionArea / targetArea;
        } else {
            // If area is zero and is intersecting, sets to 1, otherwise to 0
            this.intersectionRatio = this.isIntersecting ? 1 : 0;
        }
    }


    /**
     * Creates the global IntersectionObserver constructor.
     * https://w3c.github.io/IntersectionObserver/#intersection-observer-interface
     * @param {Function} callback The function to be invoked after intersection
     *     changes have queued. The function is not invoked if the queue has
     *     been emptied by calling the `takeRecords` method.
     * @param {Object=} opt_options Optional configuration options.
     * @constructor
     */
    function IntersectionObserver(callback, opt_options) {

        var options = opt_options || {};

        if (typeof callback !== 'function') {
            throw new Error('callback must be a function');
        }

        if (options.root && options.root.nodeType != 1) {
            throw new Error('root must be an Element');
        }

        // Binds and throttles `this._checkForIntersections`.
        this._checkForIntersections = throttle(
            this._checkForIntersections.bind(this), this.THROTTLE_TIMEOUT);

        // Private properties.
        this._callback = callback;
        this._observationTargets = [];
        this._queuedEntries = [];
        this._rootMarginValues = this._parseRootMargin(options.rootMargin);

        // Public properties.
        this.thresholds = this._initThresholds(options.threshold);
        this.root = options.root || null;
        this.rootMargin = this._rootMarginValues.map(function(margin) {
            return margin.value + margin.unit;
        }).join(' ');
    }


    /**
     * The minimum interval within which the document will be checked for
     * intersection changes.
     */
    IntersectionObserver.prototype.THROTTLE_TIMEOUT = 100;


    /**
     * The frequency in which the polyfill polls for intersection changes.
     * this can be updated on a per instance basis and must be set prior to
     * calling `observe` on the first target.
     */
    IntersectionObserver.prototype.POLL_INTERVAL = null;

    /**
     * Use a mutation observer on the root element
     * to detect intersection changes.
     */
    IntersectionObserver.prototype.USE_MUTATION_OBSERVER = true;


    /**
     * Starts observing a target element for intersection changes based on
     * the thresholds values.
     * @param {Element} target The DOM element to observe.
     */
    IntersectionObserver.prototype.observe = function(target) {
        var isTargetAlreadyObserved = this._observationTargets.some(function(item) {
            return item.element == target;
        });

        if (isTargetAlreadyObserved) {
            return;
        }

        if (!(target && target.nodeType == 1)) {
            throw new Error('target must be an Element');
        }

        this._registerInstance();
        this._observationTargets.push({element: target, entry: null});
        this._monitorIntersections();
        this._checkForIntersections();
    };


    /**
     * Stops observing a target element for intersection changes.
     * @param {Element} target The DOM element to observe.
     */
    IntersectionObserver.prototype.unobserve = function(target) {
        this._observationTargets =
            this._observationTargets.filter(function(item) {

                return item.element != target;
            });
        if (!this._observationTargets.length) {
            this._unmonitorIntersections();
            this._unregisterInstance();
        }
    };


    /**
     * Stops observing all target elements for intersection changes.
     */
    IntersectionObserver.prototype.disconnect = function() {
        this._observationTargets = [];
        this._unmonitorIntersections();
        this._unregisterInstance();
    };


    /**
     * Returns any queue entries that have not yet been reported to the
     * callback and clears the queue. This can be used in conjunction with the
     * callback to obtain the absolute most up-to-date intersection information.
     * @return {Array} The currently queued entries.
     */
    IntersectionObserver.prototype.takeRecords = function() {
        var records = this._queuedEntries.slice();
        this._queuedEntries = [];
        return records;
    };


    /**
     * Accepts the threshold value from the user configuration object and
     * returns a sorted array of unique threshold values. If a value is not
     * between 0 and 1 and error is thrown.
     * @private
     * @param {Array|number=} opt_threshold An optional threshold value or
     *     a list of threshold values, defaulting to [0].
     * @return {Array} A sorted list of unique and valid threshold values.
     */
    IntersectionObserver.prototype._initThresholds = function(opt_threshold) {
        var threshold = opt_threshold || [0];
        if (!Array.isArray(threshold)) threshold = [threshold];

        return threshold.sort().filter(function(t, i, a) {
            if (typeof t !== 'number' || isNaN(t) || t < 0 || t > 1) {
                throw new Error('threshold must be a number between 0 and 1 inclusively');
            }
            return t !== a[i - 1];
        });
    };


    /**
     * Accepts the rootMargin value from the user configuration object
     * and returns an array of the four margin values as an object containing
     * the value and unit properties. If any of the values are not properly
     * formatted or use a unit other than px or %, and error is thrown.
     * @private
     * @param {string=} opt_rootMargin An optional rootMargin value,
     *     defaulting to '0px'.
     * @return {Array<Object>} An array of margin objects with the keys
     *     value and unit.
     */
    IntersectionObserver.prototype._parseRootMargin = function(opt_rootMargin) {
        var marginString = opt_rootMargin || '0px';
        var margins = marginString.split(/\s+/).map(function(margin) {
            var parts = /^(-?\d*\.?\d+)(px|%)$/.exec(margin);
            if (!parts) {
                throw new Error('rootMargin must be specified in pixels or percent');
            }
            return {value: parseFloat(parts[1]), unit: parts[2]};
        });

        // Handles shorthand.
        margins[1] = margins[1] || margins[0];
        margins[2] = margins[2] || margins[0];
        margins[3] = margins[3] || margins[1];

        return margins;
    };


    /**
     * Starts polling for intersection changes if the polling is not already
     * happening, and if the page's visibilty state is visible.
     * @private
     */
    IntersectionObserver.prototype._monitorIntersections = function() {
        if (!this._monitoringIntersections) {
            this._monitoringIntersections = true;

            // If a poll interval is set, use polling instead of listening to
            // resize and scroll events or DOM mutations.
            if (this.POLL_INTERVAL) {
                this._monitoringInterval = setInterval(
                    this._checkForIntersections, this.POLL_INTERVAL);
            }
            else {
                addEvent(window, 'resize', this._checkForIntersections, true);
                addEvent(document, 'scroll', this._checkForIntersections, true);

                if (this.USE_MUTATION_OBSERVER && 'MutationObserver' in window) {
                    this._domObserver = new MutationObserver(this._checkForIntersections);
                    this._domObserver.observe(document, {
                        attributes: true,
                        childList: true,
                        characterData: true,
                        subtree: true
                    });
                }
            }
        }
    };


    /**
     * Stops polling for intersection changes.
     * @private
     */
    IntersectionObserver.prototype._unmonitorIntersections = function() {
        if (this._monitoringIntersections) {
            this._monitoringIntersections = false;

            clearInterval(this._monitoringInterval);
            this._monitoringInterval = null;

            removeEvent(window, 'resize', this._checkForIntersections, true);
            removeEvent(document, 'scroll', this._checkForIntersections, true);

            if (this._domObserver) {
                this._domObserver.disconnect();
                this._domObserver = null;
            }
        }
    };


    /**
     * Scans each observation target for intersection changes and adds them
     * to the internal entries queue. If new entries are found, it
     * schedules the callback to be invoked.
     * @private
     */
    IntersectionObserver.prototype._checkForIntersections = function() {
        var rootIsInDom = this._rootIsInDom();
        var rootRect = rootIsInDom ? this._getRootRect() : getEmptyRect();

        this._observationTargets.forEach(function(item) {
            var target = item.element;
            var targetRect = getBoundingClientRect(target);
            var rootContainsTarget = this._rootContainsTarget(target);
            var oldEntry = item.entry;
            var intersectionRect = rootIsInDom && rootContainsTarget &&
                this._computeTargetAndRootIntersection(target, rootRect);

            var newEntry = item.entry = new IntersectionObserverEntry({
                time: now(),
                target: target,
                boundingClientRect: targetRect,
                rootBounds: rootRect,
                intersectionRect: intersectionRect
            });

            if (!oldEntry) {
                this._queuedEntries.push(newEntry);
            } else if (rootIsInDom && rootContainsTarget) {
                // If the new entry intersection ratio has crossed any of the
                // thresholds, add a new entry.
                if (this._hasCrossedThreshold(oldEntry, newEntry)) {
                    this._queuedEntries.push(newEntry);
                }
            } else {
                // If the root is not in the DOM or target is not contained within
                // root but the previous entry for this target had an intersection,
                // add a new record indicating removal.
                if (oldEntry && oldEntry.isIntersecting) {
                    this._queuedEntries.push(newEntry);
                }
            }
        }, this);

        if (this._queuedEntries.length) {
            this._callback(this.takeRecords(), this);
        }
    };


    /**
     * Accepts a target and root rect computes the intersection between then
     * following the algorithm in the spec.
     * TODO(philipwalton): at this time clip-path is not considered.
     * https://w3c.github.io/IntersectionObserver/#calculate-intersection-rect-algo
     * @param {Element} target The target DOM element
     * @param {Object} rootRect The bounding rect of the root after being
     *     expanded by the rootMargin value.
     * @return {?Object} The final intersection rect object or undefined if no
     *     intersection is found.
     * @private
     */
    IntersectionObserver.prototype._computeTargetAndRootIntersection =
        function(target, rootRect) {

            // If the element isn't displayed, an intersection can't happen.
            if (window.getComputedStyle(target).display == 'none') return;

            var targetRect = getBoundingClientRect(target);
            var intersectionRect = targetRect;
            var parent = getParentNode(target);
            var atRoot = false;

            while (!atRoot) {
                var parentRect = null;
                var parentComputedStyle = parent.nodeType == 1 ?
                    window.getComputedStyle(parent) : {};

                // If the parent isn't displayed, an intersection can't happen.
                if (parentComputedStyle.display == 'none') return;

                if (parent == this.root || parent == document) {
                    atRoot = true;
                    parentRect = rootRect;
                } else {
                    // If the element has a non-visible overflow, and it's not the <body>
                    // or <html> element, update the intersection rect.
                    // Note: <body> and <html> cannot be clipped to a rect that's not also
                    // the document rect, so no need to compute a new intersection.
                    if (parent != document.body &&
                        parent != document.documentElement &&
                        parentComputedStyle.overflow != 'visible') {
                        parentRect = getBoundingClientRect(parent);
                    }
                }

                // If either of the above conditionals set a new parentRect,
                // calculate new intersection data.
                if (parentRect) {
                    intersectionRect = computeRectIntersection(parentRect, intersectionRect);

                    if (!intersectionRect) break;
                }
                parent = getParentNode(parent);
            }
            return intersectionRect;
        };


    /**
     * Returns the root rect after being expanded by the rootMargin value.
     * @return {Object} The expanded root rect.
     * @private
     */
    IntersectionObserver.prototype._getRootRect = function() {
        var rootRect;
        if (this.root) {
            rootRect = getBoundingClientRect(this.root);
        } else {
            // Use <html>/<body> instead of window since scroll bars affect size.
            var html = document.documentElement;
            var body = document.body;
            rootRect = {
                top: 0,
                left: 0,
                right: html.clientWidth || body.clientWidth,
                width: html.clientWidth || body.clientWidth,
                bottom: html.clientHeight || body.clientHeight,
                height: html.clientHeight || body.clientHeight
            };
        }
        return this._expandRectByRootMargin(rootRect);
    };


    /**
     * Accepts a rect and expands it by the rootMargin value.
     * @param {Object} rect The rect object to expand.
     * @return {Object} The expanded rect.
     * @private
     */
    IntersectionObserver.prototype._expandRectByRootMargin = function(rect) {
        var margins = this._rootMarginValues.map(function(margin, i) {
            return margin.unit == 'px' ? margin.value :
                margin.value * (i % 2 ? rect.width : rect.height) / 100;
        });
        var newRect = {
            top: rect.top - margins[0],
            right: rect.right + margins[1],
            bottom: rect.bottom + margins[2],
            left: rect.left - margins[3]
        };
        newRect.width = newRect.right - newRect.left;
        newRect.height = newRect.bottom - newRect.top;

        return newRect;
    };


    /**
     * Accepts an old and new entry and returns true if at least one of the
     * threshold values has been crossed.
     * @param {?IntersectionObserverEntry} oldEntry The previous entry for a
     *    particular target element or null if no previous entry exists.
     * @param {IntersectionObserverEntry} newEntry The current entry for a
     *    particular target element.
     * @return {boolean} Returns true if a any threshold has been crossed.
     * @private
     */
    IntersectionObserver.prototype._hasCrossedThreshold =
        function(oldEntry, newEntry) {

            // To make comparing easier, an entry that has a ratio of 0
            // but does not actually intersect is given a value of -1
            var oldRatio = oldEntry && oldEntry.isIntersecting ?
                oldEntry.intersectionRatio || 0 : -1;
            var newRatio = newEntry.isIntersecting ?
                newEntry.intersectionRatio || 0 : -1;

            // Ignore unchanged ratios
            if (oldRatio === newRatio) return;

            for (var i = 0; i < this.thresholds.length; i++) {
                var threshold = this.thresholds[i];

                // Return true if an entry matches a threshold or if the new ratio
                // and the old ratio are on the opposite sides of a threshold.
                if (threshold == oldRatio || threshold == newRatio ||
                    threshold < oldRatio !== threshold < newRatio) {
                    return true;
                }
            }
        };


    /**
     * Returns whether or not the root element is an element and is in the DOM.
     * @return {boolean} True if the root element is an element and is in the DOM.
     * @private
     */
    IntersectionObserver.prototype._rootIsInDom = function() {
        return !this.root || containsDeep(document, this.root);
    };


    /**
     * Returns whether or not the target element is a child of root.
     * @param {Element} target The target element to check.
     * @return {boolean} True if the target element is a child of root.
     * @private
     */
    IntersectionObserver.prototype._rootContainsTarget = function(target) {
        return containsDeep(this.root || document, target);
    };


    /**
     * Adds the instance to the global IntersectionObserver registry if it isn't
     * already present.
     * @private
     */
    IntersectionObserver.prototype._registerInstance = function() {
        if (registry.indexOf(this) < 0) {
            registry.push(this);
        }
    };


    /**
     * Removes the instance from the global IntersectionObserver registry.
     * @private
     */
    IntersectionObserver.prototype._unregisterInstance = function() {
        var index = registry.indexOf(this);
        if (index != -1) registry.splice(index, 1);
    };


    /**
     * Returns the result of the performance.now() method or null in browsers
     * that don't support the API.
     * @return {number} The elapsed time since the page was requested.
     */
    function now() {
        return window.performance && performance.now && performance.now();
    }


    /**
     * Throttles a function and delays its executiong, so it's only called at most
     * once within a given time period.
     * @param {Function} fn The function to throttle.
     * @param {number} timeout The amount of time that must pass before the
     *     function can be called again.
     * @return {Function} The throttled function.
     */
    function throttle(fn, timeout) {
        var timer = null;
        return function () {
            if (!timer) {
                timer = setTimeout(function() {
                    fn();
                    timer = null;
                }, timeout);
            }
        };
    }


    /**
     * Adds an event handler to a DOM node ensuring cross-browser compatibility.
     * @param {Node} node The DOM node to add the event handler to.
     * @param {string} event The event name.
     * @param {Function} fn The event handler to add.
     * @param {boolean} opt_useCapture Optionally adds the even to the capture
     *     phase. Note: this only works in modern browsers.
     */
    function addEvent(node, event, fn, opt_useCapture) {
        if (typeof node.addEventListener === 'function') {
            node.addEventListener(event, fn, opt_useCapture || false);
        }
        else if (typeof node.attachEvent === 'function') {
            node.attachEvent('on' + event, fn);
        }
    }


    /**
     * Removes a previously added event handler from a DOM node.
     * @param {Node} node The DOM node to remove the event handler from.
     * @param {string} event The event name.
     * @param {Function} fn The event handler to remove.
     * @param {boolean} opt_useCapture If the event handler was added with this
     *     flag set to true, it should be set to true here in order to remove it.
     */
    function removeEvent(node, event, fn, opt_useCapture) {
        if (typeof node.removeEventListener === 'function') {
            node.removeEventListener(event, fn, opt_useCapture || false);
        }
        else if (typeof node.detatchEvent === 'function') {
            node.detatchEvent('on' + event, fn);
        }
    }


    /**
     * Returns the intersection between two rect objects.
     * @param {Object} rect1 The first rect.
     * @param {Object} rect2 The second rect.
     * @return {?Object} The intersection rect or undefined if no intersection
     *     is found.
     */
    function computeRectIntersection(rect1, rect2) {
        var top = Math.max(rect1.top, rect2.top);
        var bottom = Math.min(rect1.bottom, rect2.bottom);
        var left = Math.max(rect1.left, rect2.left);
        var right = Math.min(rect1.right, rect2.right);
        var width = right - left;
        var height = bottom - top;

        return (width >= 0 && height >= 0) && {
            top: top,
            bottom: bottom,
            left: left,
            right: right,
            width: width,
            height: height
        };
    }


    /**
     * Shims the native getBoundingClientRect for compatibility with older IE.
     * @param {Element} el The element whose bounding rect to get.
     * @return {Object} The (possibly shimmed) rect of the element.
     */
    function getBoundingClientRect(el) {
        var rect;

        try {
            rect = el.getBoundingClientRect();
        } catch (err) {
            // Ignore Windows 7 IE11 "Unspecified error"
            // https://github.com/w3c/IntersectionObserver/pull/205
        }

        if (!rect) return getEmptyRect();

        // Older IE
        if (!(rect.width && rect.height)) {
            rect = {
                top: rect.top,
                right: rect.right,
                bottom: rect.bottom,
                left: rect.left,
                width: rect.right - rect.left,
                height: rect.bottom - rect.top
            };
        }
        return rect;
    }


    /**
     * Returns an empty rect object. An empty rect is returned when an element
     * is not in the DOM.
     * @return {Object} The empty rect.
     */
    function getEmptyRect() {
        return {
            top: 0,
            bottom: 0,
            left: 0,
            right: 0,
            width: 0,
            height: 0
        };
    }

    /**
     * Checks to see if a parent element contains a child elemnt (including inside
     * shadow DOM).
     * @param {Node} parent The parent element.
     * @param {Node} child The child element.
     * @return {boolean} True if the parent node contains the child node.
     */
    function containsDeep(parent, child) {
        var node = child;
        while (node) {
            if (node == parent) return true;

            node = getParentNode(node);
        }
        return false;
    }


    /**
     * Gets the parent node of an element or its host element if the parent node
     * is a shadow root.
     * @param {Node} node The node whose parent to get.
     * @return {Node|null} The parent node or null if no parent exists.
     */
    function getParentNode(node) {
        var parent = node.parentNode;

        if (parent && parent.nodeType == 11 && parent.host) {
            // If the parent is a shadow root, return the host element.
            return parent.host;
        }
        return parent;
    }


// Exposes the constructors globally.
    window.IntersectionObserver = IntersectionObserver;
    window.IntersectionObserverEntry = IntersectionObserverEntry;

}(window, document));

if (window.NodeList && !NodeList.prototype.forEach) {
    NodeList.prototype.forEach = function (callback, thisArg) {
        thisArg = thisArg || window;
        for (var i = 0; i < this.length; i++) {
            callback.call(thisArg, this[i], i, this);
        }
    };
}

CAFE24.SMART_BANNER_DEFAULT = {

    /**
     * ECHOSTING-331346 : 프론트화면에서 스마트배너 갱신시 화면 리로드
     * @returns {boolean}
     */
    reloadFrontPage: function () {
        addEventListener('message', function(e) {

            if (e.origin.indexOf(EC_FRONT_JS_CONFIG_MANAGE.sDefaultAppDomain) < 0) {
                return false;
            }

            var jsonData = JSON.parse(e.data);

            if (jsonData == '' || jsonData.type == '') {
                return false;
            }

            if (jsonData.type == 'frontReload') {
                window.location.reload();
                return true;
            }

        }, false);
    }
};

var SMART_BANNER_DEFAULT = CAFE24.getDeprecatedNamespace('SMART_BANNER_DEFAULT');

EC$(function () {

    try {
        // 스마트배너 모듈명이 존재하는지 확인
        var smartBannerModuleCount = EC$("div[module*='smart-banner-admin'],div[app4you-smart-banner*='smart-banner-admin']").length;

        if (!smartBannerModuleCount) {
            return false;
        }

        // SDK 가 로드되지 않은경우 필요값 설정
        if (typeof CAFE24API === "undefined") {
            CAFE24API = {
                MALL_ID: EC_FRONT_JS_CONFIG_MANAGE.sMallId,
                SHOP_NO: CAFE24.SDE_SHOP_NUM
            };
        }

        // 스마트배너 모듈명이 존재할경우 ScriptTag Load
        var defaultAppScripts = document.createElement("script");
        defaultAppScripts.type = "text/javascript";
        defaultAppScripts.src = EC_FRONT_JS_CONFIG_MANAGE.sSmartBannerScriptUrl;
        EC$("head").append(defaultAppScripts);

        // ECHOSTING-331346 : 프론트화면에서 스마트배너 갱신시 화면 리로드
        CAFE24.SMART_BANNER_DEFAULT.reloadFrontPage();

    } catch (e) {
        if (typeof(e) === 'object' && e.stack && window.console) {
            console.log(e);
        }
    }
});

/*! Copyright (c) 2013 Brandon Aaron (http://brandonaaron.net)
 * Licensed under the MIT License (LICENSE.txt).
 *
 * Version 3.0.0
 */

(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {
    $.fn.bgiframe = function(s) {
        s = $.extend({
            top         : 'auto', // auto == borderTopWidth
            left        : 'auto', // auto == borderLeftWidth
            width       : 'auto', // auto == offsetWidth
            height      : 'auto', // auto == offsetHeight
            opacity     : true,
            src         : 'javascript:false;',
            conditional : /MSIE 6.0/.test(navigator.userAgent) // expresion or function. return false to prevent iframe insertion
        }, s);

        // wrap conditional in a function if it isn't already
        if (!$.isFunction(s.conditional)) {
            var condition = s.conditional;
            s.conditional = function() { return condition; };
        }

        var $iframe = $('<iframe class="bgiframe"frameborder="0"tabindex="-1"src="'+s.src+'"'+
                           'style="display:block;position:absolute;z-index:-1;"/>');

        return this.each(function() {
            var $this = $(this);
            if ( s.conditional(this) === false ) { return; }
            var existing = $this.children('iframe.bgiframe');
            var $el = existing.length === 0 ? $iframe.clone() : existing;
            $el.css({
                'top': s.top == 'auto' ?
                    ((parseInt($this.css('borderTopWidth'),10)||0)*-1)+'px' : prop(s.top),
                'left': s.left == 'auto' ?
                    ((parseInt($this.css('borderLeftWidth'),10)||0)*-1)+'px' : prop(s.left),
                'width': s.width == 'auto' ? (this.offsetWidth + 'px') : prop(s.width),
                'height': s.height == 'auto' ? (this.offsetHeight + 'px') : prop(s.height),
                'opacity': s.opacity === true ? 0 : undefined
            });

            if ( existing.length === 0 ) {
                $this.prepend($el);
            }
        });
    };

    // old alias
    $.fn.bgIframe = $.fn.bgiframe;

    function prop(n) {
        return n && n.constructor === Number ? n + 'px' : n;
    }

}));
/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options = $.extend({}, options); // clone object since it's unexpected behavior if the expired property were changed
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // NOTE Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};
/* Copyright (c) 2007 Paul Bakaus (paul.bakaus@googlemail.com) and Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * $LastChangedDate: 2007-12-20 08:46:55 -0600 (Thu, 20 Dec 2007) $
 * $Rev: 4259 $
 *
 * Version: 1.2
 *
 * Requires: jQuery 1.2+
 *
 */

(function($){

$.dimensions = {
    version: '1.2'
};

// Create innerHeight, innerWidth, outerHeight and outerWidth methods
$.each( [ 'Height', 'Width' ], function(i, name){
    if (!$.fn[ 'inner' + name ]) {
        // innerHeight and innerWidth
        $.fn[ 'inner' + name ] = function() {
            if (!this[0]) return;

            var torl = name == 'Height' ? 'Top'    : 'Left',  // top or left
                borr = name == 'Height' ? 'Bottom' : 'Right'; // bottom or right

            return this.is(':visible') ? this[0]['client' + name] : num( this, name.toLowerCase() ) + num(this, 'padding' + torl) + num(this, 'padding' + borr);
        };

        // outerHeight and outerWidth
        $.fn[ 'outer' + name ] = function(options) {
            if (!this[0]) return;

            var torl = name == 'Height' ? 'Top'    : 'Left',  // top or left
                borr = name == 'Height' ? 'Bottom' : 'Right'; // bottom or right

            options = $.extend({ margin: false }, options || {});

            var val = this.is(':visible') ?
                this[0]['offset' + name] :
                num( this, name.toLowerCase() )
                + num(this, 'border' + torl + 'Width') + num(this, 'border' + borr + 'Width')
                + num(this, 'padding' + torl) + num(this, 'padding' + borr);

            return val + (options.margin ? (num(this, 'margin' + torl) + num(this, 'margin' + borr)) : 0);
        };
    }
});

// Create scrollLeft and scrollTop methods
$.each( ['Left', 'Top'], function(i, name) {
    if (!$.fn[ 'scroll' + name ]) {
        $.fn[ 'scroll' + name ] = function(val) {
            if (!this[0]) return;

            return val != undefined ?

                // Set the scroll offset
                this.each(function() {
                    this == window || this == document ?
                        window.scrollTo(
                            name == 'Left' ? val : $(window)[ 'scrollLeft' ](),
                            name == 'Top'  ? val : $(window)[ 'scrollTop'  ]()
                        ) :
                        this[ 'scroll' + name ] = val;
                }) :

                // Return the scroll offset
                this[0] == window || this[0] == document ?
                    self[ (name == 'Left' ? 'pageXOffset' : 'pageYOffset') ] ||
                    $.boxModel && document.documentElement[ 'scroll' + name ] ||
                    document.body[ 'scroll' + name ] :
                    this[0][ 'scroll' + name ];
        };
    }
});

if (!$.fn.position) {
    $.fn.extend({
        position: function() {
            var left = 0, top = 0, elem = this[0], offset, parentOffset, offsetParent, results;

            if (elem) {
                // Get *real* offsetParent
                offsetParent = this.offsetParent();

                // Get correct offsets
                offset       = this.offset();
                parentOffset = offsetParent.offset();

                // Subtract element margins
                offset.top  -= num(elem, 'marginTop');
                offset.left -= num(elem, 'marginLeft');

                // Add offsetParent borders
                parentOffset.top  += num(offsetParent, 'borderTopWidth');
                parentOffset.left += num(offsetParent, 'borderLeftWidth');

                // Subtract the two offsets
                results = {
                    top:  offset.top  - parentOffset.top,
                    left: offset.left - parentOffset.left
                };
            }
            return results;
        }
    });
}

if (!$.fn.offsetParent) {
    $.fn.extend({
        offsetParent: function () {
            var offsetParent = this[0].offsetParent;
            while (offsetParent && (!/^body|html$/i.test(offsetParent.tagName) && $.css(offsetParent, 'position') == 'static'))
                offsetParent = offsetParent.offsetParent;
            return $(offsetParent);
        }
    });
}

if (!$.fn.curCSS) {
    $.fn.extend({
        curCss : function (element, prop, val) {
            return $(element).css(prop, val);
        }
    });
}

function num(el, prop) {
    return parseInt($.curCSS(el.jquery?el[0]:el,prop,true))||0;
};

})(jQuery);

/*
 * jQuery Easing v1.1.1 - http://gsgd.co.uk/sandbox/jquery.easing.php
 *
 * Uses the built in easing capabilities added in jQuery 1.1
 * to offer multiple easing options
 *
 * Copyright (c) 2007 George Smith
 * Licensed under the MIT License:
 *   http://www.opensource.org/licenses/mit-license.php
 */

jQuery.extend(jQuery.easing, {
    easein: function(x, t, b, c, d) {
    return c*(t/=d)*t + b; // in
    },
    easeinout: function(x, t, b, c, d) {
    if (t < d/2) return 2*c*t*t/(d*d) + b;
    var ts = t - d/2;
    return -2*c*ts*ts/(d*d) + 2*c*ts/d + c/2 + b;
    },
    easeout: function(x, t, b, c, d) {
    return -c*t*t/(d*d) + 2*c*t/d + b;
    },
    expoin: function(x, t, b, c, d) {
    var flip = 1;
    if (c < 0) {
    flip *= -1;
    c *= -1;
    }
    return flip * (Math.exp(Math.log(c)/d * t)) + b;
    },
    expoout: function(x, t, b, c, d) {
    var flip = 1;
    if (c < 0) {
    flip *= -1;
    c *= -1;
    }
    return flip * (-Math.exp(-Math.log(c)/d * (t-d)) + c + 1) + b;
    },
    expoinout: function(x, t, b, c, d) {
    var flip = 1;
    if (c < 0) {
    flip *= -1;
    c *= -1;
    }
    if (t < d/2) return flip * (Math.exp(Math.log(c/2)/(d/2) * t)) + b;
    return flip * (-Math.exp(-2*Math.log(c/2)/d * (t-d)) + c + 1) + b;
    },
    bouncein: function(x, t, b, c, d) {
    return c - jQuery.easing['bounceout'](x, d-t, 0, c, d) + b;
    },
    bounceout: function(x, t, b, c, d) {
    if ((t/=d) < (1/2.75)) {
    return c*(7.5625*t*t) + b;
    } else if (t < (2/2.75)) {
    return c*(7.5625*(t-=(1.5/2.75))*t + .75) + b;
    } else if (t < (2.5/2.75)) {
    return c*(7.5625*(t-=(2.25/2.75))*t + .9375) + b;
    } else {
    return c*(7.5625*(t-=(2.625/2.75))*t + .984375) + b;
    }
    },
    bounceinout: function(x, t, b, c, d) {
    if (t < d/2) return jQuery.easing['bouncein'] (x, t*2, 0, c, d) * .5 + b;
    return jQuery.easing['bounceout'] (x, t*2-d,0, c, d) * .5 + c*.5 + b;
    },
    elasin: function(x, t, b, c, d) {
    var s=1.70158;var p=0;var a=c;
    if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
    if (a < Math.abs(c)) { a=c; var s=p/4; }
    else var s = p/(2*Math.PI) * Math.asin (c/a);
    return -(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
    },
    elasout: function(x, t, b, c, d) {
    var s=1.70158;var p=0;var a=c;
    if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
    if (a < Math.abs(c)) { a=c; var s=p/4; }
    else var s = p/(2*Math.PI) * Math.asin (c/a);
    return a*Math.pow(2,-10*t) * Math.sin( (t*d-s)*(2*Math.PI)/p ) + c + b;
    },
    elasinout: function(x, t, b, c, d) {
    var s=1.70158;var p=0;var a=c;
    if (t==0) return b;  if ((t/=d/2)==2) return b+c;  if (!p) p=d*(.3*1.5);
    if (a < Math.abs(c)) { a=c; var s=p/4; }
    else var s = p/(2*Math.PI) * Math.asin (c/a);
    if (t < 1) return -.5*(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
    return a*Math.pow(2,-10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )*.5 + c + b;
    },
    backin: function(x, t, b, c, d) {
    var s=1.70158;
    return c*(t/=d)*t*((s+1)*t - s) + b;
    },
    backout: function(x, t, b, c, d) {
    var s=1.70158;
    return c*((t=t/d-1)*t*((s+1)*t + s) + 1) + b;
    },
    backinout: function(x, t, b, c, d) {
    var s=1.70158;
    if ((t/=d/2) < 1) return c/2*(t*t*(((s*=(1.525))+1)*t - s)) + b;
    return c/2*((t-=2)*t*(((s*=(1.525))+1)*t + s) + 2) + b;
    }
});
/*
 * Metadata - jQuery plugin for parsing metadata from elements
 *
 * Copyright (c) 2006 John Resig, Yehuda Katz, J�örn Zaefferer, Paul McLanahan
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id$
 *
 */

/**
 * Sets the type of metadata to use. Metadata is encoded in JSON, and each property
 * in the JSON will become a property of the element itself.
 *
 * There are three supported types of metadata storage:
 *
 *   attr:  Inside an attribute. The name parameter indicates *which* attribute.
 *
 *   class: Inside the class attribute, wrapped in curly braces: { }
 *
 *   elem:  Inside a child element (e.g. a script tag). The
 *          name parameter indicates *which* element.
 *
 * The metadata for an element is loaded the first time the element is accessed via jQuery.
 *
 * As a result, you can define the metadata type, use $(expr) to load the metadata into the elements
 * matched by expr, then redefine the metadata type and run another $(expr) for other elements.
 *
 * @name $.metadata.setType
 *
 * @example <p id="one" class="some_class {item_id: 1, item_label: 'Label'}">This is a p</p>
 * @before $.metadata.setType("class")
 * @after $("#one").metadata().item_id == 1; $("#one").metadata().item_label == "Label"
 * @desc Reads metadata from the class attribute
 *
 * @example <p id="one" class="some_class" data="{item_id: 1, item_label: 'Label'}">This is a p</p>
 * @before $.metadata.setType("attr", "data")
 * @after $("#one").metadata().item_id == 1; $("#one").metadata().item_label == "Label"
 * @desc Reads metadata from a "data" attribute
 *
 * @example <p id="one" class="some_class"><script>{item_id: 1, item_label: 'Label'}</script>This is a p</p>
 * @before $.metadata.setType("elem", "script")
 * @after $("#one").metadata().item_id == 1; $("#one").metadata().item_label == "Label"
 * @desc Reads metadata from a nested script element
 *
 * @param String type The encoding type
 * @param String name The name of the attribute to be used to get metadata (optional)
 * @cat Plugins/Metadata
 * @descr Sets the type of encoding to be used when loading metadata for the first time
 * @type undefined
 * @see metadata()
 */

(function($) {

$.extend({
    metadata : {
    defaults : {
    type: 'class',
    name: 'metadata',
    cre: /({.*})/,
    single: 'metadata'
    },
    setType: function( type, name ){
    this.defaults.type = type;
    this.defaults.name = name;
    },
    get: function( elem, opts ){
    var settings = $.extend({},this.defaults,opts);
    // check for empty string in single property
    if ( !settings.single.length ) settings.single = 'metadata';

    var data = $.data(elem, settings.single);
    // returned cached data if it already exists
    if ( data ) return data;

    data = "{}";

    if ( settings.type == "class" ) {
    var m = settings.cre.exec( elem.className );
    if ( m )
    data = m[1];
    } else if ( settings.type == "elem" ) {
    if( !elem.getElementsByTagName )
    return undefined;
    var e = elem.getElementsByTagName(settings.name);
    if ( e.length )
    data = $.trim(e[0].innerHTML);
    } else if ( elem.getAttribute != undefined ) {
    var attr = elem.getAttribute( settings.name );
    if ( attr )
    data = attr;
    }

    if ( data.indexOf( '{' ) <0 )
    data = "{" + data + "}";

    data = eval("(" + data + ")");

    $.data( elem, settings.single, data );
    return data;
    }
    }
});

/**
 * Returns the metadata object for the first member of the jQuery object.
 *
 * @name metadata
 * @descr Returns element's metadata object
 * @param Object opts An object contianing settings to override the defaults
 * @type jQuery
 * @cat Plugins/Metadata
 */
$.fn.metadata = function( opts ){
    return $.metadata.get( this[0], opts );
};

})(jQuery);

/**
 * popup open
 * 팝업의 형태는 layer popup 과 window popup 두형태가 존재한다.
 */
var aPopupList = [];
var aPopupCouponList;

EC$(function() {
    POPUP.init();
});

// ECHOSTING-91093 EP 캐시문제로 기존에 PHP 에서 처리하던 부분을 ajax를 호출하여 처리하도록 합니다.
var POPUP = {

    init: function ()
    {
        //어드민 팝업등록 페이지접근시 리턴
        if (typeof aPopupListData === 'undefined') {
            return;
        }

        try {
            aPopupList = (typeof JSON === 'object' && JSON.parse) ? JSON.parse(aPopupListData.aPopupList): eval("("+aPopupListData.aPopupList+")");
            aPopupCouponList = (typeof JSON === 'object' && JSON.parse) ? JSON.parse(aPopupListData.aPopupCouponList): eval("("+aPopupListData.aPopupCouponList+")");
        } catch (e) {}

        //팝업페이지에서 호출일시 리턴
        if (typeof aPopupListData.sPopupPage !== 'undefined' && aPopupListData.sPopupPage =='T') {
            return;
        }

        // 팝업 (웹페이지, 상품분류, 상품별) 출력
        POPUP.setPopup();

        // 로그인 여부
        var bIsLogin = (document.cookie.match(/(?:^| |;)iscache=F/) ? true : false);

        //팝업정보 (쿠폰, 본인인증 유도) 설정시
        if (bIsLogin === true) {
            if (aPopupCouponList || aPopupListData.sIsAuthGuidePopup == 'T' || aPopupListData.sIsUpdateEventGuidePopup || aPopupListData.sIsLifetimeEventGuidePopup) {
                // 팝업에서 ajax 로 세션을 동시접근 방지
                setTimeout(function () {
                    POPUP.setPopupList();
                }, 1000);
            }
        }
    },
    setPopupList: function ()
    {
        var sIsCouponPopup;
        (aPopupCouponList != '') ? sIsCouponPopup = 'T' : sIsCouponPopup = 'F';
        (aPopupListData.sIsUpdateEventGuidePopup != '') ? sIsUpdateEventGuidePopup = 'T' : sIsUpdateEventGuidePopup = 'F';
        (aPopupListData.sIsLifetimeEventGuidePopup != '') ? sIsLifetimeEventGuidePopup = 'T' : sIsLifetimeEventGuidePopup = 'F';

        EC$.ajax({
            url: '/exec/front/popup/AjaxMain',
            type: "post",
            data: {'coupon': sIsCouponPopup, 'authGuide': aPopupListData.sIsAuthGuidePopup, 'updateEventGuide': sIsUpdateEventGuidePopup, 'lifetimeEventGuide': sIsLifetimeEventGuidePopup},
            dataType: "json",
            success: function (oResult) {

                if (oResult.coupon == '0000') {
                    aPopupList = aPopupCouponList;
                    POPUP.setPopup();
                }

                if (oResult.authGuide == '0000') {
                    POPUP_AUTH_GUIDE.openPopup();
                }
                
                if (oResult.updateEventGuide == '0000') {
                    POPUP_UPDATE_EVENT_GUIDE.openPopup();
                }

                if (oResult.lifetimeEventGuide == '0000') {
                    POPUP_LIFETIME_EVENT_GUIDE.openPopup();
                }
            },
            error: function () {
            }
        });
    },
    //팝업출력
    setPopup: function ()
    {
        if (!aPopupList) {
            return;
        }

        if (EC$.cookie('SDE_POPUP')) {
            var aPopupCookie = EC$.cookie('SDE_POPUP').split('&');
        }

        // 팝업리스트를 호출하며
        // 시간이 만료시간 전이며, SDE_POPUP에 쿠키값이 없는지 검사
        for (var i=0; i<aPopupList.length; i++) {
            if (aPopupList[i].open) {
                if (this.bOpenPopup(aPopupList[i].idx, aPopupCookie)) {
                    open_popup(aPopupList[i]);
                }
            }
        }
    },
    //회원이 그만본다고 정의한 idx를 비교
    bOpenPopup: function(iIdx, aPopupCookie)
    {
        if (!aPopupCookie) return true;

        var aCookie = [];

        for (var i=0; i<aPopupCookie.length; i++) {
            aCookie = aPopupCookie[i].split('=');
            if (aCookie[0] == iIdx) {
                // [솔업2] - 2013.11.28
                // SUB-6539 오늘 하루 열지 않음 만료시간과 현재시간을 체크 하는 로직 추가
                var oCookieTime = new Date(parseInt(aCookie[1]) * 1000);
                
                var sCookieTime = new String(oCookieTime.getFullYear());
                sCookieTime += (oCookieTime.getMonth() < 10) ? '0' + new String(oCookieTime.getMonth()) : new String(oCookieTime.getMonth());
                sCookieTime += (oCookieTime.getDate() < 10) ? '0' + new String(oCookieTime.getDate()) : new String(oCookieTime.getDate());
                sCookieTime += (oCookieTime.getHours() < 10) ? '0' + new String(oCookieTime.getHours()) : new String(oCookieTime.getHours());
                sCookieTime += (oCookieTime.getMinutes() < 10) ? '0' + new String(oCookieTime.getMinutes()) : new String(oCookieTime.getMinutes());
                sCookieTime += (oCookieTime.getSeconds() < 10) ? '0' + new String(oCookieTime.getSeconds()) : new String(oCookieTime.getSeconds());
                
                var oCurrentTime = new Date();
                
                var sCurrentTime = new String(oCurrentTime.getFullYear());
                sCurrentTime += (oCurrentTime.getMonth() < 10) ? '0' + new String(oCurrentTime.getMonth()) : new String(oCurrentTime.getMonth());
                sCurrentTime += (oCurrentTime.getDate() < 10) ? '0' + new String(oCurrentTime.getDate()) : new String(oCurrentTime.getDate());
                sCurrentTime += (oCurrentTime.getHours() < 10) ? '0' + new String(oCurrentTime.getHours()) : new String(oCurrentTime.getHours());
                sCurrentTime += (oCurrentTime.getMinutes() < 10) ? '0' + new String(oCurrentTime.getMinutes()) : new String(oCurrentTime.getMinutes());
                sCurrentTime += (oCurrentTime.getSeconds() < 10) ? '0' + new String(oCurrentTime.getSeconds()) : new String(oCurrentTime.getSeconds());
                
                if (parseInt(sCookieTime) < parseInt(sCurrentTime)) {
                    return true;
                }
                
                return false;
            }

        }
        return true;
    }
};


var open_popup = function(aData) {


    var aSize = aData.size.split('*');
    var aPos = aData.position.split('*');
    var ds = aData.file.indexOf('?') == -1 ? '?' : '&';
    var sUri = aData.file+ds+'idx='+aData.idx+'&type='+aData.type+'&__popupPage=T';
    var sChildType = aData.child_type;
        
    /**
     * layer popup open
     */
    this.layer_popup = function() {
        var oElement = document.createElement('div');

        oElement.id = 'popup_'+aData.idx;
        oElement.style.position = 'absolute';
        oElement.style.top = aPos[0]+'px';
        oElement.style.left = aPos[1]+'px';
        oElement.style.zIndex = '9999';

        //ECHOSTING-39168 [긴급][스타일맨]IE8 개별팝업 이슈확인요청
        oElement.style.width = aSize[0]+'px';

        oElement.innerHTML = '<iframe src="'+EC_ROUTE.getPrefixUrl(sUri)+'" scrolling="no" width="'+aSize[0]+'" height="'+aSize[1]+'" frameborder="0"  allowTransparency="true"></iframe>';
        document.body.appendChild(oElement);
       
        // 레이어 팝업 드래그
        EC$('#'+oElement.id+' iframe').on('load', function() {
            var iframeBody = EC$(this).contents().find('body');
            iframeBody.css({'margin': 0});
            
            if (navigator.userAgent.indexOf('MSIE') > 0) {
                iframeBody.on('contextmenu', function() { return false; });
                iframeBody.on('selectstart', function() { return false; });
                iframeBody.on('dragstart', function() { return false; });
            }
            
            // ECHOSTING-91562 샘플 팝업인 경우에만 레이어팝업 리사이징
            if (sChildType == 'W') {
                // ECHOSTING-114699 팝업 리사이징 오류 관련 수정 로직 추가 - 2014.11.04
                var bIsExistsGoogleAd = (iframeBody.find('iframe[name="google_conversion_frame"]').length > 0) ? true : false;
                
                if (bIsExistsGoogleAd == true) {
                    iframeBody.find('iframe[name="google_conversion_frame"]').attr('width', '13px');
                }

                var iAdjustSizeX = this.contentWindow.document.body.scrollWidth + 'px';
                var iAdjustSizeY = this.contentWindow.document.body.scrollHeight + 'px';
                
                this.style.width = iAdjustSizeX;
                this.style.height = iAdjustSizeY;
                
                iframeBody.find('.xans-popup-footer > div').css('width', (parseInt(iAdjustSizeX) - 10) + 'px');
            }

            iframeBody.mousedown(function(e) {
                var orgX = e.clientX;
                var orgY = e.clientY;

                iframeBody.mousemove(function(e) {
                    oElement.style.left = (parseInt(oElement.style.left) + e.clientX - orgX) + "px";
                    oElement.style.top = (parseInt(oElement.style.top) + e.clientY - orgY) + "px";
                });

                iframeBody.mouseup(function(e) {
                    iframeBody.off('mousemove');
                });

                iframeBody.mouseleave(function(e) {
                    iframeBody.off('mousemove');
                });
            });
        }); // end of 레이어 팝업 드래그
    };

    /**
     * window popup open
     */
    this.win_popup = function() {
        try {
            //ECHOSTING-385143
            var popup = window.open(EC_ROUTE.getPrefixUrl(sUri), 'popup_' + aData.idx, 'width=' + aSize[0] + ', height=' + aSize[1] + ', top=' + aPos[0] + ', left=' + aPos[1] + ', toolbar=0, menubar=0, scrollbars=yes');
            popup.focus();
        } catch (e) {

        }
    };

    var aFunction = {
        'W': 'win_popup',
        'L': 'layer_popup'
    };

    this[aFunction[aData.type]]();
};


/**
 * 본인인증안내 레이어 팝업
 */
var POPUP_AUTH_GUIDE = {
    openPopup: function ()
    {
        if (POPUP_AUTH_GUIDE.getCookie('CERTIFICATION_LAYER_NOT_TODAY') === 'T') {
            return;
        } else {
            var bBuyLayer = false;
            var agent = navigator.userAgent.toLowerCase();
            if (agent.indexOf('iphone') != -1 || agent.indexOf('android') != -1) {
                try {
                    if (parent.EC$('#opt_layer_window').length > 0 && typeof(window.parent) === 'object') {
                        parent.EC$('html, body').css('overflowY', 'auto');
                        parent.EC$('#opt_layer_window').hide();
                        bBuyLayer = true;
                    }
                } catch (e) {}
            }
    
            EC$.get('/member/certification_layer.html','',function(sHtml)
            {
                if (bBuyLayer == true) {
                    if (parent.EC$('#authInfoLayer').length <= 0) {
                        parent.EC$('body').append(EC$('<div id=\"authInfoLayer\"></div>'));
                        parent.EC$('#authInfoLayer').html(sHtml);
                        parent.EC$('#authInfoLayer').show();
                    }
                } else {
                    if (EC$('#authInfoLayer').length <= 0) {
                        EC$('<div id=\"authInfoLayer\"></div>').appendTo('body');
                        EC$('#authInfoLayer').html(sHtml);
                        EC$('#authInfoLayer').show();
                    }
                }
            });
        }
    },
    getCookie: function(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i=0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }
};

/**
 * 회원정보 수정이벤트 팝업
 */
var POPUP_UPDATE_EVENT_GUIDE = {
    openPopup: function ()
    {
        if (POPUP_UPDATE_EVENT_GUIDE.getCookie('UPDATEEVENT_LAYER_NOT_TODAY') === 'T') {
            return;
        } else {
            var bBuyLayer = false;
            var agent = navigator.userAgent.toLowerCase();
            if (agent.indexOf('iphone') != -1 || agent.indexOf('android') != -1) {
                try {
                    if (parent.EC$('#opt_layer_window').length > 0 && typeof(window.parent) === 'object') {
                        parent.EC$('html, body').css('overflowY', 'auto');
                        parent.EC$('#opt_layer_window').hide();
                        bBuyLayer = true;
                    }
                } catch (e) {}
            }

            EC$.get('/member/update_event.html?__popupPage=T','',function(sHtml)
            {
                if (bBuyLayer == true) {
                    if (parent.EC$('#updateEventLayer').length <= 0) {
                        parent.EC$('body').append(EC$('<div id=\"updateEventLayer\"></div>'));
                        parent.EC$('#updateEventLayer').html(sHtml);
                        parent.EC$('#updateEventLayer').show();
                    }
                } else {
                    if (EC$('#updateEventLayer').length <= 0) {
                        EC$('<div id=\"updateEventLayer\"></div>').appendTo('body');
                        EC$('#updateEventLayer').html(sHtml);
                        EC$('#updateEventLayer').show();
                    }
                }
            });
        }
    },
    getCookie: function(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i=0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }
};

/**
 * 평생회원 이벤트 팝업
 */
var POPUP_LIFETIME_EVENT_GUIDE = {
    openPopup: function ()
    {
        if (POPUP_LIFETIME_EVENT_GUIDE.getCookie('LIFETIMEEVENT_LAYER_NEVER_OPEN') === 'T') {
            return;
        } else {
            var bBuyLayer = false;
            var agent = navigator.userAgent.toLowerCase();
            if (agent.indexOf('iphone') != -1 || agent.indexOf('android') != -1) {
                try {
                    if (parent.EC$('#opt_layer_window').length > 0 && typeof(window.parent) === 'object') {
                        parent.EC$('html, body').css('overflowY', 'auto');
                        parent.EC$('#opt_layer_window').hide();
                        bBuyLayer = true;
                    }
                } catch (e) {}
            }

            EC$.get('/member/lifetime_event.html','',function(sHtml)
            {
                if (bBuyLayer == true) {
                    if (parent.EC$('#lifetimeEventLayer').length <= 0) {
                        parent.EC$('body').append(EC$('<div id=\"lifetimeEventLayer\"></div>'));
                        parent.EC$('#lifetimeEventLayer').html(sHtml);
                        parent.EC$('#lifetimeEventLayer').show();
                    }
                } else {
                    if (EC$('#lifetimeEventLayer').length <= 0) {
                        EC$('<div id=\"lifetimeEventLayer\"></div>').appendTo('body');
                        EC$('#lifetimeEventLayer').html(sHtml);
                        EC$('#lifetimeEventLayer').show();
                    }
                }
            });
        }
    },
    getCookie: function(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i=0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }
};

/**
 * 접속통계 & 실시간접속통계
 */
EC$(function() {
    // 이미 weblog.js 실행 되었을 경우 종료 
    if (EC$('#log_realtime').length > 0) {
        return;
    }
    /*
     * QueryString에서 디버그 표시 제거
     */
    function stripDebug(sLocation)
    {
        if (typeof sLocation !== 'string') return '';

        sLocation = sLocation.replace(/^d[=]*[\d]*[&]*$/, '');
        sLocation = sLocation.replace(/^d[=]*[\d]*[&]/, '');
        sLocation = sLocation.replace(/(&d&|&d[=]*[\d]*[&]*)/, '&');

        return sLocation;
    }

    // 벤트 몰이 아닐 경우에만 V3(IFrame)을 로드합니다.
    // @date 190117
    // @date 191217 - 이벤트에도 V3 상시 적재로 변경.
    //if (EC_FRONT_JS_CONFIG_MANAGE.sWebLogEventFlag == "F")
    //{
    // T 일 경우 IFRAME 을 노출하지 않는다.
    if (EC_FRONT_JS_CONFIG_MANAGE.sWebLogOffFlag == "F")
    {
        var frame_print = null;
        if (window.self == window.top) {
            var rloc = escape(document.location);
            var rref = escape(document.referrer);
            var frame_print = 1;
        } else if (aLogData.hash != '') {
            var rloc = (document.location).pathname;
            var rref = '';
            var frame_print = 1;
        }

        // 광고 랜딩에서 iframe 일 경우 window.top 을 제외하고 노출하지 않는다.
        if (frame_print != null) {
            // realconn & Ad aggregation
            var _aPrs = new Array();
            _sUserQs = window.location.search.substring(1);
            _sUserQs = stripDebug(_sUserQs);
            _aPrs[0] = 'rloc=' + rloc;
            _aPrs[1] = 'rref=' + rref;
            _aPrs[2] = 'udim=' + window.screen.width + '*' + window.screen.height;
            _aPrs[3] = 'rserv=' + aLogData.log_server2;
            _aPrs[4] = 'cid=' + eclog.getCid();
            _aPrs[5] = 'role_path=' + EC$('meta[name="path_role"]').attr('content');
            _aPrs[6] = 'stype=' + aLogData.stype;
            _aPrs[7] = 'shop_no=' + aLogData.shop_no;
            _aPrs[8] = 'lang=' + aLogData.lang;
            _aPrs[9] = 'ver=' + aLogData.ver;

            // 모바일웹일 경우 추가 파라미터 생성
            var _sMobilePrs = '';
            // V3 mobile flag (skincode)
            if (mobileWeb === true) _sMobilePrs = '&mobile=T&mobile_ver=new';
            // 실시간접속자 mobile_flag (page, device)
            if (aLogData.mobile_flag === 'T') _sMobilePrs = '&mob_flag=T';
            // cid.generate.js 연계 (ca_external_id, ca_event_id)
            if (!document.cookie.includes('fb_external_id')) {
                if (sessionStorage.getItem('fb_external_id')) {
                    _sMobilePrs += '&ca_external_id=' + sessionStorage.getItem('fb_external_id');
                }
            }
            if (!document.cookie.includes('fb_event_id')) {
                if (sessionStorage.getItem('fb_event_id')) {
                    _sMobilePrs += '&ca_event_id=' + sessionStorage.getItem('fb_event_id');
                }
            }
            if (!document.cookie.includes('CFAE_CID')) {
                if (sessionStorage.getItem('CFAE_CID_' + aLogData.mid + '_' + aLogData.shop_no)) {
                    _sMobilePrs += '&ca_cid=' + sessionStorage.getItem('CFAE_CID_' + aLogData.mid + '_' + aLogData.shop_no);
                }
            }
            if (!document.cookie.includes('CFAE_LC')) {
                if (sessionStorage.getItem('LC_' + aLogData.mid + '_' + aLogData.shop_no)) {
                    _sMobilePrs += '&ca_lc=' + sessionStorage.getItem('LC_' + aLogData.mid + '_' + aLogData.shop_no);
                }
            }
            if (!document.cookie.includes('CFAE_CUK1Y')) {
                if (sessionStorage.getItem('CFAE_CUK1Y_' + aLogData.mid + '_' + aLogData.shop_no)) {
                    _sMobilePrs += '&ca_cuk1y=' + sessionStorage.getItem('CFAE_CUK1Y_' + aLogData.mid + '_' + aLogData.shop_no);
                }
            }
            _sUrlQs = _sUserQs + '&' + _aPrs.join('&') + _sMobilePrs;

            var _sUrlFull = '/exec/front/eclog/main/?' + _sUrlQs;

            var node = document.createElement('iframe');
            node.setAttribute('src', _sUrlFull);
            node.setAttribute('id', 'log_realtime');
            document.body.appendChild(node);

            EC$('#log_realtime').hide();
        }
    }

    // eclog2.0, eclog1.9
    var sTime = new Date().getTime();//ECHOSTING-54575

    // 접속통계 서버값이 있다면 weblog.js 호출
    // ECHOSTING-427891 전송 하지 않도록 제거
//    if (aLogData.log_server1 != null && aLogData.log_server1 != '') {
//        var sScriptSrc = '//' + aLogData.log_server1 + '/weblog.js?uid=' + aLogData.mid + '&uname=' + aLogData.mid + '&r_ref=' + document.referrer + '&shop_no=' + aLogData.shop_no;
//        if (mobileWeb === true) sScriptSrc += '&cafe_ec=mobile';
//        sScriptSrc += '&t=' + sTime;//ECHOSTING-54575
//        var node = document.createElement('script');
//        node.setAttribute('type', 'text/javascript');
//        node.setAttribute('src', sScriptSrc);
//        node.setAttribute('id', 'log_script');
//        document.body.appendChild(node);
//    }

    // CA (Cafe24 Analytics
    if (aLogData.ca != null) {
        (function (i, s, o, g, r, a, m, n, d) {
            i['cfaObject'] = g;
            i['cfaUid'] = r;
            i['cfaStype'] = a;
            i['cfaDomain'] = m;
            i['cfaSno'] = n;
            i['cfaEtc'] = d;
            a = s.createElement(o), m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            a.setAttribute('crossorigin', 'anonymous');
            m.parentNode.insertBefore(a, m);
        })(window, document, 'script', '//' + aLogData.ca +'?v=' + sTime, aLogData.mid, aLogData.stype, aLogData.domain, aLogData.shop_no, aLogData.etc);
    }
});

/**
 * 쇼핑몰 금액 라이브러리
 */
CAFE24.SHOP_PRICE = {

    /**
     * iShopNo 쇼핑몰의 결제화폐에 맞게 리턴합니다.
     * @param float fPrice 금액
     * @param bool bIsNumberFormat number_format 적용 유무
     * @param int iShopNo 쇼핑몰번호
     * @return float|string
     */
    toShopPrice: function(fPrice, bIsNumberFormat, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        // 결제화폐 정보
        if (typeof CAFE24.SHOP_CURRENCY_INFO[undefined] == 'object') {
            var aCurrencyInfo = CAFE24.SHOP_CURRENCY_INFO[undefined].aShopCurrencyInfo;
        } else {
            var aCurrencyInfo = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo;
        }

        return CAFE24.SHOP_PRICE.toPrice(fPrice, aCurrencyInfo, bIsNumberFormat);
    },

    /**
     * iShopNo 쇼핑몰의 참조화폐에 맞게 리턴합니다.
     * @param float fPrice 금액
     * @param bool bIsNumberFormat number_format 적용 유무
     * @param int iShopNo 쇼핑몰번호
     * @return float|string
     */
    toShopSubPrice: function(fPrice, bIsNumberFormat, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        // 참조화폐 정보
        if (typeof CAFE24.SHOP_CURRENCY_INFO[undefined] == 'object') {
            var aSubCurrencyInfo = CAFE24.SHOP_CURRENCY_INFO[undefined].aShopSubCurrencyInfo;
        } else {
            var aSubCurrencyInfo = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopSubCurrencyInfo;
        }

        if (! aSubCurrencyInfo) {
            // 참조화폐가 없으면
            return '';

        } else {
            // 결제화폐 정보
            if (typeof CAFE24.SHOP_CURRENCY_INFO[undefined] == 'object') {
                var aCurrencyInfo = CAFE24.SHOP_CURRENCY_INFO[undefined].aShopCurrencyInfo;
            } else {
                var aCurrencyInfo = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo;
            }

            if (aSubCurrencyInfo.currency_code === aCurrencyInfo.currency_code) {
                // 결제화폐와 참조화폐가 동일하면
                return '';
            } else {
                return CAFE24.SHOP_PRICE.toPrice(fPrice, aSubCurrencyInfo, bIsNumberFormat);
            }
        }
    },

    /**
     * 쇼핑몰의 기준화폐에 맞게 리턴합니다.
     * @param float fPrice 금액
     * @param bool bIsNumberFormat number_format 적용 유무
     * @param int iShopNo 쇼핑몰번호
     * @return float
     */
    toBasePrice: function(fPrice, bIsNumberFormat, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        // 기준화폐 정보
        if (typeof CAFE24.SHOP_CURRENCY_INFO[undefined] == 'object') {
            var aBaseCurrencyInfo = CAFE24.SHOP_CURRENCY_INFO[undefined].aBaseCurrencyInfo;
        } else {
            var aBaseCurrencyInfo = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aBaseCurrencyInfo;
        }

        return CAFE24.SHOP_PRICE.toPrice(fPrice, aBaseCurrencyInfo, bIsNumberFormat);
    },

    /**
     * 결제화폐 금액을 참조화폐 금액으로 변환하여 리턴합니다.
     * @param float fPrice 금액
     * @param bool bIsNumberFormat number_format 적용 유무
     * @param int iShopNo 쇼핑몰번호
     * @return float 참조화폐 금액
     */
    shopPriceToSubPrice: function(fPrice, bIsNumberFormat, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        // 결제화폐 금액 => 참조화폐 금액
        if (typeof CAFE24.SHOP_CURRENCY_INFO[undefined] == 'object') {
            fPrice = fPrice * (CAFE24.SHOP_CURRENCY_INFO[undefined].fExchangeSubRate || 0);
        } else {
            fPrice = fPrice * (CAFE24.SHOP_CURRENCY_INFO[iShopNo].fExchangeSubRate || 0);
        }

        return CAFE24.SHOP_PRICE.toShopSubPrice(fPrice, bIsNumberFormat, iShopNo);
    },

    /**
     * 결제화폐 대비 기준화폐 환율 리턴
     * @param int iShopNo 쇼핑몰번호
     * @return float 결제화폐 대비 기준화폐 환율
     */
    getRate: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        if (typeof CAFE24.SHOP_CURRENCY_INFO[undefined] == 'object') {
            return CAFE24.SHOP_CURRENCY_INFO[undefined].fExchangeRate;
        } else {
            return CAFE24.SHOP_CURRENCY_INFO[iShopNo].fExchangeRate;
        }
    },

    /**
     * 결제화폐 대비 참조화폐 환율 리턴
     * @param int iShopNo 쇼핑몰번호
     * @return float 결제화폐 대비 참조화폐 환율 (참조화폐가 없는 경우 null을 리턴합니다.)
     */
    getSubRate: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        if (typeof CAFE24.SHOP_CURRENCY_INFO[undefined] == 'object') {
            return CAFE24.SHOP_CURRENCY_INFO[undefined].fExchangeSubRate;
        } else {
            return CAFE24.SHOP_CURRENCY_INFO[iShopNo].fExchangeSubRate;
        }
    },

    /**
     * 금액을 원하는 화폐코드의 제약조건(소수점 절삭)에 맞춰 리턴합니다.
     * @param float fPrice 금액
     * @param string aCurrencyInfo 원하는 화폐의 화폐 정보
     * @param bool bIsNumberFormat number_format 적용 유무
     * @return float|string
     */
    toPrice: function(fPrice, aCurrencyInfo, bIsNumberFormat)
    {
        // 소수점 아래 절삭
        var iPow = Math.pow(10, aCurrencyInfo['decimal_place']);
        fPrice = fPrice * iPow;
        if (aCurrencyInfo['round_method_type'] === 'F') {
            fPrice = Math.floor(fPrice);
        } else if (aCurrencyInfo['round_method_type'] === 'C') {
            fPrice = Math.ceil(fPrice);
        } else {
            fPrice = Math.round(fPrice);
        }
        fPrice = fPrice / iPow;

        if (! fPrice) {
            // 가격이 없는 경우
            return 0;

        } else if (bIsNumberFormat === true) {
            // 3자리씩 ,로 끊어서 리턴
            var sPrice = fPrice.toFixed(aCurrencyInfo['decimal_place']);
            var regexp = /^(-?[0-9]+)([0-9]{3})($|\.|,)/;
            while (regexp.test(sPrice)) {
                sPrice = sPrice.replace(regexp, "$1,$2$3");
            }
            return sPrice;

        } else {
            // 숫자만 리턴
            return fPrice;

        }
    }    
};

var SHOP_PRICE = CAFE24.getDeprecatedNamespace('SHOP_PRICE');
/**
 * 화폐 포맷
 */
CAFE24.SHOP_CURRENCY_FORMAT = {
    /**
     * 어드민 페이지인지
     * @var bool
     */
    _bIsAdmin: /^\/(admin\/php|disp\/admin|exec\/admin)\//.test(location.pathname) ? true : false,

    /**
     * iShopNo 쇼핑몰의 결제화폐 포맷을 리턴합니다.
     * @param int iShopNo 쇼핑몰번호
     * @return array head,tail
     */
    getShopCurrencyFormat: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        // 결제화폐 코드
        var sCurrencyCode = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo.currency_code;

        if (CAFE24.SHOP_CURRENCY_FORMAT._bIsAdmin === true) {
            // 어드민

            // 기준화폐 코드
            var sBaseCurrencyCode = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aBaseCurrencyInfo.currency_code;

            if (sCurrencyCode === sBaseCurrencyCode) {
                // 결제화폐와 기준화폐가 동일한 경우
                return {
                    'head': '',
                    'tail': ''
                };

            } else {
                return {
                    'head': sCurrencyCode + ' ',
                    'tail': ''
                };
            }

        } else {
            // 프론트
            return CAFE24.SHOP_CURRENCY_INFO[iShopNo].aFrontCurrencyFormat;
        }
    },

    /**
     * iShopNo 쇼핑몰의 참조화폐의 포맷을 리턴합니다.
     * @param int iShopNo 쇼핑몰번호
     * @return array head,tail
     */
    getShopSubCurrencyFormat: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        // 참조화폐 정보
        var aSubCurrencyInfo = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopSubCurrencyInfo;

        if (! aSubCurrencyInfo) {
            // 참조화폐가 없으면
            return {
                'head': '',
                'tail': ''
            };

        } else if (CAFE24.SHOP_CURRENCY_FORMAT._bIsAdmin === true) {
            // 어드민
            return {
                'head': '(' + aSubCurrencyInfo.currency_code + ' ',
                'tail': ')'
            };

        } else {
            // 프론트
            return CAFE24.SHOP_CURRENCY_INFO[iShopNo].aFrontSubCurrencyFormat;
        }

    },

    /**
     * 쇼핑몰의 기준화폐의 포맷을 리턴합니다.
     * @param int iShopNo 쇼핑몰번호
     * @return array head,tail
     */
    getBaseCurrencyFormat: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        // 기준화폐 코드
        var sBaseCurrencyCode = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aBaseCurrencyInfo.currency_code;

        // 결제화폐 코드
        var sCurrencyCode = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo.currency_code;

        if (sCurrencyCode === sBaseCurrencyCode) {
            // 기준화폐와 결제화폐가 동일하면
            return {
                'head': '',
                'tail': ''
            };

        } else {
            // 어드민
            return {
                'head': '(' + sBaseCurrencyCode + ' ',
                'tail': ')'
            };

        }
    },

    /**
     * 금액 입력란 화폐 포맷용 head,tail
     * @param int iShopNo 쇼핑몰번호
     * @return array head,tail
     */
    getInputFormat: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        var sCurrencyCode = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo;

        // 멀티쇼핑몰이 아니고 단위가 '원화'인 경우
        if (SHOP.isMultiShop() === false && sCurrencyCode === 'KRW') {
            return {
                'head': '',
                'tail': '원'
            };

        } else {
            return {
                'head': '',
                'tail': sCurrencyCode
            };
        }
    },

    /**
     * 해당몰 결제 화폐 코드 반환
     * ECHOSTING-266141 대응
     * 국문 기본몰 일 경우에는 화폐코드가 아닌 '원' 으로 반환
     *
     * @param int iShopNo 쇼핑몰번호
     * @return string currency_code
     */
    getCurrencyCode: function(iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        var sCurrencyCode = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo.currency_code;

        // 멀티쇼핑몰이 아니고 단위가 '원화'인 경우
        if (SHOP.isMultiShop() === false && sCurrencyCode === 'KRW') {
            return '원';
        } else {
            return sCurrencyCode;
        }
    }

};

var SHOP_CURRENCY_FORMAT = CAFE24.getDeprecatedNamespace('SHOP_CURRENCY_FORMAT');

/**
 * 금액 포맷
 */
CAFE24.SHOP_PRICE_FORMAT = {
    /**
     * iShopNo 쇼핑몰의 결제화폐에 맞도록 하고 포맷팅하여 리턴합니다.
     * @param float fPrice 금액
     * @param int iShopNo 쇼핑몰번호
     * @return string
     */
    toShopPrice: function(fPrice, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        var aFormat = CAFE24.SHOP_CURRENCY_FORMAT.getShopCurrencyFormat(iShopNo);
        var sPrice = CAFE24.SHOP_PRICE.toShopPrice(fPrice, true, iShopNo);
        return aFormat.head + sPrice + aFormat.tail;
    },

    /**
     * iShopNo 쇼핑몰의 참조화폐에 맞도록 하고 포맷팅하여 리턴합니다.
     * @param float fPrice 금액
     * @param int iShopNo 쇼핑몰번호
     * @return string
     */
    toShopSubPrice: function(fPrice, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        var aFormat = CAFE24.SHOP_CURRENCY_FORMAT.getShopSubCurrencyFormat(iShopNo);
        var sPrice = CAFE24.SHOP_PRICE.toShopSubPrice(fPrice, true, iShopNo);
        return aFormat.head + sPrice + aFormat.tail;
    },

    /**
     * 쇼핑몰의 기준화폐에 맞도록 하고 포맷팅하여 리턴합니다.
     * @param float fPrice 금액
     * @param int iShopNo 쇼핑몰번호
     * @return string
     */
    toBasePrice: function(fPrice, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        var aFormat = CAFE24.SHOP_CURRENCY_FORMAT.getBaseCurrencyFormat(iShopNo);
        var sPrice = CAFE24.SHOP_PRICE.toBasePrice(fPrice, true, iShopNo);
        return aFormat.head + sPrice + aFormat.tail;
    },

    /**
     * 결제화폐 금액을 참조화폐 금액으로 변환하고 포맷팅하여 리턴합니다.
     * @param float fPrice 금액
     * @param int iShopNo 쇼핑몰번호
     * @return string
     */
    shopPriceToSubPrice: function(fPrice, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;


        var aFormat = CAFE24.SHOP_CURRENCY_FORMAT.getShopSubCurrencyFormat(iShopNo);
        var sPrice = CAFE24.SHOP_PRICE.shopPriceToSubPrice(fPrice, true, iShopNo);

        if (CAFE24.CURRENCY_INFO.isUseReferenceCurrency() !== true) { return sPrice; }

        return '<span class="eRefPriceUnitHead">' + aFormat.head + '</span><span class="eRefPrice">' + sPrice + '</span><span class="eRefPriceUnitTail">'  + aFormat.tail + '</span>';
    },


    /**
     * 금액을 적립금 단위 명칭 설정에 따라 반환
     * @param float fPrice 금액
     * @return float|string
     */
    toShopMileagePrice: function (fPrice, iShopNo) {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;
        
        var sPrice = CAFE24.SHOP_PRICE.toShopPrice(fPrice, true, iShopNo);
        if (typeof sMileageUnit !== 'undefined' && CAFE24.UTIL.trim(sMileageUnit) != '') {
            sConvertMileageUnit = sMileageUnit.replace('[:PRICE:]', sPrice);
            return sConvertMileageUnit;
        } else {
            return CAFE24.SHOP_PRICE_FORMAT.toShopPrice(fPrice);
        }
    },

    /**
     * 금액을 예치금 단위 명칭 설정에 따라 반환
     * @param float fPrice 금액
     * @return float|string
     */
    toShopDepositPrice: function (fPrice, iShopNo) {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;
        
        var sPrice = CAFE24.SHOP_PRICE.toShopPrice(fPrice, true, iShopNo);
        if (typeof sDepositUnit !== 'undefined' || CAFE24.UTIL.trim(sDepositUnit) != '') {
            return sPrice + sDepositUnit;
        } else {
            return CAFE24.SHOP_PRICE_FORMAT.toShopPrice(fPrice);
        }
    },

    /**
     * 금액을 부가결제수단(통합포인트) 단위 명칭 설정에 따라 반환
     * @param float fPrice 금액
     * @return float|string
     */
    toShopAddpaymentPrice: function (fPrice, sAddpaymentUnit, iShopNo) {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        var sPrice = CAFE24.SHOP_PRICE.toShopPrice(fPrice, true, iShopNo);
        if (typeof sDepositUnit !== 'undefined' || CAFE24.UTIL.trim(sDepositUnit) != '') {
            return sPrice + sAddpaymentUnit;
        } else {
            return CAFE24.SHOP_PRICE_FORMAT.toShopPrice(fPrice);
        }
    },

    /**
     * 포맷을 제외한 금액정보만 리턴합니다.
     * @param {string} sFormattedPrice
     * @returns {string}
     */
    detachFormat: function(sFormattedPrice) {
        if (typeof sFormattedPrice === 'undefined' || sFormattedPrice === null) {
            return '0';
        }

        var sPattern = /[0-9.]/;
        var sPrice = '';
        for (var i = 0; i < sFormattedPrice.length; i++) {
            if (sPattern.test(sFormattedPrice[i])) {
                sPrice += sFormattedPrice[i];
            }
        }

        return sPrice;
    }
};

var SHOP_PRICE_FORMAT = CAFE24.getDeprecatedNamespace('SHOP_PRICE_FORMAT');
CAFE24.SHOP_PRICE_UTIL = {
    /**
     * iShopNo 쇼핑몰의 결제화폐 금액 입력폼으로 만듭니다.
     * @param Element elem 입력폼
     * @param bool bUseMinus 마이너스 입력 사용 여부
     */
    toShopPriceInput: function(elem, iShopNo, bUseMinus)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        var iDecimalPlace = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopCurrencyInfo.decimal_place;
        bUseMinus ? CAFE24.SHOP_PRICE_UTIL._toPriceInput(elem, iDecimalPlace, bUseMinus) : CAFE24.SHOP_PRICE_UTIL._toPriceInput(elem, iDecimalPlace);
    },

    /**
     * iShopNo 쇼핑몰의 참조화폐 금액 입력폼으로 만듭니다.
     * @param Element elem 입력폼
     */
    toShopSubPriceInput: function(elem, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        var iDecimalPlace = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aShopSubCurrencyInfo.decimal_place;
        CAFE24.SHOP_PRICE_UTIL._toPriceInput(elem, iDecimalPlace);
    },

    /**
     * iShopNo 쇼핑몰의 기준화폐 금액 입력폼으로 만듭니다.
     * @param Element elem 입력폼
     */
    toBasePriceInput: function(elem, iShopNo)
    {
        iShopNo = parseInt(iShopNo) || CAFE24.SDE_SHOP_NUM;

        var iDecimalPlace = CAFE24.SHOP_CURRENCY_INFO[iShopNo].aBaseCurrencyInfo.decimal_place;
        CAFE24.SHOP_PRICE_UTIL._toPriceInput(elem, iDecimalPlace);
    },

    /**
     * 소수점 iDecimalPlace까지만 입력 가능하도록 처리
     * @param Element elem 입력폼
     * @param int iDecimalPlace 허용 소수점
     * @param bool bUseMinus 마이너스 입력 사용 여부
     */
    _toPriceInput: function(elem, iDecimalPlace, bUseMinus)
    {
        attachEvent(elem, 'keyup', function(e) {
            e = e || window.event;
            bUseMinus ? replaceToMinusPrice(e.srcElement) : replaceToPrice(e.srcElement);
        });
        attachEvent(elem, 'blur', function(e) {
            e = e || window.event;
            bUseMinus ? replaceToMinusPrice(e.srcElement) : replaceToPrice(e.srcElement);
        });

        // 추가금액에서 마이너스를 입력받기 위해 사용
        function replaceToMinusPrice(target) {
            var value = target.value;

            var regExpTest = new RegExp('^[0-9]*' + (iDecimalPlace ? '' : '\\.[0-9]{0, ' + iDecimalPlace + '}') + '$');

            if (regExpTest.test(value) === false) {
                value = value.replace(/[^0-9.|\-]/g, '');
                if (parseInt(iDecimalPlace)) {
                    value = value.replace(/^([0-9]+\.[0-9]+)\.+.*$/, '$1');
                    value = value.replace(new RegExp('(\\.[0-9]{' + iDecimalPlace + '})[0-9]*$'), '$1');
                } else {
                    value = value.replace(/[^(0-9|\-)]/g, '');
                }
                target.value = value;
            }
        }

        function replaceToPrice(target)
        {
            var value = target.value;

            var regExpTest = new RegExp('^[0-9]*' + (iDecimalPlace ? '' : '\\.[0-9]{0, ' + iDecimalPlace + '}') + '$');
            if (regExpTest.test(value) === false) {
                value = value.replace(/[^0-9.]/g, '');
                if (parseInt(iDecimalPlace)) {
                    value = value.replace(/^([0-9]+\.[0-9]+)\.+.*$/, '$1');
                    value = value.replace(new RegExp('(\\.[0-9]{' + iDecimalPlace + '})[0-9]*$'), '$1');
                } else {
                    value = value.replace(/\.+[0-9]*$/, '');
                }
                target.value = value;
            }
        }

        function attachEvent(elem, sEventName, fn)
        {
            if (elem.addEventListener) {
                elem.addEventListener(sEventName, fn, false);

            } else if (elem.attachEvent) {
                elem.attachEvent("on" + sEventName, fn);
            }
        }

    }
};

if (window.jQuery !== undefined) {
    $.fn.extend({
        toShopPriceInput: function(iShopNo)
        {
            return this.each(function() {
                var iElementShopNo = $(this).data('shop_no') || iShopNo;
                CAFE24.SHOP_PRICE_UTIL.toShopPriceInput(this, iElementShopNo);
            });
        },
        toShopSubPriceInput: function(iShopNo)
        {
            return this.each(function() {
                var iElementShopNo = $(this).data('shop_no') || iShopNo;
                CAFE24.SHOP_PRICE_UTIL.toShopSubPriceInput(this, iElementShopNo);
            });
        },
        toBasePriceInput: function(iShopNo)
        {
            return this.each(function() {
                var iElementShopNo = $(this).data('shop_no') || iShopNo;
                CAFE24.SHOP_PRICE_UTIL.toBasePriceInput(this, iElementShopNo);
            });
        }
    });
}

// EC$ 별칭용
if (typeof window.EC$ === 'function') {
    EC$.fn.extend({
        toShopPriceInput: function(iShopNo)
        {
            return this.each(function() {
                var iElementShopNo = EC$(this).data('shop_no') || iShopNo;
                CAFE24.SHOP_PRICE_UTIL.toShopPriceInput(this, iElementShopNo);
            });
        },
        toShopSubPriceInput: function(iShopNo)
        {
            return this.each(function() {
                var iElementShopNo = EC$(this).data('shop_no') || iShopNo;
                CAFE24.SHOP_PRICE_UTIL.toShopSubPriceInput(this, iElementShopNo);
            });
        },
        toBasePriceInput: function(iShopNo)
        {
            return this.each(function() {
                var iElementShopNo = EC$(this).data('shop_no') || iShopNo;
                CAFE24.SHOP_PRICE_UTIL.toBasePriceInput(this, iElementShopNo);
            });
        }
    });
}

var SHOP_PRICE_UTIL = CAFE24.getDeprecatedNamespace('SHOP_PRICE_UTIL');
(function(window) {
    window.htmlentities = {
        /**
         * Converts a string to its html characters completely.
         *
         * @param {String} str String with unescaped HTML characters
         **/
        encode: function(str) {
            var buf = [];

            for (var i=str.length-1; i>=0; i--) {
                buf.unshift(['&#', str[i].charCodeAt(), ';'].join(''));
            }

            return buf.join('');
        },
        /**
         * Converts an html characterSet into its original character.
         *
         * @param {String} str htmlSet entities
         **/
        decode: function(str) {
            return str.replace(/&#(\d+);/g, function(match, dec) {
                return String.fromCharCode(dec);
            });
        }
    };
})(window);

CAFE24.CRYPTOKEY = (function() {
    const algorithm = {
        name: "AES-CBC",
        length: 256
    };

    async function generateKey() {
        const key = await window.crypto.subtle.generateKey(
            algorithm,
            true,
            ["encrypt", "decrypt"]
        );
        return key;
    }

    async function saveKey(key) {
        const exportedKey = await window.crypto.subtle.exportKey("jwk", key);
        window.sessionStorage.setItem("cryptoKey", JSON.stringify(exportedKey));
    }

    async function loadKey() {
        const json = window.sessionStorage.getItem("cryptoKey");
        const keyData = JSON.parse(json);
        const key = await window.crypto.subtle.importKey(
            "jwk",
            keyData,
            algorithm,
            true,
            ["encrypt", "decrypt"]
        );
        return key;
    }

    async function encryptData(data, key) {
        const encodedData = new TextEncoder().encode(data);
        const iv = window.crypto.getRandomValues(new Uint8Array(16));
        const encryptedData = await window.crypto.subtle.encrypt(
            { name: "AES-CBC", iv: iv },
            key,
            encodedData
        );
        return { encryptedData, iv };
    }

    async function decryptData(encryptedData, iv, key) {
        const decryptedData = await window.crypto.subtle.decrypt(
            { name: "AES-CBC", iv: iv },
            key,
            encryptedData
        );
        const decodedData = new TextDecoder().decode(decryptedData);
        return decodedData;
    }

    return {
        generateKey,
        saveKey,
        loadKey,
        encryptData,
        decryptData
    };
})();


CAFE24.CRYPTOKEY.encryptAndSave = async function(name, data) {
    let key = '';
    if (!window.sessionStorage.getItem("cryptoKey")) {
        key = await CAFE24.CRYPTOKEY.generateKey();
        await CAFE24.CRYPTOKEY.saveKey(key);
    } else {
        key = await CAFE24.CRYPTOKEY.loadKey();
    }
    const result = await CAFE24.CRYPTOKEY.encryptData(data, key);
    const encryptedData = new Uint8Array(result.encryptedData);
    const iv = new Uint8Array(result.iv);
    window.sessionStorage.setItem(`encryptedData_${name}`, encryptedData);
    window.sessionStorage.setItem(`iv_${name}`, iv);
};

CAFE24.CRYPTOKEY.decryptAndLoad = async function(name) {
    const loadedKey = await CAFE24.CRYPTOKEY.loadKey();
    const encryptedData = window.sessionStorage.getItem(`encryptedData_${name}`);
    const iv = window.sessionStorage.getItem(`iv_${name}`);
    const encryptedArray = new Uint8Array(
        encryptedData.split(",").map(Number)
    );
    const ivArray = new Uint8Array(iv.split(",").map(Number));
    return await CAFE24.CRYPTOKEY.decryptData(
        encryptedArray.buffer,
        ivArray.buffer,
        loadedKey
    );
};

/**
 * 비동기식 데이터
 */
var CAPP_ASYNC_METHODS = {
    STATUS: 'unready',
    DEBUG: false,
    IS_LOGIN: (document.cookie.match(/(?:^| |;)iscache=F/) ? true : false),
    EC_PATH_ROLE: EC$('meta[name="path_role"]').attr('content') || '',
    aDatasetList: [],
    $xansMyshopMain: EC$('.xans-myshop-main'),
    init: function()
    {
        CAPP_ASYNC_METHODS.STATUS = 'ready';
    	var bDebug = CAPP_ASYNC_METHODS.DEBUG;

        var aUseModules = [];
        var aNoCachedModules = [];

        EC$(CAPP_ASYNC_METHODS.aDatasetList).each(function() {
            var sKey = this;

            var oTarget = CAPP_ASYNC_METHODS[sKey];

            if (bDebug) {
                console.log(sKey);
            }
            var bIsUse = oTarget.isUse();
            if (bDebug) {
                console.log('   isUse() : ' + bIsUse);
            }

            if (bIsUse === true) {
                aUseModules.push(sKey);

                if (oTarget.restoreCache === undefined || oTarget.restoreCache() === false) {
                    if (bDebug) {
                        console.log('   restoreCache() : true');
                    }
                    aNoCachedModules.push(sKey);
                }
            }
        });

        if (aNoCachedModules.length > 0) {
            var sEditor = '';
            try {
                if (bEditor === true) {
                    // 에디터에서 접근했을 경우 임의의 상품 지정
                    sEditor = '&PREVIEW_SDE=1';
                }
            } catch (e) { }

            var sPathRole = '&path_role=' + CAPP_ASYNC_METHODS.EC_PATH_ROLE;
            var sEcMobile = '&EC_MOBILE=' + EC_MOBILE;

            var sUrl = '/exec/front/manage/async?module=' + aNoCachedModules.join(',') + sEditor + sPathRole + sEcMobile;
            EC$.ajax(
            {
                url: sUrl,
                dataType: 'json',
                success: function(aData)
                {
                	CAPP_ASYNC_METHODS.setData(aData, aUseModules);
                }
            });

        } else {
        	CAPP_ASYNC_METHODS.setData({}, aUseModules);

        }
    },
    setData: function(aData, aUseModules)
    {
        aData = aData || {};

        EC$(aUseModules).each(function() {
            var sKey = this;

            var oTarget = CAPP_ASYNC_METHODS[sKey];

            if (oTarget.setData !== undefined && aData.hasOwnProperty(sKey) === true) {
                oTarget.setData(aData[sKey]);
            }

            if (oTarget.execute !== undefined) {
                oTarget.execute();
            }
        });

        CAPP_ASYNC_METHODS.STATUS = 'complete';
    },

    _getCookie: function(sCookieName)
    {
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        return aCookieValue ? aCookieValue[1] : null;
    }
};

var eCache = null;
if (window.location.protocol === 'https:' && (typeof CAFE24.CRYPTOKEY) == 'object') {
    CAFE24.CRYPTOKEY.decryptAndLoad('member_' + CAFE24.SDE_SHOP_NUM).then(function(o) {
        eCache = o;
    }).catch(function(err) {
        // console.log(err);
    });
}


/**
 * 비동기식 데이터 - 회원 정보
 */
CAPP_ASYNC_METHODS.aDatasetList.push('member');
CAPP_ASYNC_METHODS.member = {
    __sEncryptedString: null,
    __isAdult: 'F',

    // 회원 데이터
    __sMemberId: null,
    __sName: null,
    __sNickName: null,
    __sGroupName: null,
    __sEmail: null,
    __sNewsMail: null,
    __sPhone: null,
    __sCellphone: null,
    __sSms: null,
    __sBirthday: null,
    __sGroupNo: null,
    __sBoardWriteName: null,
    __sAdditionalInformation: null,
    __sAuthenticationMethod: null,
    __sCreatedDate: null,

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (EC$('.xans-layout-statelogon, .xans-layout-logon').length > 0) {
                return true;
            }

            if (CAPP_ASYNC_METHODS.recent.isUse() === true
                && typeof(EC_FRONT_JS_CONFIG_SHOP) !== 'undefined'
                && EC_FRONT_JS_CONFIG_SHOP.adult19Warning === 'T') {
                return true;
            }

            if (typeof CAFE24.APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('customer', CAFE24.APPSCRIPT_SDK_DATA) > -1) {
                return true;
            }

        } else {
            // 비 로그인 상태에서 삭제처리
            this.removeCache();
        }

        return false;
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        // 데이터 복구 유무
        var bRestored = false;

        try {
            // 데이터 복구
            var oCache = null;
            if (window.location.protocol === 'https:' && (typeof CAFE24.CRYPTOKEY) == 'object') {
                if (eCache) {
                    oCache = JSON.parse(eCache);
                }
            } else if (window.location.protocol === 'http:' && (typeof CryptoJS) == 'object') {
                if (window.sessionStorage.getItem('member_' + CAFE24.SDE_SHOP_NUM)) {
                    oCache = window.sessionStorage.getItem('member_' + CAFE24.SDE_SHOP_NUM);
                    oCache = CryptoJS.AES.decrypt(oCache, SHOP.getMallID() + '_' + EC_SDE_SHOP_NUM).toString(CryptoJS.enc.Utf8);
                    oCache = JSON.parse(oCache);
                }
            }

            // expire 체크
            if (oCache.exp < Date.now()) {
                throw 'cache has expired.';
            }

            // 데이터 체크
            if (typeof oCache.data.member_id === 'undefined'
                || oCache.data.member_id === ''
                || typeof oCache.data.name === 'undefined'
                || typeof oCache.data.nick_name === 'undefined'
                || typeof oCache.data.group_name === 'undefined'
                || typeof oCache.data.group_no === 'undefined'
                || typeof oCache.data.email === 'undefined'
                || typeof oCache.data.news_mail === 'undefined'
                || typeof oCache.data.phone === 'undefined'
                || typeof oCache.data.cellphone === 'undefined'
                || typeof oCache.data.sms === 'undefined'
                || typeof oCache.data.birthday === 'undefined'
                || typeof oCache.data.board_write_name === 'undefined'
                || typeof oCache.data.additional_information === 'undefined'
                || typeof oCache.data.authentication_method === 'undefined'
                || typeof oCache.data.created_date === 'undefined'
            ) {
                throw 'Invalid cache data.';
            }

            // 데이터 복구
            this.__sMemberId = oCache.data.member_id;
            this.__sName = oCache.data.name;
            this.__sNickName = oCache.data.nick_name;
            this.__sGroupName = oCache.data.group_name;
            this.__sGroupNo = oCache.data.group_no;
            this.__sEmail = oCache.data.email;
            this.__sNewsMail = oCache.data.news_mail;
            this.__sPhone = oCache.data.phone;
            this.__sCellphone = oCache.data.cellphone;
            this.__sSms = oCache.data.sms;
            this.__sBirthday = oCache.data.birthday;
            this.__sBoardWriteName = oCache.data.board_write_name;
            this.__sAdditionalInformation = oCache.data.additional_information;
            this.__sAuthenticationMethod = oCache.data.authentication_method;
            this.__sCreatedDate = oCache.data.created_date;

            bRestored = true;
        } catch (e) {
            // 복구 실패시 캐시 삭제
            this.removeCache();
        }

        return bRestored;
    },

    cache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }
        // 캐시
        var sData = JSON.stringify({exp: Date.now() + (1000 * 60 * 10),data: this.getData()})

        if (window.location.protocol === 'https:' && (typeof CAFE24.CRYPTOKEY) == 'object') {
            CAFE24.CRYPTOKEY.encryptAndSave('member_' + CAFE24.SDE_SHOP_NUM, sData);
        } else if (window.location.protocol === 'http:' && (typeof CryptoJS) == 'object') {
            sData = CryptoJS.AES.encrypt(sData, SHOP.getMallID() + '_' + EC_SDE_SHOP_NUM).toString();
            window.sessionStorage.setItem('member_' + CAFE24.SDE_SHOP_NUM, sData);
        }
    },

    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        // 캐시 삭제
        window.sessionStorage.removeItem('encryptedData_member_' + CAFE24.SDE_SHOP_NUM);
        window.sessionStorage.removeItem('iv_member_' + CAFE24.SDE_SHOP_NUM);
        window.sessionStorage.removeItem('member_' + CAFE24.SDE_SHOP_NUM);
    },

    setData: function(oData)
    {
        this.__sEncryptedString = oData.memberData;
        this.__isAdult = oData.memberIsAdult;
    },

    execute: function()
    {
        if (this.__sMemberId === null) {
            if (this.__sEncryptedString.id) {
                this.setDataCallback(this.__sEncryptedString);
            } else {
                AuthSSLManager.weave({
                    'auth_mode': 'decryptClient',
                    'auth_string': this.__sEncryptedString,
                    'auth_callbackName': 'CAPP_ASYNC_METHODS.member.setDataCallback'
                });
            }
        } else {
            this.render();
        }
    },

    setDataCallback: function(sData)
    {
        try {
            if (sData.id) {
                var oData = sData;
            } else {
                var sDecodedData = decodeURIComponent(sData);
                if (AuthSSLManager.isError(sDecodedData) == true) {
                    console.log(sDecodedData);
                    return;
                }
                var oData = AuthSSLManager.unserialize(sDecodedData);
            }

            this.__sMemberId = oData.id || '';
            this.__sName = oData.name || '';
            this.__sNickName = oData.nick || '';
            this.__sGroupName = oData.group_name || '';
            this.__sGroupNo = oData.group_no || '';
            this.__sEmail = oData.email || '';
            this.__sNewsMail = oData.news_mail || '';
            this.__sPhone = oData.phone || '';
            this.__sCellphone = oData.cellphone || '';
            this.__sSms = oData.sms || '';
            this.__sBirthday = oData.birthday || 'F';
            this.__sBoardWriteName = oData.board_write_name || '';
            this.__sAdditionalInformation = oData.additional_information || '';
            this.__sAuthenticationMethod = oData.personal_type || null;
            this.__sCreatedDate = oData.created_date || '';

            // 데이터 랜더링
            this.render();

            // 데이터 캐시
            this.cache();
        } catch (e) {}
    },

    render: function()
    {
        // 친구초대
        if (EC$('.xans-myshop-asyncbenefit').length > 0) {
            if (EC$('#reco_url').val() && EC$('#reco_url').val().split('=')[1] == '') {
                EC$('#reco_url').attr({value: EC$('#reco_url').val() + this.__sMemberId});
            }
        }

        EC$('.authssl_member_name').html(this.__sName);
        EC$('.xans-member-var-id').html(this.__sMemberId);
        EC$('.xans-member-var-name').html(this.__sName);
        EC$('.xans-member-var-nick').html(this.__sNickName);
        EC$('.xans-member-var-group_name').html(this.__sGroupName);
        EC$('.xans-member-var-group_no').html(this.__sGroupNo);
        EC$('.xans-member-var-email').html(this.__sEmail);
        EC$('.xans-member-var-phone').html(this.__sPhone);

        if (EC$('.xans-board-commentwrite').length > 0 && typeof BOARD_COMMENT !== 'undefined') {
            BOARD_COMMENT.setCmtData();
        }
    },

    getMemberIsAdult: function()
    {
        if (CAPP_ASYNC_METHODS.STATUS == 'unready') {
            CAPP_ASYNC_METHODS.init();
        }
        return this.__isAdult;
    },

    getData: function()
    {
        if (CAPP_ASYNC_METHODS.STATUS == 'unready') {
            CAPP_ASYNC_METHODS.init();
        }
        return {
            member_id: this.__sMemberId,
            name: this.__sName,
            nick_name: this.__sNickName,
            group_name: this.__sGroupName,
            group_no: this.__sGroupNo,
            email: this.__sEmail,
            news_mail: this.__sNewsMail,
            phone: this.__sPhone,
            cellphone: this.__sCellphone,
            sms: this.__sSms,
            birthday: this.__sBirthday,
            board_write_name: this.__sBoardWriteName,
            additional_information: this.__sAdditionalInformation,
            authentication_method: this.__sAuthenticationMethod,
            created_date: this.__sCreatedDate
        };
    }
};

/**
 * 비동기식 데이터 - 예치금
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Ordercnt');
CAPP_ASYNC_METHODS.Ordercnt = {
    __iOrderShppiedBeforeCount: null,
    __iOrderShppiedStandbyCount: null,
    __iOrderShppiedBeginCount: null,
    __iOrderShppiedComplateCount: null,
    __iOrderShppiedCancelCount: null,
    __iOrderShppiedExchangeCount: null,
    __iOrderShppiedReturnCount: null,

    __$target: EC$('#xans_myshop_orderstate_shppied_before_count'),
    __$target2: EC$('#xans_myshop_orderstate_shppied_standby_count'),
    __$target3: EC$('#xans_myshop_orderstate_shppied_begin_count'),
    __$target4: EC$('#xans_myshop_orderstate_shppied_complate_count'),
    __$target5: EC$('#xans_myshop_orderstate_order_cancel_count'),
    __$target6: EC$('#xans_myshop_orderstate_order_exchange_count'),
    __$target7: EC$('#xans_myshop_orderstate_order_return_count'),

    isUse: function()
    {
        if (EC$('.xans-myshop-orderstate').length > 0) {
            return true; 
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'ordercnt_' + CAFE24.SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            var aData = CAFE24.UTIL.parseJSON(decodeURIComponent(aCookieValue[1]));
            this.__iOrderShppiedBeforeCount = aData.shipped_before_count;
            this.__iOrderShppiedStandbyCount = aData.shipped_standby_count;
            this.__iOrderShppiedBeginCount = aData.shipped_begin_count;
            this.__iOrderShppiedComplateCount = aData.shipped_complate_count;
            this.__iOrderShppiedCancelCount = aData.order_cancel_count;
            this.__iOrderShppiedExchangeCount = aData.order_exchange_count;
            this.__iOrderShppiedReturnCount = aData.order_return_count;
            return true;
        }

        return false;
    },

    setData: function(aData)
    {
        this.__iOrderShppiedBeforeCount = aData['shipped_before_count'];
        this.__iOrderShppiedStandbyCount = aData['shipped_standby_count'];
        this.__iOrderShppiedBeginCount = aData['shipped_begin_count'];
        this.__iOrderShppiedComplateCount = aData['shipped_complate_count'];
        this.__iOrderShppiedCancelCount = aData['order_cancel_count'];
        this.__iOrderShppiedExchangeCount = aData['order_exchange_count'];
        this.__iOrderShppiedReturnCount = aData['order_return_count'];
    },

    execute: function()
    {
        this.__$target.html(this.__iOrderShppiedBeforeCount);
        this.__$target2.html(this.__iOrderShppiedStandbyCount);
        this.__$target3.html(this.__iOrderShppiedBeginCount);
        this.__$target4.html(this.__iOrderShppiedComplateCount);
        this.__$target5.html(this.__iOrderShppiedCancelCount);
        this.__$target6.html(this.__iOrderShppiedExchangeCount);
        this.__$target7.html(this.__iOrderShppiedReturnCount);
    },

    getData: function()
    {
        return {
            shipped_before_count: this.__iOrderShppiedBeforeCount,
            shipped_standby_count: this.__iOrderShppiedStandbyCount,
            shipped_begin_count: this.__iOrderShppiedBeginCount,
            shipped_complate_count: this.__iOrderShppiedComplateCount,
            order_cancel_count: this.__iOrderShppiedCancelCount,
            order_exchange_count: this.__iOrderShppiedExchangeCount,
            order_return_count: this.__iOrderShppiedReturnCount
        };
    }
};

/**
 * 비동기식 데이터 - 장바구니 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Basketcnt');
CAPP_ASYNC_METHODS.Basketcnt = {
    __iBasketCount: null,

    __$target: EC$('.xans-layout-orderbasketcount span a'),
    __$target2: EC$('#xans_myshop_basket_cnt'),
    __$target3: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_basket_cnt'),
    __$target4: EC$('.EC-Layout-Basket-count'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        if (this.__$target2.length > 0) {
            return true;
        }
        if (this.__$target3.length > 0) {
            return true;
        }
        if (this.__$target4.length > 0) {
            return true;
        }

        if (typeof CAFE24.APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('personal', CAFE24.APPSCRIPT_SDK_DATA) > -1) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'basketcount_' + CAFE24.SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iBasketCount = parseInt(aCookieValue[1], 10);
            return true;
        }
        
        return false;
    },

    setData: function(sData)
    {
        this.__iBasketCount = Number(sData);
    },

    execute: function()
    {
        this.__$target.html(this.__iBasketCount);

        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target2.html(this.__iBasketCount + '개');
        } else {
            this.__$target2.html(this.__iBasketCount);
        }

        this.__$target3.html(this.__iBasketCount);
        
        this.__$target4.html(this.__iBasketCount);
        
        if (this.__iBasketCount > 0 && this.__$target4.length > 0) {
            var $oCountDisplay = EC$('.EC-Layout_Basket-count-display');

            if ($oCountDisplay.length > 0) {
                $oCountDisplay.removeClass('displaynone');
            }
        }
    },

    getData: function()
    {
        return {
            count: this.__iBasketCount
        };
    }
};

/**
 * 비동기식 데이터 - 장바구니 금액
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Basketprice');
CAPP_ASYNC_METHODS.Basketprice = {
    __sBasketPrice: null,

    __$target: EC$('#xans_myshop_basket_price'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }

        if (typeof CAFE24.APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('personal', CAFE24.APPSCRIPT_SDK_DATA) > -1) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'basketprice_' + CAFE24.SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__sBasketPrice = decodeURIComponent((aCookieValue[1]+ '').replace(/\+/g, '%20'));
            return true;
        }
        
        return false;
    },

    setData: function(sData)
    {
        this.__sBasketPrice = sData;
    },

    execute: function()
    {
        this.__$target.html(this.__sBasketPrice);
    },

    getData: function()
    {
        // 데이터 없는경우 0
        var sBasketPrice = (this.__sBasketPrice || 0) + '';

        return {
            basket_price: parseFloat(CAFE24.SHOP_PRICE_FORMAT.detachFormat(htmlentities.decode(sBasketPrice))).toFixed(2)
        };
    }
};

/*
 * 비동기식 데이터 - 장바구니 상품리스트
 */
CAPP_ASYNC_METHODS.aDatasetList.push('BasketProduct');
CAPP_ASYNC_METHODS.BasketProduct = {

    STORAGE_KEY: 'BasketProduct_' + CAFE24.SDE_SHOP_NUM,

    __aData: null,

    __$target: EC$('.xans-layout-orderbasketcount span a'),
    __$target2: EC$('#xans_myshop_basket_cnt'),
    __$target3: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_basket_cnt'),
    __$target4: EC$('.EC-Layout-Basket-count'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        if (this.__$target2.length > 0) {
            return true;
        }
        if (this.__$target3.length > 0) {
            return true;
        }
        if (this.__$target4.length > 0) {
            return true;
        }

        if (typeof CAFE24.APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('personal', CAFE24.APPSCRIPT_SDK_DATA) > -1) {
            return true;
        }
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        try {
            this.__aData = [];
            var aStorageData = JSON.parse(sSessionStorageData);

            for (var iKey in aStorageData) {
                this.__aData.push(aStorageData[iKey]);
            }

            return true;
        } catch (e) {

            // 복구 실패시 캐시 삭제
            this.removeCache();

            return false;
        }
    },

    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }
        // 캐시 삭제
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    setData: function(oData)
    {
        this.__aData = oData;

        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        try {
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.getData()));
        } catch (error) {
        }
    },

    execute: function()
    {

    },

    getData: function()
    {
        var aStorageData = this.__aData;
        var aResult = [];
        return new Promise(function (resolve, reject) {

            if (aStorageData != null && aStorageData.length > 1) {
                var oNewStorageData = [];

                for (var iKey in aStorageData) {
                    oNewStorageData.push(aStorageData[iKey]);
                }

                aResult = oNewStorageData;
                resolve(aResult);
            } else {
                var sUrl = '/exec/front/manage/async?module=BasketProduct';
                //랜딩결제 : ch_ref 붙여주기
                sUrl = CAFE24.attachShoppingpayParam(sUrl);
                EC$.ajax({
                    url: sUrl,
                    success: function (aData) {
                        aResult = aData.BasketProduct;
                        resolve(aResult);
                    }
                });
            }

        });
    },

    setAsyncData: function (aPostData) {
        return new Promise(function (resolve, reject) {
            EC$.post('/exec/front/order/basket/', aPostData, function (data) {
                resolve(data);
            }).catch(function (data) {
                reject(data);
            });
        });
    },
    deleteAllAsyncData: function (basket_shipping_type) {
        return new Promise(function (resolve, reject) {
            EC$.post('/exec/front/order/basket/', {command: 'delete', delvtype: basket_shipping_type}, function(data) {
                resolve(data);
            }, 'json').catch(function (data) {
                reject(data);
            });
        });
    },

    deleteCartItems: function (basket_shipping_type, product_list) {
        var aDeleteProducts = [];
        var idx = 0;
        if (product_list.length > 0) {
            for (var iKey in product_list) {
                aDeleteProducts[idx] = product_list[iKey].product_no + ':' + product_list[iKey].option_id + ':F:' + product_list[iKey].basket_product_no + ':null:'+basket_shipping_type;
                idx ++;
            }
        }

        var sDeleteProducts = aDeleteProducts.join();
        return new Promise(function (resolve, reject) {
            EC$.post('/exec/front/order/basket/', {command: 'select_delete', checked_product:sDeleteProducts, calls:'CAFE24_SDK'}, function(data) {
                CAPP_ASYNC_METHODS.BasketProduct.setData();
                resolve(data.aResultDeleteCartItems);
            }, 'json').catch(function (data) {
                reject(data);
            });
        });
    }
};

/**
 * 비동기식 데이터 - 쿠폰 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Couponcnt');
CAPP_ASYNC_METHODS.Couponcnt = {
    __iCouponCount: null,

    __$target: EC$('.xans-layout-myshopcouponcount'),
    __$target2: EC$('#xans_myshop_coupon_cnt'),
    __$target3: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_coupon_cnt'),
    __$target4: EC$('#xans_myshop_bankbook_coupon_cnt'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }

            if (this.__$target4.length > 0) {
                return true;
            }

            if (typeof CAFE24.APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('promotion', CAFE24.APPSCRIPT_SDK_DATA) > -1) {
                return true;
            }
        }

        return false;
    },
    
    restoreCache: function()
    {
        var sCookieName = 'couponcount_' + CAFE24.SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iCouponCount = parseInt(aCookieValue[1], 10);
            return true;
        }
        
        return false;
    },
    setData: function(sData)
    {
        this.__iCouponCount = Number(sData);
    },

    execute: function()
    {
        this.__$target.html(this.__iCouponCount);

        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target2.html(this.__iCouponCount + '개');
        } else {
            this.__$target2.html(this.__iCouponCount);
        }

        this.__$target3.html(this.__iCouponCount);
        this.__$target4.html(this.__iCouponCount);
    },

    getData: function()
    {
        return {
            count: this.__iCouponCount
        };
    }
};

/**
 * 비동기식 데이터 - 적립금
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Mileage');
CAPP_ASYNC_METHODS.Mileage = {
    __sAvailMileage: null,
    __sUsedMileage: null,
    __sTotalMileage: null,
    __sUnavailMileage: null,
    __sReturnedMileage: null,

    __$target: EC$('#xans_myshop_mileage'),
    __$target2: EC$('#xans_myshop_bankbook_avail_mileage, #xans_myshop_summary_avail_mileage'),
    __$target3: EC$('#xans_myshop_bankbook_used_mileage, #xans_myshop_summary_used_mileage'),
    __$target4: EC$('#xans_myshop_bankbook_total_mileage, #xans_myshop_summary_total_mileage'),
    __$target5: EC$('#xans_myshop_summary_unavail_mileage'),
    __$target6: EC$('#xans_myshop_summary_returned_mileage'),
    __$target7: EC$('#xans_myshop_avail_mileage'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }

            if (this.__$target4.length > 0) {
                return true;
            }

            if (this.__$target5.length > 0) {
                return true;
            }

            if (this.__$target6.length > 0) {
                return true;
            }

            if (this.__$target7.length > 0) {
                return true;
            }

            if (typeof CAFE24.APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('customer', CAFE24.APPSCRIPT_SDK_DATA) > -1) {
                return true;
            }
        }

        return false;
    },

    restoreCache: function()
    {
        // 특정 경로 룰의 경우 복구 취소
        if (PathRoleValidator.isInvalidPathRole()) {
            return false;
        }

        // 쿠키로부터 데이터 획득
        var sAvailMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_avail_mileage_' + CAFE24.SDE_SHOP_NUM);
        var sReturnedMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_returned_mileage_' + CAFE24.SDE_SHOP_NUM);
        var sUnavailMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_unavail_mileage_' + CAFE24.SDE_SHOP_NUM);
        var sUsedMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_used_mileage_' + CAFE24.SDE_SHOP_NUM);

        // 데이터가 하나라도 없는경우 복구 실패
        if (sAvailMileage === null
            || sReturnedMileage === null
            || sUnavailMileage === null
            || sUsedMileage === null
        ) {
            return false;
        }

        // 전체 마일리지 계산
        var sTotalMileage = (parseFloat(sAvailMileage) +
            parseFloat(sUnavailMileage) +
            parseFloat(sUsedMileage)).toString();

        // 단위정보를 계산하여 필드에 셋
        this.__sAvailMileage = parseFloat(sAvailMileage).toFixed(2);
        this.__sReturnedMileage = parseFloat(sReturnedMileage).toFixed(2);
        this.__sUnavailMileage = parseFloat(sUnavailMileage).toFixed(2);
        this.__sUsedMileage = parseFloat(sUsedMileage).toFixed(2);
        this.__sTotalMileage = parseFloat(sTotalMileage).toFixed(2);

        return true;
    },

    setData: function(aData)
    {
        this.__sAvailMileage = parseFloat(aData['avail_mileage'] || 0).toFixed(2);
        this.__sUsedMileage = parseFloat(aData['used_mileage'] || 0).toFixed(2);
        this.__sTotalMileage = parseFloat(aData['total_mileage'] || 0).toFixed(2);
        this.__sUnavailMileage = parseFloat(aData['unavail_mileage'] || 0).toFixed(2);
        this.__sReturnedMileage = parseFloat(aData['returned_mileage'] || 0).toFixed(2);
    },

    execute: function()
    {
        this.__$target.html(CAFE24.SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sAvailMileage));
        this.__$target2.html(CAFE24.SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sAvailMileage));
        this.__$target3.html(CAFE24.SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sUsedMileage));
        this.__$target4.html(CAFE24.SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sTotalMileage));
        this.__$target5.html(CAFE24.SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sUnavailMileage));
        this.__$target6.html(CAFE24.SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sReturnedMileage));
        this.__$target7.html(CAFE24.SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sAvailMileage));
    },

    getData: function()
    {
        return {
            available_mileage: this.__sAvailMileage,
            used_mileage: this.__sUsedMileage,
            total_mileage: this.__sTotalMileage,
            returned_mileage: this.__sReturnedMileage,
            unavailable_mileage: this.__sUnavailMileage
        };
    }
};

/**
 * 비동기식 데이터 - 예치금
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Deposit');
CAPP_ASYNC_METHODS.Deposit = {
    __sTotalDeposit: null,
    __sAllDeposit: null,
    __sUsedDeposit: null,
    __sRefundWaitDeposit: null,
    __sMemberTotalDeposit: null,

    __$target: EC$('#xans_myshop_deposit'),
    __$target2: EC$('#xans_myshop_bankbook_deposit'),
    __$target3: EC$('#xans_myshop_summary_deposit'),
    __$target4: EC$('#xans_myshop_summary_all_deposit'),
    __$target5: EC$('#xans_myshop_summary_used_deposit'),
    __$target6: EC$('#xans_myshop_summary_refund_wait_deposit'),
    __$target7: EC$('#xans_myshop_total_deposit'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }

            if (this.__$target4.length > 0) {
                return true;
            }

            if (this.__$target5.length > 0) {
                return true;
            }

            if (this.__$target6.length > 0) {
                return true;
            }

            if (this.__$target7.length > 0) {
                return true;
            }

            if (typeof CAFE24.APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('customer', CAFE24.APPSCRIPT_SDK_DATA) > -1) {
                return true;
            }
        }

        return false;
    },

    restoreCache: function()
    {
        // 특정 경로 룰의 경우 복구 취소
        if (PathRoleValidator.isInvalidPathRole()) {
            return false;
        }

        // 쿠키로부터 데이터 획득
        var sAllDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_all_deposit_' + CAFE24.SDE_SHOP_NUM);
        var sUsedDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_used_deposit_' + CAFE24.SDE_SHOP_NUM);
        var sRefundWaitDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_deposit_refund_wait_' + CAFE24.SDE_SHOP_NUM);
        var sMemberTotalDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_member_total_deposit_' + CAFE24.SDE_SHOP_NUM);

        // 데이터가 하나라도 없는경우 복구 실패
        if (sAllDeposit === null
            || sUsedDeposit === null
            || sRefundWaitDeposit === null
            || sMemberTotalDeposit === null
        ) {
            return false;
        }

        // 사용 가능한 예치금 계산
        var sTotalDeposit = (parseFloat(sAllDeposit) -
            parseFloat(sUsedDeposit) -
            parseFloat(sRefundWaitDeposit)).toString();

        // 단위정보를 계산하여 필드에 셋
        this.__sTotalDeposit = parseFloat(sTotalDeposit).toFixed(2);
        this.__sAllDeposit = parseFloat(sAllDeposit).toFixed(2);
        this.__sUsedDeposit = parseFloat(sUsedDeposit).toFixed(2);
        this.__sRefundWaitDeposit = parseFloat(sRefundWaitDeposit).toFixed(2);
        this.__sMemberTotalDeposit = parseFloat(sMemberTotalDeposit).toFixed(2);

        return true;
    },

    setData: function(aData)
    {
        this.__sTotalDeposit = parseFloat(aData['total_deposit'] || 0).toFixed(2);
        this.__sAllDeposit = parseFloat(aData['all_deposit'] || 0).toFixed(2);
        this.__sUsedDeposit = parseFloat(aData['used_deposit'] || 0).toFixed(2);
        this.__sRefundWaitDeposit = parseFloat(aData['deposit_refund_wait'] || 0).toFixed(2);
        this.__sMemberTotalDeposit = parseFloat(aData['member_total_deposit'] || 0).toFixed(2);
    },

    execute: function()
    {
        this.__$target.html(CAFE24.SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sTotalDeposit));
        this.__$target2.html(CAFE24.SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sTotalDeposit));
        this.__$target3.html(CAFE24.SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sTotalDeposit));
        this.__$target4.html(CAFE24.SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sAllDeposit));
        this.__$target5.html(CAFE24.SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sUsedDeposit));
        this.__$target6.html(CAFE24.SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sRefundWaitDeposit));
        this.__$target7.html(CAFE24.SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sMemberTotalDeposit));
    },

    getData: function()
    {
        return {
            total_deposit: this.__sTotalDeposit,
            used_deposit: this.__sUsedDeposit,
            refund_wait_deposit: this.__sRefundWaitDeposit,
            all_deposit: this.__sAllDeposit,
            member_total_deposit: this.__sMemberTotalDeposit
        };
    }
};

/**
 * 비동기식 데이터 - 위시리스트
 */
CAPP_ASYNC_METHODS.aDatasetList.push('WishList');
CAPP_ASYNC_METHODS.WishList = {
    STORAGE_KEY: 'localWishList' + CAFE24.SDE_SHOP_NUM,
    __$targetWishIcon: EC$('.icon_img.ec-product-listwishicon'),
    __$targetWishList: EC$('.xans-myshop-wishlist'),
    __aWishList: null,
    __aTags_on: null,
    __aTags_off: null,

    isUse: function()
    {
        if (this.__$targetWishIcon.length > 0 || this.__$targetWishList.length > 0
        || CAPP_ASYNC_METHODS.EC_PATH_ROLE === 'PRODUCT_DETAIL') {
            return true;
        }
        return false;
    },

    restoreCache: function()
    {
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        var aStorageData = CAFE24.UTIL.parseJSON(sSessionStorageData);
        if (this.__$targetWishList.length > 0 || aStorageData['isLogin'] !== CAPP_ASYNC_METHODS.IS_LOGIN) {
            this.clearStorage();
            return false;
        }

        var aWishList = aStorageData['wishList'];
        this.__aTags_on = aStorageData['on_tags'];
        this.__aTags_off = aStorageData['off_tags'];
        this.__aWishList = [];
        for (var i = 0; i < aWishList.length; i++) {
            var aTempWishList = [];
            aTempWishList.product_no = aWishList[i];
            this.__aWishList.push(aTempWishList);
        }
        return true;
    },

    setData: function(aData)
    {
        if (aData.hasOwnProperty('wishList') === false || aData.hasOwnProperty('on_tags') === false) {
            return;
        }

        this.__aWishList = aData.wishList;
        this.__aTags_on = aData.on_tags;
        this.__aTags_off = aData.off_tags;

        if (window.sessionStorage) {
            var aWishList = [];

            for (var i = 0; i < aData.wishList.length; i++) {
                aWishList.push(aData.wishList[i].product_no);
            }

            var oNewStorageData = {
                'wishList': aWishList,
                'on_tags': aData.on_tags,
                'off_tags': aData.off_tags,
                'isLogin': CAPP_ASYNC_METHODS.IS_LOGIN
            };

            if (typeof oNewStorageData !== 'undefined') {
                sessionStorage.setItem(this.STORAGE_KEY , JSON.stringify(oNewStorageData));
            }
        }
    },

    execute: function()
    {
        var aWishList = this.__aWishList;
        var aTagsOn = this.__aTags_on;
        var aTagsOff = this.__aTags_off;

        if (aWishList === null || typeof aWishList === 'undefined') {
            aWishList = [];
        }

        var oTarget = EC$('.ec-product-listwishicon');
        for (var sKey in aTagsOff) {
            oTarget.attr(sKey, aTagsOff[sKey]);
        }

        for (var i = 0; i < aWishList.length; i++) {
            assignAttribute(aWishList[i]);
        }

        /**
         * oTarget 엘레먼트에 aData의 정보를 어싸인함.
         * @param array aData 위시리스트 정보
         */
        function assignAttribute(aData)
        {
            var iProductNo = aData['product_no'];
            var oTarget = EC$('.ec-product-listwishicon[productno="'+iProductNo+'"]');

            // oTarget의 src, alt, icon_status attribute의 값을 할당
            for (var sKey in aTagsOn) {
                oTarget.attr(sKey, aTagsOn[sKey]);
            }
        }

    },

    /**
     * 세션스토리지 삭제
     */
    clearStorage: function()
    {
        if (!window.sessionStorage) {
            return;
        }
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    /**
     * sCommand에 따른 sessionStorage Set
     * @param iProductNo
     * @param sCommand 추가(add)/삭제(del) sCommand
     */
    setSessionStorageItem: function(iProductNo, sCommand)
    {
        if (this.isUse() === false) {
            return;
        }

        var oStorageData = CAFE24.UTIL.parseJSON(sessionStorage.getItem(this.STORAGE_KEY));
        var aWishList = oStorageData['wishList'];
        var iLimit = 200;

        if (aWishList === null) {
            aWishList = [];
        }

        var iProductNo = parseInt(iProductNo, 10);
        var iIndex = aWishList.indexOf(iProductNo);

        if (sCommand === 'add') {
            if (aWishList.length >= iLimit) {
                aWishList.splice(aWishList.length - 1, 1);
            }
            if (iIndex < 0) {
                aWishList.unshift(iProductNo);
            }
        } else {
            if (iIndex > -1) {
                aWishList.splice(iIndex, 1);
            }
        }

        oStorageData['wishList'] = aWishList;
        sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(oStorageData));
    }
};

/**
 * 비동기식 데이터 - 관심상품 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Wishcount');
CAPP_ASYNC_METHODS.Wishcount = {
    __iWishCount: null,

    __$target: EC$('#xans_myshop_interest_prd_cnt'),
    __$target2: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_interest_prd_cnt'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        if (this.__$target2.length > 0) {
            return true;
        }

        if (typeof CAFE24.APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('personal', CAFE24.APPSCRIPT_SDK_DATA) > -1) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'wishcount_' + CAFE24.SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iWishCount = parseInt(aCookieValue[1], 10);
            return true;
        }

        return false;
    },

    setData: function(sData)
    {
        this.__iWishCount = Number(sData);
    },

    execute: function()
    {
        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target.html(this.__iWishCount + '개');
        } else {
            this.__$target.html(this.__iWishCount);
        }

        this.__$target2.html(this.__iWishCount);
    },

    getData: function()
    {
        return {
            count: this.__iWishCount
        };
    }
};

/**
 * 비동기식 데이터 - 최근 본 상품
 */
CAPP_ASYNC_METHODS.aDatasetList.push('recent');
CAPP_ASYNC_METHODS.recent = {
    STORAGE_KEY: 'localRecentProduct' + CAFE24.SDE_SHOP_NUM,

    __$target: EC$('.xans-layout-productrecent'),

    __aData: null,

    isUse: function()
    {
        this.__$target.hide();

        if (this.__$target.find('.xans-record-').length > 0) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        this.__aData = [];

        var iTotalCount = CAPP_ASYNC_METHODS.RecentTotalCount.getData();
        if (iTotalCount == 0) {
            // 총 갯수가 없는 경우 복구할 것이 없으므로 복구한 것으로 리턴
            return true;
        }

        var sAdultImage = '';

        if (window.sessionStorage === undefined) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        var iViewCount = EC_FRONT_JS_CONFIG_SHOP.recent_count;

        this.__aData = [];
        var aStorageData = CAFE24.UTIL.parseJSON(sSessionStorageData);
        var iCount = 1;
        var bDispRecent = true;
        for (var iKey in aStorageData) {
            var sProductImgSrc = aStorageData[iKey].sImgSrc;

            if (isFinite(iKey) === false) {
                continue;
            }

            var aDataTmp = [];
            aDataTmp.recent_img = getImageUrl(sProductImgSrc);
            aDataTmp.name = aStorageData[iKey].sProductName;
            aDataTmp.disp_recent = true;
            aDataTmp.is_adult_product = aStorageData[iKey].isAdultProduct;
            aDataTmp.link_product_detail = aStorageData[iKey].link_product_detail;

            //aDataTmp.param = '?product_no=' + aStorageData[iKey].iProductNo + '&cate_no=' + aStorageData[iKey].iCateNum + '&display_group=' + aStorageData[iKey].iDisplayGroup;
            aDataTmp.param = filterXssUrlParameter(aStorageData[iKey].sParam);
            if (iViewCount < iCount) {
                bDispRecent = false;
            }
            aDataTmp.disp_recent = bDispRecent;

            iCount++;
            this.__aData.push(aDataTmp);
        }

        return true;

        /**
         * get SessionStorage image url
         * @param sNewImgUrl DB에 저장되어 있는 tiny값
         */
        function getImageUrl(sImgUrl)
        {
            if (typeof(sImgUrl) === 'undefined' || sImgUrl === null) {
                return;
            }
            var sNewImgUrl = '';

            if (sImgUrl.indexOf('http://') >= 0 || sImgUrl.indexOf('https://') >= 0 || sImgUrl.substr(0, 2) === '//') {
                sNewImgUrl = sImgUrl;
            } else {
                sNewImgUrl = EC_FRONT_JS_CONFIG_SHOP.cdnUrl + '/web/product/tiny/' + sImgUrl;
            }

            return sNewImgUrl;
        }

        /**
         * 파라미터 URL에서 XSS 공격 관련 파라미터를 필터링합니다. ECHOSTING-162977
         * @param string sParam 파라미터
         * @return string 필터링된 파라미터
         */
        function filterXssUrlParameter(sParam)
        {
            sParam = sParam || '';

            var sPrefix = '';
            if (sParam.substr(0, 1) === '?') {
                sPrefix = '?';
                sParam = sParam.substr(1);
            }

            var aParam = {};

            var aParamList = (sParam).split('&');
            EC$.each(aParamList, function() {
                var aMatch = this.match(/^([^=]+)=(.*)$/);
                if (aMatch) {
                    aParam[aMatch[1]] = aMatch[2];
                }
            });

            return sPrefix + EC$.param(aParam);
        }

    },

    setData: function(aData)
    {
        this.__aData = aData;

        // 쿠키엔 있지만 sessionStorage에 없는 데이터 복구
        if (window.sessionStorage) {

            var oNewStorageData = [];

            for (var i = 0; i < aData.length; i++) {
                if (aData[i].bNewProduct !== true) {
                    continue;
                }

                var aNewStorageData = {
                    'iProductNo': aData[i].product_no,
                    'sProductName': aData[i].name,
                    'sImgSrc': aData[i].recent_img,
                    'sParam': aData[i].param,
                    'link_product_detail': aData[i].link_product_detail
                };

                oNewStorageData.push(aNewStorageData);
            }

            if (oNewStorageData.length > 0) {
                sessionStorage.setItem(this.STORAGE_KEY , JSON.stringify(oNewStorageData));
            }
        }
    },

    execute: function()
    {
        var sAdult19Warning = EC_FRONT_JS_CONFIG_SHOP.adult19Warning;

        var aData = this.__aData;

        var aNodes = this.__$target.find('.xans-record-');
        var iRecordCnt = aNodes.length;
        var iAddedElementCount = 0;

        var aNodesParent = EC$(aNodes[0]).parent();
        for (var i = 0; i < aData.length; i++) {
            if (!aNodes[i]) {
                EC$(aNodes[iRecordCnt - 1]).clone().appendTo(aNodesParent);
                iAddedElementCount++;
            }
        }

        if (iAddedElementCount > 0) {
            aNodes = this.__$target.find('.xans-record-');
        }

        if (aData.length > 0) {
            this.__$target.show();
        }

        for (var i = 0; i < aData.length; i++) {
            assignVariables(aNodes[i], aData[i]);
        }

        // 종료 카운트 지정
        if (aData.length < aNodes.length) {
            iLength = aData.length;
            deleteNode();
        }

        recentBntInit(this.__$target);

        /**
         * 패치되지 않은 노드를 제거
         */
        function deleteNode()
        {
            for (var i = iLength; i < aNodes.length; i++) {
                EC$(aNodes[i]).remove();
            }
        }

        /**
         * oTarget 엘레먼트에 aData의 변수를 어싸인합니다.
         * @param Element oTarget 변수를 어싸인할 엘레먼트
         * @param array aData 변수 데이터
         */
        function assignVariables(oTarget, aData)
        {
            var recentImage = aData.recent_img;

            if (sAdult19Warning === 'T' && CAPP_ASYNC_METHODS.member.getMemberIsAdult() === 'F' && aData.is_adult_product === 'T') {
                    recentImage = EC_FRONT_JS_CONFIG_SHOP.adult19BaseTinyImage;
            }

            var $oTarget = EC$(oTarget);

            var sHtml = $oTarget.html();

            sHtml = sHtml.replace('about:blank', recentImage)
                         .replace('##param##', aData.param)
                         .replace('##name##',aData.name)
                         .replace('##link_product_detail##', aData.link_product_detail);
            $oTarget.html(sHtml);

            if (aData.disp_recent === true) {
                $oTarget.removeClass('displaynone');
            }
        }

        function recentBntInit($target)
        {
            // 화면에 뿌려진 갯수
            var iDisplayCount = 0;
            // 보여지는 style
            var sDisplay = '';
            var iIdx = 0;
            //
            var iDisplayNoneIdx = 0;

            var nodes = $target.find('.xans-record-').each(function()
            {
                sDisplay = EC$(this).css('display');
                if (sDisplay != 'none') {
                    iDisplayCount++;
                } else {
                    if (iDisplayNoneIdx == 0) {
                        iDisplayNoneIdx = iIdx;
                    }

                }
                iIdx++;
            });

            var iRecentCount = nodes.length;
            var bBtnActive = iDisplayCount > 0;
            EC$('.xans-layout-productrecent .prev').off('click').click(function()
            {
                if (bBtnActive !== true) return;
                var iFirstNode = iDisplayNoneIdx - iDisplayCount;
                if (iFirstNode == 0 || iDisplayCount == iRecentCount) {
                    alert(__('최근 본 첫번째 상품입니다.'));
                    return;
                } else {
                    iDisplayNoneIdx--;
                    EC$(nodes[iDisplayNoneIdx]).hide();
                    EC$(nodes[iFirstNode - 1]).removeClass('displaynone');
                    EC$(nodes[iFirstNode - 1]).fadeIn('fast');

                }
            }).css(
            {
                cursor: 'pointer'
            });

            EC$('.xans-layout-productrecent .next').off('click').click(function()
            {
                if (bBtnActive !== true) return;
                if ((iRecentCount) == iDisplayNoneIdx || iDisplayCount == iRecentCount) {
                    alert(__('최근 본 마지막 상품입니다.'));
                } else {
                    EC$(nodes[iDisplayNoneIdx]).fadeIn('fast');
                    EC$(nodes[iDisplayNoneIdx]).removeClass('displaynone');
                    EC$(nodes[ (iDisplayNoneIdx - iDisplayCount)]).hide();
                    iDisplayNoneIdx++;
                }
            }).css(
            {
                cursor: 'pointer'
            });

        }

    }
};

/**
 * 비동기식 데이터 - Recentkeyword
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Recentkeyword');
CAPP_ASYNC_METHODS.Recentkeyword = {
    __$target: EC$('div.xans-search-recentkeyword'),
    RECENT_WORD_KEY: 'RECENT_WORD_' + EC_SDE_SHOP_NUM + '_',
    RECENT_WORD_COUNT: 10,

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
    },
    //restoreCache: function()
    // {
    //     var sCookieName = 'RECENT_WORD_' + CAFE24.SDE_SHOP_NUM;
    //     var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
    //     var aCookieValue = document.cookie.match(re);
    //     if (aCookieValue) {
    //         this.recentword = aCookieValue[1];
    //         return true;
    //     }
    //
    //     return false;
    // },

    setData: function()
    {
    },
    execute: function()
    {
        var oTemplate = this.getTemplate();
        var sHtml = '';
        var sRecentSearchUrl = '/product/search.html?keyword=';
        if (typeof(EC_FRONT_JS_CONFIG_SHOP.sSearchUrl) !== 'undefined') {
            sRecentSearchUrl = EC_FRONT_JS_CONFIG_SHOP.sSearchUrl;
        }

        for (var i=1; i <= this.RECENT_WORD_COUNT; i++) {
            var sRecentKeyword = CAPP_ASYNC_METHODS._getCookie(this.RECENT_WORD_KEY + i);

            if (sRecentKeyword !== null) {
                sRecentKeyword = decodeURIComponent(sRecentKeyword);
                var oLi = oTemplate.clone();
                oLi.find('a').attr('href', sRecentSearchUrl + sRecentKeyword);
                oLi.find('a').text(sRecentKeyword);
                oLi.find('button').addClass('recent_keyword_remove');
                oLi.find('button').attr('index', i);
                oLi.find('button').removeAttr('onclick');
                sHtml += '<li data-index="' + i + '">' + oLi.html() + '</li>' ;

            }
        }

        CAPP_ASYNC_METHODS.Recentkeyword.__$target.find('ul').html(sHtml);
        CAPP_ASYNC_METHODS.Recentkeyword.__$target.find('button.btnDeleteAll').addClass('recent_keyword_remove_all').removeAttr('onclick');
    },
    getTemplate: function ()
    {
        return this.__$target.find('ul > li:eq(0)').clone();

    }
}
/**
 * 비동기식 데이터 - 최근본상품 총 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('RecentTotalCount');
CAPP_ASYNC_METHODS.RecentTotalCount = {
    __iRecentCount: null,

    __$target: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_recent_cnt'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'recent_plist';
        if (CAFE24.SDE_SHOP_NUM > 1) {
            sCookieName = 'recent_plist' + CAFE24.SDE_SHOP_NUM;
        }
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iRecentCount = decodeURI(aCookieValue[1]).split('|').length;
        } else {
            this.__iRecentCount = 0;
        }
    },

    execute: function()
    {
        this.__$target.html(this.__iRecentCount);
    },

    getData: function()
    {
        if (this.__iRecentCount === null) {
            // this.isUse값이 false라서 복구되지 않았는데 이 값이 필요한 경우 복구
            this.restoreCache();
        }

        return this.__iRecentCount;
    }
};

/**
 * 비동기식 데이터 - 주문정보
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Order');
CAPP_ASYNC_METHODS.Order = {
    __iOrderCount: null,
    __iOrderTotalPrice: null,
    __iGradeIncreaseValue: null,

    __$target: EC$('#xans_myshop_bankbook_order_count'),
    __$target2: EC$('#xans_myshop_bankbook_order_price'),
    __$target3: EC$('#xans_myshop_bankbook_grade_increase_value'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }
        }
        
        return false;        
    },

    restoreCache: function()
    {
        var sCookieName = 'order_' + CAFE24.SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            var aData = CAFE24.UTIL.parseJSON(decodeURIComponent(aCookieValue[1]));
            this.__iOrderCount = aData.total_order_count;
            this.__iOrderTotalPrice = aData.total_order_price;
            this.__iGradeIncreaseValue = Number(aData.grade_increase_value);
            return true;
        }

        return false;
    },

    setData: function(aData)
    {
        this.__iOrderCount = aData['total_order_count'];
        this.__iOrderTotalPrice = aData['total_order_price'];
        this.__iGradeIncreaseValue = Number(aData['grade_increase_value']);
    },

    execute: function()
    {
        this.__$target.html(this.__iOrderCount);
        this.__$target2.html(this.__iOrderTotalPrice);
        this.__$target3.html(this.__iGradeIncreaseValue);
    },

    getData: function()
    {
        return {
            total_order_count: this.__iOrderCount,
            total_order_price: this.__iOrderTotalPrice,
            grade_increase_value: this.__iGradeIncreaseValue
        };
    }
};

/**
 * 비동기식 데이터 - Benefit
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Benefit');
CAPP_ASYNC_METHODS.Benefit = {
    __aBenefit: null,
    __$target: EC$('.xans-myshop-asyncbenefit'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }
        }

        return false;
    },

    setData: function(aData)
    {
        this.__aBenefit = aData;
    },

    execute: function()
    {
        var aFilter = [
            'group_image_tag',
            'group_icon_tag',
            'display_no_benefit',
            'display_with_all',
            'display_mobile_use_dc',
            'display_mobile_use_mileage',
            'display_member_group_dc_limit'
        ];
        var __aData = this.__aBenefit;
        
        // 그룹이미지
        EC$('.myshop_benefit_group_image_tag').attr({alt: __aData['group_name'], src: __aData['group_image']});

        // 그룹아이콘
        EC$('.myshop_benefit_group_icon_tag').attr({alt: __aData['group_name'], src: __aData['group_icon']});

        if (__aData['display_no_benefit'] === true) {
            EC$('.myshop_benefit_display_no_benefit').removeClass('displaynone').show();
        }
        
        if (__aData['display_with_all'] === true) {
            EC$('.myshop_benefit_display_with_all').removeClass('displaynone').show();
        }
        
        if (__aData['display_mobile_use_dc'] === true) {
            EC$('.myshop_benefit_display_mobile_use_dc').removeClass('displaynone').show();
        } 
        
        if (__aData['display_mobile_use_mileage'] === true) {
            EC$('.myshop_benefit_display_mobile_use_mileage').removeClass('displaynone').show();
        }

        if (__aData['display_member_group_dc_limit'] === true) {
            EC$('.myshop_benefit_display_member_group_dc_limit').removeClass('displaynone').show();
        }

        EC$.each(__aData, function(key, val) {
            if (EC$.inArray(key, aFilter) === -1) {
                EC$('.myshop_benefit_' + key).html(val);
            }
        });
    }    
};

/**
 * 비동기식 데이터 - 비동기장바구니 레이어
 */
CAPP_ASYNC_METHODS.aDatasetList.push('BasketLayer');
CAPP_ASYNC_METHODS.BasketLayer = {
    __sBasketLayerHtml: null,
    __$target: document.getElementById('ec_async_basket_layer_container'),

    isUse: function()
    {
        if (this.__$target !== null) {
            return true;
        }
        return false;
    },

    execute: function()
    {
        EC$.ajax({
            url: '/order/async_basket_layer.html?__popupPage=T',
            async: false,
            success: function(data) {
                var sBasketLayerHtml = data;
                var sBasketLayerStyle = '';
                var sBasketLayerBody = '';

                sBasketLayerHtml = sBasketLayerHtml.replace(/<script([\s\S]*?)<\/script>/gi,''); // 스크립트 제거
                sBasketLayerHtml = sBasketLayerHtml.replace(/<link([\s\S]*?)\/>/gi,''); // 옵티마이져 제거

                var regexStyle = /<style([\s\S]*?)<\/style>/; // Style 추출
                if (regexStyle.exec(sBasketLayerHtml) != null) sBasketLayerStyle = regexStyle.exec(sBasketLayerHtml)[0];

                var regexBody = /<body[\s\S]*?>([\s\S]*?)<\/body>/; // Body 추출
                if (regexBody.exec(sBasketLayerHtml) != null) sBasketLayerBody = regexBody.exec(sBasketLayerHtml)[1];

                CAPP_ASYNC_METHODS.BasketLayer.__sBasketLayerHtml = sBasketLayerStyle + sBasketLayerBody;
            }
        });
        this.__$target.innerHTML = this.__sBasketLayerHtml;
    }
};

/**
 * 비동기식 데이터 - Benefit
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Grade');
CAPP_ASYNC_METHODS.Grade = {
    __aGrade: null,
    __$target: EC$('#sGradeAutoDisplayArea'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }
        }

        return false;
    },

    setData: function(aData)
    {
        this.__aGrade = aData;
    },

    execute: function()
    {
        var __aData = this.__aGrade;
        var aFilter = ['bChangeMaxTypePrice', 'bChangeMaxTypePriceAndCount', 'bChangeMaxTypePriceOrCount', 'bChangeMaxTypeCount'];

        var aMaxDisplayJson = {
            "bChangeMaxTypePrice": [
                {"sId": "sChangeMaxTypePriceArea"}
            ],
            "bChangeMaxTypePriceAndCount": [
                {"sId": "sChangeMaxTypePriceAndCountArea"}
            ],
            "bChangeMaxTypePriceOrCount": [
                {"sId": "sChangeMaxTypePriceOrCountArea"}
            ],
            "bChangeMaxTypeCount": [
                {"sId": "sChangeMaxTypeCountArea"}
            ]
        };

        if (EC$('.sNextGroupIconArea').length > 0) {
            if (__aData['bDisplayNextGroupIcon'] === true) {
                EC$('.sNextGroupIconArea').removeClass('displaynone').show();
                EC$('.myshop_benefit_next_group_icon_tag').attr({alt: __aData['sNextGrade'], src: __aData['sNextGroupIcon']});
            } else {
                EC$('.sNextGroupIconArea').addClass('displaynone');
            }
        }

        var sIsAutoGradeDisplay = "F";
        EC$.each(__aData, function(key, val) {
            if (EC$.inArray(key, aFilter) === -1) {
                return true;
            }
            if (val === true) {
                if (EC$('#'+aMaxDisplayJson[key][0].sId).length > 0) {
                    EC$('#' + aMaxDisplayJson[key][0].sId).removeClass('displaynone').show();
                }
                sIsAutoGradeDisplay = "T";
            }
        });
        if (sIsAutoGradeDisplay == "T" && EC$('#sGradeAutoDisplayArea .sAutoGradeDisplay').length > 0) {
            EC$('#sGradeAutoDisplayArea .sAutoGradeDisplay').addClass('displaynone');
        }

        EC$.each(__aData, function(key, val) {
            if (EC$.inArray(key, aFilter) === -1) {
                if (EC$('.xans-member-var-' + key).length > 0) {
                    EC$('.xans-member-var-' + key).html(val);
                }
            }
        });
    }    
};

/**
 * 비동기식 데이터 - Benefit
 */
CAPP_ASYNC_METHODS.aDatasetList.push('AutomaticGradeShow');
CAPP_ASYNC_METHODS.AutomaticGradeShow = {
    __aGrade: null,
    __$target: EC$('#sAutomaticGradeShowArea'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }
        }
        return false;
    },

    setData: function(aData)
    {
        this.__aGrade = aData;
    },

    execute: function()
    {
        var __aData = this.__aGrade;

        /**
         * 아이콘 표기 제외
        if (EC$('.sNextGroupIconArea').length > 0) {
            if (__aData['bDisplayNextGroupIcon'] === true) {
                EC$('.sNextGroupIconArea').removeClass('displaynone').show();
                EC$('.myshop_benefit_next_group_icon_tag').attr({alt: __aData['sNextGrade'], src: __aData['sNextGroupIcon']});
            } else {
                EC$('.sNextGroupIconArea').addClass('displaynone');
            }
        }
         */

        var sIsAutoGradeDisplay = "F";
        EC$.each(__aData, function(key, val) {
            if (val === true) {
                sIsAutoGradeDisplay = "T";
                return false;
            }
        });
        if (sIsAutoGradeDisplay == "T" && EC$('#sAutomaticGradeShowArea .sAutoGradeDisplay').length > 0) {
            EC$('#sAutomaticGradeShowArea .sAutoGradeDisplay').addClass('displaynone');
        }

        EC$.each(__aData, function(key, val) {
            if (EC$('.xans-member-var-' + key).length > 0) {
                EC$('.xans-member-var-' + key).html(val);
            }
        });
    }    
};

/**
 * 비동기식 데이터 - 비동기장바구니 레이어
 */
CAPP_ASYNC_METHODS.aDatasetList.push('MobileMutiPopup');
CAPP_ASYNC_METHODS.MobileMutiPopup = {
    __$target: EC$('div[class^="ec-async-multi-popup-layer-container"]'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        return false;
    },

    execute: function()
    {
        for (var i=0; i < this.__$target.length; i++) {
            EC$.ajax({
                url: '/exec/front/popup/AjaxMultiPopup?index='+i,
                data: EC_ASYNC_MULTI_POPUP_OPTION[i],
                dataType: "json",
                success: function (oResult) {
                    switch (oResult.code) {
                        case '0000' :
                            if (oResult.data.length < 1) {
                                break;
                            }
                            EC$('.ec-async-multi-popup-layer-container-' + oResult.data.html_index).html(oResult.data.html_text);
                            if (oResult.data.type == 'P') {
                                BANNER_POPUP_OPEN.setPopupSetting();
                                BANNER_POPUP_OPEN.setPopupWidth();
                                BANNER_POPUP_OPEN.setPopupClose();
                            } else {
                                /**
                                 * 이중 스크롤 방지 클래스 추가(비동기) 
                                 *
                                 */
                                EC$('body').addClass('eMobilePopup');
                                EC$('body').width('100%');

                                BANNER_POPUP_OPEN.setFullPopupSetting();
                                BANNER_POPUP_OPEN.setFullPopupClose();
                            }
                            break;
                        default :
                            break;
                    }
                },
                error: function () {
                }
            });
        }
    }
};

/**
 * sns 연동 정보
 */
CAPP_ASYNC_METHODS.aDatasetList.push('CustomerProvider');
CAPP_ASYNC_METHODS.CustomerProvider = {
    STORAGE_KEY: 'CustomerProvider_' + CAFE24.SDE_SHOP_NUM,
    aData: {member_id: null, provider: null},

    isUse: function()
    {
        return false;
    },

    restoreCache: function() {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        try {

            var jsonData = JSON.parse(this.aData);
            var aStorageData = JSON.parse(sSessionStorageData);
            
            // 캐쉬 데이터 설정
            jsonData.member_id = aStorageData.membr_id;
            jsonData.provider = aStorageData.provider;

            this.aData = jsonData;

            return true;
        } catch (e) {

            // 복구 실패시 캐시 삭제
            this.removeCache();

            return false;
        }
    },

    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }
        // 캐시 삭제
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    setData: function(aData)
    {
        this.aData = JSON.stringify(aData);
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        try {
            sessionStorage.setItem(this.STORAGE_KEY, this.aData);
        } catch (error) {
        }
    },

    execute: function()
    {

    },

    getData: function()
    {
        if (typeof this.aData.member_id === 'object') return this.aData;
        return JSON.parse(this.aData);
    }
};

/**
 * 비동기식 데이터 - 좋아요 상품 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('MyLikeProductCount');
CAPP_ASYNC_METHODS.MyLikeProductCount = {
    __iMyLikeCount: null,

    __$target: EC$('#xans_myshop_like_prd_cnt'),
    __$target_main: EC$('#xans_myshop_main_like_prd_cnt'),
    isUse: function()
    {
        if (this.__$target.length > 0 && SHOP.getLanguage() === 'ko_KR') {
            return true;
        }

        if (this.__$target_main.length > 0 && SHOP.getLanguage() === 'ko_KR') {
            return true;
        }

        return false;
    },
    restoreCache: function()
    {
        var sCookieName = 'like_product_cnt' + CAFE24.SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iMyLikeCount = parseInt(aCookieValue[1], 10);
            return true;
        }

        return false;
    },

    setData: function(sData)
    {
        this.__iMyLikeCount = Number(sData);
    },

    execute: function()
    {
        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target.html(this.__iMyLikeCount + '개');
            this.__$target_main.html(this.__iMyLikeCount);
        }
    }
};

/**
 * 비동기식 데이터 - 좋아요 상품 list
 */
CAPP_ASYNC_METHODS.aDatasetList.push('MyLikeProductList');
CAPP_ASYNC_METHODS.MyLikeProductList = {
    __aMyLikeList: null,
    __iMyLikeListLimit: 10,
    __$target: EC$('.xans-product-likeproductasync'),
    isUse: function()
    {
        if (this.__$target.length > 0 && SHOP.getLanguage() === 'ko_KR') {
            return true;
        }

        if (EC$('#EC_LIKE_ASYNC_LINK_DATA_LIST').length > 0) {
            return true;
        }
        return false;
    },
    setData: function(aData)
    {
        this.__iMyLikeListLimit = EC_FRONT_JS_CONFIG_SHOP.aSyncLikeLimit;
        this.__aMyLikeList = aData;
    },
    execute: function()
    {

        if (this.__aMyLikeList === null || this.__aMyLikeList.length === 0) {
            EC$('#EC_LIKE_ASYNC_LINK_DATA_EMPTY').html('');
            return;
        }

        //EC$('#EC_LIKE_ASYNC_LINK_DATA_EMPTY').remove();
        var sSpaceIcon = ' ';
        for (var iKey = 0; iKey < this.__aMyLikeList.length; iKey++) {
            var oRowData = EC$('#EC_LIKE_ASYNC_LINK_DATA_LIST_TEMP').clone().removeAttr('id');
            oRowData.find('a[href^="/product/detail.html"').attr('href', this.__aMyLikeList[iKey].link_product_detail);
            oRowData.find('.thumb img').attr('src',this.__aMyLikeList[iKey].image_medium);
            oRowData.find('.EC_LIKE_ASYNC_LINK_DATA_PRODUCT_NAME').html('<a href="' + this.__aMyLikeList[iKey].link_product_detail + '">' + this.__aMyLikeList[iKey].disp_product_name + '</a>');

            var sIconListHtml = this.__aMyLikeList[iKey].soldout_icon + sSpaceIcon + this.__aMyLikeList[iKey].stock_icon + sSpaceIcon + this.__aMyLikeList[iKey].recommend_icon + sSpaceIcon +
                this.__aMyLikeList[iKey].new_icon + sSpaceIcon + this.__aMyLikeList[iKey].product_icons + sSpaceIcon + this.__aMyLikeList[iKey].benefit_icons;
             if (sIconListHtml !== '') {
                oRowData.find('.EC_LIKE_ASYNC_LINK_DATA_ICON_LIST').html(sIconListHtml);
            }

            EC$('#EC_LIKE_ASYNC_LINK_DATA_APPEND').append(oRowData);

            if (iKey >= (this.__iMyLikeListLimit - 1)) {
                break;
            }
        }
        EC$('#EC_LIKE_ASYNC_LINK_DATA_LIST_TEMP').remove();
        if (this.__aMyLikeList.length < this.__iMyLikeListLimit) {
            EC$('#EC_LIKE_ASYNC_LINK_DATA_MORE_VIEW').remove();
        }

        if (EC_FRONT_JS_CONFIG_SHOP.bAutoView === 'T') {
            document.getElementById('EC_LIKE_ASYNC_LINK_DATA_LIST').style.display = 'block';
        }

    }
};

/**
 * 라이브 링콘 on/off이미지
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Livelinkon');
CAPP_ASYNC_METHODS.Livelinkon = {
    __$target: EC$('#ec_livelinkon_campain_on'),
    __$target2: EC$('#ec_livelinkon_campain_off'),

    isUse: function()
    {
        if (this.__$target.length > 0 && this.__$target2.length > 0) {
            return true;
        }
        return false;
    },

    execute: function()
    {
        var sCampaignid = '';
        if (EC_ASYNC_LIVELINKON_ID != undefined) {
            sCampaignid = EC_ASYNC_LIVELINKON_ID;
        }
        EC$.ajax({
            url: '/exec/front/Livelinkon/Campaignajax?campaign_id='+sCampaignid,
            async: false,
            success: function(data) {
                if (data == 'on') {
                    CAPP_ASYNC_METHODS.Livelinkon.__$target.removeClass('displaynone').show();
                    CAPP_ASYNC_METHODS.Livelinkon.__$target2.removeClass('displaynone').hide();
                } else if (data == 'off') {
                    CAPP_ASYNC_METHODS.Livelinkon.__$target.removeClass('displaynone').hide();
                    CAPP_ASYNC_METHODS.Livelinkon.__$target2.removeClass('displaynone').show();
                } else {
                    CAPP_ASYNC_METHODS.Livelinkon.__$target.removeClass('displaynone').hide();
                    CAPP_ASYNC_METHODS.Livelinkon.__$target2.removeClass('displaynone').hide();
                }
            }
        });
    }
};

/**
 * 비동기식 데이터 - 마이쇼핑 > 주문 카운트 (주문 건수 / CS건수 / 예전주문)
 */
CAPP_ASYNC_METHODS.aDatasetList.push('OrderHistoryCount');
CAPP_ASYNC_METHODS.OrderHistoryCount = {
    __sTotalOrder: null,
    __sTotalOrderCs: null,
    __sTotalOrderOld: null,

    __$target: EC$('#ec_myshop_total_orders'),
    __$target2: EC$('#ec_myshop_total_orders_cs'),
    __$target3: EC$('#ec_myshop_total_orders_old'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }
        }

        return false;
    },

    setData: function(aData)
    {
        this.__sTotalOrder = aData['total_orders'];
        this.__sTotalOrderCs = aData['total_orders_cs'];
        this.__sTotalOrderOld = aData['total_orders_old'];

    },

    execute: function()
    {
        this.__$target.html(this.__sTotalOrder);
        this.__$target2.html(this.__sTotalOrderCs);
        this.__$target3.html(this.__sTotalOrderOld);
    }
};

/**
 * 주문조회 > 주문내역조회 및 취소/교환/반품내역 등 탭(OrderHistoryTab) 갯수 비동기호출
 */
CAPP_ASYNC_METHODS.aDatasetList.push('OrderHistoryTab');
CAPP_ASYNC_METHODS.OrderHistoryTab = {
    __$targetTotalOrders: EC$('#xans_myshop_total_orders'),
    __$targetTotalOrdersCs: EC$('#xans_myshop_total_orders_cs'),
    __$targetTotalOrdersPast: EC$('#xans_myshop_total_orders_past'),
    __$targetTotalOrdersOld: EC$('#xans_myshop_total_orders_old'),

    isUse: function()
    {
        if (EC$('.xans-myshop-orderhistorytab').length > 0) {
            return true;
        }
        return false;
    },
    execute: function()
    {
        try {
            var mode = this.getUrlParam('mode');
            var order_id = this.getUrlParam('order_id');
            var order_status = this.getUrlParam('order_status');
            var history_start_date = this.getUrlParam('history_start_date');
            var history_end_date = this.getUrlParam('history_end_date');
            var past_year = this.getUrlParam('past_year');
            var count = this.getUrlParam('count');

            var sPathName = window.location.pathname;

            var oParameters = {
                'mode': mode == null ? '' : mode,
                'order_id': order_id == null ? '' : order_id,
                'order_status': order_status == null ? '' : order_status,
                'history_start_date': history_start_date == null ? '' : history_start_date,
                'history_end_date': history_end_date == null ? '' : history_end_date,
                'past_year': past_year == null ? '' : past_year,
                'count': count == null ? '' : count,
                'page_name': sPathName.substring(sPathName.lastIndexOf("/") + 1, sPathName.indexOf('.'))
            };

            if (typeof EC_ASYNC_ORDERHISTORYTAB_ORDER_ID !== 'undefined') {
                oParameters['encrypted_str'] = EC_ASYNC_ORDERHISTORYTAB_ORDER_ID;
            }

            var oThis = this;

            EC$.ajax({
                url: '/exec/front/Myshop/OrderHistoryTab',
                dataType: 'json',
                data: oParameters,
                success: function (aData) {
                    if (aData['result'] === true) {
                        oThis.__$targetTotalOrders.html(aData['total_orders']);
                        oThis.__$targetTotalOrdersCs.html(aData['total_orders_cs']);
                        oThis.__$targetTotalOrdersOld.html(aData['total_orders_old']);
                        oThis.__$targetTotalOrdersPast.html(aData['total_orders_past']);

                        var oTabATagList = {
                            'param': EC$('.tab_class a'),
                            'param_cs': EC$('.tab_class_cs a'),
                            'param_past': EC$('.tab_class_past a'),
                            'param_old': EC$('.tab_class_old a'),
                        };
                        var sHref;
                        EC$.each(oTabATagList, function(sKey, oTarget) {
                            if (oTarget.length > 0) {
                                sHref = oTarget.attr("href");
                                sHref = sHref.replace("$" + sKey, aData[sKey]);
                                oTarget.attr("href", sHref);
                            }
                        });

                        EC$("." + aData['selected_tab_class']).addClass('selected');

                        if (aData['is_past_list_display'] === false) {
                            EC$('.tab_class_past').addClass("displaynone");
                        } else {
                            EC$('.tab_class_past').removeClass("displaynone");
                        }

                        if (aData['old_list_display'] === false) {
                            EC$('.tab_class_old').addClass("displaynone");
                        } else {
                            EC$('.tab_class_old').removeClass("displaynone");
                        }
                    }
                }
            });
        } catch (oError) {
            this.errorAjaxCall(oError);
        }
    },
    getUrlParam: function(name)
    {
        var param = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        if (param == null) {
            return null;
        } else {
            return decodeURI(param[1]) || null;
        }
    },
    errorAjaxCall: function(oError)
    {
        var sError = oError.toString();
        var aMatch = sError.match(/Error*/g);

        if (typeof(oError) !== 'object' || aMatch == null || aMatch.length < 1 || !oError.stack) return;

        EC$.ajax({
            url: '/exec/front/order/FormJserror/',
            method: 'POST',
            cache: false,
            async: false,
            data: {
                errorMessage: oError.message,
                errorStack: oError.stack,
                errorName: oError.name
            }
        });
    }
};

/*
 * 비동기식 데이터 - 주문조회 품목 리스트
 */
CAPP_ASYNC_METHODS.aDatasetList.push('OrderHistoryItemList');
CAPP_ASYNC_METHODS.OrderHistoryItemList = {

    STORAGE_KEY: 'OrderHistoryItemList_' + EC_SDE_SHOP_NUM,

    __aData: null,

    isUse: function()
    {
        // 주문조회 페이지 && SDK order권한이 있는경우에만 노출
        if (EC$('.xans-myshop-orderhistorylistitem').length > 0 && typeof EC_APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('order', EC_APPSCRIPT_SDK_DATA) > -1) {
            return true;
        }

        // 비 로그인 상태에서 삭제처리
        if (CAPP_ASYNC_METHODS.IS_LOGIN === false) {
            this.removeCache();
        }
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null || !sSessionStorageData) {
            return false;
        }

        try {
            var aStorageData = JSON.parse(sSessionStorageData);

            // expire 체크
            if (aStorageData.exp < Date.now()) {
                throw 'cache has expired.';
            }

            this.__aData = [];
            for (var iKey in aStorageData.data) {
                this.__aData.push(aStorageData.data[iKey]);
            }

            return true;
        } catch (e) {

            // 복구 실패시 캐시 삭제
            this.removeCache();

            return false;
        }
    },

    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }
        // 캐시 삭제
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    setData: function(oData)
    {
        this.__aData = oData;

        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        try {
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify({
                exp: Date.now() + (1000 * 60 * 1),
                data: this.getData()
            }));
        } catch (error) {
        }
    },

    getData: function()
    {
        this.restoreCache();
        var aStorageData = this.__aData;

        if (aStorageData !== null && aStorageData !== false) {
            var oNewStorageData = [];
            for (var iKey in aStorageData) {
                oNewStorageData.push(aStorageData[iKey]);
            }
            return oNewStorageData;
        }
        return false;
    },

    getAsyncData: function(start_date, end_date, order_status, page, count, order_id)
    {
        return new Promise(function (res) {
            var oParameters = {
                'order_id': order_id === null ? '' : order_id,
                'order_status': order_status === null ? '' : order_status,
                'start_date': start_date === null ? '' : start_date,
                'end_date': end_date === null ? '' : end_date,
                'count': count === null ? '' : count,
                'page': page === null ? '' : page,
            };

            if (typeof EC_ASYNC_ORDERHISTORYTAB_ORDER_ID !== 'undefined') {
                oParameters['encrypted_str'] = EC_ASYNC_ORDERHISTORYTAB_ORDER_ID;
            }

            EC$.ajax(
                {
                    url: '/exec/front/manage/async?module=OrderHistoryItemList',
                    data: oParameters,
                    dataType: 'json',
                    success: function (aData) {
                        var aResult = [];
                        var aStorageData = aData.OrderHistoryItemList;

                        for (var iKey in aStorageData) {
                            aResult.push(aStorageData[iKey]);
                        }
                        res(aResult);
                    }
                });
        });
    }
};
/**
 * 비동기식 데이터 - 주문상세 조회
 */
CAPP_ASYNC_METHODS.aDatasetList.push('OrderDetailInfo');
CAPP_ASYNC_METHODS.OrderDetailInfo = {
    STORAGE_KEY: 'OrderDetailInfo_' + EC_SDE_SHOP_NUM,
    __aData: null,


    isUse: function()
    {
        // 주문상세 페이지 && SDK order권한이 있는경우에만 노출
        if (EC$('.xans-myshop-orderhistorydetail').length > 0 && typeof EC_APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('order', EC_APPSCRIPT_SDK_DATA) > -1) {
            return true;
        }

        // 비 로그인 상태에서 삭제처리
        if (CAPP_ASYNC_METHODS.IS_LOGIN === false) {
            this.removeCache();
        }
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null || !sSessionStorageData) {
            return false;
        }

        try {
            var aStorageData = JSON.parse(sSessionStorageData);

            // expire 체크
            if (aStorageData.exp < Date.now()) {
                throw 'cache has expired.';
            }

            this.__aData = [];
            for (var iKey in aStorageData.data) {
                this.__aData.push(aStorageData.data[iKey]);
            }

            return true;
        } catch (e) {

            // 복구 실패시 캐시 삭제
            this.removeCache();

            return false;
        }
    },


    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }
        // 캐시 삭제
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    setData: function(oData)
    {
        this.__aData = oData;

        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        try {
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify({
                exp: Date.now() + (1000 * 60 * 1),
                data: this.getData()
            }));
        } catch (error) {
        }
    },

    getData: function()
    {
        this.restoreCache();
        var aStorageData = this.__aData;

        if (aStorageData !== null && aStorageData !== false) {
            var oNewStorageData = [];
            for (var iKey in aStorageData) {
                oNewStorageData.push(aStorageData[iKey]);
            }
            return oNewStorageData;
        }
        return false;
    },

    getAsyncData: function (shop_no, order_id) {
        return new Promise(function (res) {
            var oParameters = {
                'shop_no': shop_no === null ? '' : shop_no,
                'order_id': order_id === null ? '' : order_id,
            };

            if (typeof EC_ASYNC_ORDERHISTORYTAB_ORDER_ID !== 'undefined') {
                oParameters['encrypted_str'] = EC_ASYNC_ORDERHISTORYTAB_ORDER_ID;
            }

            EC$.ajax(
                {
                    url: '/exec/front/manage/async?module=OrderDetailInfo',
                    data: oParameters,
                    dataType: 'json',
                    success: function (aData) {
                        var aResult = [];
                        var aStorageData = aData.OrderDetailInfo;

                        for (var iKey in aStorageData) {
                            aResult.push(aStorageData[iKey]);
                        }
                        res(aResult);
                    }
                });
        });
    }
}
/**
 * 비동기식 데이터 - 주문 취소/교환/반품 가능 품목리스트
 */
CAPP_ASYNC_METHODS.aDatasetList.push('ClaimableItemList');
CAPP_ASYNC_METHODS.ClaimableItemList = {
    STORAGE_KEY: 'ClaimableItemList_' + EC_SDE_SHOP_NUM,
    __aData: null,
    __$target: EC$('.xans-myshop-orderhistoryapplycancel'), //취소
    __$target2: EC$('.xans-myshop-orderhistoryapplyexchange'), //교환
    __$target3: EC$('.xans-myshop-orderhistoryapplyreturn'), //반품

    isUse: function()
    {
        // 주문상세 페이지 && SDK order권한이 있는경우에만 노출
        if (typeof EC_APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('order', EC_APPSCRIPT_SDK_DATA) > -1) {
            if (this.__$target.length > 0) {
                return true;
            }
            if (this.__$target2.length > 0) {
                return true;
            }
            if (this.__$target3.length > 0) {
                return true;
            }
        }

        // 비 로그인 상태에서 삭제처리
        if (CAPP_ASYNC_METHODS.IS_LOGIN === false) {
            this.removeCache();
        }
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null || !sSessionStorageData) {
            return false;
        }

        try {
            var aStorageData = JSON.parse(sSessionStorageData);

            // expire 체크
            if (aStorageData.exp < Date.now()) {
                throw new Error('cache has expired.');
            }

            this.__aData = [];
            for (var iKey in aStorageData.data) {
                this.__aData.push(aStorageData.data[iKey]);
            }

            return true;
        } catch (e) {
            // 복구 실패시 캐시 삭제
            this.removeCache();
            return false;
        }
    },


    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }
        // 캐시 삭제
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    setData: function(oData)
    {
        this.__aData = oData;

        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        try {
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify({
                exp: Date.now() + (1000 * 60 * 1),
                data: this.getData()
            }));
        } catch (error) {
        }
    },

    getData: function()
    {
        this.restoreCache();
        var aStorageData = this.__aData;

        if (aStorageData !== null && aStorageData !== false && aStorageData.length > 0) {
            var oNewStorageData = [];
            for (var iKey in aStorageData) {
                oNewStorageData.push(aStorageData[iKey]);
            }
            return oNewStorageData;
        }
        return false;
    },

    getAsyncData: function (order_id, customer_service_type) {
        return new Promise(function (res,rej) {
            if (customer_service_type === null) {
                if (CAPP_ASYNC_METHODS.ClaimableItemList.__$target.length > 0) {
                    customer_service_type = 'C';
                } else if (CAPP_ASYNC_METHODS.ClaimableItemList.__$target2.length > 0) {
                    customer_service_type = 'E';
                } else if (CAPP_ASYNC_METHODS.ClaimableItemList.__$target3.length > 0) {
                    customer_service_type = 'R';
                }
            }
            order_id = order_id === null ? CAPP_ASYNC_METHODS.ClaimableItemList.getUrlParam('order_id') : order_id;

            if (order_id === '' || order_id === null) {
                rej({code: 422, message: 'order_id is empty'})
            }

            var oParameters = {
                'order_id': order_id,
                'customer_service_type': customer_service_type
            };

            EC$.ajax(
                {
                    url: '/exec/front/manage/async?module=ClaimableItemList',
                    data: oParameters,
                    dataType: 'json',
                    success: function (aData) {
                        var aResult = [];
                        var aStorageData = aData.ClaimableItemList;

                        CAPP_ASYNC_METHODS.ClaimableItemList.removeCache();
                        CAPP_ASYNC_METHODS.ClaimableItemList.setData(aStorageData);

                        for (var iKey in aStorageData) {
                            aResult.push(aStorageData[iKey]);
                        }
                        res(aResult);
                    }
                });
        });
    },
    getUrlParam: function(name)
    {
        var param = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        if (param === null) {
            return null;
        } else {
            return decodeURI(param[1]) || null;
        }
    }
};
var PathRoleValidator = (function() {
    /**
     * Milage, Deposit 의 경우 처리되지 말아야할 페이지 확인
     * @returns {boolean}
     */
    function isInvalidPathRole()
    {
        // path role
        var sCurrentPathRole = null;

        // // euckr 환경에서 path role 획득
        if (SHOP.getProductVer() === 1) {
            // path 와 role 매핑
            var aPathRoleMap = {
                '/myshop/index.html': 'MYSHOP_MAIN',
                '/myshop/mileage/historyList.html': 'MYSHOP_MILEAGE_LIST',
                '/myshop/deposits/historyList.html': 'MYSHOP_DEPOSIT_LIST',
                '/order/orderform.html': 'ORDER_ORDERFORM'
            };

            // 페이지 경로로부터 path role 획득
            sCurrentPathRole = aPathRoleMap[document.location.pathname];

            // utf8 환경에서 path role 획득
        } else {
            // 현재 페이지 path role 획득
            sCurrentPathRole = EC$('meta[name="path_role"]').attr('content');
        }

        // 처리되면 안되는 경로
        var aInvalidPathRole = [
            'MYSHOP_MAIN',
            'MYSHOP_MILEAGE_LIST',
            'MYSHOP_DEPOSIT_LIST',
            'ORDER_ORDERFORM'
        ];

        return EC$.inArray(sCurrentPathRole, aInvalidPathRole) >= 0;
    }

    return {
        isInvalidPathRole: isInvalidPathRole
    };
})();

EC$(function()
{
    CAPP_ASYNC_METHODS.init();
});

CAFE24.MANAGE_PRODUCT_RECENT = {
    getRecentImageUrl: function() {
        var sStorageKey = 'localRecentProduct' + CAFE24.SDE_SHOP_NUM;

        if (typeof sessionStorage[sStorageKey] !== 'undefined') {
            var sRecentData = sessionStorage.getItem(sStorageKey);
            var oJsonData = JSON.parse(sRecentData);
            var sImageSrc = '';

            if (oJsonData[0] !== undefined) {
                sImageSrc = oJsonData[0].sImgSrc;
                if (typeof EC_FRONT_JS_CONFIG_MANAGE !== 'undefined' && typeof EC_FRONT_JS_CONFIG_MANAGE.cdnUrl !== 'undefined' && EC_FRONT_JS_CONFIG_MANAGE.cdnUrl !== '') {
                    sImageSrc = EC_FRONT_JS_CONFIG_MANAGE.cdnUrl + '/web/product/tiny/' + sImageSrc;
                }
            }

            document.location.replace('recentproduct://setinfo?simg_src=' + sImageSrc);
        }
    }
};

var EC_MANAGE_PRODUCT_RECENT = CAFE24.getDeprecatedNamespace('EC_MANAGE_PRODUCT_RECENT');

CAFE24.MANAGE_MEMBER = {
    // 카카오싱크 로그인
    kakaosyncLogin: function(clientSecret) {
        if (Kakao.isInitialized()) {
            Kakao.cleanup();
        }
        Kakao.init(clientSecret);

        Kakao.Auth.authorize({
            redirectUri: location.origin + EC_ROUTE.getPrefixUrl('/Api/Member/Oauth2ClientCallback/kakao/')
        });
    }
};

var EC_MANAGE_MEMBER = CAFE24.getDeprecatedNamespace('EC_MANAGE_MEMBER');

// 프로토콜 체크 포함
if (window.navigator && 'serviceWorker' in window.navigator) {
    window.addEventListener('load', function () {
        var getFrontendConfig = function (sOption) {
            if (typeof window.CAFE24 === 'object' && typeof window.CAFE24.FRONTEND === 'object') {
                return window.CAFE24.FRONTEND[sOption];
            }

            return null;
        };

        var sCafeSWName = '/ind-script/sw.php';
        var sA2hsLogKey = 'a2hs_manifest_name';
        var oManifest = document.querySelector('link[rel="manifest"]');
        var bHasManifest = !!oManifest;
        if (bHasManifest === true) {
            // start_url 로 들어온 경우 로깅
            if (location.pathname === '/' && location.search.indexOf('a2hs=1') !== -1) {
                var sManifestName = oManifest.href.split('/').pop();

                var sStoredManifestName = localStorage.getItem(sA2hsLogKey);
                if (!sStoredManifestName || sStoredManifestName !== sManifestName) {
                    if (window.fetch) {
                        fetch('/exec/front/manage/a2hs', {
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            method: 'POST',
                            body: JSON.stringify({
                                ua: navigator.userAgent
                            })
                        }).then(function (oResponse) {
                            localStorage.setItem(sA2hsLogKey, sManifestName);
                        });
                    }
                }
            }
        }

        var sUserAgent = (navigator.userAgent || '').toLowerCase();
        var sClientWebView = ((sUserAgent.indexOf('android') !== -1 && sUserAgent.indexOf('wv') !== -1)
            || sUserAgent.indexOf('cafe24plus') !== -1)
            ? 'T'
            : 'F';

        // 서비스워커 설치 검증
        var bIsWebView = getFrontendConfig('IS_WEB_VIEW') === 'T' || sClientWebView === 'T';
        var oRegistrationPromise = navigator.serviceWorker.getRegistration('/');
        if (!oRegistrationPromise) {
            return;
        }

        oRegistrationPromise.then(function (oSWRegistration) {
            var bInstallable = true;
            // 등록된 서비스워커가 있을 경우, Cafe24 서비스워커인지 확인
            if (oSWRegistration) {
                var oSW = oSWRegistration.installing || oSWRegistration.waiting || oSWRegistration.active;
                if (oSW && oSW.scriptURL.indexOf(sCafeSWName) === -1) {
                    bInstallable = false;
                } else if (bIsWebView === true) {
                    // cafe24 서비스워커면서 웹뷰 접근일 경우 서비스워커 삭제 (크롬 75.0.3770.67 버전 대응)
                    return oSWRegistration.unregister().then(function () {
                        return false;
                    }).catch(function (oError) {
                        if (window.EC_JET && EC_JET.message) {
                            EC_JET.message(oError, 'ServiceWorker');
                        }
                        console.warn('unregisterError => ', oError, oError.message, oError.name);
                        return false;
                    });
                }
            }

            if (bIsWebView) {
                bInstallable = false;
            }

            return bInstallable;
        })
        .then(function (bInstallable) {
            if (!bInstallable) {
                return;
            }

            var sRevision = getFrontendConfig('FW_MANIFEST_CACHE_REVISION');
            if (sRevision) {
                sCafeSWName = sCafeSWName + '?v=' + sRevision;
            }

            return navigator.serviceWorker.register(sCafeSWName, {
                scope: '/',
                updateViaCache: 'all'
            }).catch(function (oError) {
                if (window.EC_JET && EC_JET.message) {
                    EC_JET.message(oError, 'ServiceWorker');
                }
                console.warn('registerError => ', oError, oError.message, oError.name);
            });
        });
    });
}

CAFE24.EXTERNAL_FRONT_APPSCRIPT = {
    insertAppScript: function() {
        if (typeof CAFE24.APPSCRIPT_ASSIGN_DATA !== "undefined" && Array.isArray(CAFE24.APPSCRIPT_ASSIGN_DATA)) {
            while (CAFE24.APPSCRIPT_ASSIGN_DATA.length > 0) {
                var oSrcData = CAFE24.APPSCRIPT_ASSIGN_DATA.pop();
                CAFE24.EXTERNAL_FRONT_APPSCRIPT.appendAppScript(oSrcData['src'], oSrcData['integrity']);
            }
        }
        if (typeof CAFE24.APPSCRIPT_SOURCE_DATA !== "undefined" && Array.isArray(CAFE24.APPSCRIPT_SOURCE_DATA)) {
            while (CAFE24.APPSCRIPT_SOURCE_DATA.length > 0) {
                CAFE24.EXTERNAL_FRONT_APPSCRIPT.appendSourceTypeScript(CAFE24.APPSCRIPT_SOURCE_DATA.pop());
            }
        }
    },
    appendAppScript: function(sSrc, sIntegrity) {
        var js = document.createElement('script');
        js.src = sSrc;
        // integrity 필드가 존재하는 경우 스크립트 무결성 체킹을 위해 추가.
        if (sIntegrity && sIntegrity !== null) {
            js.integrity = sIntegrity;
            js.crossOrigin = "anonymous";
        }
        document.body.appendChild(js);
    },
    appendSourceTypeScript: function (sSrc) {
        var js = document.createElement('script');
        js.type = 'text/javascript';
        js.text = CAFE24.EXTERNAL_FRONT_APPSCRIPT.base64Decode(sSrc);
        document.body.appendChild(js);
    },
    base64Decode: function (sEncoded) {
        return decodeURIComponent(atob(sEncoded).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
    }
};
if (window.addEventListener) {
    window.addEventListener('load', CAFE24.EXTERNAL_FRONT_APPSCRIPT.insertAppScript);
} else if (window.attachEvent) {
    window.attachEvent('onload', CAFE24.EXTERNAL_FRONT_APPSCRIPT.insertAppScript);
}

var EC_EXTERNAL_FRONT_APPSCRIPT = CAFE24.getDeprecatedNamespace('EC_EXTERNAL_FRONT_APPSCRIPT');

/**
 * SDK spec interface
 */
EC_EXTERNAL_UTIL_APP_SPECINTERFACE = {

    oLoginProvider: {
        member_id: null,
        provider: null,
        client_id: null
    },

    oCustomerProvider: {
        member_id: null,
        provider: null
    },

    oMemberInfo: {
        member_id: null,
        group_no: null,
        guest_id: null
    },

    oCustomerIDInfo: {
        member_id: null,
        guest_id: null
    },

    oCustomerInfo: {
        member_id: null,
        name: null,
        nick_name: null,
        group_name: null,
        group_no: null,
        email: null,
        news_mail: null,
        phone: null,
        cellphone: null,
        sms: null,
        birthday: null,
        additional_information: null,
        authentication_method: null,
        created_date: null
    },

    // @todo deprecated
    oMileageInfo: {
        available_mileage: null,
        returned_mileage: null,
        total_mileage: null,
        unavailable_mileage: null,
        used_mileage: null
    },

    // @todo deprecated
    oDepositInfo: {
        all_deposit: null,
        member_total_deposit: null,
        refund_wait_deposit: null,
        total_deposit: null,
        used_deposit: null
    },

    oPointInfo: {
        available_point: null,
        returned_point: null,
        total_point: null,
        unavailable_point: null,
        used_point: null
    },

    oCreditInfo: {
        all_credit: null,
        member_total_credit: null,
        refund_wait_credit: null,
        total_credit: null,
        used_credit: null
    },

    oCartList: {
        shop_no: null,
        product_no: null,
        additional_option: null,
        attached_file_option: null,
        basket_product_no: null,
        product_price: null,
        quantity: null,
        selected_product: null,
        variant_code: null,
        option_id: null,
        is_set_product: null,
        set_product_no: null,
        delvtype: null
    },

    oCartInfo: {
        basket_price: null
    },

    oCartItemList: {
        basket_product_no: null,
        product_no: null,
        price: null,
        option_price: null,
        quantity: null,
        discount_price: null,
        variant_code: null,
        product_weight: null,
        display_group: null,
        quantity_based_discount: null,
        non_quantity_based_discount: null,
        product_volume: null,
        additional_option_values: null,
        product_bundle: null,
        product_bundle_no: null,
        option_id: null,
        product_name: null,
        product_image: null,
        option_value: null,
        shipping_fee_type: null,
        categories: null
    },

    oCount: {
        count: 0
    },

    oShopInfo: {
        language_code: null,
        currency_code: null,
        timezone: null
    },

    oAddedProductToCart : {
        shop_no: null,
        category_no: null,
        quantity: null,
        additional_option_value: null,
        variant_code: null,
        product_bundle: null,
        prefaid_shipping_fee: null,
        attached_file_option: null,
        created_date: null,
        product_price: null,
        option_price: null,
        product_bundle_price: null,
        product_no: null,
        option_id: null,
        product_bundle_no: null,
        shipping_type : null,
        subscription: null,
        subscription_cycle: null,
        subscription_shipments_cycle_count: null,
        basket_product_no: null,
        additional_option_values: null,
        product_name: null,
        checked_products : null
    },

    oOrderItemList: {
        order_id: null,
        order_item_code: null,
        order_status: null
    },

    oOrderDetailInfo: {
        shop_no: null,
        order_id: null,
        initial_order_amount: null,
        actual_order_amount: null,
        payment_method: null,
        order_date: null,
        first_payment_method: null,
        items: null
    },

    oClaimableItemList: {
        order_id: null,
        item_no: null,
        order_item_code: null,
        variant_code: null,
        product_no: null,
        product_code: null,
        custom_product_code: null,
        custom_variant_code: null,
        option_id: null,
        quantity: null,
        price: null,
        order_status: null,
        supplier_code: null,
        supplier_name: null
    },

    oPrecreateOrder : {
        products: null,
        hmac: null,
        response_time: null,
        order_id: null,
        return_notification_url: null
    },

    oPrecreateOrderProduct : {
        shop_no: null,
        category_no: null,
        quantity: null,
        additional_option_values: null,
        variant_code: null,
        product_bundle: null,
        prefaid_shipping_fee: null,
        attached_file_option: null,
        created_date: null,
        product_price: null,
        option_price: null,
        product_bundle_price: null,
        product_no: null,
        option_id: null,
        product_bundle_no: null,
        shipping_type : null,
        subscription: null,
        subscription_cycle: null,
        subscription_shipments_cycle_count: null,
        basket_product_no: null,
        product_name: null,
        checked_products : null,
        option_text : null,
        product_bundle_list: null
    },

    oBatchBasketAddItem : {
        product_no: null,
        variants_code: null,
        quantity: null,
        options: null,
        additional_option_values: null
    },

    oBatchBasketAddSetItem : {
        product_no: null,
        variants_code: null,
        quantity: null,
        bundle_product_components: null
    }
};

/**
 * 비동기식 데이터 - App Common ( 앱 공통정보 )
 */
CAPP_ASYNC_METHODS.aDatasetList.push('AppCommon');
CAPP_ASYNC_METHODS.AppCommon = {

    STORAGE_KEY: 'AppCommon_' + CAFE24.SDE_SHOP_NUM,

    __sGuestId: null,

    isUse: function()
    {
        if (typeof CAFE24.APPSCRIPT_SDK_DATA !== "undefined" && EC$.inArray('application', CAFE24.APPSCRIPT_SDK_DATA) > -1) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        try {
            var aStorageData = JSON.parse(window.sessionStorage.getItem(this.STORAGE_KEY));

            // expire 체크
            if (aStorageData.exp < Date.now()) {
                throw 'cache has expired.';
            }

            // 데이터 체크
            if (typeof aStorageData.data.guest_id === 'undefined') {
                throw 'Invalid cache data.';
            }

            // 데이터 복구
            this.__sGuestId = aStorageData.data.guest_id;

            return true;

        } catch (e) {
            // 복구 실패시 캐시 삭제
            this.removeCache();
            return false;
        }
    },

    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }
        // 캐시 삭제
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    setData: function(oData)
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        this.__sGuestId = oData.guest_id || '';

        try {
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify({
                exp: Date.now() + (1000 * 60 * 10),
                data: this.getData()
            }));
        } catch (error) {
        }
    },

    execute: function()
    {
    },

    getData: function()
    {
        return {
            guest_id: this.__sGuestId
        };
    },

    setSpecData: function(oSpec, oData) {
        var aData = {};
        for (var prop in oSpec) {
            if (oData.hasOwnProperty(prop) === true) {
                aData[prop] = oData[prop];
            } else {
                aData[prop] = oSpec[prop];
            }
        }
        return aData;
    },

    setSpecDataMap: function(oSpec, oData, oMapData) {
        var aData = {};
        for (var prop in oSpec) {
            if (oData.hasOwnProperty(oMapData[prop]) === true) {
                aData[prop] = oData[oMapData[prop]];
            } else {
                aData[prop] = oSpec[prop];
            }
        }
        return aData;
    },

    // sdk function list
    getMemberID: function()
    {
        return CAPP_ASYNC_METHODS.member.getData().member_id;
    },

    getEncryptedMemberId: function(sClientId)
    {
        var sUrl = '/shop' + EC_SDE_SHOP_NUM + '/Api/Member/Encryptmemberid';
        var aParams = {
            'client_id': sClientId
        };

        var aReturn = null;
        EC$.ajax({
            dataType: "json",
            type: "POST",
            url: sUrl,
            data: aParams,
            async: false,

            success: function(response) {
                var sReturnCode = response.return_code;
                var aEncryptedId = response.result.member_id;

                if (sReturnCode === '0000') {
                    aEncryptedId = '';
                }

                aReturn = CAPP_ASYNC_METHODS.AppCommon.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerIDInfo, {member_id: aEncryptedId});
            }
        });
        return aReturn;
    },

    getLoginProvider: function()
    {
        var sCookieName = "login_provider_"+CAFE24.SDE_SHOP_NUM;
        var sLoginProvider = CAPP_ASYNC_METHODS._getCookie(sCookieName);
        if (sLoginProvider === null) {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oLoginProvider, EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oLoginProvider);
        }

        var oLoginProvider = JSON.parse(decodeURIComponent(sLoginProvider));
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oLoginProvider, oLoginProvider);
    },

    getCustomerProvider: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN !== true) {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerProvider, EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerProvider);
        }

        if (typeof CAPP_ASYNC_METHODS.CustomerProvider.getData().member_id !== 'undefined') {
            if (CAPP_ASYNC_METHODS.CustomerProvider.getData().member_id !== null) {
                return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerProvider, CAPP_ASYNC_METHODS.CustomerProvider.getData());
            }
        }

        var sUrl = EC_ROUTE.getPrefixUrl('/Api/Member/Customerprovider');

        var oReturn = EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerProvider;
        EC$.ajax({
            dataType: "json",
            type: "GET",
            url: sUrl,
            async: false,

            success: function(response) {
                var sReturnCode = response.return_code;

                if (sReturnCode === '0000') {
                    return oReturn;
                }
                oReturn = response.result;

                return oReturn;
            }
        });
        CAPP_ASYNC_METHODS.CustomerProvider.setData(oReturn);
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerProvider, oReturn);
    },

    getMemberInfo: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oMemberInfo, {group_no: CAPP_ASYNC_METHODS.member.getData().group_no, member_id: CAPP_ASYNC_METHODS.member.getData().member_id});
        } else {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oMemberInfo, {guest_id: CAPP_ASYNC_METHODS.AppCommon.getData().guest_id});
        }
    },

    getCustomerIDInfo: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerIDInfo, {member_id: CAPP_ASYNC_METHODS.member.getData().member_id});
        } else {
            return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerIDInfo, {guest_id: CAPP_ASYNC_METHODS.AppCommon.getData().guest_id});
        }
    },

    getCustomerInfo: function()
    {
        var oMember = CAPP_ASYNC_METHODS.member.getData();
        if (oMember.created_date && typeof oMember.created_date === 'string') {
            oMember.created_date = oMember.created_date.replace(/(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}).+/, '$1');
        }
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCustomerInfo, oMember);
    },

    // @todo deprecated
    getMileageInfo: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oMileageInfo, CAPP_ASYNC_METHODS.Mileage.getData());
    },

     // @todo deprecated
    getDepositInfo: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oDepositInfo, CAPP_ASYNC_METHODS.Deposit.getData());
    },

    getPointInfo: function()
    {
        var oMapData = {
            available_point: 'available_mileage',
            returned_point: 'returned_mileage',
            total_point: 'total_mileage',
            unavailable_point: 'unavailable_mileage',
            used_point: 'used_mileage'
        };

        return this.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oPointInfo, CAPP_ASYNC_METHODS.Mileage.getData(), oMapData);
    },

    getCreditInfo: function()
    {
        var oMapData = {
            all_credit: 'all_deposit',
            member_total_credit: 'member_total_deposit',
            refund_wait_credit: 'refund_wait_deposit',
            total_credit: 'total_deposit',
            used_credit: 'used_deposit'
        };

        return this.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCreditInfo, CAPP_ASYNC_METHODS.Deposit.getData(), oMapData);
    },

    getCartList: function()
    {
        var oCartList = EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCartList;
        var aCartList = [];
        return new Promise(function (resolve, reject) {
            CAPP_ASYNC_METHODS.BasketProduct.getData().then(function(oData) {
                for (var iKey in oData) {
                    aCartList.push(CAPP_ASYNC_METHODS.AppCommon.setSpecData(oCartList, oData[iKey]));
                }
                resolve(aCartList);
            }).catch(function(data) {
                reject(data);
            });
        });
    },

    getCartInfo: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCartInfo, CAPP_ASYNC_METHODS.Basketprice.getData());
    },

    getCartItemList: function()
    {
        var aCartItemList = [];
        if (typeof aBasketProductData === "undefined" && typeof aBasketProductOrderData === "undefined") {
            return aCartItemList;
        }

        //aBasketProductOrderData : 주문서
        //aBasketProductData : 장바구니
        var aData = (typeof aBasketProductOrderData !== "undefined") ? aBasketProductOrderData : aBasketProductData;

        var oMapData = {
            basket_product_no: 'basket_prd_no',
            product_no: 'product_no',
            price: 'product_price',
            option_price: 'opt_price',
            quantity: 'product_qty',
            discount_price: 'product_sale_price',
            variant_code: 'item_code',
            product_weight: 'product_weight',
            display_group: 'main_cate_no',
            quantity_based_discount: 'add_sale_related_qty',
            non_quantity_based_discount: 'add_sale_not_related_qty',
            product_volume: 'volume_size_serial',
            product_bundle: 'is_set_product',
            product_bundle_no: 'set_product_no',
            option_id: 'opt_id',
            product_name: 'product_name',
            product_image: 'product_image', //tiny 작은목록이미지
            option_value: 'option_str',
            shipping_fee_type : 'shipping_fee_type',
            categories: 'categories'
        };

        var idx = 0;
        var iOldBpPrdNo = null;
        var iNewBpPrdNo = null;
        for (var iKey in aData) {
            if (typeof aData[iKey].basket_prd_no === "undefined" || aData[iKey].basket_prd_no === null) {
                continue;
            }
            iNewBpPrdNo = aData[iKey].basket_prd_no;
            if (iOldBpPrdNo !== iNewBpPrdNo) {
                aCartItemList[idx] = this.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCartItemList, aData[iKey], oMapData);

                if (aCartItemList[idx]['product_volume'] !== '' && aCartItemList[idx]['product_volume'] !== null && aCartItemList[idx]['product_volume'] !== undefined) {
                    var aProductVolume = aCartItemList[idx]['product_volume'].split('|');
                    aCartItemList[idx]['product_volume'] = {
                        'product_width': parseFloat(aProductVolume[0]),
                        'product_height': parseFloat(aProductVolume[1]),
                        'product_length': parseFloat(aProductVolume[2]),
                    };
                } else {
                    aCartItemList[idx]['product_volume'] = {
                        'product_width': null,
                        'product_height': null,
                        'product_length': null,
                    };
                }

                if (aCartItemList[idx]['product_bundle_no'] == '0') { //세트상품번호 기본값 null 로 노출되도록
                    aCartItemList[idx]['product_bundle_no'] = null;
                }

                if (aCartItemList[idx]['product_image'] == '') {
                    aCartItemList[idx]['product_image'] = null;
                }

                if (aCartItemList[idx]['option_value'] !== null && aCartItemList[idx]['option_value'].length < 1) {
                    aCartItemList[idx]['option_value'] = null;
                }

                if (aData[iKey].custom_data != null) {
                    aCartItemList[idx]['additional_option_values'] = [];
                    aCartItemList[idx]['additional_option_values'].push(aData[iKey].custom_data);
                }
                idx++;

            } else {
                aCartItemList[idx - 1]['quantity'] += aData[iKey].product_qty;

                if (aData[iKey].custom_data != null) {
                    aCartItemList[idx - 1]['additional_option_values'].push(aData[iKey].custom_data);
                }
            }

            iOldBpPrdNo = iNewBpPrdNo;
        }

        return aCartItemList;
    },

    getCartCount: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCount, CAPP_ASYNC_METHODS.Basketcnt.getData());
    },

    getCouponCount: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCount, CAPP_ASYNC_METHODS.Couponcnt.getData());
    },

    getWishCount: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oCount, CAPP_ASYNC_METHODS.Wishcount.getData());
    },

    getShopInfo: function()
    {
        return this.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oShopInfo, {language_code: SHOP.getLanguage(), currency_code: SHOP.getCurrency(), timezone: SHOP.getTimezone()});
    },

    getCartMapData : function () {
        var oMapData = {
            shop_no: 'shop_no',
            category_no: 'main_cate_no',
            quantity: 'quantity',
            additional_option_value: 'option_add',
            variant_code: 'item_code',
            product_bundle: 'is_set_product',
            prefaid_shipping_fee: 'prd_detail_ship_type',
            attached_file_option: 'option_attached_file_info_json',
            created_date: 'ins_timestamp',
            product_price: 'product_price',
            option_price: 'opt_price',
            product_bundle_price: 'set_discount_price',
            product_no: 'product_no',
            option_id: 'opt_id',
            product_bundle_no: 'set_product_no',
            shipping_type : 'delvtype',
            subscription: 'is_subscription',
            subscription_shipments_cycle : 'subscription_cycle',
            subscription_shipments_cycle_count: 'subscription_cycle_count',
            basket_product_no: 'basket_prd_no',
            additional_option_values: 'custom_data',
            product_name: 'product_name',
            checked_products : 'is_prd',
            option_text: 'opt_str',
            product_bundle_list: 'product_bundle_list'
        };
        return oMapData;
    },

    getPrecreateOrderMapData : function () {
        var oMapData = {
            products: 'products',
            hmac: 'hmac',
            response_time: 'response_time',
            order_id: 'order_id',
            return_notification_url: 'return_noty_url'
        };

        return oMapData;
    },

    addCurrentProductToCart: function(mall_id, request_time, app_key, member_id, hmac)
    {
        return new Promise(function (resolve, reject) {
            new Promise(function (res, rej) {
                var aInfo = {
                    mall_id: mall_id,
                    request_time: request_time,
                    app_key: app_key,
                    request_member_id: member_id,
                    hmac: hmac
                };

                var proc = (typeof(set_option_data) === 'undefined') ? product_submit : product_set_submit;
                if(proc === 'undefined') reject();
                var procResult = proc('app', '/exec/front/order/basket/', null, aInfo, res);
                if (procResult && procResult.result === false) reject(procResult);

            }).then(function (data) {
                var oMapData =  CAPP_ASYNC_METHODS.AppCommon.getCartMapData();
                var aCurrentProductToCartResult = [];
                for (var index in data) {
                    aCurrentProductToCartResult[index] = CAPP_ASYNC_METHODS.AppCommon.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oAddedProductToCart, data[index], oMapData);
                }
                resolve(aCurrentProductToCartResult);
            }).catch(function (data) {
                reject(data);
            })
        });
    },

    precreateOrder: function(mall_id, request_time, app_key, member_id, hmac)
    {
        return new Promise(function (resolve, reject) {
            var aInfo = {
                mall_id: mall_id,
                request_time: request_time,
                app_key: app_key,
                member_id: member_id,
                hmac: hmac,
                delv_type : (typeof(delvtype) == "undefined") ? sBasketDelvType : delvtype
            };
            new Promise(function (res, rej) {
                if(Basket.orderSelectBasket != undefined) {
                    var result = Basket.orderSelectBasket(this, aInfo['delv_type'], 'app', res);
                    if(result == false) rej();
                } else {
                    CAPP_ASYNC_METHODS.AppCommon.addCurrentProductToCart(mall_id, request_time, app_key, member_id, hmac).then(function(){
                          res();
                    }).catch(function (result) {
                          rej(result);
                    });
                }
            }).then(function () {
                EC$.post('/exec/front/order/reserve/', aInfo, function(data) {

                    if (data['result'] == false) {
                        reject(data);
                    }

                    var oPrecreateOrderMapData = CAPP_ASYNC_METHODS.AppCommon.getPrecreateOrderMapData();
                    var oOrderProductMapData =  CAPP_ASYNC_METHODS.AppCommon.getCartMapData();
                    var aPrecreateOrderResult = [];

                    var aSpecData = CAPP_ASYNC_METHODS.AppCommon.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oPrecreateOrder, data, oPrecreateOrderMapData);

                    for (var key in aSpecData) {
                        aPrecreateOrderResult[key] = aSpecData[key];
                    }

                    var prodList = data['products'];
                    for (var index in prodList) {
                        // temp, amazon, paypal 다 걷어내면 없앨코드
                        aPrecreateOrderResult[index] = CAPP_ASYNC_METHODS.AppCommon.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oPrecreateOrderProduct, prodList[index], oOrderProductMapData);
                        aPrecreateOrderResult['products'][index] = CAPP_ASYNC_METHODS.AppCommon.setSpecDataMap(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oPrecreateOrderProduct, prodList[index], oOrderProductMapData);
                    }

                    resolve(aPrecreateOrderResult);
                }, 'json');
            }).catch(function (data) {
                reject(data);
            })
        });
    },

    getOrderItemList: function(start_date, end_date, order_status, page, count, order_id)
    {
        count = count === null ? iOrderHistoryLimit : count;
        return new Promise(function (resolve, reject) {
            if (start_date || end_date || order_status || order_id || page || count != iOrderHistoryLimit ) { //기본조회값과 조건이 다른경우 새롭게 갱신
                CAPP_ASYNC_METHODS.OrderHistoryItemList.getAsyncData(start_date, end_date, order_status, page, count, order_id)
                    .then(function(oData) {
                        resolve(oData);
                    }).catch(function(data) {
                        reject(data);
                    });
            } else {
                var oData = CAPP_ASYNC_METHODS.OrderHistoryItemList.getData();

                // 주문조회페이지에서만 로딩시 페이지 리로드후 반영됨
                // async.js 호출과 sdk호출이 순차적이지 않아서 세션스토리지에 데이터를 못가져오는 경우가 발생하여 데이터가 없는경우 async한번더 호출 하도록 수정
                if (oData) {
                    var oOrderItemList = EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oOrderItemList;
                    var aOrderHistoryItemList = [];

                    for (var iKey in oData) {
                        aOrderHistoryItemList.push(CAPP_ASYNC_METHODS.AppCommon.setSpecData(oOrderItemList, oData[iKey]));
                    }
                    resolve(aOrderHistoryItemList);
                } else {
                    CAPP_ASYNC_METHODS.OrderHistoryItemList.getAsyncData(start_date, end_date, order_status, page, count, order_id)
                        .then(function(oData) {
                            resolve(oData);
                        }).catch(function(data) {
                            reject(data);
                    });
                }
            }
        });
    },

    getOrderDetailInfo: function (shop_no, order_id) {
        return new Promise(function (resolve, reject) {
            var oData = CAPP_ASYNC_METHODS.OrderDetailInfo.getData();
            if (oData[0] != null) {
                for (var iKey in oData){
                    if (oData[iKey].order_id == order_id){
                        var oOrderDetailInfo = EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oOrderDetailInfo;
                        var aOrderDetailInfo = [];

                        for (var iKey in oData) {
                            aOrderDetailInfo.push(CAPP_ASYNC_METHODS.AppCommon.setSpecData(oOrderDetailInfo, oData[iKey]));
                        }
                        resolve(aOrderDetailInfo);
                    } else {
                        CAPP_ASYNC_METHODS.OrderDetailInfo.getAsyncData(shop_no, order_id)
                            .then(function (oData) {
                                resolve(oData);
                            }).catch(function () {
                            reject(oData);
                        });
                    }
                }
            } else {
                CAPP_ASYNC_METHODS.OrderDetailInfo.getAsyncData(shop_no, order_id)
                    .then(function (oData) {
                        resolve(oData);
                    }).catch(function () {
                    reject(oData);
                });
            }
        });
    },

    getClaimableItemList: function (order_id, customer_service_type) {
        return new Promise(function (resolve, reject) {
            var orderPattern = /^[0-9]{8}-[0-9]{7}$/;

            if (order_id !== null && orderPattern.test(order_id) === false) {
                reject({code: 422, message: 'order_id is not valid'});
            }
            if (customer_service_type !== null && $.inArray(customer_service_type, ['C','E','R']) === -1) {
                reject({code: 422, message: 'customer_service_type is not valid'});
            }
            var oData = CAPP_ASYNC_METHODS.ClaimableItemList.getData();
            if (oData) {
                if (oData[0].order_id === order_id) {
                    var oClaimableItemList = EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oClaimableItemList;
                    var aClaimableItemList = [];

                    for (var iKey in oData) {
                        aClaimableItemList.push(CAPP_ASYNC_METHODS.AppCommon.setSpecData(oClaimableItemList, oData[iKey]));
                    }
                    resolve(aClaimableItemList);
                } else {
                    CAPP_ASYNC_METHODS.ClaimableItemList.getAsyncData(order_id, customer_service_type)
                        .then(function (oData) {
                            resolve(oData);
                        }).catch(function (oData) {
                            reject(oData);
                    });
                }
            } else {
                CAPP_ASYNC_METHODS.ClaimableItemList.getAsyncData(order_id, customer_service_type)
                    .then(function (oData) {
                        resolve(oData);
                    }).catch(function (oData) {
                        reject(oData);
                });
            }
        });
    },

    emptyCart: function (basket_shipping_type) {
        return new Promise(function (resolve, reject) {
            if (basket_shipping_type !== null && EC$.inArray(basket_shipping_type, ['A', 'B']) === -1) {
                reject({code: 422, message: '"basket_shipping_type" must be provided as one of "A, B".'});
                return;
            }

            CAPP_ASYNC_METHODS.BasketProduct.deleteAllAsyncData(basket_shipping_type).then(function (oData) {
                if (oData.result < 0) {
                    reject({code: 500, message: 'Empty shopping cart failed.'});
                } else {
                    resolve('success');
                }
            }).catch(function () {
                reject({code: 500, message: 'Empty shopping cart failed.'});
            });
        });
    },

    deleteCartItems: function (basket_shipping_type, product_list) {
        return new Promise(function (resolve, reject) {
            // 1) basket_shipping_type 유효성
            if (EC$.inArray(basket_shipping_type, ['A', 'B']) === -1) {
                reject({code: 422, message: '"basket_shipping_type" must be provided as one of "A, B".'});
                return;
            }

            // 2) product_list 유효성
            if (!EC$.isArray(product_list) || product_list.length < 1) {
                reject({code: 422, message: '"product_list" is not valid.'});
                return;
            }
            for (var idx in product_list) {
                if (product_list[idx] === null || product_list[idx] === undefined) {
                    reject({code: 422, message: '"product_list" is not valid.'});
                    return;
                }
            }

            CAPP_ASYNC_METHODS.BasketProduct.deleteCartItems(basket_shipping_type, product_list).then(function (oData) {
                if (oData.result < 0) {
                    reject({code: 500, message: 'Delete Cart Items failed.'});
                } else {
                    resolve(oData);
                }
            }).catch(function () {
                reject({code: 500, message: 'Delete Cart Items failed.'});
            });
        });
    },

    addCart: function (basket_type, prepaid_shipping_fee, product_list) {
        return new Promise(function (resolve, reject) {
            ////////////////////// 입력값 valid 체크 //////////////////////
            if (product_list.length > 10) {
                reject({code: 422, message: 'The maximum number of "product_list" has been exceeded. (Maximum number of requests: 10)'});
                return;
            }
            // 1) basket_type valid 체크
            if (basket_type !== null && EC$.inArray(basket_type, ['A0000', 'A0001']) === -1) {
                reject({code: 422, message: '"basket_type" must be provided as one of "A0000 : General, A0001 : Interest-free".'});
                return;
            }

            // 2) prepaid_shipping_fee 체크
            if (prepaid_shipping_fee !== null && EC$.inArray(prepaid_shipping_fee, ['P','C']) === -1) {
                reject({code: 422, message: '"prepaid_shipping_fee" should be provided as one of "P : Prepaid, C : Collected".'});
                return;
            }

            var errorProductMapping = {}; // 실패데이터 세팅
            var aProductListRequest = []; // 실제 장바구니 exec 에 요청할 상품파라미터

            // 3) product_list 유효성 체크
            for (var i in product_list) {
                var sInvalidField = ''; // 유효하지 않은 필드

                if (isNaN(product_list[i]['product_no']) || product_list[i]['product_no'] < 1) {
                    sInvalidField = 'product_no';
                } else if (isNaN(product_list[i]['quantity']) || product_list[i]['quantity'] < 1) {
                    sInvalidField = 'quantity';
                } else if (/^P.{11}$/.test(product_list[i]['variants_code']) === false) {
                    sInvalidField = 'variants_code';
                } else if (Array.isArray(product_list[i]['additional_option_values'])) {
                    for (var j in product_list[i]['additional_option_values']) {
                        if (product_list[i]['additional_option_values'][j]['key'] != 'item_option_add') {
                            sInvalidField = 'additional_option_values.key';
                            break;
                        }

                        if (product_list[i]['additional_option_values'][j]['type'] != 'text') {
                            sInvalidField = 'additional_option_values.type';
                            break;
                        }
                    }
                }

                // 유효하지 않은 필드가 있는 경우
                if (sInvalidField !== '') {
                    if (errorProductMapping[sprintf('%s is not valid', sInvalidField)] === undefined) {
                        errorProductMapping[sprintf('%s is not valid', sInvalidField)] = [];
                    }

                    errorProductMapping[sprintf('%s is not valid', sInvalidField)].push(
                        CAPP_ASYNC_METHODS.AppCommon.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oBatchBasketAddItem, product_list[i])
                    );
                } else {
                    aProductListRequest.push(product_list[i]); // 파라미터 유효성을 통과한 요청데이터만 장바구니 exec 진행
                }
            }
            ////////////////////// 입력값 valid 체크 //////////////////////

            // 장바구니 담기 exec 요청데이터
            var addCartParam = CAPP_ASYNC_METHODS.AppCommon.getAddCartParam(basket_type, prepaid_shipping_fee, aProductListRequest);

            if (addCartParam.batch_add_item.length === 0) { // 파라미터 valid 체크 이후, 장바구니 insert 할 데이터가 없는 경우
                var aResponse = {}; // sdk 응답값

                // 실패 데이터 (message, more_info) 세팅
                for (let error_message of Object.keys(errorProductMapping)) {
                    if (aResponse['errors'] === undefined) {
                        aResponse['errors'] = [];
                    }
                    aResponse['errors'].push({code: 422, message: error_message, more_info: errorProductMapping[error_message]});
                }

                resolve(aResponse);
            }

            CAPP_ASYNC_METHODS.BasketProduct.setAsyncData(addCartParam).then(function (oData) {
                var aResponse = {}; // sdk 응답값

                if (oData.result.code === 0) { // 장바구니 담기 성공
                    if (oData.aBatchAddResult.success !== undefined) {
                        aResponse['cart'] = [];
                        oData.aBatchAddResult.success.forEach(function (successData) {
                            aResponse['cart'].push(
                                CAPP_ASYNC_METHODS.AppCommon.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oBatchBasketAddItem, successData)
                            );
                        });
                    }

                    if (oData.aBatchAddResult.fail !== undefined) {
                        // 장바구니 exec 이후 실패한 데이터는 아래에서 failMapping 에 등록
                        oData.aBatchAddResult.fail.forEach(function (failData) {
                            var errMsg = failData.errMsg ? failData.errMsg : 'Failed to add the product to the cart';
                            if (errorProductMapping[errMsg] === undefined) {
                                errorProductMapping[errMsg] = [];
                            }

                            errorProductMapping[errMsg].push(
                                CAPP_ASYNC_METHODS.AppCommon.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oBatchBasketAddItem, failData)
                            );
                        });
                    }

                    // 실패 데이터 (message, more_info) 세팅
                    for (let error_message of Object.keys(errorProductMapping)) {
                        if (aResponse['errors'] === undefined) {
                            aResponse['errors'] = [];
                        }
                        aResponse['errors'].push({code: 422, message: error_message, more_info: errorProductMapping[error_message]});
                    }

                    resolve(aResponse);
                } else { // 장바구니 담기 전체 실패
                    reject({code: 422, message: oData.result.errMsg ? oData.result.errMsg : 'Failed to add the product to the cart'});
                }
            }).catch(function () {
                reject({code: 422, message: 'Failed to add the product to the cart'});
            });
        });
    },
    addBundleProductsCart: function (basket_type, prepaid_shipping_fee, product_list) {
        return new Promise(function (resolve, reject) {
            ////////////////////// 입력값 valid 체크 //////////////////////
            if (product_list.length > 10) {
                reject({code: 422, message: 'The maximum number of "product_list" has been exceeded. (Maximum number of requests: 10)'});
                return;
            }
            // 1) basket_type valid 체크
            if (basket_type !== null && EC$.inArray(basket_type, ['A0000', 'A0001']) === -1) {
                reject({code: 422, message: '"basket_type" must be provided as one of "A0000 : General, A0001 : Interest-free".'});
                return;
            }

            // 2) prepaid_shipping_fee 체크
            if (prepaid_shipping_fee !== null && EC$.inArray(prepaid_shipping_fee, ['P','C']) === -1) {
                reject({code: 422, message: '"prepaid_shipping_fee" should be provided as one of "P : Prepaid, C : Collected".'});
                return;
            }

            var errorProductMapping = {}; // 실패데이터 세팅
            var aProductListRequest = []; // 실제 장바구니 exec 에 요청할 상품파라미터

            // 3) aProductList 유효성 체크
            for (var i = 0; i < product_list.length; i++) {
                var sInvalidField = ''; // 유효하지 않은 필드

                if (isNaN(product_list[i]['product_no']) || product_list[i]['product_no'] < 1) {
                    sInvalidField = 'product_no';
                } else if (isNaN(product_list[i]['quantity']) || product_list[i]['quantity'] < 1) {
                    sInvalidField = 'quantity';
                } else if (typeof product_list[i]['bundle_product_components'] == 'undefined') {
                    sInvalidField = 'bundle_product_components';
                }

                if (Array.isArray(product_list[i]['bundle_product_components'])) {
                    if (product_list[i]['bundle_product_components'].length < 1) {
                        sInvalidField = 'bundle_product_components';
                    }

                    for (var j = 0; j < product_list[i]['bundle_product_components'].length; j++) {
                        if (Array.isArray(product_list[i]['bundle_product_components'][j]['additional_option_values'])) {
                            for (var k = 0; k < product_list[i]['bundle_product_components'][j]['additional_option_values'].length; k++) {
                                if (product_list[i]['bundle_product_components'][j]['additional_option_values'][k]['key'] != 'item_option_add') {
                                    sInvalidField = 'bundle_product_components.additional_option_values.key';
                                    break;
                                }

                                if (product_list[i]['bundle_product_components'][j]['additional_option_values'][k]['type'] != 'text') {
                                    sInvalidField = 'bundle_product_components.additional_option_values.type';
                                    break;
                                }
                            }
                        }
                    }
                }

                // 유효하지 않은 필드가 있는 경우
                if (sInvalidField !== '') {
                    if (errorProductMapping[sprintf('%s is not valid', sInvalidField)] === undefined) {
                        errorProductMapping[sprintf('%s is not valid', sInvalidField)] = [];
                    }

                    errorProductMapping[sprintf('%s is not valid', sInvalidField)].push(
                        CAPP_ASYNC_METHODS.AppCommon.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oBatchBasketAddSetItem, product_list[i])
                    );
                } else {
                    aProductListRequest.push(product_list[i]); // 파라미터 유효성을 통과한 요청데이터만 장바구니 exec 진행
                }
            }
            ////////////////////// 입력값 valid 체크 //////////////////////

            // 장바구니 담기 exec 요청데이터
            var addCartParam = CAPP_ASYNC_METHODS.AppCommon.getAddSetProductCartParam(basket_type, prepaid_shipping_fee, aProductListRequest);

            if (addCartParam.batch_add_item.length === 0) { // 파라미터 valid 체크 이후, 장바구니 insert 할 데이터가 없는 경우
                var aResponse = {}; // sdk 응답값

                // 실패 데이터 (message, more_info) 세팅
                for (let error_message of Object.keys(errorProductMapping)) {
                    if (aResponse['errors'] === undefined) {
                        aResponse['errors'] = [];
                    }
                    aResponse['errors'].push({code: 422, message: error_message, more_info: errorProductMapping[error_message]});
                }

                resolve(aResponse);
            }

            CAPP_ASYNC_METHODS.BasketProduct.setAsyncData(addCartParam).then(function (oData) {
                var aResponse = {}; // sdk 응답값

                if (oData.result.code === 0) { // 장바구니 담기 성공
                    if (oData.aBatchAddResult.success !== undefined) {
                        aResponse['cart'] = [];
                        oData.aBatchAddResult.success.forEach(function (successData) {
                            aResponse['cart'].push(
                                CAPP_ASYNC_METHODS.AppCommon.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oBatchBasketAddSetItem, successData)
                            );
                        });
                    }

                    if (oData.aBatchAddResult.fail !== undefined) {
                        // 장바구니 exec 이후 실패한 데이터는 아래에서 failMapping 에 등록
                        oData.aBatchAddResult.fail.forEach(function (failData) {
                            var errMsg = failData.errMsg ? failData.errMsg : 'Failed to add the product to the cart';
                            if (errorProductMapping[errMsg] === undefined) {
                                errorProductMapping[errMsg] = [];
                            }

                            errorProductMapping[errMsg].push(
                                CAPP_ASYNC_METHODS.AppCommon.setSpecData(EC_EXTERNAL_UTIL_APP_SPECINTERFACE.oBatchBasketAddSetItem, failData)
                            );
                        });
                    }

                    // 실패 데이터 (message, more_info) 세팅
                    for (let error_message of Object.keys(errorProductMapping)) {
                        if (aResponse['errors'] === undefined) {
                            aResponse['errors'] = [];
                        }
                        aResponse['errors'].push({code: 422, message: error_message, more_info: errorProductMapping[error_message]});
                    }

                    resolve(aResponse);
                } else { // 장바구니 담기 전체 실패
                    reject({code: 422, message: oData.result.errMsg ? oData.result.errMsg : 'Failed to add the product to the cart'});
                }
            }).catch(function () {
                reject({code: 422, message: 'Failed to add the product to the cart'});
            });
        });
    },
    getAddCartParam: function (basket_type, prepaid_shipping_fee, aProductList) {
        var aPostData = {
            'command': 'add',
            'basket_type': basket_type,
            'prd_detail_ship_type': prepaid_shipping_fee,
            'is_set_product': 'F',
            'batch_add_item': [] // 다수품목 대량 insert 필드
        };

        for (var idx in aProductList) {
            aPostData['batch_add_item'].push({
                'product_no': aProductList[idx]['product_no'],
                'variants_code': aProductList[idx]['variants_code'],
                'quantity': aProductList[idx]['quantity'],
                'options': aProductList[idx]['options'], // 상품연동형 옵션정보
                'additional_option_values': aProductList[idx]['additional_option_values'] // 사용자 지정 옵션
            });
        }

        return aPostData;
    },
    getAddSetProductCartParam: function (basket_type, prepaid_shipping_fee, aProductList) {
        var aPostData = {
            'command': 'add',
            'basket_type': basket_type,
            'prd_detail_ship_type': prepaid_shipping_fee,
            'is_set_product': 'T',
            'batch_add_item': [] // 다수품목 대량 insert 필드
        };

        for (var idx in aProductList) {
            aPostData['batch_add_item'].push({
                'product_no': aProductList[idx]['product_no'],
                'quantity': aProductList[idx]['quantity'],
                'bundle_product_components': aProductList[idx]['bundle_product_components'] // 세트 구성 상품
            });
        }

        return aPostData;
    }
};

/**
 * 도메인 개선으로 인한 이미지 로드 이슈 케이스를 수정
 * @type {{bInit: boolean, init: EC_ROUTE_FIX.init}}
 */
EC$(function () {
    var EC_ROUTE_FIX = {
        bInit: false,
        init: function () {
            if (this.bInit || typeof EC_ROUTE === 'undefined') {
                return ;
            }
            this.bInit = true;

            this.setEvent();
            this.setFix();
        },
        setEvent: function () {
            this.setErrorSrcEvent();
            this.setErrorHrefEvent();
        },
        setFix: function ()
        {
            // 스킨 미리보기에서 쿠키가 설정되지 않도록 설정 후 재발할 경우 처리되도록 한다.
            // this.setFixDomainCookie();
        },
        /**
         * EP 캐시 대응용 ECHOSTING-475508
         * 서버사이드 까지 전달되지 못해 url 에 포함된 도메인 정보가 쿠키에 저장되지 못할 경우
         * 실제 유입된 url 과 쿠키에 저장된 도메인 정보가 다를 수 있다.
         * 따라서 쿠키가 존재한다면 쿠키에 저장된 정보를 url 에 따라 캐싱된 ep 에 assign 된 javascript 변수를 사용하여
         * 재 갱신 시켜 준다.
         */
        setFixDomainCookie: function ()
        {
            if (EC$.cookie(EC_ROUTE.EC_DOMAIN_PATH_INFO) === null) {
                return;
            }
            if (typeof EC_ROUTE.isNeedRoute() !== 'undefined' && EC_ROUTE.isNeedRoute() === false) {
                EC$.cookie(EC_ROUTE.EC_DOMAIN_PATH_INFO, null, {path: '/', domain: '.' + window.location.host});
                return;
            }

            var oOverwriteCookie = {}, bWrite = false;
            if (typeof EC_ROUTE.getShopNo() !== 'undefined'
                && EC_ROUTE.getShopNo() > 0) {
                oOverwriteCookie['shop_no'] = EC_ROUTE.getShopNo();
                bWrite = true;
            }
            if (typeof EC_ROUTE.getMobile() !== 'undefined'
            && EC_ROUTE.getMobile() === true) {
                oOverwriteCookie['is_mobile'] = EC_ROUTE.getMobile();
                bWrite = true;
            }
            if (typeof EC_ROUTE.getLanguageCode() !== 'undefined'
            && EC_ROUTE.getLanguageCode() !== 'ZZ') {
                oOverwriteCookie['language_code'] = EC_ROUTE.getLanguageCode();
                bWrite = true;
            }
            if (typeof EC_ROUTE.getSkinCode() !== 'undefined'
            && EC_ROUTE.getSkinCode() !== 'default') {
                oOverwriteCookie['skin_code'] = EC_ROUTE.getSkinCode();
                bWrite = true;
            }

            if (bWrite) {
                EC$.cookie(EC_ROUTE.EC_DOMAIN_PATH_INFO, JSON.stringify(oOverwriteCookie), {path: '/', domain: '.' + window.location.host});
            } else {
                EC$.cookie(EC_ROUTE.EC_DOMAIN_PATH_INFO, null, {path: '/', domain: '.' + window.location.host});
            }
        },
        setErrorSrcEvent: function ()
        {
            EC$("img, input[type='image'], script").on("error", function(e){
                this.onerror = null;
                if (EC$(this).attr('src') != EC_ROUTE.getPrefixUrl(EC$(this).attr('src'))) {
                    this.src = EC_ROUTE.getPrefixUrl(EC$(this).attr('src'));
                }
            });
        },
        setErrorHrefEvent: function ()
        {
            EC$("link").on("error", function(e){
                this.onerror = null;
                if (EC$(this).attr('href') != EC_ROUTE.getPrefixUrl(EC$(this).attr('href'))) {
                    this.href = EC_ROUTE.getPrefixUrl(EC$(this).attr('href'));
                }
            });
        }
    };

    EC_ROUTE_FIX.init();
});

