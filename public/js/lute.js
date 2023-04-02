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
    position: { my: 'left top+10', at: 'left bottom', collision: 'flipfit flip' },
    items: '.word.showtooltip',
    show: { easing: 'easeOutCirc' },
    content: function () { return tooltip_textitem_hover_content($(this)); }
  });

}


/**
 * Build the html content for jquery-ui tooltip.
 */
let tooltip_textitem_hover_content = function (el) {

  let tooltip_title = function() {
    let t = el.text();
    const parent_text = el.attr('parent_text') ?? '';
    if (parent_text != '')
      t = `${t} (${parent_text})`;
    return t;
  }

  const status_span = function() {
    const status = parseInt(el.attr('data_status'));
    const st = STATUSES[status];
    const statname = `[${st['abbr']}]`;
    return `<span style="margin-left: 12px; float: right;" class="status${status}">${statname}</span>`;
  }

  let image_if_src = function(el, attr) {
    const filename = el.attr(attr) ?? '';
    if (filename == '')
      return '';
    return `<img class="tooltip-image" src="${filename}" />`;
  }

  let tooltip_images = function() {
    const images = [ 'data_img_src', 'parent_img_src' ].
          map(s => image_if_src(el, s));
    const unique = (value, index, self) => {
      return self.indexOf(value) === index
    }
    const unique_images = images.filter(unique).join(' ');
    if (unique_images == ' ')
      return '';
    return `<p>${unique_images}</p>`;
  }

  let build_entry = function(term, transl, roman, tags) {
    let have_val = (v) => v != null && `${v}`.trim() != '';
    if (!have_val(term))
      return '';
    let ret = [ `<b>${term}</b>` ];
    if (have_val(roman))
      ret.push(` <i>(${roman})</i>`);
    if (have_val(transl))
      ret.push(`: ${transl}`);
    if (have_val(tags))
      ret.push(` [${tags}]`);
    return `<p>${ret.join('')}</p>`;
  }

  let get_attr = a => (el.attr(a) ?? '').
      trim().
      replace(/(\r\n|\n|\r)/gm, "<br />");  // Some translations have cr/lf.
  ptrans = get_attr('parent_trans');
  ctrans = get_attr('data_trans');
  ctags = get_attr('data_tags');

  let translation_content = ctrans;
  if (ctags != '') {
    translation_content = `${translation_content} [${ctags}]`;
  }
  if (ptrans != '' && ctrans != '' && ctrans != ptrans) {
    // show both.
    translation_content = [
      build_entry(el.text(), ctrans, el.attr('data_rom'), el.attr('data_tags')),
      build_entry(el.attr('parent_text'), ptrans, null, el.attr('parent_tags'))
    ].join('');
  }
  if (ptrans != '' && ctrans == '') {
    translation_content = build_entry(el.attr('parent_text'), ptrans, null, el.attr('parent_tags'));
  }

  let content = `<p><b style="font-size:120%">${tooltip_title()}</b>${status_span()}</p>`;
  const rom = get_attr('data_rom');
  if (rom != '')
    content += `<p><i>${rom}</i></p>`;
  content += translation_content;
  content += tooltip_images();
  return content;
}


function showEditFrame(el, extra_args = {}) {
  const lid = parseInt(el.attr('lid'));

  // Join the words together so they can be sent in the URL
  // string, but in such a way that the string can be "safely"
  // disassembled on the server back into the component words.
  const zeroWidthSpace = '\u200b';
  let text = extra_args.textparts ?? [ el.text() ];
  const sendtext = text.join(zeroWidthSpace);

  let extras = Object.entries(extra_args).
      map((p) => `${p[0]}=${encodeURIComponent(p[1])}`).
      join('&');

  const url = `/read/termform/${lid}/${sendtext}?${extras}`;
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
  const selected = $("span.textitem").filter(function() {
    const ord = $(this).attr("data_order");
    return ord >= startord && ord <= endord;
  });
  const textparts = selected.toArray().map((el) => $(el).text());

  const text = textparts.join('').trim();
  if (text.length > 250) {
    alert(`Selections can be max length 250 chars ("${text}" is ${text.length} chars)`);
    clear_newmultiterm_elements();
    return;
  }

  showEditFrame(selection_start_el, { textparts: textparts });
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

  if (e.which == kESC || newindex < 0 || newindex > maxindex) {
    $('span.kwordmarked').removeClass('kwordmarked');
    return;
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
    const selindex = current_word_index();
    if (selindex == -1)
      return;
    const w = words.eq(selindex);
    const seid = w.attr('seid');
    const tis = $('span.textitem').toArray().filter(x => x.getAttribute('seid') === seid);
    const sentence = tis.map(s => $(s).text()).join('');
    // console.log(sentence);

    const userdict = $('#translateURL').text();
    if (userdict == null || userdict == '')
      console.log('No userdict for lookup.  ???');

    // console.log(userdict);
    const url = userdict.replace('###', encodeURIComponent(sentence));
    if (url[0] == '*') {
      const finalurl = url.substring(1);  // drop first char.
      const settings = 'width=800, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no';
      window.open(finalurl, 'dictwin', settings);
    }
    else {
      top.frames.dictframe.location.href = url;
    }
    return false;
  }

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
      $(m).removeClass('status98 status99 status0 status1 status2 status3 status4 status5 shiftClicked')
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
