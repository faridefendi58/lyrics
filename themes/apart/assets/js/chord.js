jQuery(document).ready(function($) {
    $('body').append('<div id="switcher-button" style="background: #00ca9b; text-align: center; padding-top: 12px; width: 50px; height: 50px; color: #ffffff; cursor: pointer; z-index: 560; position: fixed; top: 150px; right: 0px;"><i class="fa fa-music fa-2x"></i></div><div id="switcher" style="width: 285px; position: fixed; overflow-x: hidden; right: -285px; top: 130px; z-index: 560; margin-top: 20px; margin-bottom: 20px;"> <div style="float: left; background: #00ca9b; width: 285px; height: 50px; line-height: 50px; text-align: center; color: #ffffff;"> <h4 style="color:#ffffff; line-height: 50px;">Transpose</h4> </div><div id="switch-content" style="width: 285px; background: white; padding: 10px; float: left;"> <h5 style="margin: 0px 0 10px 0" class="text-bold">Ubah Nada Dasar</h5> <div id="C" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px; margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">C</div><div id="C#" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px; margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">C#</div><div id="D" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px; margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">D</div><div id="D#" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px;margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">D#</div><div id="E" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px;margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">E</div><div id="F" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">F</div><div id="F#" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px; margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">F#</div><div id="G" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px;margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">G</div><div id="G#" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px;margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">G#</div><div id="A" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px;margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">A</div><div id="A#" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-right: 10px;margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">A#</div><div id="B" style="width: 35px; height: 35px; background: #ebebeb; float: left; margin-bottom: 15px; cursor: pointer;padding-top:5px;" class="font-size-18 text-bold text-center">B</div></div></div>');
    $('head').append('<style>.switch-button-label{float:left;font-size:10pt;cursor:pointer}.switch-button-label.off{color:#adadad}.switch-button-label.on{color:#08C}.switch-button-background{float:left;position:relative;background:#ccc;border:1px solid #aaa;margin:1px 10px;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;cursor:pointer}.switch-button-button{position:absolute;left:-1px;top:-1px;background:#FAFAFA;border:1px solid #aaa;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;}.current-basic-tone{background:#f0ad4e !important;color:#ffffff;}</style>');
    $("input#boxed").switchButton({on_label: " BOXED",off_label: "WIDE ",width: 125, height: 25, button_width: 30, checked: false });
    if( w_height - 150 < $("#switcher").innerHeight() ) {
        $("#switcher").css('height', w_height - 150 + "px");
        $("#switcher").css('overlow-y', "scroll");
    }
    $("#switcher-button").click( function () {
        if( $("#switcher").hasClass("open") ) {
            $("#switcher").removeClass("open");
            $("#switcher").animate( { "right": "-285px" }, 500);
            $("#switcher-button").animate( { "right": "0px" }, 500);
            $("#switch-content").css("box-shadow", "none");
            $(this).find('i').removeClass('fa-times').addClass('fa-music');
        } else {
            $("#switcher").addClass("open");
            $("#switcher").animate( { "right": "0px" }, 500);
            $("#switcher-button").animate( { "right": "285px" }, 500);
            $("#switch-content").css("box-shadow", "0px 5px 5px 0px rgba(0, 0, 0, 0.35)");
            $("#switch-content").css("border-bottom", "1px solid #ebebeb");
            $("#switch-content").css("border-left", "1px solid #ebebeb");
            $(this).find('i').removeClass('fa-music').addClass('fa-times');
        }
    });
    $("#scroll-button").click( function () {
        if( $("#scroll").hasClass("open") ) {
            $("#scroll").removeClass("open");
            $("#scroll").animate( { "right": "-100px" }, 500);
            $("#scroll-button").animate( { "right": "0px" }, 500);
            $("#scroll-content").css("box-shadow", "none");
            $(this).find('i').addClass('hidden');
        } else {
            $("#scroll").addClass("open");
            $("#scroll").animate( { "right": "0px" }, 80);
            $("#scroll-button").animate( { "right": "100px" }, 500);
            $(this).find('i').removeClass('hidden');
        }
    });
    build_chord();
    if ($('a.chord').length > 0) {
        var nada_dasar = clean_basic_tone($('a.chord').first().text());

        $('#switch-content').find('div[id="'+ nada_dasar +'"]').addClass('current-basic-tone');
        $('#switch-content').find('div').click(function () {
            transpose(clean_basic_tone($('a.chord').first().text()),$(this).text());
            $('#switch-content').find('.current-basic-tone').removeClass('current-basic-tone');
            $(this).addClass('current-basic-tone');
        });
    }
});
function clean_basic_tone(nada_dasar) {
    if (nada_dasar.length > 1 && nada_dasar.charAt(1) != '#') {
        $('#switch-content').find('div').each(function () {
            if (!$(this).hasClass('font-size-14')) {
                $(this).text($(this).text()+nada_dasar.substr(1));
                $(this).removeClass('font-size-18').addClass('font-size-14');
            }
        });
        nada_dasar = nada_dasar.charAt(0);
    }
    if (nada_dasar.length > 2 && nada_dasar.charAt(1) == '#') {
        nada_dasar = nada_dasar.charAt(0)+'#';
    }
    return nada_dasar;
}
function transpose(basic_tone,new_basic_tone) {
    var bobots = ['C','C#','D','D#','E','F','F#','G','G#','A','A#','B'];
    var index_basic_tone = bobots.indexOf(basic_tone);
    var index_new_basic_tone = bobots.indexOf(clean_basic_tone(new_basic_tone));
    var transpose_val = (index_basic_tone - index_new_basic_tone)/2;
    $('a.chord').each(function () {
        var chord = $(this).text();
        var index = bobots.indexOf(chord);
        if (index < 0) {
            if (chord.length > 2) {
                if (chord.charAt(1) == '#') {
                    index = bobots.indexOf(chord.charAt(0)+chord.charAt(1));
                } else {
                    index = bobots.indexOf(chord.charAt(0));
                }
            } else if (chord.length == 2) {
                index = bobots.indexOf(chord.charAt(0));
            }
        }

        var selisih = index - transpose_val*2;
        if (selisih < 0) {
            selisih = bobots.length + selisih;
        } else {
            if (selisih > 11) {
                selisih = selisih - 12;
            }
        }

        var suffix = "";
        if (chord.length > 2) {
            suffix = chord.substr(1);
            if (chord.charAt(1) == '#') {
                suffix = chord.substr(2);
            }
        } else if (chord.length == 2) {
            if (chord.charAt(1) != '#') {
                suffix = chord.substr(1);
            }
        }

        var new_chord = bobots[selisih]+suffix;
        if (new_chord == 'E#')
            new_chord = 'F';
        if (new_chord == 'B#')
            new_chord = 'C';

        if ($(this).text().length > 0) {
            $(this).text(new_chord);
        }
        resetPosition();
    });
    onAfterTransposed();
}
function build_chord() {
    $('head').append('<style>.blog-text sup {top: -1.3em;font-size: 14px;}.blog-text p {line-height: 2.7;}</style>');
    /*var blog_text = $('.blog-text').html().replaceAll("[",'<sup><a href="#" class="chord">');
    var blog_text = blog_text.replaceAll("]",'</a></sup>');
    var blog_text = blog_text.replaceAll("{", '<a href="#" class="chord">');
    var blog_text = blog_text.replaceAll("}",'</a>');
    $('.blog-text').html(blog_text);*/
    $('.blog-text').find('a').each(function () {
        $(this).addClass($(this).text());
    });
    resetPosition();
}
function resetPosition() {
    $('.blog-text').find('a').each(function () {
        if ($(this).parent()[0].nodeName.toLowerCase() == 'sup') {
            if ($(this).text().length > 0) {
                $(this).parent().attr('style', 'width:0px;height:0px;display:inline-block;');
            } else {
                $(this).parent().attr('style', 'width:20px;height:0px;display:inline-block;');
            }
        }
    });
}
function onAfterTransposed() {}
String.prototype.replaceAll = function (stringFind, stringReplace) {
    var ex = new RegExp(stringFind.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1"), "g");
    return this.replace(ex, stringReplace);
};

/**
 * jquery.switchButton.js v1.0
 * jQuery iPhone-like switch button
 * @author Olivier Lance <olivier.lance@sylights.com>
 */

!function(t){t.widget("sylightsUI.switchButton",{options:{checked:void 0,show_labels:!0,labels_placement:"both",on_label:"ON",off_label:"OFF",width:25,height:11,button_width:12,clear:!0,clear_after:null,on_callback:void 0,off_callback:void 0},_create:function(){void 0===this.options.checked&&(this.options.checked=this.element.prop("checked")),this._initLayout(),this._initEvents()},_initLayout:function(){this.element.hide(),this.off_label=t("<span>").addClass("switch-button-label"),this.on_label=t("<span>").addClass("switch-button-label"),this.button_bg=t("<div>").addClass("switch-button-background"),this.button=t("<div>").addClass("switch-button-button"),this.off_label.insertAfter(this.element),this.button_bg.insertAfter(this.off_label),this.on_label.insertAfter(this.button_bg),this.button_bg.append(this.button),this.options.clear&&(null===this.options.clear_after&&(this.options.clear_after=this.on_label),t("<div>").css({clear:"left"}).insertAfter(this.options.clear_after)),this._refresh(),this.options.checked=!this.options.checked,this._toggleSwitch()},_refresh:function(){switch(this.options.show_labels?(this.off_label.show(),this.on_label.show()):(this.off_label.hide(),this.on_label.hide()),this.options.labels_placement){case"both":(this.button_bg.prev()!==this.off_label||this.button_bg.next()!==this.on_label)&&(this.off_label.detach(),this.on_label.detach(),this.off_label.insertBefore(this.button_bg),this.on_label.insertAfter(this.button_bg),this.on_label.addClass(this.options.checked?"on":"off").removeClass(this.options.checked?"off":"on"),this.off_label.addClass(this.options.checked?"off":"on").removeClass(this.options.checked?"on":"off"));break;case"left":(this.button_bg.prev()!==this.on_label||this.on_label.prev()!==this.off_label)&&(this.off_label.detach(),this.on_label.detach(),this.off_label.insertBefore(this.button_bg),this.on_label.insertBefore(this.button_bg),this.on_label.addClass("on").removeClass("off"),this.off_label.addClass("off").removeClass("on"));break;case"right":(this.button_bg.next()!==this.off_label||this.off_label.next()!==this.on_label)&&(this.off_label.detach(),this.on_label.detach(),this.off_label.insertAfter(this.button_bg),this.on_label.insertAfter(this.off_label),this.on_label.addClass("on").removeClass("off"),this.off_label.addClass("off").removeClass("on"))}this.on_label.html(this.options.on_label),this.off_label.html(this.options.off_label),this.button_bg.width(this.options.width),this.button_bg.height(this.options.height),this.button.width(this.options.button_width),this.button.height(this.options.height)},_initEvents:function(){var t=this;this.button_bg.click(function(e){return e.preventDefault(),e.stopPropagation(),t._toggleSwitch(),!1}),this.button.click(function(e){return e.preventDefault(),e.stopPropagation(),t._toggleSwitch(),!1}),this.on_label.click(function(){return t.options.checked&&"both"===t.options.labels_placement?!1:(t._toggleSwitch(),!1)}),this.off_label.click(function(){return t.options.checked||"both"!==t.options.labels_placement?(t._toggleSwitch(),!1):!1})},_setOption:function(t,e){return"checked"===t?void this._setChecked(e):(this.options[t]=e,void this._refresh())},_setChecked:function(t){t!==this.options.checked&&(this.options.checked=!t,this._toggleSwitch())},_toggleSwitch:function(){this.options.checked=!this.options.checked;var t="";if(this.options.checked){this.element.prop("checked",!0),this.element.change();var e=this.options.width-this.options.button_width;t="+="+e,"both"==this.options.labels_placement?(this.off_label.removeClass("on").addClass("off"),this.on_label.removeClass("off").addClass("on")):(this.off_label.hide(),this.on_label.show()),this.button_bg.addClass("checked"),"function"==typeof this.options.on_callback&&this.options.on_callback.call(this)}else this.element.prop("checked",!1),this.element.change(),t="-1px","both"==this.options.labels_placement?(this.off_label.removeClass("off").addClass("on"),this.on_label.removeClass("on").addClass("off")):(this.off_label.show(),this.on_label.hide()),this.button_bg.removeClass("checked"),"function"==typeof this.options.off_callback&&this.options.off_callback.call(this);this.button.animate({left:t},250,"easeInOutCubic")}})}(jQuery);