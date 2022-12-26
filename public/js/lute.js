/* Lute js.  Moving things over as needed from existing js files. */

/** 
 * Prepare the interaction events with the text.
 */
function prepareTextInteractions(textid) {
  const t = $('#thetext');
  // Using "t.on" here because .word elements
  // are added and removed dynamically, and "t.on"
  // ensures that events remain for each element.
  t.on('click', '.word', word_clicked);
  t.on('mousedown', '.word', select_started);
  t.on('mouseover', '.word', select_over);
  t.on('mouseup', '.word', select_ended);

  $(document).on('keydown', handle_keydown);

  $('#thetext').tooltip({
    position: { my: 'left top+10', at: 'left bottom', collision: 'flipfit' },
    items: '.hword',
    show: { easing: 'easeOutCirc' },
    content: function () { return tooltip_wsty_content($(this)); }
  });

  $('#thetext').hoverIntent(
    {
      over: word_hover_over, 
      out: word_hover_out, 
      interval: 150, 
      selector:".word:not(.status0)"
    }
  );

}

let word_hover_over = function() {
  $(this).addClass('hword');
  $(this).trigger('mouseover');
}

let word_hover_out = function() {
  $('.hword').removeClass('hword');
  $('.ui-helper-hidden-accessible>div[style]').remove();
}


let tooltip_wsty_content = function (el) {
  let content = `<p><b style="font-size:120%">${el.text()}</b></p>`;

  const roman = el.attr('data_rom');
  if (roman != '') {
    content += '<p><b>Roman.</b>: ' + roman + '</p>';
  }

  const trans = el.attr('data_trans');
  if (trans != '' && trans != '*') {
    content += '<p><b>Transl.</b>: ' + trans + '</p>';
  }

  const status = parseInt(el.attr('data_status'));
  const st = STATUSES[status];
  const statname = `${st['name']} [${st['abbr']}]`;
  content += `<p><b>Status</b>: <span class="status${status}">${statname}</span></p>`;

  const parent_text = el.attr('parent_text')
  if (parent_text && parent_text != '') {
    content += '<hr /><p><i>Parent term:</i></p>';
    content += "<p><b style='font-size:120%'>" + el.attr('parent_text') + "</b></p>";
    let ptrans = el.attr('parent_trans');
    content += '<p><b>Transl.</b>: ' + ptrans + '</p>';
  }

  return content;
}


function showEditFrame(el, extra_args = {}) {

  const int_attr = function(name) {
    let ret = el.attr(name);
    if (!ret || ret == '')
      return 0;
    return parseInt(ret);
  };
  const wid = int_attr('data_wid');
  const tid = int_attr('tid');
  const ord = int_attr('data_order');
  const text = encodeURIComponent(extra_args.text ?? '-');

  let extras = Object.entries(extra_args).
      map((p) => `${p[0]}=${encodeURIComponent(p[1])}`).
      join('&');

  const url = `/read/termform/${wid}/${tid}/${ord}/${text}?${extras}`;
  top.frames.wordframe.location.href = url;
}

function show_help() {
  const url = `/read/shortcuts`;
  top.frames.wordframe.location.href = url;
}


function add_active(e) {
  e.addClass('kwordmarked');
}


function mark_active(e) {
  $('span.kwordmarked').removeClass('kwordmarked');
  e.addClass('kwordmarked');
}

function word_clicked(e) {
  if (e.shiftKey) {
    // console.log('shift click, adding to ' + $(this).text());
    add_active($(this));
  }
  else {
    mark_active($(this));
    showEditFrame($(this));
  }
}

let selection_start_el = null;

function select_started(e) {
  // mark_active($(this));
  $(this).addClass('newmultiterm');
  selection_start_el = $(this);
}

