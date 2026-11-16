/**
 * js/script.js - Comportamento do site público (usa jQuery)
 * 1. Menu mobile (abrir/fechar)
 * 2. Modal de agendamento (abrir com médico pré-selecionado / fechar)
 * 3. Máscaras de CPF e telefone
 * 4. Envio do formulário via AJAX para agendar.php
 *
 * Obs.: a validação aqui é só para ajudar o usuário. A validação que vale
 * de verdade é a do servidor (PHP), que roda sempre.
 */
$(function () {

    var $modal = $('#modal_agendamento');
    var $form = $('#form_agendamento');
    var $mensagem = $('#agendamento_mensagem');

    // ---------------------------------------------------------------
    // 1. MENU MOBILE
    // ---------------------------------------------------------------
    $('.btn-mobile').on('click', function () {
        var aberto = $('#mobile_menu').toggleClass('aberto').hasClass('aberto');
        $(this).attr('aria-expanded', aberto);
    });

    // Fecha o menu ao clicar em um link
    $('#mobile_menu .nav_link').on('click', function () {
        $('#mobile_menu').removeClass('aberto');
        $('.btn-mobile').attr('aria-expanded', false);
    });

    // ---------------------------------------------------------------
    // 2. MODAL DE AGENDAMENTO
    // ---------------------------------------------------------------
    function abrirModal(medicoId) {
        $mensagem.empty();
        $form.show();
        // Pré-seleciona o médico do card clicado (ou deixa "Selecione...")
        $('#ag_medico').val(medicoId || '');
        $modal.prop('hidden', false);
        $('body').addClass('modal-aberto');
        $('#mobile_menu').removeClass('aberto');
        // Foca o primeiro campo que falta preencher
        (medicoId ? $('#ag_data_hora') : $('#ag_medico')).trigger('focus');
    }

    function fecharModal() {
        $modal.prop('hidden', true);
        $('body').removeClass('modal-aberto');
    }

    // Todos os botões "Agendar" (header, menu mobile e cards)
    $('.js-abrir-agendamento').on('click', function () {
        abrirModal($(this).data('medico-id'));
    });

    $('.modal-fechar').on('click', fecharModal);

    // Clicar fora da caixa (no fundo escuro) fecha
    $modal.on('click', function (evento) {
        if (evento.target === this) {
            fecharModal();
        }
    });

    // Tecla Esc fecha
    $(document).on('keydown', function (evento) {
        if (evento.key === 'Escape' && !$modal.prop('hidden')) {
            fecharModal();
        }
    });

    // ---------------------------------------------------------------
    // 3. MÁSCARAS
    // ---------------------------------------------------------------
    $('#ag_cpf').on('input', function () {
        var v = this.value.replace(/\D/g, '').slice(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        this.value = v;
    });

    $('#ag_telefone').on('input', function () {
        var v = this.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 10) {
            v = v.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');   // celular
        } else if (v.length > 6) {
            v = v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');   // fixo
        } else if (v.length > 2) {
            v = v.replace(/^(\d{2})(\d*)/, '($1) $2');
        }
        this.value = v;
    });

    // ---------------------------------------------------------------
    // 4. ENVIO DO FORMULÁRIO (AJAX)
    // ---------------------------------------------------------------

    /** Mostra uma mensagem no topo do modal. Usa .text() (nunca .html()) contra XSS. */
    function mostrarMensagem(tipo, texto, erros) {
        var $caixa = $('<div>').addClass('msg msg-' + tipo).text(texto);
        if (erros && erros.length) {
            var $lista = $('<ul>');
            $.each(erros, function (i, erro) {
                $lista.append($('<li>').text(erro));
            });
            $caixa.append($lista);
        }
        $mensagem.empty().append($caixa);
        $modal.find('.modal-caixa').scrollTop(0);
    }

    $form.on('submit', function (evento) {
        evento.preventDefault(); // não recarrega a página

        // Validação rápida do navegador (campos required, e-mail etc.)
        if (!this.checkValidity()) {
            mostrarMensagem('erro', 'Preencha todos os campos obrigatórios (*) corretamente.');
            this.reportValidity();
            return;
        }

        var $botao = $form.find('.btn-enviar');
        $botao.prop('disabled', true).text('Enviando...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        })
            .done(function (resposta) {
                if (resposta.sucesso) {
                    mostrarMensagem('sucesso', resposta.mensagem);
                    $form[0].reset();
                    $form.hide(); // esconde o formulário e deixa só a confirmação
                } else {
                    mostrarMensagem('erro', resposta.mensagem, resposta.erros);
                }
            })
            .fail(function () {
                mostrarMensagem('erro', 'Não foi possível enviar agora. Verifique sua conexão e tente novamente.');
            })
            .always(function () {
                $botao.prop('disabled', false).text('Confirmar agendamento');
            });
    });
});
