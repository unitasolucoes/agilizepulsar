(function () {
  'use strict';

  const initTinyMCE = () => {
    if (typeof tinymce === 'undefined') {
      console.error('TinyMCE is not loaded');
      return;
    }

    tinymce.init({
      selector: '#form-nova-campanha .tinymce-editor',
      menubar: false,
      branding: false,
      statusbar: true,
      height: 220,
      language: 'pt_BR',
      plugins: 'lists link table autoresize',
      toolbar: 'undo redo | bold italic underline | bullist numlist | link table | removeformat',
      convert_urls: false,
      setup: (editor) => {
        editor.on('change', () => {
          editor.save();
        });
      }
    });
  };

  const initFlatpickr = () => {
    if (typeof flatpickr === 'undefined') {
      console.warn('Flatpickr is not loaded');
      return;
    }

    flatpickr('.flatpickr-input', {
      dateFormat: 'd/m/Y',
      altInput: false,
      locale: {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
          longhand: [
            'Domingo',
            'Segunda-feira',
            'Terça-feira',
            'Quarta-feira',
            'Quinta-feira',
            'Sexta-feira',
            'Sábado'
          ]
        },
        months: {
          shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
          longhand: [
            'Janeiro',
            'Fevereiro',
            'Março',
            'Abril',
            'Maio',
            'Junho',
            'Julho',
            'Agosto',
            'Setembro',
            'Outubro',
            'Novembro',
            'Dezembro'
          ]
        }
      }
    });
  };

  const setupFileInput = (form) => {
    const fileInput = form.querySelector('input[type="file"][name="anexos[]"]');
    const trigger = form.querySelector('[data-action="select-files"]');
    const list = form.querySelector('#selected-files');

    if (!fileInput || !trigger || !list) {
      return;
    }

    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      fileInput.click();
    });

    fileInput.addEventListener('change', () => {
      list.innerHTML = '';
      if (!fileInput.files || fileInput.files.length === 0) {
        return;
      }

      Array.from(fileInput.files).forEach((file) => {
        const item = document.createElement('li');
        item.textContent = `${file.name} (${Math.round(file.size / 1024)} KB)`;
        list.appendChild(item);
      });
    });
  };

  const toggleSubmitState = (button, isLoading) => {
    if (!button) {
      return;
    }

    if (isLoading) {
      button.dataset.originalText = button.textContent;
      button.innerHTML = '<span class="loading-spinner"></span>Enviando...';
      button.disabled = true;
    } else {
      const text = button.dataset.originalText || 'Cadastrar campanha';
      button.textContent = text;
      button.disabled = false;
    }
  };

  const validateForm = (form) => {
    const titulo = form.querySelector('input[name="titulo"]');
    const descricao = form.querySelector('textarea[name="descricao"]');
    const beneficios = form.querySelector('textarea[name="beneficios"]');
    const areasImpactadas = form.querySelector('select[name="areas_impactadas[]"]');

    if (!titulo.value.trim()) {
      alert('Informe o título da campanha.');
      titulo.focus();
      return false;
    }

    if (!descricao.value.trim()) {
      alert('Informe a descrição da campanha.');
      descricao.focus();
      return false;
    }

    if (!beneficios.value.trim()) {
      alert('Informe os benefícios esperados.');
      beneficios.focus();
      return false;
    }

    if (areasImpactadas && !Array.from(areasImpactadas.selectedOptions).length) {
      alert('Selecione ao menos uma área impactada.');
      areasImpactadas.focus();
      return false;
    }

    return true;
  };

  const handleSubmit = (form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!validateForm(form)) {
        return;
      }

      if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
      }

      const submitButton = form.querySelector('button[type="submit"]');
      const formData = new FormData(form);

      toggleSubmitState(submitButton, true);

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const text = await response.text();
        let data;

        try {
          data = JSON.parse(text);
        } catch (error) {
          console.error('Resposta inválida do servidor', text);
          throw new Error('Não foi possível interpretar a resposta do servidor.');
        }

        if (!response.ok || !data.success) {
          const message = data && data.message ? data.message : 'Falha ao criar a campanha.';
          throw new Error(message);
        }

        alert(data.message || 'Campanha criada com sucesso!');
        if (data.ticket_link) {
          window.location.href = data.ticket_link;
        }
      } catch (error) {
        alert(error.message || 'Erro ao enviar a campanha.');
      } finally {
        toggleSubmitState(submitButton, false);
      }
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-nova-campanha');
    if (!form) {
      return;
    }

    initTinyMCE();
    initFlatpickr();
    setupFileInput(form);
    handleSubmit(form);
  });
})();
