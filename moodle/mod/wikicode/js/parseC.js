/*
 Simple parser for C
 Written for C 5.1, based on parsecss and other parsers.

 to make this parser highlight your special functions pass table with this functions names to parserConfig argument of creator,
 
 @author Antonio J. González
  
 parserConfig: ["myfunction1","myfunction2"],
 */

function findFirstRegexp(words) {
    return new RegExp("^(?:" + words.join("|") + ")", "i");
}

function matchRegexp(words) {
    return new RegExp("^(?:" + words.join("|") + ")$", "i");
}
 
var luaCustomFunctions= matchRegexp([]);
 
function configureLUA(parserConfig){
  if(parserConfig)
  luaCustomFunctions= matchRegexp(parserConfig);
}

//long list of standard functions from lua manual
var luaStdFunctions = matchRegexp([
// assert.h
            "assert",

            //complex.h
            "cabs", "cacos", "cacosh", "carg", "casin", "casinh", "catan",
            "catanh", "ccos", "ccosh", "cexp", "cimag", "cis", "clog", "conj",
            "cpow", "cproj", "creal", "csin", "csinh", "csqrt", "ctan", "ctanh",

            //ctype.h
            "digittoint", "isalnum", "isalpha", "isascii", "isblank", "iscntrl",
            "isdigit", "isgraph", "islower", "isprint", "ispunct", "isspace",
            "isupper", "isxdigit", "toascii", "tolower", "toupper",

            //inttypes.h
            "imaxabs", "imaxdiv", "strtoimax", "strtoumax", "wcstoimax",
            "wcstoumax",

            //locale.h
            "localeconv", "setlocale",

            //math.h
            "acos", "asin", "atan", "atan2", "ceil", "cos", "cosh", "exp",
            "fabs", "floor", "frexp", "ldexp", "log", "log10", "modf", "pow",
            "sin", "sinh", "sqrt", "tan", "tanh",

            //setjmp.h
            "longjmp", "setjmp",

            //signal.h
            "raise",

            //stdarg.h
            "va_arg", "va_copy", "va_end", "va_start",

            //stddef.h
            "offsetof",

            //stdio.h
            "clearerr", "fclose", "fdopen", "feof", "ferror", "fflush", "fgetc",
            "fgetpos", "fgets", "fopen", "fprintf", "fputc", "fputchar",
            "fputs", "fread", "freopen", "fscanf", "fseek", "fsetpos", "ftell",
            "fwrite", "getc", "getch", "getchar", "gets", "perror", "printf",
            "putc", "putchar", "puts", "remove", "rename", "rewind", "scanf",
            "setbuf", "setvbuf", "snprintf", "sprintf", "sscanf", "tmpfile",
            "tmpnam", "ungetc", "vfprintf", "vfscanf", "vprintf", "vscanf",
            "vsprintf", "vsscanf",

            //stdlib.h
            "abort", "abs", "atexit", "atof", "atoi", "atol", "bsearch",
            "calloc", "div", "exit", "free", "getenv", "itoa", "labs", "ldiv",
            "ltoa", "malloc", "qsort", "rand", "realloc", "srand", "strtod",
            "strtol", "strtoul", "system",

            //string.h
            "memchr", "memcmp", "memcpy", "memmove", "memset", "strcat",
            "strchr", "strcmp", "strcoll", "strcpy", "strcspn", "strerror",
            "strlen", "strncat", "strncmp", "strncpy", "strpbrk", "strrchr",
            "strspn", "strstr", "strtok", "strxfrm",

            //time.h
            "asctime", "clock", "ctime", "difftime", "gmtime", "localtime",
            "mktime", "strftime", "time",

            //wchar.h
            "btowc", "fgetwc", "fgetws", "fputwc", "fputws", "fwide",
            "fwprintf", "fwscanf", "getwc", "getwchar", "mbrlen", "mbrtowc",
            "mbsinit", "mbsrtowcs", "putwc", "putwchar", "swprintf", "swscanf",
            "ungetwc", "vfwprintf", "vswprintf", "vwprintf", "wcrtomb",
            "wcscat", "wcschr", "wcscmp", "wcscoll", "wcscpy", "wcscspn",
            "wcsftime", "wcslen", "wcsncat", "wcsncmp", "wcsncpy", "wcspbrk",
            "wcsrchr", "wcsrtombs", "wcsspn", "wcsstr", "wcstod", "wcstok",
            "wcstol", "wcstoul", "wcsxfrm", "wctob", "wmemchr", "wmemcmp",
            "wmemcpy", "wmemmove", "wmemset", "wprintf", "wscanf",

            //wctype.h
            "iswalnum", "iswalpha", "iswcntrl", "iswctype", "iswdigit",
            "iswgraph", "iswlower", "iswprint", "iswpunct", "iswspace",
            "iswupper", "iswxdigit", "towctrans", "towlower", "towupper",
            "wctrans", "wctype"
]);



