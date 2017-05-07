<?php
	$_verbose = $_GET["v"];
	
	$_s = urldecode($_GET["p"]);
	$_index = 0;
	
	$_vars = array();
	if ($_GET["a"]) {
		$args = $_GET["a"];
		foreach ($args as $arg) { // array(2, "+", 2, 2, 12, 3, 6, "hello", 3, "el")
			$name .= ":$";
			if (is_numeric($arg))
				$_vars[$name] = intval($arg);
			else
				$_vars[$name] = $arg;
		}
	}
	
	$_exprs = array();
	$_lastExpr = 0;
	$_stack = array();
	
	function error($description, $code = 1) {
		echo "@=== $description ===*" . "\n";
		echo "Program exited on error." . "\n";
		exit($code);
	}
	
	function isskipable($c) {
		return in_array($c, array(" ", "\n", "\t"));
	}

	function isoperator($tok) {
		return in_array($tok, array(":#", ":>", ":*", ":/", "%)", ":&", ":|"));
	}

	function opprecedence($op) {
		if /**/ ($op == ":&" || $op == ":|")
			return 30;
		else if ($op == ":*" || $op == ":/" || $op == "%)")
			return 20;
		else if ($op == ":#" || $op == ":>")
			return 10;
		return 0;
	}
	
	function printop($tok) { # DEBUG
		$index = array_search($tok, array(":#", ":>", ":*", ":/", "%)", ":&", ":|"));
		$ops = array("+", "-", "*", "/", "%", "and", "or");
		return $ops[$index];
	}
	
	function gettok() {
		global $_s;
		global $_index;
		
		while (1) {
			if ($_index > strlen($_s))
				break;
			$c = $_s[$_index];
			if (isskipable($c)) {
				$_index++;
			} else {
				if (substr($_s, $_index, 2) == "</") { // End of program
					// set |index| out of bounds (to break the next call)
					$_index = strlen($_s) + 1;
					return 0;
				} else {
					$tok = substr($_s, $_index, 2);
					$_index += 2;
					return $tok;
				}
			}
		}
	}
	
	function nexttok($skipSkipable = true) {
		global $_s;
		global $_index;
		$tmp_index = $_index;
		while (1) {
			if ($_index > strlen($_s))
				break;
			
			$c = $_s[$tmp_index];
			if ($skipSkipable && isskipable($c))
				$tmp_index++;
			else {
				if (substr($_s, $tmp_index, 2) == "</") # End of program
					return "</3";
				else {
					$tok = substr($_s, $tmp_index, 2);
					return $tok;
				}
			}
		}
		return 0;
	}
	
	class Expr {
		public function description() {
			return "*** expr ***";
		}
	}

	class VarExpr extends Expr {
		public $name = "";
		public $inversed = false;
		function __construct($name, $inversed) {
			$this->name = $name;
			$this->inversed = $inversed;
		}
			
		public function description() {
			return (($this->inversed) ? "Inversed " : "") . "Var named \"" . $this->name . "\"";
		}
			
		public function execute() {
			global $_vars;
			if (strlen($this->name) == 0)
				return 0;
			else if ($this->inversed)
				return (array_key_exists($this->name, $_vars)) ? !($_vars[$this->name]) : 0;
			else
				return (array_key_exists($this->name, $_vars)) ? $_vars[$this->name] : 0;
		}
	}

	class NamedVarExpr extends Expr {
		public $expr = 0;
		public $inversed = false;
		function __construct($expr, $inversed) {
			$this->expr = $expr;
			$this->inversed = $inversed;
		}
			
		function description() {
			return (($this->inversed) ? "Inversed " : "") . "Var from \"" . $this->expr->description() . "\"";
		}
			
		function execute() {
			global $_vars;
			$name = $this->expr->execute();
			if (strlen($name) == 0)
				return 0;
			else if ($this->inversed)
				return (array_key_exists($name, $_vars)) ? !($_vars[$name]) : $name;
			else
				return (array_key_exists($name, $_vars)) ? $_vars[$name] : $name;
		}
	}

	function parseVarExpr($inversed = false) {
		if (nexttok() == ":(") {
			gettok(); # Eat ":("
			$expr = parseVarExpr($inversed);
			gettok(); # Eat ":)"
			return new NamedVarExpr($expr, $inversed);
		}
		else {
			$name = "";
			while (1) {
				$tok = gettok();
				if ($tok == ":)") break;
				else
					$name .= $tok;
			}
			return new VarExpr($name, $inversed);
		}
	}

	class InitExpr extends Expr {
		public $LHS;
		public $RHS;
		function __construct($LHS, $RHS) {
			$this->LHS = $LHS;
			$this->RHS = $RHS;
		}
			
		function description() {
			return $this->LHS->description() . " = " . $this->RHS->description();
		}
			
		function execute() {
			global $_vars;
			$name = (is_a($this->LHS, "VarExpr")) ? $this->LHS->name : $this->LHS->execute();
			$_vars[$name] = $this->RHS->execute();
		}
	}

	function parseInitExpr() {
		global $_lastExpr;
		return new InitExpr($_lastExpr, parseExpr());
	}

	class InputExpr extends Expr {
		public $input = 0;
		function __construct($input) {
			$this->input = $input;
		}
			
		function description() {
			return "Input " . $this->input;
		}
			
		function execute() {
			global $_vars;
			$name = str_repeat(":$", $this->input);
			return $_vars[$name];
		}
	}

	function parseInputExpr() {
		$i = 1; # The first ":$" has been already found (because this function is called)
		while (1) {
			$tok = nexttok(false); # Don't skip skipable characters, ":$" must be concatened to count as only one input
			if ($tok == ":$") {
				$i++;
				gettok(); # Eat the ":$" found
			}
			else break;
		}
		return new InputExpr($i);
	}

	class PrintExpr extends Expr {
		public $exprs;
		function __construct($exprs) {
			$this->exprs = $exprs;
		}
			
		function description() {
			$desc = "";
			foreach ($this->exprs as $expr)
				$desc .= $expr->description() . (($expr === $this->exprs[count($this->exprs)-1]) ? "" : ", ");
			return "Print: ($desc)";
		}
			
		function execute() {
			$s = "";
			foreach ($this->exprs as $expr) {
				$value = $expr->execute();
				$s .= $value . " ";
			}
			echo $s . "\n";
		}
	}

	function parsePrintExpr() {
		$exprs = array();
		while (1) {
			$expr = parseExpr();
			$exprs[] = $expr;
			if (nexttok() == "@)") {
				gettok(); # Eat "@)"
				return new PrintExpr($exprs);
			}
		}
	}

	class HelloPrintExpr extends Expr {
		function description() {
			return "Print: \"Hello, World!\"";
		}
			
		function execute() {
			global $_vars;
			$input = 0;
			if ($_vars[":$"])
				$input = $_vars[":$"];
			echo "Hello, " . (($input) ? ($input . "!") : "World!") . "\n";
		}
	}

	function parseHelloPrintExpr() {
		return new HelloPrintExpr();
	}

	class BinOpExpr extends Expr {
		public $LHS = 0;
		public $RHS = 0;
		public $op = 0;
		function __construct($LHS, $op, $RHS) {
			$this->LHS = $LHS;
			$this->op = $op;
			$this->RHS = $RHS;
		}
			
		function description() {
			return "(" . $this->LHS->description() . " " . printop($this->op) . " " . $this->RHS->description() . ")";
		}
			
		function execute() {
			# ----------------------------------- #
			#     | + | - | * | / | % | && | || | #
			# ----------------------------------- #
			# n,n | x | x | x | x | x | x  | x  | #
			# s,n | x | x | x | x | x |    |    | #
			# s,s | x | x |   |   |   |    |    | #
			# ----------------------------------- #
			# "abc" + 3 = "abc3"; 3 + "abc" = "3abc"
			# "abc" + "def" = "abcdef"
			# "abcd" - 2 = "ab"
			# "abcd" - "bc" = "ad"
			# "abc" * 2 = "abcabc"
			# "abc" / 2 = "a"
			# "abc" % 2 = "bca"
			
			$op = $this->op;
			$LHS = $this->LHS->execute();
			$RHS = $this->RHS->execute();
			
			if (op == ":/" && !(is_string($RHS)) && int($RHS) == 0) {
				echo "@=== " . $this->LHS->description() . " divised by zero: unexpected behaviour activated ===*" . "\n";
				echo "PrO6raw 3x1t3d." . "\n";
				exit(1);
			}
			if (!(is_string($LHS)) && !(is_string($RHS)) ) { # n,n
				$l = (int)$LHS;
				$r = (int)$RHS;
				if /**/ ($op == ":#") return $l + $r;
				else if ($op == ":>") return $l - $r;
				else if ($op == ":*") return $l * $r;
				else if ($op == ":/") return $l / $r;
				else if ($op == "%)") return $l % $r;
				else if ($op == ":&") return $l && $r;
				else if ($op == ":|") return $l || $r;
			}
			else if (is_string($LHS) xor is_string($RHS)) { # s,n
				if /**/ ($op == ":#") return $LHS.$RHS;
				else if ($op == ":>") return (is_string($LHS)) ? substr($LHS, 0, strlen($LHS)-$RHS) : array_slice($RHS, count($RHS)-$LHS);
				else if ($op == ":*") return (is_string($LHS)) ? str_repeat($LHS, $RHS) : str_repeat($RHS, $LHS);
				else if ($op == ":/") return (is_string($LHS)) ? substr($LHS, $RHS*strlen($LHS)) : substr($RHS, $LHS*strlen($RHS));
				else if ($op == "%)") {
					if (is_string($LHS))
						return substr($LHS, strlen($LHS)-$RHS) . substr($LHS, 0, strlen($LHS)-$RHS);
					else
						return substr($RHS, strlen($RHS)-$LHS) . substr($RHS, 0, strlen($RHS)-$LHS);
				}
			}
			else { # s,s
				if /**/ ($op == ":#") return $LHS.$RHS;
				else if ($op == ":>") return str_replace($RHS, "", $LHS); // Remove occ of $RHS from $LHS
			}
			# Show an assertion: no valid operation
			error("No valid operation for '" . $this->LHS->description() . " $op " . $this->RHS->description() . "'");
			return;
		}
	}

	function parseOperand() { # VarExpr, NamedExpr, InputExpr, LengthFuncExpr
		$expr = 0;
		$tok = gettok();
		if /**/ ($tok == ":(" || $tok == "x(") {
			$inversed = ($tok == "x(");
			$expr = parseVarExpr($inversed);
		}
		else if ($tok == ":$")
			$expr = parseInputExpr();
		else if ($tok == "L)")
			$expr = parseLengthFuncExpr();
		return $expr;
	}

	function parseBinOpExpr($LHS, $op) {
		$ops = array($op);
		$outputRPN = array($LHS);
		
		while (1) {
			$RHS = parseOperand();
			$op = nexttok();
			if ($op === 0)
				return;
			if (!RHS || !(isoperator($op)))
				break;
				
			gettok(); # Eat operator
			$outputRPN[] = $RHS;
			
			$lastOp = $ops[count($ops)-1];
			if (opprecedence($lastOp) >= opprecedence($op)) {
				$rops = array_reverse($ops);
				foreach ($rops as $rop) {
					if (opprecedence($op) > opprecedence($rop))
						break;
					$outputRPN[] = $rop;
					array_pop($ops);
				}
			}
			$ops[] = $op;
		}
		$outputRPN[] = $RHS;
		
		$rops = array_reverse($ops);
		$outputRPN = array_merge($outputRPN, $rops);
		
		$binops = array();
		foreach ($outputRPN as $output) {
			if (isoperator($output)) {
				$LHS = array_pop($binops);
				$RHS = array_pop($binops);
				$expr = new BinOpExpr($RHS, $output, $LHS);
				$binops[] = $expr;
			}
			else $binops[] = $output;
		}
		return $binops[0];
	}
	
	class CommentExpr extends Expr {
		public $comment = 0;
		function __construct($comment) {
			$this->comment = $comment;
		}
			
		function description() {
			return "Comment: \"" . $this->comment . "\"";
		}
	}

	function parseCommentExpr() {
		$comment = "";
		global $_s;
		global $_index;
		while ($_s[$_index] != "\n") {
			if ($_index > strlen($_s)) { break; }
			$comment .= $_s[$_index];
			$_index++;
		}
		return new CommentExpr(comment);
	}

	class NopExpr extends Expr {
		function description() {
			return "Paku paku";
		}
	}

	function parseNopExpr() {
		return new NopExpr();
	}

	class ExitExpr extends Expr {
		public $code = 0;
		function __construct($code = 0) {
			$this->code = $code;
		}
		
		function description() {
			return "Exit(" . $this->code . ")";
		}
		
		function execute() {
			exit($this->code);
		}
	}

	function parseExitExpr() {
		return new ExitExpr(0);
	}

	class LoopExpr extends Expr {
		public $condition = 0;
		public $thenExprs = array();
		public $thelseExprs = array();
		function __construct($condition, $thenExprs, $thelseExprs) {
			$this->condition = $condition;
			$this->thenExprs = $thenExprs;
			$this->thelseExprs = $thelseExprs;
		}
			
		function description() {
			$str = "Loop: " . $this->condition->description() . " | ";
			foreach ($this->thenExprs as $expr) {
				if (canExecute($expr))
					$str .= $expr->description() . (($expr == $this->thenExprs[count($this->thenExprs)-1]) ? "" : ", ");
			}
			$str .= " | ";
			foreach ($this->thelseExprs as $expr) {
				if (canExecute($expr))
					$str .= $expr->description() . (($expr == $this->thelseExprs[count($this->thelseExprs)-1]) ? "" : ", ");
			}
			return $str . " |";
		}
			
		function execute() {
			$cond = $this->condition->execute();
			if (!( (is_string($cond)) ? (strlen($cond) > 0) : ($cond > 0) )) {
				# Thelse block
				foreach ($this->thelseExprs as $expr) {
					if (canExecute($expr))
						$expr->execute();
				}
			}
			else {
				# Then block
				while (1) {
					$cond = $this->condition->execute();
					if (!( (is_string($cond)) ? (strlen($cond) > 0) : ($cond > 0)))
						return;
					foreach ($this->thenExprs as $expr) {
						if (canExecute($expr))
							$expr->execute();
					}
				}
			}
		}
	}

	function parseLoopExpr() {
		global $_lastExpr;
		
		$condition = 0;
		while (nexttok() != "|)") {
			if (nexttok() === 0)
				return;
			$condition = parseExpr();
			if (is_a($condition, "Expr"))
				$_lastExpr = $condition;
		}
		gettok(); # Eat "|)"
		
		$thenExprs = array();
		while (nexttok() != "8)") {
			if (nexttok() === 0)
				return;
			$expr = parseExpr();
			$thenExprs[] = $expr;
			if (is_a($expr, "Expr"))
				$_lastExpr = $expr;
		}
		gettok(); # Eat "8)"
		
		$thelseExprs = array();
		while (nexttok() != "8}") {
			if (nexttok() === 0)
				return;
			$expr = parseExpr();
			$thelseExprs[] = $expr;
			if (is_a($expr, "Expr"))
				$_lastExpr = $expr;
		}
		gettok(); # Eat "8}"
		
		return new LoopExpr($condition, $thenExprs, $thelseExprs);
	}
	
	class LengthFuncExpr extends Expr {
		public $expr = 0;
		function __construct($expr) {
			$this->expr = $expr;
		}
			
		function description() {
			return "Length of: \"" . $this->expr->description() . "\"";
		}
			
		function execute() {
			$v = $this->expr->execute();
			return (is_string($v)) ? strlen($v) : 0;
		}
	}
	
	function parseLengthFuncExpr() {
		return new LengthFuncExpr(parseExpr());
	}

	### Stack management ###
	class PushExpr extends Expr {
		public $expr = 0;
		function __construct($expr) {
			$this->expr = $expr;
		}
			
		function description() {
			return "Push: \"" . $this->expr->description() . "\"";
		}
		
		function execute() {
			global $_stack;
			$_stack[] = $this->expr->execute();
		}
	}

	function parsePushExpr() {
		return new PushExpr(parseExpr());
	}

	class PopExpr extends Expr {
		public $expr = 0;
		function __construct($expr) {
			$this->expr = $expr;
		}
			
		function description() {
			return "Pop to: \"" . $this->expr->description() . "\"";
		}
			
		function execute() {
			global $_stack;
			global $_vars;
			$name = (is_a($this->expr, "VarExpr")) ? $this->expr->name : $this->expr->execute();
			$_vars[$name] = array_pop($_stack);
		}
	}
	
	function parsePopExpr() {
		return new PopExpr(parseExpr());
	}

	class ClearExpr extends Expr {
		function description() {
			return "Clear stack";
		}
			
		function execute() {
			global $_stack;
			$_stack = array();
		}
	}
			
	function parseClearExpr() {
		return new ClearExpr();
	}

	class UnknownExpr extends Expr {
		public $tok = 0;
		function __construct($tok) {
			$this->tok = $tok;
		}
			
		function description() {
			return "Unknown expr: \"" . $this->tok . "\"";
		}
	}

	function parseUnknownExpr() {
		global $_s;
		global $_index;
		$tok = substr($_s, $_index-2, 2);
		return new UnknownExpr($tok);
	}
	
	function canExecute($expr) {
		if (!is_string($expr)) {
			foreach (array("InitExpr", "PrintExpr", "HelloPrintExpr",  "ExitExpr", "LoopExpr",  "PushExpr", "PopExpr", "ClearExpr") as $classname) {
				if (is_a($expr, $classname))
					return true;
			}
		}
		return false;
	}

	function parseExpr() {
		$expr = 0;
		$tok = gettok();
		if ($tok === 0)
			return;
		else if ($tok == ":(" || $tok == "x(") {
			$inversed = ($tok == "x(");
			$expr = parseVarExpr($inversed);
		}
		else if ($tok == "=;")
			$expr = parseInitExpr();
		else if ($tok == ":$")
			$expr = parseInputExpr();
		else if ($tok == ":@")
			$expr = parsePrintExpr();
		else if ($tok == ":B")
			$expr = parseHelloPrintExpr();
		else if ($tok == ";)")
			$expr = parseCommentExpr();
		else if ($tok == "L)")
			$expr = parseLengthFuncExpr();
		else if ($tok == ":P")
			$expr = parsePushExpr();
		else if ($tok == ":O")
			$expr = parsePopExpr();
		else if ($tok == ":D")
			$expr = parseClearExpr();
		else if ($tok == ":v")
			$expr = parseNopExpr();
		else if ($tok == "8|")
			$expr = parseLoopExpr();
		else if ($tok == "#0")
			$expr = parseExitExpr();
		else if ($tok == "<3")
			return "-start-";
		else if ($tok == "</3")
			return "-end-";
		else {
			$expr = parseUnknownExpr();
			error($expr->description());
		}
		
		// If the next token is an operator
		if (isoperator(nexttok()))
			$expr = parseBinOpExpr($expr, gettok());
		
		return $expr;
	}

	function parse() {
		global $_exprs;
		global $_lastExpr;
		global $_verbose;
		while (1) {
			$expr = parseExpr();
			if (is_a($expr, "Expr")) {
				if (is_a($expr, "UnknownExpr"))
					error($expr->description());
				$_exprs[] = $expr;
				$_lastExpr = $expr;
			}
			else if (is_string($expr)) {
			}
			else {
				break;
			}
		}
		foreach ($_exprs as $expr) {
			if (canExecute($expr)) {
				if ($_verbose)
					echo "- " . $expr->description() . "\n";
				$expr->execute();
			}
		}
	}
	parse();
?>