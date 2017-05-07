var outputRequest;
var smileysRequest;
function submit() {
  var args = document.getElementById("arguments").value.split(" ");
  var p = document.getElementById("program").value;

  var matches = p.match(/(:\$)+/g);
  var max = 0;
  if (matches) {
    for (var i = 0; i < matches.length; i++) {
      var length = matches[i].length;
      if (length > max) {
        max = length;
      }
    }
    max /= 2;
  }

  var count = args.length;
  if (count == 1 && args[0] == "") {
    count = 0;
  }
  var ele = document.getElementById("warning");
  if (max > count) {
    ele.textContent = "Program expects " + max + " arguments";
    ele.style.display = "inline";
  } else {
    ele.style.display = "none";
  }

  var verbose = document.getElementById("verbose-on").checked * 1;
  var url = "/parser.php?p=" + encodeURIComponent(p) + "&v=" + verbose;
  for (var i = 0; i < count; i++) {
    if (args[i].length > 0) {
      url += "&a[]=" + args[i];
    }
  }

  outputRequest = new XMLHttpRequest();
  outputRequest.onreadystatechange = outputStateChange;
  outputRequest.open("GET", url, true);
  outputRequest.send(null);

  var golfed = document.getElementById("golf-on").checked * 1;
  url = "/smileys.php?p=" + encodeURIComponent(p) + "&golf=" + golfed;
  smileysRequest = new XMLHttpRequest();
  smileysRequest.onreadystatechange = smileysStateChange;
  smileysRequest.open("GET", url, true);
  smileysRequest.send(null);
}

function outputStateChange() {
  if (outputRequest && outputRequest.readyState == 4 &&
    outputRequest.status == 200) {
    var output = outputRequest.responseText.replace(/\n/g, "<br>");
    document.getElementById("output").innerHTML = "<br>" + output;
    outputRequest = null;
  }
}

function smileysStateChange() {
  if (smileysRequest && smileysRequest.readyState == 4 &&
    smileysRequest.status == 200) {
    var p = document.getElementById("program").value;
    var str = smileysRequest.responseText;
    str += "<br>";
    str += "<a target=\"_blank\" href=\"/export-smileys.php?p=" + encodeURIComponent(p) + "&columns=6\">Export to PNG</a>";
    document.getElementById("smileys").innerHTML = str;
    smileysRequest = null;
  }
}

var programs = {
  "Hello" : { "args" : [ "You" ], "source" : "<3 :B </3" },
  "Factorial" : { "args" : [ 5 ], "source" : "<3\n:( =; :) =; :$ :/ :$ ;) =; = 1\n:( :P :) =; :( =; :)\n:( :D :) =; :( =; :) ;) accumulator n = 1\n8| :$ :> :( :D :) |) ;) while n < :$\n  :( :D :) =; :( :D :) :# :( =; :) ;) n++\n  :( :P :) =; :( :P :) :* :( :D :) ;) :P = :P * n \n8) 8}\n:@ :( :P :) @)\n</3" },
  "Fibonacci" : { "args" : [ 10 ], "source" : "<3\n:( =; :) =; :$ :/ :$ ;) =; = 1\n:( :P :) =; :$\n:( :O :) =; :( =; :) ;) create accumulator\n:@ :( :O :) @) ;) print first value (i.e. 1)\n:( :# :) =; :( =; :)\n:@ :( :# :) @) ;) print second value (i.e. 1)\n8| :( :P :) :> :( =; :) |) ;) loop :$ times\n  :( :P :) =; :( :P :) :> :( =; :) ;) :P--\n  :( :D :) =; :( :O :) :# :( :# :) ;) n = n-1 + n-2\n  :@ :( :D :) @) ;) print n\n  :( :O :) =; :( :# :) ;) n-2 = n-1\n  :( :# :) =; :( :D :) ;) n-1 = n\n8) 8}\n</3" }
};

function onLoad() {
  var select = document.getElementById("examples");
  var keys = Object.keys(programs);
  for (var i = 0; i < keys.length; ++i) {
    var name = keys[i];
    var option = document.createElement("option");
    option.text = name;
    option.value = name;
    try {
      select.add(option, null);
    }
    catch(e) {
      select.add(option); // IE
    }
  }
}

function loadProgram() {
  var select = document.getElementById("examples");
  var keys = Object.keys(programs);
  var name = keys[select.selectedIndex-1];
  document.getElementById("arguments").value = programs[name]["args"];
  document.getElementById("program").value = programs[name]["source"];
}
