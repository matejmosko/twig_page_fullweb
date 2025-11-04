$('document').ready(function() {
  baseUrl = "http://localhost/~gnaag/panakrala/";

  /* TURN ALL LINKS TO TARGET="_BLANK" */
  $.expr[':'].external = function(obj) {
    return !obj.href.match(/^mailto\:/) &&
      (obj.hostname != location.hostname);
  };

  $(function() {
    // Add 'external' CSS class to all external links
    $('a:external').addClass('external');

    // turn target into target=_blank for elements w external class
    $(".external").attr('target', '_blank');
  })

  /* PROCESS CONTACT FORM WITH AJAX */

  $('.contactForm').ajaxForm({
    target: ".contactFormResult",
    success: function() {
      console.log('success');
      var btn = document.getElementById("submitContactForm");
      btn.disabled = true;
      $('#submitContactForm').val("Správa bola odoslaná");
    },
    error: function() {}
  });

  /* PROCESS REGISTRATION FORM WITH AJAX */

  $('.regForm').ajaxForm({
    target: ".formResult",
    success: function() {
      ajaxGetEvent($(this).attr("panakrala-projectId"), $(this).attr("panakrala-eventId"));
    },
    error: function() {
      alert("Niekde vo vesmíre sa stalo niečo, čo sa nemalo. To, čo sa malo podariť sa nepodarilo. Skús to urobiť ešte raz.");
    }
  });

  function ajaxGetEvent(projectId, eventId) {
    link = baseUrl + "admin/ajax.php?script=eventGetGuests&eventId=" + eventId + "&projectId=" + projectId;
    $.getJSON(link, function(data) {
      if ($.isEmptyObject(data)) {}
      else {
        var items = [];
        $.each(data, function(key, val) {
          items.push("<li id='" + key + "'>" + val.name + "</li>");
        });

        $("." + eventId + " .guestList").replaceWith("<ol>" + items.join('') + "</ol>");
      }
    });
  }


  /* SHOW/HIDE hiddenInfo on button click */

  $('.hiddenInfo').children('.hideBtn').click(function() {
    var hiddenBox = $(this).closest('.hiddenInfo');
    $('html, body').animate({
      scrollTop: ($(this).offset().top - 60)
    }, 500);
    hiddenBox.children('.foldable').slideToggle('0', "swing");
  });

  $('.scrollToProjects').click(function() {
    $('html, body').animate({
      scrollTop: ($('#projects').offset().top - 60)
    }, 500);
  });

  /* Toggle between adding and removing the "responsive" class to topnav when the user clicks on the icon or on any other element */
  $('#topMenuHamburger').click(function() {
    $(this).parent(".topnav").toggleClass("responsive");
  });

  $('html').click(function(event) {
    if ($(event.target).closest('.topnav').length === 0) {
      $('.topnav').removeClass("responsive");
    }
  });

  /* MOVE OBJECT (KING) WHEN SCROLLING PAGE */

  function kingMove(king) {
    $(king).closest(".kingDiv").css("left", $(window).scrollTop())
    let xs = $(window).scrollTop() / 10 | 0;
    if (xs % 4 == 0) {
      $(king).addClass("kingwalk-2");
      $(king).removeClass("kingwalk-3");
      $(king).removeClass("kingwalk-1");
    }
    else if (xs % 3 == 0) {
      $(king).addClass("kingwalk-3");
      $(king).removeClass("kingwalk-1");
      $(king).removeClass("kingwalk-2");
    }
    else if (xs % 2 == 0) {
      $(king).addClass("kingwalk-1");
      $(king).removeClass("kingwalk-2");
      $(king).removeClass("kingwalk-3");
    }
    else {
      $(king).addClass("kingwalk-2");
      $(king).removeClass("kingwalk-1");
      $(king).removeClass("kingwalk-3");
    }
  }

  /* DISPLAY OBJECTS (TOOLTIPS) WHEN SCROLLING PAGE */

  function kingTooltips(king) {
    let kingPos = $(king).closest(".kingDiv").position().left;
    if (kingPos > 50 && kingPos < 250) {
      $(".kingTip-1").show();
      $(".kingTip-2").hide();
      $(".kingTip-3").hide();
    }
    else if (kingPos > 350 && kingPos < 550) {
      $(".kingTip-1").hide();
      $(".kingTip-2").show();
      $(".kingTip-3").hide();
    }
    else if (kingPos > 750 && kingPos < 950) {
      $(".kingTip-1").hide();
      $(".kingTip-2").hide();
      $(".kingTip-3").show();
    }
    else {
      $(".kingTip-1").hide();
      $(".kingTip-2").hide();
      $(".kingTip-3").hide();
    }
  }

  $(function() {
    $(window).scroll(function() {
      kingMove(".topking");
      kingTooltips(".topking");
    });
    $(document).ready(function() {
      kingMove(".topking");
      kingTooltips(".topking");
    });
  });

  $('.thumb').click(function() {
    $(this).closest('.gallery').children('.galleryPhoto').attr('src', $(this).attr('src'))
  });

  $('.filterBtn').click(function() {
    $(this).parent('div').find('.filterMenu').toggle();
});


  $('.emblemsForm :checkbox').change(function() {
    checkedEmblems = $('.emblemsForm :checkbox:checked');
    if (checkedEmblems.length == 0 || checkedEmblems.length == $('.emblemsForm :checkbox').length) {
      $('.productPreview').show();
    }
    else {
      $('.productPreview').hide();
      checkedEmblems.each(function() {
        $('.productPreview.' + this.value).show();
      });
    }
  });

  function fixDiv() {
    var $cache = $('#filterArea');
    if ($(window).scrollTop() > $("#content").offset().top-100) {
      $cache.addClass("positionFixed shadow");
      $cache.find(".emblemsFilter").removeClass("shadow");
    }
    else {
      $cache.removeClass("positionFixed shadow");
      $cache.find(".emblemsFilter").addClass("shadow");
    }
  }
  $(window).scroll(fixDiv);
  fixDiv();
  
  // FANCYBOX
  
	$('[data-fancybox="gallery"]').fancybox({
		'transitionIn'	:	'elastic',
		'transitionOut'	:	'elastic',
		'speedIn'	:	300, 
		'speedOut'	:	200,
		'loop'		:	true
	});


  /*
    grecaptcha.ready(function() {
      grecaptcha.execute('6LcsPIYUAAAAAMqbNsx-May3Gd7UMglrYCVelRFu', {
          action: 'sendmail'
        })
        .then(function(token) {
          var recaptchaResponse = document.getElementById('recaptchaResponse');
                  recaptchaResponse.value = token;
        });
    });
    */
});