function select_over(e) {
  if (selection_start_el == null)
    return;  // Not selecting

  const startord = parseInt(selection_start_el.attr('data_order'))
  const endord = parseInt($(this).attr('data_order'));
  const selected = $("span.word").filter(function() {
    const ord = $(this).attr("data_order");
    return ord >= startord && ord <= endord;
  });
  selected.addClass('newmultiterm');

  const notselected = $("span.word").filter(function() {
    const ord = $(this).attr("data_order");
    return ord < startord || ord > endord;
  });
  notselected.removeClass('newmultiterm');
}

function select_ended(e) {

  const clear_newmultiterm_elements = function() {
    $('.newmultiterm').removeClass('newmultiterm');
    selection_start_el = null;
  }
  
  if (selection_start_el.attr('id') == $(this).attr('id')) {
    clear_newmultiterm_elements();
    return;
  }

  if (selection_start_el.attr('seid') != $(this).attr('seid')) {
    alert("Selections cannot span sentences.");
    clear_newmultiterm_elements();
    return;
  }

  const startord = parseInt(selection_start_el.attr('data_order'));
  const endord = parseInt($(this).attr('data_order'));
  const selected = $("span.word").filter(function() {
    const ord = $(this).attr("data_order");
    return ord >= startord && ord <= endord;
  });
  const text = selected.toArray().map((el) => $(el).text()).join('');

  if (text.length > 250) {
    alert(`Selections can be max length 250 chars ("${text}" is ${text.length} chars)`);
    clear_newmultiterm_elements();
    return;
  }

  showEditFrame(selection_start_el, { text: text });
  clear_newmultiterm_elements();
}

/********************************************/
// Keyboard navigation.

// Load all words into scope.
var words = null;
var maxindex = null;

// A public function because this is called from
// read/updated.html.twig, when elements are added/removed.
function load_reading_pane_globals() {
  // console.log('loading reading pane globals');
  words = $('span.word').sort(function(a, b) {
    return $(a).attr('data_order') - $(b).attr('data_order');
  });
  // console.log('have ' + words.size() + ' words');
  maxindex = words.size() - 1;
}

$(document).ready(load_reading_pane_globals);


let current_word_index = function() {
  var currmarked = $('span.kwordmarked');
  if (currmarked.length == 0) {
    return -1;
  }
  if (currmarked.length > 1) {
    // console.log('multiple marked, using the first one.');
    currmarked = currmarked.first();
  }
  const ord = currmarked.attr('data_order');
  const i = words.toArray().findIndex(x => x.getAttribute('data_order') === ord);
  // console.log(`Current index: ${i}`);
  return i;
};


let find_next_non_ignored_non_well_known = function(currindex, shiftby = 1) {
  let newindex = currindex + shiftby;
  while (newindex >= 0 && newindex <= maxindex) {
    const nextword = words.eq(newindex);
    const st = nextword.attr('data_status');
    if (st != 99 && st != 98) {
      break;
    }
    newindex += shiftby;
  }
  return newindex;
};


let next_unknown_word_index = function(currindex) {
  let newindex = currindex + 1;
  while (newindex <= maxindex) {
    const nextword = words.eq(newindex);
    const st = nextword.attr('data_status');
    if (st == 0) {
      break;
    }
    newindex += 1;
  }
  return newindex;
}


