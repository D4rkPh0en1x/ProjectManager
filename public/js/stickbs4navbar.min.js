/* ========================================================================
 * Tutorial specific Javascript
 * 
 * ========================================================================
 * Copyright 2015 Bootbites.com (unless otherwise stated)
 * For license information see: http://bootbites.com/license
 * ======================================================================== */// Sticky navbar
// =========================
$(document).ready(function(){var e=function(e,t,n){var r=e.outerHeight(),i=t.offset().top;if(n.scrollTop()>=i){t.height(r);e.addClass("is-sticky")}else{e.removeClass("is-sticky");t.height("auto")}};$('[data-toggle="sticky-onscroll"]').each(function(){var t=$(this),n=$("<div>").addClass("sticky-wrapper");t.before(n);t.addClass("sticky");$(window).on("scroll.sticky-onscroll resize.sticky-onscroll",function(){e(t,n,$(this))});e(t,n,$(window))})});