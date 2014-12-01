ace.define("ace/theme/zenburn",["require","exports","module","ace/lib/dom"], function(require, exports, module) {

exports.isDark = true;
exports.cssClass = "ace-zenburn";
exports.cssText = ".ace-zenburn .ace_gutter,\
.ace-zenburn .ace_gutter {\
background-color: #333333;\
// color: #FF00FF\
}\
.ace-zenburn .ace_print-margin {\
width: 1px;\
background: #333333\
}\
.ace-zenburn .ace_scroller {\
// background-color: #FF00FF\
}\
.ace-zenburn .ace_text-layer {\
// color: #FF00FF\
}\
.ace-zenburn .ace_cursor {\
border-left: 2px solid #FFFFFF\
}\
.ace-zenburn .ace_overwrite-cursors .ace_cursor {\
border-left: 0px;\
border-bottom: 1px solid #FFFFFF\
}\
.ace-zenburn .ace_marker-layer .ace_selection {\
background: rgba(131, 131, 131, 0.23)\
}\
.ace-zenburn.ace_multiselect .ace_selection.ace_start {\
box-shadow: 0 0 3px 0px #FF00FF;\
border-radius: 2px\
}\
.ace-zenburn .ace_marker-layer .ace_step {\
background: rgb(198, 219, 174)\
}\
.ace-zenburn .ace_marker-layer .ace_bracket {\
margin: -1px 0 0 -1px;\
border: 1px solid rgba(165, 165, 165, 0.32)\
}\
.ace-zenburn .ace_marker-layer .ace_active-line {\
background: rgba(48, 48, 48, 0.32)\
}\
.ace-zenburn .ace_gutter-active-line {\
background-color: rgba(48, 48, 48, 0.32)\
}\
.ace-zenburn .ace_marker-layer .ace_selected-word {\
border: 1px solid rgba(131, 131, 131, 0.23)\
}\
.ace-zenburn .ace_fold {\
background-color: #FF00FF;\
border-color: #FF00FF\
}\
.ace-zenburn .ace_entity.ace_other.ace_attribute-name,\
.ace-zenburn .ace_keyword,\
.ace-zenburn .ace_variable.ace_parameter {\
color: #CDBFA3\
}\
.ace-zenburn .ace_keyword.ace_operator {\
color: #97C0EB\
}\
.ace-zenburn .ace_constant.ace_language {\
color: #00005E\
}\
.ace-zenburn .ace_constant.ace_numeric {\
color: #F79B57\
}\
.ace-zenburn .ace_constant.ace_character,\
.ace-zenburn .ace_constant.ace_other,\
.ace-zenburn .ace_support.ace_constant {\
color: #FF00FF\
}\
.ace-zenburn .ace_support.ace_function {\
color: #7CC8CF\
}\
.ace-zenburn .ace_storage.ace_type,\
.ace-zenburn .ace_support.ace_type {\
color: #FF00FF\
}\
.ace-zenburn .ace_invalid {\
text-decoration: underline;\
font-style: italic;\
color: #FF00FF\
}\
.ace-zenburn .ace_string {\
color: #CC9893\
}\
.ace-zenburn .ace_string.ace_regexp {\
color: #FF00FF\
}\
.ace-zenburn .ace_comment {\
color: #7F9F7F\
}\
.ace-zenburn .ace_entity.ace_name.ace_tag {\
color: #FF00FF\
}";

var dom = require("../lib/dom");
dom.importCssString(exports.cssText, exports.cssClass);
});
