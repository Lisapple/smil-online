<style>
.smiley-1, .smiley-2, .smiley-3, .smiley-4,
.smiley-5, .smiley-6, .smiley-7, .smiley-8,
.smiley-9, .smiley-10, .smiley-11, .smiley-12,
.smiley-13, .smiley-14, .smiley-15, .smiley-16,
.smiley-17, .smiley-18, .smiley-19, .smiley-20,
.smiley-21, .smiley-22, .smiley-23, .smiley-24,
.smiley-25, .smiley-26, .smiley-27, .smiley-28 {
    background-image:url("smileys.svg");
  width:30px;
  height:30px;
  margin:1px;

  display:inline-block;
}

.smiley-1 {
  background-position:-2px 0px;
}

.smiley-2 {
  background-position:-34px 0px;
}

.smiley-3 {
  background-position:-65px 0px;
}

.smiley-4 {
  background-position:-97px 0px;
}

.smiley-5 {
  background-position:-128px 0px;
}

.smiley-6 {
  background-position:-159px 0px;
}

.smiley-7 {
  background-position:-2px -34px;
}

.smiley-8 {
  background-position:-34px -34px;
}

.smiley-9 {
  background-position:-65px -34px;
}

.smiley-10 {
  background-position:-97px -34px;
}

.smiley-11 {
  background-position:-128px -34px;
}

.smiley-12 {
  background-position:-159px -34px;
}

.smiley-13 {
  background-position:-2px -67px;
}

.smiley-14 {
  background-position:-34px -67px;
}

.smiley-15 {
  background-position:-65px -67px;
}

.smiley-16 {
  background-position:-97px -67px;
}

.smiley-17 {
  background-position:-128px -67px;
}

.smiley-18 {
  background-position:-159px -67px;
}

.smiley-19 {
  background-position:-2px -100px;
}

.smiley-20 {
  background-position:-34px -100px;
}

.smiley-21 {
  background-position:-65px -100px;
}

.smiley-22 {
  background-position:-97px -100px;
}

.smiley-23 {
  background-position:-128px -100px;
}

.smiley-24 {
  background-position:-159px -100px;
}

.smiley-25 {
  background-position:-3px -133px;
  border-radius:0px;
}

.smiley-26 {
  background-position:-34px -133px;
}

.smiley-27 {
  background-position:-66px -133px;
}

.smiley-28 {
  background-position:-97px -133px;
}

.tab {
  margin:10px;
}

.comment {
  color:gray;
  top:-10px;
  position:relative;
  font-family:courier;
}
</style>

<?php
  $smileys = array(
      "<3", ";)", "x(", ":$", "L)", "=;",
      "</3", ":)", ":(", ":/", "|)", ":|",
      "%)", ":D", ":B", ":@", "#0", ":#",
      "8)", ":P", "8|", "@)", ":O", ":&",
      ":>", ":v", "8}", ":*");

  $p = $_GET["p"];
  $p = preg_replace("/(?<=\\:\\$)\\s+(?=\\:\\$)/m", '*', $p);
  $golf = $_GET["golf"];
  if (!$golf) {
    $p = preg_replace("/;\\)([^\\n]+)/m", ";) <span class=\"comment\">// $1</span>", $p);
    $p = preg_replace("/\\n/m", "<br>", $p);
  }
  else {
    $p = preg_replace("/;\\)([^\\n]+)/m", "", $p);
    $p = preg_replace("/\\n/m", "", $p);
  }

  $index = 1;
  foreach ($smileys as $smiley) {
    $smil = htmlspecialchars($smiley);
    $replace = "<div class=\"smiley-$index\"></div>";
    $p = str_replace($smiley, $replace, $p);
    $index++;
  }
  $p = preg_replace("/>([ ])+</m", '><', $p); // Remove extra whitespace between markups
  echo $p;
?>
