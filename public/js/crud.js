(function ($) {
    $.fn.crud = function (options) {
        var opts = $.extend({}, $.fn.crud.defaults, options);

        $.fn.crud.defaults = {
            addTemplate: 'adicionar_template',
        };

        console.log(opts.editAction);
        var root = this;
        var itemTemplate = this.find('.item_template').html();
        var formTemplate = this.find('.form_template').html();
        var addTemplate = this.find('.adicionar_template').html();

        var itemTarget = this.find('.item_target');
        var addTarget = this.find('.adicionar_target');

        // itens obrigatórios
        // var opts.editAction = 'editarEleitor';
        // var opts.removeAction = 'removerEleitor';
        // var opts.addAction = 'adicionarEleitor';

        //var addFormTargetClass = '.add_form_target';
        var formTargetClass = '.form_target';
        this.find(formTargetClass).hide();

        addTarget.append(addTemplate);

        this.find('.item').hover(
            function () { itemTarget.append(itemTemplate); },
            function () { itemTarget.html(''); }
        );

        this.on('click', '.cancelar_btn', function (e) {
            e.preventDefault();
            var item = $(this).closest('.form_target').slideUp(300, function () {
                $(this).empty();
            });
        });

        this.on('click', '.adicionar_btn', function (e) {
            e.preventDefault();
            var target = root.find('.add_form_target');
            renderForm(target, opts.addAction).slideDown(300);
        });

        this.on('click', '.editar_btn', function (e) {
            e.preventDefault();
            var target = $(this).closest('.item').find('.form_target');
            renderForm(target, opts.editAction).slideDown(300);
            addFormData(target);
        });

        this.on('click', '.remover_btn', function (e) {
            if (confirm('Tem certeza que quer remover?') == false) {
                return false;
            }
            e.preventDefault();
            var target = $(this).closest('.item').find('.form_target');
            renderForm(target, opts.removeAction);
            addFormData(target);
            target.find('button[name=acao]').click();
        });

        var renderForm = function (target, action) {
            target.html(formTemplate);
            target.find('button[name=acao]').val(action);
            return target;
        }

        var addFormData = function (target) {
            target.find('input').each(function () {
                var name = $(this).attr('name');
                var value = target.closest('.item').find('.item_target').attr(name);
                $(this).val(value);
            });
        }

        return this;
    };

}(jQuery));