var luaKeywords = matchRegexp(["if", "return", "while", "case", "continue", "default",
            "do", "else", "for", "switch", "goto", "null", "false", "break", "true", "function", "enum", "extern", "inline",
            "auto", "char", "const", "double",  "float", "int", "long",
            "register", "short", "signed", "sizeof", "static", "struct",
            "typedef", "union", "unsigned", "void", "volatile", "wchar_t",

            "int8", "int16", "int32", "int64",
            "uint8", "uint16", "uint32", "uint64",

            "int_fast8_t", "int_fast16_t", "int_fast32_t", "int_fast64_t",
            "uint_fast8_t", "uint_fast16_t", "uint_fast32_t", "uint_fast64_t",

            "int_least8_t", "int_least16_t", "int_least32_t", "int_least64_t",
            "uint_least8_t", "uint_least16_t", "uint_least32_t", "uint_least64_t",

            "int8_t", "int16_t", "int32_t", "int64_t",
            "uint8_t", "uint16_t", "uint32_t", "uint64_t",

            "intmax_t", "uintmax_t", "intptr_t", "uintptr_t",
            "size_t", "off_t" ]);

var luaIndentKeys = matchRegexp(["[\(]", "{"]);
var luaUnindentKeys = matchRegexp(["[\)]", "}"]);

var luaUnindentKeys2 = findFirstRegexp(["[\)]", "}"]);
var luaMiddleKeys = findFirstRegexp(["else"]);

