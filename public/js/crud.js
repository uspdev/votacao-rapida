/**
 * Este plugin faz parte do sistema de votação rápida 
 * github.com/uspdev/votacao-rapida
 * 
 * Plugin que facilita a criação de um crud para uma lista de dados
 * A idéia é que na lista de dados possua quatro conaineres que recebem 
 * o botão de adicionar, o formulário de adicionar, os botões de 
 * editar/excluir e o formulário de edição.
 * 
 * O post do formulário é definido pelo campo acao cujos valores devem ser
 * informados na inicialização.
 * 
 * Os templates de formulário e de botões devem estar no html usando as tags 
 * especificadas.
 * 
 * Tudo deve estar dentro de um escopo (div) no qual vai ser aplicado o plugin.
 */

(function ($) {
    $.fn.crud = function (options) {
        var opts = $.extend({}, $.fn.crud.defaults, options);

        $.fn.crud.defaults = {
            addTemplate: 'adicionar_template',
        };

        //console.log(opts.editAction);
        var itemTemplate = this.find('.item_template').html();
        var formTemplate = this.find('.form_template').html();
        var addTemplate = this.find('.adicionar_template').html();

        var itemTarget = this.find('.item_target');
        var addTarget = this.find('.adicionar_target');
        var edit = false;
        this.edit = function () {
            return edit;
        };

        // itens obrigatórios
        // var opts.editAction = 'editarEleitor';
        // var opts.removeAction = 'removerEleitor';
        // var opts.addAction = 'adicionarEleitor';

        //var addFormTargetClass = '.add_form_target';
        //var formTargetClass = '.form_target';
        this.find('.form_target').hide();

        addTarget.append(addTemplate);

        this.find('.item').hover(
            function () { itemTarget.append(itemTemplate); },
            function () { itemTarget.html(''); }
        );

        this.on('change', ':input', function (e) {
            $(e.target).data('changed', true);
        });

        this.on('submit', '.form_template form', function () {
            if ($(this).find(':input[name=acao]').val() != opts.addAction) {
                $(this).find(':input:not([type=hidden], [type=submit])').each(function () {
                    if ($(this).data('changed') == true) {
                        $(this).prop('disabled', false);
                    } else {
                        $(this).prop('disabled', true);
                    }
                });
            }
        });

        this.on('click', '.cancelar_btn', function (e) {
            e.preventDefault();
            var item = $(this).closest('.form_target').slideUp(300, function () {
                $(this).empty();
            });
            edit = false;
        });

        this.on('click', '.adicionar_btn', function (e) {
            e.preventDefault();
            var alternativas = "Favorável\nContrário\nAbstenção";
            var target = $(e.delegateTarget).find('.add_form_target');
            renderForm(target, opts.addAction).slideDown(300);
            target.find(':input[name=alternativas]').val(alternativas);
        });

        this.on('click', '.editar_btn', function (e) {
            e.preventDefault();
            var target = $(this).closest('.item').find('.form_target');
            renderForm(target, opts.editAction).slideDown(300);
            addFormData(target);
            edit = true;
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
                if (['text', 'hidden', 'email'].includes($(this).attr('type'))) {
                    $(this).val(value);
                } else if ($(this).attr('type') == 'radio') {
                    $(this).attr('value') == value ? $(this).prop('checked', true) : $(this).prop('checked', false);
                }
            });

            target.find('textarea').each(function () {
                var name = $(this).attr('name');
                var value = target.closest('.item').find('.item_target').attr(name);
                $(this).val(value);
            });
        }
        return this;
    };

}(jQuery));