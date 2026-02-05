(function() {
    var display = document.getElementById('display');
    var form = document.getElementById('calcForm');
    var exprField = document.getElementById('exprField');
    var modeField = document.getElementById('modeField');
    var sciKeys = document.getElementById('sciKeys');
    
    var current = '';

    document.querySelectorAll('[data-v]').forEach(function(btn) {
        btn.onclick = function() {
            current += this.dataset.v;
            display.value = current;
        };
    });

    document.querySelectorAll('[data-a]').forEach(function(btn) {
        btn.onclick = function() {
            var action = this.dataset.a;
            if (action === 'clear') {
                current = '';
                display.value = '0';
            } else if (action === 'back') {
                current = current.slice(0, -1);
                display.value = current || '0';
            } else if (action === 'submit' && current) {
                exprField.value = current;
                form.submit();
            }
        };
    });

    document.getElementById('btnBasic').onclick = function() {
        sciKeys.classList.add('hide');
        modeField.value = 'basic';
        this.classList.add('on');
        document.getElementById('btnSci').classList.remove('on');
        document.getElementById('calcTitle').textContent = 'Basic Calculator';
    };

    document.getElementById('btnSci').onclick = function() {
        sciKeys.classList.remove('hide');
        modeField.value = 'scientific';
        this.classList.add('on');
        document.getElementById('btnBasic').classList.remove('on');
        document.getElementById('calcTitle').textContent = 'Scientific Calculator';
    };

    document.querySelectorAll('.history-section li').forEach(function(li) {
        li.onclick = function() {
            var ex = this.dataset.expr;
            if (ex) {
                current = ex;
                display.value = current;
            }
        };
    });

    document.onkeydown = function(e) {
        var k = e.key;
        if (/^[0-9+\-*\/.%^()]$/.test(k)) {
            current += k;
            display.value = current;
            e.preventDefault();
        } else if (k === 'Enter') {
            if (current) {
                exprField.value = current;
                form.submit();
            }
            e.preventDefault();
        } else if (k === 'Backspace') {
            current = current.slice(0, -1);
            display.value = current || '0';
            e.preventDefault();
        } else if (k === 'Escape') {
            current = '';
            display.value = '0';
        }
    };
})();