var LUAParser = Editor.Parser = (function() {
   var tokenizeLUA = (function() {
      function normal(source, setState) {
         var ch = source.next();
         
         if (source.equals('!') && ch == "<") {
         	setState(inLock);
         	return null;
         }
         
         if (ch == "#") {
         	setState(inSLDefine);
         	return null;
         }

         if (ch == "/" && source.equals("/")) {
           source.next();
           setState(inSLComment);
           return null;
         }
         else if (ch == "\"" || ch == "'") {
            setState(inString(ch));
            return null;
         }
        
         if (source.equals('*')) {
         	setState(inMLComment);
   	        return null;
         }
         
         if (ch == "=") {
            if (source.equals("="))
               source.next();
               return "c-token";
         }
         else if (ch == ".") {
            if (source.equals("."))
               source.next();
            if (source.equals("."))
               source.next();
            return "c-token";
         }
         else if (ch == "+" || ch == "-" || ch == "*" || ch == "/" || ch == "%" || ch == "^" ) {
            return "c-token";
         }
         else if (ch == ">" || ch == "<" || ch == "(" || ch == ")" || ch == "{" || ch == "}" || ch == "[" ) {
            return "c-token";
         }
         else if (ch == "]" || ch == ";" || ch == ":" || ch == ",") {
            return "c-token";
         }
         else if (source.equals("=") && (ch == "~" || ch == "<" || ch == ">")) {
            source.next();
            return "c-token";
         }
         else if (/\d/.test(ch)) {
            source.nextWhileMatches(/[\w.%]/);
            return "c-number";
         }
         else {
            source.nextWhileMatches(/[\w\\\-_.]/);
            return "c-identifier";
         }
      }
      
      function inMLComment(source, setState){
         
         while (!source.endOfLine()) {
         	var ant = next;
            var next = source.next();
            
            if ((ant == "*") && (next == "/")){
               setState(normal);
               break;
            }
         }
        
         return "c-comment";
      }
      
      function inLock(source, setState){
         
         while (!source.endOfLine()) {
         	var ant = next;
            var next = source.next();
            
            if ((ant == "!") && (next == ">")){
               setState(normal);
               break;
            }
         }
        
         return "c-lock";
      }
      
      function inSLDefine(source, setState) {
      	 var start = true;
         var count=0;
      
         while (!source.endOfLine()) {
            var ch = source.next();
            var level = 0;
    
            if ((ch =="[") && start)
               while(source.equals("=")){
                  source.next();
                  level++;
               }
         
            if (source.equals("[")){
               setState(inMLSomething(level,"c-define"));
               return null;
            }
      
            start = false;  
         }
   
         setState(normal);          
         return "c-define";
      }
 
      function inSLComment(source, setState) {
         var start = true;
         var count=0;
      
         while (!source.endOfLine()) {
            var ch = source.next();
            var level = 0;
    
            if ((ch =="/") && start)
               while(source.equals("=")){
                  source.next();
                  level++;
               }
         
            if (source.equals("[")){
               setState(inMLSomething(level,"c-comment"));
               return null;
            }
      
            start = false;  
         }
   
         setState(normal);          
         return "c-comment";
  
      }

      function inMLSomething(level,what) {
      //wat sholud be "c-string" or "c-comment", level is the number of "=" in opening mark.
         return function(source, setState){
            var dashes = 0;
            while (!source.endOfLine()) {
               var ch = source.next();
        
               if (dashes == level+1 && ch == "/" ) {
                  setState(normal);
                  break;
               }
    
               if (dashes == 0)
                  dashes = (ch == "/") ? 1:0;
               else
                  dashes = (ch == "*") ? dashes + 1 : 0;
            }
            
            return what;
         }
      }

      function inString(quote) {
         return function(source, setState) {
            var escaped = false;
            
            while (!source.endOfLine()) {               
               var ch = source.next();
               if (ch == quote && !escaped)
                  break;
               escaped = !escaped && ch == "\\";
            }
        
            if (!escaped)
               setState(normal);
            return "c-string";
         };
      }

      return function(source, startState) {
         return tokenizer(source, startState || normal);
      };
   })();

   function indentLUA(indentDepth, base) {
     return function(nextChars) {
        var closing = (luaUnindentKeys2.test(nextChars) || luaMiddleKeys.test(nextChars));
          
        return base + ( indentUnit * (indentDepth - (closing?1:0)) );
     };
   }

  
   function parseLUA(source,basecolumn) {
      basecolumn = basecolumn || 0;
    
      var tokens = tokenizeLUA(source);
      var indentDepth = 0;

      var iter = {
         next: function() {
            var token = tokens.next(), style = token.style, content = token.content;
  
            if (style == "c-identifier" && luaKeywords.test(content)) {
               token.style = "c-keyword";
            }  
            if (style == "c-identifier" && luaStdFunctions.test(content)){
               token.style = "c-stdfunc";
            }
            if (style == "c-identifier" && luaCustomFunctions.test(content)){
               token.style = "c-customfunc";
            }
            
            if (luaIndentKeys.test(content))
               indentDepth++;
            else if (luaUnindentKeys.test(content))
               indentDepth--;
        
            if (content == "\n")
               token.indentation = indentLUA( indentDepth, basecolumn);

            return token;
         },

         copy: function() {
            var  _tokenState = tokens.state, _indentDepth = indentDepth;
            return function(source) {
               tokens = tokenizeLUA(source, _tokenState);
      
               indentDepth = _indentDepth;
               return iter;
            };
         }
      };
      return iter;
   }

   return {make: parseLUA, configure:configureLUA, electricChars: "delf})"};   //en[d] els[e] unti[l] elsei[f]  // this should be taken from Keys keywords
})();