function handle_keydown (e) {
  if (words.size() == 0) {
    // console.log('no words, exiting');
    return; // Nothing to do.
  }

  // Keys handled in this routine:
  const kESC = 27;
  const kHOME = 36;
  const kEND = 35;
  const kLEFT = 37;
  const kRIGHT = 39;
  const kRETURN = 13;
  const kE = 69; // E)dit
  const kT = 84; // T)ranslate

  const currindex = current_word_index();
  let newindex = currindex;

  if (e.which == kESC) {
    $('span.kwordmarked').removeClass('kwordmarked');
    return;
  }
  if (e.which == kHOME) {
    newindex = 0;
  }
  if (e.which == kEND) {
    newindex = maxindex;
  }
  if (e.which == kLEFT && !e.shiftKey) {
    newindex = currindex - 1;
  }
  if (e.which == kRIGHT && !e.shiftKey) {
    newindex = currindex + 1;
  }
  if (e.which == kLEFT && e.shiftKey) {
    newindex = find_next_non_ignored_non_well_known(currindex, -1);
  }
  if (e.which == kRIGHT && e.shiftKey) {
    newindex = find_next_non_ignored_non_well_known(currindex, +1);
  }
  if (e.which == kRETURN) {
    newindex = next_unknown_word_index(currindex);
  }

  if (newindex < 0) {
    newindex = 0;
  }
  if (newindex > maxindex) {
    newindex = maxindex;
  }

  // If moved, update UI and exit.
  if (newindex != currindex) {
    // console.log(`Moving from index ${currindex} to ${newindex}`);
    let curr = words.eq(newindex);
    mark_active(curr);
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });

    showEditFrame(curr, { autofocus: false });
    return false;
  }

  let curr = $('span.kwordmarked');
  if (curr.length == 0)
    return;

  if (e.which == kE) {
    showEditFrame(curr[0]);
    return false;
  }

  // Statuses.
  const status_key_map = {
    49: 1,  // key 1
    50: 2,  // key 2
    51: 3,
    52: 4,
    53: 5,
    73: 98, // key I)gnore
    87: 99  // key W)ell known
  };
  var newstatus = status_key_map[e.which] ?? 0;
  if (newstatus != 0) {
    update_status_for_marked_elements(newstatus);
    return;
  }

  if (e.which == kT) {
    // TODO:translation
    /*
    const trans = 'trans.php?i=' + ord + '&t=' + tid;
    const userdict = $('#translateURL').val();
    if (userdict.substr(0, 5) == '*http') {
      const settings = 'width=800, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no';
      window.open(trans, 'dictwin', settings);
    }
    else {
      showDictionaryFrame(trans);
    }
    return false;
    */
  }

  // Not ported (yet?)
  /*
  if (e.which == 80) { // P : pronounce term
    const lg = getLangFromDict(WBLINK3);
    readTextAloud(txt, lg);
    return false;
  }
  */

  /*
  if (e.which == 65) { // A : set audio pos.
    let p = curr.attr('data_pos');
    const t = parseInt($('#totalcharcount').text(), 10);
    if (t == 0) return true;
    p = 100 * (p - 5) / t;
    if (p < 0) p = 0;
    if (typeof (window.parent.frames.h.new_pos) === 'function') { 
      window.parent.frames.h.new_pos(p); 
    } else { 
      return true; 
    }
    return false;
  }
  */
  
  return true;
}


/**
 * post update ajax call, fix the UI.
 */
function update_selected_statuses(newStatus) {
  const newClass = `status${newStatus}`;
  $('span.kwordmarked').each(function (e) {
    const curr = $(this);
    ltext = curr.text().toLowerCase();
    matches = $('span.word').toArray().filter(el => $(el).text().toLowerCase() == ltext);
    matches.forEach(function (m) {
      $(m).removeClass('status98 status99 status1 status2 status3 status4 status5 shiftClicked')
        .addClass(newClass)
        .attr('data_status',`${newStatus}`);
    });
  });
}


function update_status_for_marked_elements(new_status) {
  const els = $('span.kwordmarked').toArray().map(el => $(el).text());
  if (els.length == 0)
    return;
  const textid = $('span.kwordmarked').first().attr('tid');
  // console.log('To update to ' + newStatus + ': ' + els.join(', '));

  $.ajax({
    url: '/read/update_status',
    type: 'post',
    data: { textid: textid, terms: els, new_status: new_status },
    dataType: 'JSON',
    success: function(response) {
      update_selected_statuses(new_status);
    },
    error: function(response, status, err) {
      const msg = {
        response: response,
        status: status,
        error: err
      };
      console.log(`failed: ${JSON.stringify(msg, null, 2)}`);
    }
  });

}
