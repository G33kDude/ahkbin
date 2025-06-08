/* Algorithm from maestrith's AHK Studio */

var autoIndent = function(code) {
	var indent = "\t",
		newline = "\r\n",
		indent_re = /^}?\s*\b(Catch|else|for|Finally|if|IfEqual|IfExist|IfGreater|IfGreaterOrEqual|IfInString|IfLess|IfLessOrEqual|IfMsgBox|IfNotEqual|IfNotExist|IfNotInString|IfWinActive|IfWinExist|IfWinNotActive|IfWinNotExist|Loop|Try|while)\b/i,
		exp_cont_re = /^\s*(&&|OR|AND|\.|\,|\|\||:|\?)/i,
		lock = [],
		block = [],
		parent_indent = 0,
		braces = 0,
		parent_indent_obj = [],
		skip = false,
		out = '',
		current = null,
		cur = 0,
		line,
		text,
		first,
		last,
		first_two,
		is_exp_cont,
		indent_check,
		special;

	var lines = code.split('\n');
	for (var line_num = 0; line_num < lines.length; line_num++) {
		line = lines[line_num];
		text = line.replace(/\s;.*/, '').trim();
		first = text.slice(0, 1);
		first_two = text.slice(0, 2);
		last = text.slice(-1);
		is_exp_cont = exp_cont_re.test(text);
		indent_check = indent_re.test(text);

		if (first === '(' && last !== ')')
			skip = true;
		if (skip) {
			if (first === ')')
				skip = false;
			out += newline + line.replace(/\s+$/, '');
			continue;
		}

		if (first_two === '\*\/') {
			block = [];
			parent_indent = 0;
		}

		if (block.length)
			current = block, cur = 1;
		else
			current = lock, cur = 0;

		braces = (current[current.length-1] || {braces: 0}).braces,
		parent_indent = (parent_indent_obj[cur] || 0);

		if (first === '}') {
			var i = 0;
			while (true) {
				var found = text.slice(i, ++i);
				if (!/}|\s/.test(found))
					break;
				if (/\s/.test(found))
					continue;
				if (cur && current.length <= 1)
					break;
				special = (current.pop() || {ind: 0}).ind;
				braces--;
			}
		}

		if (first === '{' && parent_indent)
			parent_indent--;

		out += newline;

		for (var i = 0; i < (special
			? special-1
			: (current[current.length-1] || {ind: 0}).ind
				+ (parent_indent || 0));
			i++) {
			out += indent;
		}
		out += line.trim();

		if (first_two === '\/\*') {
			if (!block.length)
			{
				block.push({
					parent_indent: parent_indent,
					ind: (lock[lock.length-1] || {ind: 0}).ind + 1,
					braces: (lock[lock.length-1] || {braces: 0}).braces + 1
				});
			}
			current = block, parent_indent = 0;
		}

		if (last == '{') {
			braces++;
			parent_indent = (is_exp_cont && last === '{')
				? parent_indent-1 : parent_indent;
			current.push({
				braces: braces,
				ind: parent_indent
					+ (current[current.length-1] || {parent_indent: 0}).parent_indent
					+ braces,
				parent_indent: parent_indent
					+ (current[current.length-1] || {parent_indent: 0}).parent_indent
			});
			parent_indent = 0;
		}

		if ((parent_indent || is_exp_cont || indent_check)
			&& (indent_check && last !== '{'))
			parent_indent++;
		if (parent_indent > 0 && !(is_exp_cont || indent_check))
			parent_indent = 0;

		parent_indent_obj[cur] = parent_indent;
		special = 0;
	}

	if (braces)
		throw "Open segment!";

	return out.slice(newline.length);
}

var buttons = document.getElementsByClassName('reindent');
for (var i = 0; i < buttons.length; i++) {
	buttons[i].onclick = function() {
		if (!confirm('Do you want to overwrite your code with an auto-indented version?'))
			return false;

		var session = ace.edit("ahkedit").getSession();
		session.setValue(autoIndent(session.getValue()));

		return false;
	};
}
