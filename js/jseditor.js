function JSEditor(place, path, buttons) {
    var editor = document.getElementById(place);
    this.home = document.createElement("DIV");
    this.home.className = 'jse-toolbar';
    editor.parentNode.insertBefore(this.home, editor);

    var self = this;
    function makeButton(name, action) {
        var button = document.createElement("INPUT");
        button.type = "button";
        button.className = action;
        button.value = name;
        button.title = name;
        self.home.appendChild(button);
        button.onclick = function(){self[action].call(self);};
    }

    for (i=0; i<buttons.length; i++) {
        makeButton(buttons[i][0], buttons[i][1]);
    }

    options = {
        height:             "350px",
        lineNumbers:        true,
        parserfile:         ["tokenizejavascript.js", "parsejavascript.js"],
        stylesheet:         [path+"/css/jscolors.css"],
        path:               path + "/codemirror/",
        autoMatchParens:    true
    };

    this.mirror = CodeMirror.fromTextArea(place, options);
}

JSEditor.prototype = {
    undo: function() {
        this.mirror.undo();
        this.mirror.focus();
    },

    redo: function() {
        this.mirror.redo();
        this.mirror.focus();
    },

    search: function() {
        var text = prompt("Enter search term:", "");
        if (!text) return;

        var first = true;
        do {
            var cursor = this.mirror.getSearchCursor(text, first);
            first = false;
            while (cursor.findNext()) {
                cursor.select();
                if (!confirm("Search again?"))
                    return;
            }
        } while (confirm("End of document reached. Start over?"));
    },

    replace: function() {
        // This is a replace-all, but it is possible to implement a
        // prompting replace.
        var from = prompt("Enter search string:", ""), to;
        if (from) to = prompt("What should it be replaced with?", "");
        if (to == null) return;

        var cursor = this.mirror.getSearchCursor(from, false);
        while (cursor.findNext())
            cursor.replace(to);
    }
};
